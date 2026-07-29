using System.Diagnostics;
using System.Text.RegularExpressions;
using System.Runtime.InteropServices;
using Laisuriana.PrintAgent.Models;
using Microsoft.Extensions.Options;

namespace Laisuriana.PrintAgent.Services;

public sealed class PdfPrintDispatcher
{
    private static readonly Regex SafeNameRegex = new(@"[^A-Za-z0-9._-]+", RegexOptions.Compiled);

    private readonly PrinterAgentOptions _options;

    public PdfPrintDispatcher(IOptions<PrinterAgentOptions> options)
    {
        _options = options.Value;
    }

    public async Task<object> PrintAsync(PrintJobRequest request, CancellationToken cancellationToken)
    {
        ValidateRequest(request);

        Directory.CreateDirectory(_options.TempDirectory);

        var safeName = BuildSafeFileName(request.DocumentName, request.ContentType);
        var documentBytes = Convert.FromBase64String(request.DocumentBase64!);
        var contentType = request.ContentType!.Trim();

        if (string.Equals(contentType, "application/vnd.escpos", StringComparison.OrdinalIgnoreCase))
        {
            PrintRaw(documentBytes);

            return new
            {
                machine = Environment.MachineName,
                printer = _options.PrinterName,
                document = safeName,
                printedAt = DateTimeOffset.Now,
                mode = "raw",
            };
        }

        var pdfPath = Path.Combine(_options.TempDirectory, safeName);
        await File.WriteAllBytesAsync(pdfPath, documentBytes, cancellationToken);

        try
        {
            await PrintWithSumatraAsync(pdfPath, cancellationToken);

            return new
            {
                machine = Environment.MachineName,
                printer = _options.PrinterName,
                document = safeName,
                printedAt = DateTimeOffset.Now,
                mode = "pdf",
            };
        }
        finally
        {
            TryDelete(pdfPath);
        }
    }

    private void ValidateRequest(PrintJobRequest request)
    {
        if (string.IsNullOrWhiteSpace(_options.PrinterName))
        {
            throw new InvalidOperationException("No se ha configurado la impresora de esta computadora.");
        }

        if (string.IsNullOrWhiteSpace(request.Source))
        {
            throw new InvalidOperationException("La solicitud no incluye source.");
        }

        if (_options.AllowedSources.Length > 0
            && !_options.AllowedSources.Contains(request.Source, StringComparer.OrdinalIgnoreCase))
        {
            throw new InvalidOperationException("La fuente del documento no esta autorizada para esta computadora.");
        }

        if (string.IsNullOrWhiteSpace(request.DocumentBase64))
        {
            throw new InvalidOperationException("El documento no contiene datos.");
        }

        var contentType = request.ContentType?.Trim() ?? string.Empty;
        var isPdf = string.Equals(contentType, "application/pdf", StringComparison.OrdinalIgnoreCase);
        var isEscPos = string.Equals(contentType, "application/vnd.escpos", StringComparison.OrdinalIgnoreCase);

        if (!isPdf && !isEscPos)
        {
            throw new InvalidOperationException("Solo se admite impresion PDF o ESC/POS RAW en esta version del agente.");
        }

        if (isPdf && (string.IsNullOrWhiteSpace(_options.SumatraPdfPath) || !File.Exists(_options.SumatraPdfPath)))
        {
            throw new InvalidOperationException("No se encontro SumatraPDF en la ruta configurada.");
        }
    }

    private async Task PrintWithSumatraAsync(string pdfPath, CancellationToken cancellationToken)
    {
        var process = new Process
        {
            StartInfo = new ProcessStartInfo
            {
                FileName = _options.SumatraPdfPath,
                Arguments = $"-print-to \"{_options.PrinterName}\" -silent \"{pdfPath}\"",
                UseShellExecute = false,
                CreateNoWindow = true,
                WindowStyle = ProcessWindowStyle.Hidden,
            },
        };

        if (!process.Start())
        {
            throw new InvalidOperationException("No fue posible iniciar el proceso de impresion.");
        }

        using var timeoutCts = CancellationTokenSource.CreateLinkedTokenSource(cancellationToken);
        timeoutCts.CancelAfter(TimeSpan.FromSeconds(Math.Max(5, _options.PrintTimeoutSeconds)));

        await process.WaitForExitAsync(timeoutCts.Token);

        if (process.ExitCode != 0)
        {
            throw new InvalidOperationException($"SumatraPDF devolvio codigo {process.ExitCode}.");
        }
    }

    private void PrintRaw(byte[] bytes)
    {
        IntPtr printerHandle = IntPtr.Zero;
        try
        {
            if (!OpenPrinter(_options.PrinterName, out printerHandle, IntPtr.Zero))
            {
                throw new InvalidOperationException($"No se pudo abrir la impresora '{_options.PrinterName}'.");
            }

            var document = new DocInfo
            {
                pDocName = "Laisuriana ticket RAW",
                pDataType = "RAW",
            };

            if (!StartDocPrinter(printerHandle, 1, document))
            {
                throw new InvalidOperationException("No se pudo iniciar el documento RAW.");
            }

            try
            {
                if (!StartPagePrinter(printerHandle))
                {
                    throw new InvalidOperationException("No se pudo iniciar la pagina RAW.");
                }

                try
                {
                    if (!WritePrinter(printerHandle, bytes, bytes.Length, out var written))
                    {
                        throw new InvalidOperationException("No se pudieron enviar los datos RAW.");
                    }

                    if (written != bytes.Length)
                    {
                        throw new InvalidOperationException($"Se enviaron {written} de {bytes.Length} bytes RAW.");
                    }
                }
                finally
                {
                    EndPagePrinter(printerHandle);
                }
            }
            finally
            {
                EndDocPrinter(printerHandle);
            }
        }
        finally
        {
            if (printerHandle != IntPtr.Zero)
            {
                ClosePrinter(printerHandle);
            }
        }
    }

    private static string BuildSafeFileName(string? fileName, string? contentType)
    {
        var baseName = string.IsNullOrWhiteSpace(fileName)
            ? "ticket"
            : fileName.Trim();

        baseName = SafeNameRegex.Replace(baseName, "-");

        var extension = string.Equals(contentType, "application/vnd.escpos", StringComparison.OrdinalIgnoreCase)
            ? ".bin"
            : ".pdf";

        if (!baseName.EndsWith(extension, StringComparison.OrdinalIgnoreCase))
        {
            baseName += extension;
        }

        return $"{DateTime.UtcNow:yyyyMMddHHmmssfff}-{baseName}";
    }

    private static void TryDelete(string path)
    {
        try
        {
            if (File.Exists(path))
            {
                File.Delete(path);
            }
        }
        catch
        {
        }
    }

    [StructLayout(LayoutKind.Sequential, CharSet = CharSet.Unicode)]
    private sealed class DocInfo
    {
        [MarshalAs(UnmanagedType.LPWStr)]
        public string pDocName = string.Empty;

        [MarshalAs(UnmanagedType.LPWStr)]
        public string? pOutputFile;

        [MarshalAs(UnmanagedType.LPWStr)]
        public string pDataType = string.Empty;
    }

    [DllImport("winspool.drv", EntryPoint = "OpenPrinterW", SetLastError = true, CharSet = CharSet.Unicode)]
    private static extern bool OpenPrinter(string printerName, out IntPtr printerHandle, IntPtr defaults);

    [DllImport("winspool.drv", SetLastError = true)]
    private static extern bool ClosePrinter(IntPtr printerHandle);

    [DllImport("winspool.drv", SetLastError = true, CharSet = CharSet.Unicode)]
    private static extern bool StartDocPrinter(IntPtr printerHandle, int level, [In, MarshalAs(UnmanagedType.LPStruct)] DocInfo docInfo);

    [DllImport("winspool.drv", SetLastError = true)]
    private static extern bool EndDocPrinter(IntPtr printerHandle);

    [DllImport("winspool.drv", SetLastError = true)]
    private static extern bool StartPagePrinter(IntPtr printerHandle);

    [DllImport("winspool.drv", SetLastError = true)]
    private static extern bool EndPagePrinter(IntPtr printerHandle);

    [DllImport("winspool.drv", SetLastError = true)]
    private static extern bool WritePrinter(IntPtr printerHandle, byte[] bytes, int count, out int written);
}
