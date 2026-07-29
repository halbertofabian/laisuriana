using System.Diagnostics;
using System.Reflection;
using System.Security.Principal;
using System.Windows.Forms;

namespace Laisuriana.PrintAgent.Setup;

internal static class Program
{
    private const string AppTitle = "LAISURIANAPRINT-SOFTMOR";
    private const string InstallDir = @"C:\Program Files\Laisuriana\LAISURIANAPRINT-SOFTMOR";

    [STAThread]
    private static int Main(string[] args)
    {
        if (!IsAdministrator())
        {
            return RelaunchAsAdministrator(args);
        }

        try
        {
            Application.EnableVisualStyles();
            Application.SetCompatibleTextRenderingDefault(false);

            var confirmed = MessageBox.Show(
                "Se instalara LAISURIANAPRINT-SOFTMOR en esta computadora. El servicio quedara configurado para iniciar automaticamente con Windows.\n\n¿Deseas continuar?",
                AppTitle,
                MessageBoxButtons.YesNo,
                MessageBoxIcon.Question
            );

            if (confirmed != DialogResult.Yes)
            {
                return 0;
            }

            var tempDir = Path.Combine(Path.GetTempPath(), "LAISURIANAPRINT-SOFTMOR", Guid.NewGuid().ToString("N"));
            Directory.CreateDirectory(tempDir);

            try
            {
                ExtractPayload(tempDir);

                var installScript = Path.Combine(tempDir, "install-service.ps1");
                RunProcess(
                    "powershell.exe",
                    $"-NoProfile -ExecutionPolicy Bypass -File \"{installScript}\" -SourceDir \"{tempDir}\" -InstallDir \"{InstallDir}\"",
                    hidden: true
                );
            }
            finally
            {
                TryDeleteDirectory(tempDir);
            }

            MessageBox.Show(
                "LAISURIANAPRINT-SOFTMOR se instalo correctamente.\n\nLa impresion automatica quedo disponible y el servicio iniciara automaticamente con Windows.",
                AppTitle,
                MessageBoxButtons.OK,
                MessageBoxIcon.Information
            );

            return 0;
        }
        catch (Exception ex)
        {
            MessageBox.Show(
                "No fue posible completar la instalacion.\n\n" + ex.Message,
                AppTitle,
                MessageBoxButtons.OK,
                MessageBoxIcon.Error
            );

            return 1;
        }
    }

    private static bool IsAdministrator()
    {
        using var identity = WindowsIdentity.GetCurrent();
        var principal = new WindowsPrincipal(identity);

        return principal.IsInRole(WindowsBuiltInRole.Administrator);
    }

    private static int RelaunchAsAdministrator(string[] args)
    {
        try
        {
            var currentExe = Environment.ProcessPath
                ?? throw new InvalidOperationException("No se encontro la ruta del instalador.");

            var startInfo = new ProcessStartInfo
            {
                FileName = currentExe,
                Arguments = string.Join(" ", args.Select(QuoteArgument)),
                UseShellExecute = true,
                Verb = "runas",
            };

            Process.Start(startInfo);
            return 0;
        }
        catch (Exception ex)
        {
            MessageBox.Show(
                "Se necesitan permisos de administrador para instalar el agente.\n\n" + ex.Message,
                AppTitle,
                MessageBoxButtons.OK,
                MessageBoxIcon.Warning
            );

            return 1;
        }
    }

    private static void ExtractPayload(string destinationDir)
    {
        var assembly = Assembly.GetExecutingAssembly();
        var expectedFiles = new[]
        {
            "Laisuriana.PrintAgent.exe",
            "install-service.ps1",
            "uninstall-service.ps1",
            "appsettings.json",
            "appsettings.template.json",
        };

        foreach (var fileName in expectedFiles)
        {
            var resourceName = "Payload." + fileName;
            using var stream = assembly.GetManifestResourceStream(resourceName)
                ?? throw new InvalidOperationException("No se encontro el archivo requerido del instalador: " + fileName);

            using var output = File.Create(Path.Combine(destinationDir, fileName));
            stream.CopyTo(output);
        }
    }

    private static void RunProcess(string fileName, string arguments, bool hidden)
    {
        using var process = new Process
        {
            StartInfo = new ProcessStartInfo
            {
                FileName = fileName,
                Arguments = arguments,
                UseShellExecute = false,
                CreateNoWindow = hidden,
                WindowStyle = hidden ? ProcessWindowStyle.Hidden : ProcessWindowStyle.Normal,
                RedirectStandardOutput = true,
                RedirectStandardError = true,
            }
        };

        process.Start();
        var stdout = process.StandardOutput.ReadToEnd();
        var stderr = process.StandardError.ReadToEnd();
        process.WaitForExit();

        if (process.ExitCode != 0)
        {
            var detail = string.Join(
                Environment.NewLine,
                new[] { stdout.Trim(), stderr.Trim() }.Where(value => !string.IsNullOrWhiteSpace(value))
            );

            throw new InvalidOperationException(
                string.IsNullOrWhiteSpace(detail)
                    ? "El instalador no pudo registrar el servicio local."
                    : detail
            );
        }
    }

    private static string QuoteArgument(string value)
    {
        return value.Contains(' ') ? "\"" + value.Replace("\"", "\\\"") + "\"" : value;
    }

    private static void TryDeleteDirectory(string path)
    {
        try
        {
            if (Directory.Exists(path))
            {
                Directory.Delete(path, true);
            }
        }
        catch
        {
        }
    }
}
