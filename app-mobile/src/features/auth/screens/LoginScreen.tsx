import { useEffect, useState } from "react";
import { ActivityIndicator, Alert, FlatList, KeyboardAvoidingView, Platform, Pressable, StyleSheet, Text, TextInput, View } from "react-native";
import { MaterialCommunityIcons } from "@expo/vector-icons";
import { searchUsers } from "@/features/auth/api/authApi";
import { useAuth } from "@/features/auth/state/AuthContext";
import { Card } from "@/shared/ui/Card";
import { PrimaryButton } from "@/shared/ui/PrimaryButton";
import { Screen } from "@/shared/ui/Screen";
import { AppTheme } from "@/app/theme/theme";
import type { UsuarioSugerido } from "@/shared/types/auth";

export function LoginScreen() {
  const { signIn, status } = useAuth();
  const [usuario, setUsuario] = useState("");
  const [password, setPassword] = useState("");
  const [users, setUsers] = useState<UsuarioSugerido[]>([]);
  const [loading, setLoading] = useState(false);

  useEffect(() => {
    const q = usuario.trim();
    if (q.length < 2) {
      setUsers([]);
      return;
    }
    const timer = setTimeout(() => {
      void searchUsers(q).then(setUsers).catch(() => setUsers([]));
    }, 200);

    return () => clearTimeout(timer);
  }, [usuario]);

  if (status === "booting") {
    return (
      <Screen scroll={false}>
        <View style={styles.center}>
          <ActivityIndicator size="large" color={AppTheme.colors.primary} />
        </View>
      </Screen>
    );
  }

  return (
    <Screen scroll={false}>
      <KeyboardAvoidingView behavior={Platform.OS === "ios" ? "padding" : undefined} style={styles.center}>
        <Card>
          <View style={styles.brand}>
            <View style={styles.brandBadge}>
              <MaterialCommunityIcons name="storefront-outline" size={18} color="#fff" />
            </View>
            <Text style={styles.brandText}>Piso Suriana</Text>
          </View>
          <Text style={styles.title}>Acceso operativo</Text>
          <Text style={styles.subtitle}>Base Android inicial conectada al backend Laravel actual.</Text>
          <TextInput style={styles.input} value={usuario} onChangeText={setUsuario} placeholder="Usuario" autoCapitalize="none" />
          {users.length > 0 ? (
            <FlatList
              data={users}
              style={styles.suggestions}
              keyExtractor={(item) => item.usr_usuario}
              renderItem={({ item }) => (
                <Pressable style={styles.suggestion} onPress={() => setUsuario(item.usr_usuario)}>
                  <Text style={styles.suggestionUser}>{item.usr_usuario}</Text>
                  <Text style={styles.suggestionName}>{item.usr_nombre}</Text>
                </Pressable>
              )}
            />
          ) : null}
          <TextInput
            style={styles.input}
            value={password}
            onChangeText={setPassword}
            placeholder="Contraseña"
            autoCapitalize="none"
            secureTextEntry
          />
          <PrimaryButton
            label="Entrar"
            loading={loading}
            onPress={() => {
              if (!usuario.trim() || !password.trim()) {
                Alert.alert("Faltan datos", "Captura usuario y contraseña.");
                return;
              }
              setLoading(true);
              void signIn(usuario.trim(), password)
                .catch((error: unknown) => {
                  Alert.alert("No fue posible iniciar sesión", error instanceof Error ? error.message : "Error desconocido.");
                })
                .finally(() => setLoading(false));
            }}
          />
        </Card>
      </KeyboardAvoidingView>
    </Screen>
  );
}

const styles = StyleSheet.create({
  center: { flex: 1, justifyContent: "center" },
  brand: { flexDirection: "row", alignItems: "center", gap: 10 },
  brandBadge: {
    width: 36,
    height: 36,
    borderRadius: 12,
    backgroundColor: AppTheme.colors.primary,
    alignItems: "center",
    justifyContent: "center",
  },
  brandText: { fontSize: 18, fontWeight: "700", color: AppTheme.colors.text },
  title: { fontSize: 24, fontWeight: "800", color: AppTheme.colors.text },
  subtitle: { color: AppTheme.colors.textMuted, lineHeight: 20 },
  input: {
    borderWidth: 1,
    borderColor: AppTheme.colors.border,
    borderRadius: AppTheme.radius.sm,
    paddingHorizontal: 12,
    paddingVertical: 12,
    backgroundColor: "#fff",
  },
  suggestions: { maxHeight: 160, borderWidth: 1, borderColor: AppTheme.colors.border, borderRadius: AppTheme.radius.sm },
  suggestion: { padding: 10, borderBottomWidth: 1, borderBottomColor: AppTheme.colors.border },
  suggestionUser: { fontWeight: "700", color: AppTheme.colors.text },
  suggestionName: { color: AppTheme.colors.textMuted, marginTop: 2 },
});
