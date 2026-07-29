using Laisuriana.PrintAgent.Models;
using Laisuriana.PrintAgent.Services;
using Microsoft.Extensions.Options;
using System.Text.Json;

var builder = WebApplication.CreateBuilder(args);
builder.Host.UseWindowsService();

builder.Services.Configure<PrinterAgentOptions>(
    builder.Configuration.GetSection(PrinterAgentOptions.SectionName)
);
builder.Services.AddSingleton<PdfPrintDispatcher>();

var app = builder.Build();

app.Use(async (context, next) =>
{
    var origin = context.Request.Headers.Origin.ToString();

    if (IsAllowedLocalOrigin(origin))
    {
        context.Response.Headers.AccessControlAllowOrigin = origin;
        context.Response.Headers.AccessControlAllowHeaders = "Content-Type, Accept, X-Requested-With";
        context.Response.Headers.AccessControlAllowMethods = "GET, POST, OPTIONS";
        context.Response.Headers.AccessControlAllowCredentials = "false";
    }

    if (HttpMethods.IsOptions(context.Request.Method))
    {
        context.Response.StatusCode = StatusCodes.Status204NoContent;
        return;
    }

    try
    {
        await next();
    }
    catch (Exception ex)
    {
        context.Response.StatusCode = StatusCodes.Status500InternalServerError;
        context.Response.ContentType = "application/json";

        var payload = JsonSerializer.Serialize(new
        {
            ok = false,
            message = ex.Message,
        });

        await context.Response.WriteAsync(payload);
    }
});

app.MapGet("/api/status", (IOptions<PrinterAgentOptions> options) =>
{
    var config = options.Value;

    return Results.Ok(new
    {
        ok = true,
        machine = Environment.MachineName,
        bind = config.BindUrl,
        printer = config.PrinterName,
        sumatraPdfPath = config.SumatraPdfPath,
    });
});

app.MapPost("/api/print-jobs", async (PrintJobRequest request, PdfPrintDispatcher dispatcher, CancellationToken cancellationToken) =>
{
    var result = await dispatcher.PrintAsync(request, cancellationToken);

    return Results.Ok(new
    {
        ok = true,
        message = "Ticket enviado a la impresora de esta computadora.",
        job = result,
    });
});

var options = app.Services.GetRequiredService<IOptions<PrinterAgentOptions>>().Value;
app.Run(options.BindUrl);

static bool IsAllowedLocalOrigin(string? origin)
{
    if (string.IsNullOrWhiteSpace(origin))
    {
        return false;
    }

    if (!Uri.TryCreate(origin, UriKind.Absolute, out var uri))
    {
        return false;
    }

    return uri.Host.Equals("127.0.0.1", StringComparison.OrdinalIgnoreCase)
        || uri.Host.Equals("localhost", StringComparison.OrdinalIgnoreCase);
}
