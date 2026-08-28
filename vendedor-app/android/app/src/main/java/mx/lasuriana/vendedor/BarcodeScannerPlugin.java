package mx.lasuriana.vendedor;

import com.getcapacitor.JSObject;
import com.getcapacitor.Plugin;
import com.getcapacitor.PluginCall;
import com.getcapacitor.PluginMethod;
import com.getcapacitor.annotation.CapacitorPlugin;
import com.google.mlkit.vision.codescanner.GmsBarcodeScanner;
import com.google.mlkit.vision.codescanner.GmsBarcodeScannerOptions;
import com.google.mlkit.vision.codescanner.GmsBarcodeScanning;

import java.util.concurrent.atomic.AtomicBoolean;

@CapacitorPlugin(name = "BarcodeScanner")
public class BarcodeScannerPlugin extends Plugin {
    private final AtomicBoolean scanning = new AtomicBoolean(false);

    @PluginMethod
    public void scan(PluginCall call) {
        if (!scanning.compareAndSet(false, true)) {
            call.reject("Ya hay un escaneo en curso.", "SCAN_IN_PROGRESS");
            return;
        }

        getActivity().runOnUiThread(() -> {
            GmsBarcodeScannerOptions options = new GmsBarcodeScannerOptions.Builder()
                .enableAutoZoom()
                .build();
            GmsBarcodeScanner scanner = GmsBarcodeScanning.getClient(getActivity(), options);

            scanner.startScan()
                .addOnSuccessListener(barcode -> {
                    scanning.set(false);
                    String value = barcode.getRawValue();
                    if (value == null || value.trim().isEmpty()) {
                        call.reject("El código leído está vacío.", "EMPTY_BARCODE");
                        return;
                    }

                    JSObject result = new JSObject();
                    result.put("cancelled", false);
                    result.put("value", value.trim());
                    result.put("format", barcode.getFormat());
                    call.resolve(result);
                })
                .addOnCanceledListener(() -> {
                    scanning.set(false);
                    JSObject result = new JSObject();
                    result.put("cancelled", true);
                    call.resolve(result);
                })
                .addOnFailureListener(error -> {
                    scanning.set(false);
                    call.reject("No fue posible iniciar el lector de códigos.", "SCANNER_UNAVAILABLE", error);
                });
        });
    }
}
