package mx.lasuriana.vendedor;

import android.content.Context;
import android.content.SharedPreferences;
import android.security.keystore.KeyGenParameterSpec;
import android.security.keystore.KeyProperties;
import android.util.Base64;

import com.getcapacitor.JSObject;
import com.getcapacitor.Plugin;
import com.getcapacitor.PluginCall;
import com.getcapacitor.PluginMethod;
import com.getcapacitor.annotation.CapacitorPlugin;

import org.json.JSONObject;

import java.nio.charset.StandardCharsets;
import java.security.KeyStore;

import javax.crypto.Cipher;
import javax.crypto.KeyGenerator;
import javax.crypto.SecretKey;
import javax.crypto.spec.GCMParameterSpec;

@CapacitorPlugin(name = "SecureSession")
public class SecureSessionPlugin extends Plugin {
    private static final String KEY_ALIAS = "suriana_vendedor_session_key";
    private static final String PREFERENCES = "suriana_secure_session";
    private static final String TRANSFORMATION = "AES/GCM/NoPadding";

    @PluginMethod
    public void set(PluginCall call) {
        String key = call.getString("key");
        String value = call.getString("value");

        if (key == null || value == null) {
            call.reject("Se requieren key y value.");
            return;
        }

        try {
            preferences().edit().putString(key, encrypt(value)).apply();
            call.resolve();
        } catch (Exception exception) {
            call.reject("No fue posible guardar la sesión segura.", exception);
        }
    }

    @PluginMethod
    public void get(PluginCall call) {
        String key = call.getString("key");

        if (key == null) {
            call.reject("Se requiere key.");
            return;
        }

        try {
            String stored = preferences().getString(key, null);
            JSObject result = new JSObject();
            result.put("value", stored == null ? JSONObject.NULL : decrypt(stored));
            call.resolve(result);
        } catch (Exception exception) {
            preferences().edit().remove(key).apply();
            call.reject("La sesión guardada ya no es válida.", exception);
        }
    }

    @PluginMethod
    public void remove(PluginCall call) {
        String key = call.getString("key");

        if (key == null) {
            call.reject("Se requiere key.");
            return;
        }

        preferences().edit().remove(key).apply();
        call.resolve();
    }

    private SharedPreferences preferences() {
        return getContext().getSharedPreferences(PREFERENCES, Context.MODE_PRIVATE);
    }

    private String encrypt(String value) throws Exception {
        Cipher cipher = Cipher.getInstance(TRANSFORMATION);
        cipher.init(Cipher.ENCRYPT_MODE, getOrCreateKey());
        byte[] encrypted = cipher.doFinal(value.getBytes(StandardCharsets.UTF_8));

        return Base64.encodeToString(cipher.getIV(), Base64.NO_WRAP)
            + "."
            + Base64.encodeToString(encrypted, Base64.NO_WRAP);
    }

    private String decrypt(String stored) throws Exception {
        String[] parts = stored.split("\\.", 2);

        if (parts.length != 2) {
            throw new IllegalArgumentException("Formato de sesión inválido.");
        }

        Cipher cipher = Cipher.getInstance(TRANSFORMATION);
        cipher.init(
            Cipher.DECRYPT_MODE,
            getOrCreateKey(),
            new GCMParameterSpec(128, Base64.decode(parts[0], Base64.NO_WRAP))
        );

        return new String(
            cipher.doFinal(Base64.decode(parts[1], Base64.NO_WRAP)),
            StandardCharsets.UTF_8
        );
    }

    private SecretKey getOrCreateKey() throws Exception {
        KeyStore keyStore = KeyStore.getInstance("AndroidKeyStore");
        keyStore.load(null);

        if (keyStore.containsAlias(KEY_ALIAS)) {
            return ((KeyStore.SecretKeyEntry) keyStore.getEntry(KEY_ALIAS, null)).getSecretKey();
        }

        KeyGenerator generator = KeyGenerator.getInstance(KeyProperties.KEY_ALGORITHM_AES, "AndroidKeyStore");
        generator.init(new KeyGenParameterSpec.Builder(
            KEY_ALIAS,
            KeyProperties.PURPOSE_ENCRYPT | KeyProperties.PURPOSE_DECRYPT
        )
            .setBlockModes(KeyProperties.BLOCK_MODE_GCM)
            .setEncryptionPaddings(KeyProperties.ENCRYPTION_PADDING_NONE)
            .setKeySize(256)
            .build());

        return generator.generateKey();
    }
}
