import { useNavigation } from "@react-navigation/native";
import { StyleSheet, Text } from "react-native";
import { useFloorOrder } from "@/features/floor-order/state/FloorOrderContext";
import { useOperationalContext } from "@/features/operational-context/state/OperationalContext";
import { Card } from "@/shared/ui/Card";
import { PrimaryButton } from "@/shared/ui/PrimaryButton";
import { Screen } from "@/shared/ui/Screen";
import { AppTheme } from "@/app/theme/theme";

export function HomeScreen() {
  const navigation = useNavigation<any>();
  const { sucursal } = useOperationalContext();
  const { draft } = useFloorOrder();

  return (
    <Screen>
      <Card>
        <Text style={styles.title}>Operación de piso</Text>
        <Text style={styles.context}>Sucursal activa: {sucursal?.scl_nombre || "Pendiente de seleccionar"}</Text>
        <Text style={styles.context}>Partidas en borrador: {draft.lines.length}</Text>
      </Card>
      <Card>
        <Text style={styles.blockTitle}>Flujo base</Text>
        <PrimaryButton label="Seleccionar sucursal" onPress={() => navigation.navigate("BranchContext")} />
        <PrimaryButton label="Buscar o escanear producto" onPress={() => navigation.navigate("ProductSearch")} />
        <PrimaryButton label="Ver carrito agrupado" onPress={() => navigation.navigate("Cart")} disabled={!draft.lines.length} />
      </Card>
      <Card>
        <Text style={styles.blockTitle}>Cobertura actual</Text>
        <Text style={styles.note}>Implementado: autenticación, contexto, búsqueda, resolución de almacén, cliente opcional, agrupación por almacén e historial pendiente.</Text>
        <Text style={styles.note}>Pendiente de confirmar: reserva de existencia, reimpresión móvil nativa de PDF y edición/cancelación de pedidos desde app.</Text>
      </Card>
    </Screen>
  );
}

const styles = StyleSheet.create({
  title: { fontSize: 24, fontWeight: "800", color: AppTheme.colors.text },
  context: { color: AppTheme.colors.textMuted },
  blockTitle: { fontSize: 18, fontWeight: "800", color: AppTheme.colors.text },
  note: { color: AppTheme.colors.textMuted, lineHeight: 20 },
});
