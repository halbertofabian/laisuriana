using System.Text.Json.Serialization;

namespace Laisuriana.PrintAgent.Models;

public sealed class PrintJobRequest
{
    [JsonPropertyName("source")]
    public string? Source { get; init; }

    [JsonPropertyName("content_type")]
    public string? ContentType { get; init; }

    [JsonPropertyName("document_name")]
    public string? DocumentName { get; init; }

    [JsonPropertyName("document_base64")]
    public string? DocumentBase64 { get; init; }
}
