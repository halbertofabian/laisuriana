package mx.lasuriana.vendedor;

import android.Manifest;
import android.bluetooth.BluetoothAdapter;
import android.bluetooth.BluetoothDevice;
import android.bluetooth.BluetoothSocket;
import android.content.Intent;
import android.os.Build;
import android.provider.Settings;

import com.getcapacitor.JSArray;
import com.getcapacitor.JSObject;
import com.getcapacitor.PermissionState;
import com.getcapacitor.Plugin;
import com.getcapacitor.PluginCall;
import com.getcapacitor.PluginMethod;
import com.getcapacitor.annotation.CapacitorPlugin;
import com.getcapacitor.annotation.Permission;
import com.getcapacitor.annotation.PermissionCallback;

import java.io.OutputStream;
import java.nio.charset.Charset;
import java.lang.reflect.Method;
import java.util.ArrayList;
import java.util.Comparator;
import java.util.List;
import java.util.Set;
import java.util.UUID;
import java.util.concurrent.ExecutorService;
import java.util.concurrent.Executors;

@CapacitorPlugin(
    name = "BluetoothPrinter",
    permissions = {
        @Permission(alias = "bluetoothConnect", strings = { Manifest.permission.BLUETOOTH_CONNECT })
    }
)
public class BluetoothPrinterPlugin extends Plugin {
    private static final UUID SERIAL_PORT_UUID = UUID.fromString("00001101-0000-1000-8000-00805F9B34FB");
    private final ExecutorService printerExecutor = Executors.newSingleThreadExecutor();

    @PluginMethod
    public void requestAccess(PluginCall call) {
        if (Build.VERSION.SDK_INT < Build.VERSION_CODES.S || getPermissionState("bluetoothConnect") == PermissionState.GRANTED) {
            resolveAccess(call);
            return;
        }

        requestPermissionForAlias("bluetoothConnect", call, "bluetoothPermissionCallback");
    }

    @PermissionCallback
    private void bluetoothPermissionCallback(PluginCall call) {
        resolveAccess(call);
    }

    @PluginMethod
    public void getPairedDevices(PluginCall call) {
        BluetoothAdapter adapter = BluetoothAdapter.getDefaultAdapter();
        JSObject result = baseStatus(adapter);

        if (adapter == null || !hasBluetoothPermission()) {
            result.put("devices", new JSArray());
            call.resolve(result);
            return;
        }

        try {
            Set<BluetoothDevice> bondedDevices = adapter.getBondedDevices();
            List<BluetoothDevice> sortedDevices = new ArrayList<>(bondedDevices);
            sortedDevices.sort(Comparator.comparing(device -> {
                String name = device.getName();
                return name == null ? "" : name.toLowerCase();
            }));

            JSArray devices = new JSArray();
            for (BluetoothDevice device : sortedDevices) {
                JSObject item = new JSObject();
                item.put("address", device.getAddress());
                item.put("name", device.getName() == null ? "Dispositivo Bluetooth" : device.getName());
                item.put("type", deviceType(device.getType()));
                devices.put(item);
            }
            result.put("devices", devices);
            call.resolve(result);
        } catch (SecurityException exception) {
            call.reject("Permiso Bluetooth no disponible.", "BLUETOOTH_PERMISSION", exception);
        }
    }

    @PluginMethod
    public void openBluetoothSettings(PluginCall call) {
        Intent intent = new Intent(Settings.ACTION_BLUETOOTH_SETTINGS);
        intent.addFlags(Intent.FLAG_ACTIVITY_NEW_TASK);
        getContext().startActivity(intent);
        call.resolve();
    }

    @PluginMethod
    public void send(PluginCall call) {
        String address = call.getString("address");
        String payload = call.getString("payload");
        String charsetName = call.getString("charset", "UTF-8");

        if (address == null || payload == null) {
            call.reject("Faltan datos de impresión.", "INVALID_PRINT_JOB");
            return;
        }
        if (!hasBluetoothPermission()) {
            call.reject("Permiso Bluetooth no disponible.", "BLUETOOTH_PERMISSION");
            return;
        }

        BluetoothAdapter adapter = BluetoothAdapter.getDefaultAdapter();
        if (adapter == null) {
            call.reject("Bluetooth no es compatible con este dispositivo.", "BLUETOOTH_UNSUPPORTED");
            return;
        }
        if (!adapter.isEnabled()) {
            call.reject("Bluetooth está apagado.", "BLUETOOTH_DISABLED");
            return;
        }

        printerExecutor.execute(() -> {
            BluetoothSocket socket = null;
            try {
                BluetoothDevice device = adapter.getRemoteDevice(address);
                socket = connectToPrinter(device);

                OutputStream output = socket.getOutputStream();
                output.write(payload.getBytes(Charset.forName(charsetName)));
                output.flush();

                JSObject result = new JSObject();
                result.put("success", true);
                call.resolve(result);
            } catch (IllegalArgumentException exception) {
                call.reject("Impresora no encontrada.", "DEVICE_NOT_FOUND", exception);
            } catch (Exception exception) {
                call.reject("No fue posible conectar con la impresora.", "CONNECTION_FAILED", exception);
            } finally {
                if (socket != null) {
                    try {
                        socket.close();
                    } catch (Exception ignored) {
                    }
                }
            }
        });
    }

    private BluetoothSocket connectToPrinter(BluetoothDevice device) throws Exception {
        Exception lastError = null;
        BluetoothSocket attempt = null;

        try {
            attempt = device.createInsecureRfcommSocketToServiceRecord(SERIAL_PORT_UUID);
            attempt.connect();
            return attempt;
        } catch (Exception exception) {
            lastError = exception;
            closeQuietly(attempt);
        }

        try {
            attempt = device.createRfcommSocketToServiceRecord(SERIAL_PORT_UUID);
            attempt.connect();
            return attempt;
        } catch (Exception exception) {
            lastError = exception;
            closeQuietly(attempt);
        }

        try {
            Method channelMethod = device.getClass().getMethod("createRfcommSocket", int.class);
            attempt = (BluetoothSocket) channelMethod.invoke(device, 1);
            attempt.connect();
            return attempt;
        } catch (Exception exception) {
            closeQuietly(attempt);
            if (lastError != null) exception.addSuppressed(lastError);
            throw exception;
        }
    }

    private void closeQuietly(BluetoothSocket socket) {
        if (socket == null) return;
        try {
            socket.close();
        } catch (Exception ignored) {
        }
    }

    @Override
    protected void handleOnDestroy() {
        printerExecutor.shutdownNow();
    }

    private void resolveAccess(PluginCall call) {
        BluetoothAdapter adapter = BluetoothAdapter.getDefaultAdapter();
        JSObject result = new JSObject();
        result.put("granted", hasBluetoothPermission());
        result.put("enabled", adapter != null && adapter.isEnabled());
        call.resolve(result);
    }

    private JSObject baseStatus(BluetoothAdapter adapter) {
        JSObject result = new JSObject();
        result.put("supported", adapter != null);
        result.put("enabled", adapter != null && adapter.isEnabled());
        result.put("permissionGranted", hasBluetoothPermission());
        return result;
    }

    private boolean hasBluetoothPermission() {
        return Build.VERSION.SDK_INT < Build.VERSION_CODES.S
            || getPermissionState("bluetoothConnect") == PermissionState.GRANTED;
    }

    private String deviceType(int type) {
        if (type == BluetoothDevice.DEVICE_TYPE_CLASSIC) return "classic";
        if (type == BluetoothDevice.DEVICE_TYPE_DUAL) return "dual";
        if (type == BluetoothDevice.DEVICE_TYPE_LE) return "ble";
        return "unknown";
    }
}
