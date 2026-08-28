package mx.lasuriana.vendedor;

import android.os.Bundle;
import com.getcapacitor.BridgeActivity;

public class MainActivity extends BridgeActivity {
    @Override
    public void onCreate(Bundle savedInstanceState) {
        registerPlugin(SecureSessionPlugin.class);
        registerPlugin(BluetoothPrinterPlugin.class);
        registerPlugin(BarcodeScannerPlugin.class);
        super.onCreate(savedInstanceState);
    }
}
