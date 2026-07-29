namespace Laisuriana.PrintAgent.Services;

public sealed class PrinterAgentOptions
{
    public const string SectionName = "PrinterAgent";

    public string BindUrl { get; init; } = "http://127.0.0.1:17890";
    public string PrinterName { get; init; } = string.Empty;
    public string[] AllowedOrigins { get; init; } = [];
    public string[] AllowedSources { get; init; } = [];
    public string TempDirectory { get; init; } = "C:\\Temp\\LaisurianaPrintAgent";
    public string SumatraPdfPath { get; init; } = string.Empty;
    public int PrintTimeoutSeconds { get; init; } = 30;
}
