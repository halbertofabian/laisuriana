import { StyleSheet, Text } from "react-native";
import { useAuth } from "@/features/auth/state/AuthContext";
import { Card } from "@/shared/ui/Card";
import { PrimaryButton } from "@/shared/ui/PrimaryButton";
import { Screen } from "@/shared/ui/Screen";
import { AppTheme } from "@/app/theme/theme";

export function ProfileScreen() {
  const { session, signOut } = useAuth();

  return (
    <Screen>
      <Card>
        <Text style={styles.title}>Perfil operativo</Text>
        <Text style={styles.text}>Usuario autenticado: {session?.usuario || "Sin sesión"}</Text>
        <Text style={styles.note}>Pendiente de confirmar: permisos por rol, sesión expirada y refresh dedicado para móvil.</Text>
        <PrimaryButton label="Cerrar sesión" onPress={() => void signOut()} />
      </Card>
    </Screen>
  );
}

const styles = StyleSheet.create({
  title: { fontSize: 22, fontWeight: "800", color: AppTheme.colors.text },
  text: { color: AppTheme.colors.textMuted },
  note: { color: AppTheme.colors.warning, lineHeight: 20 },
});
