import { useState } from "react";
import { Alert, StyleSheet, Text } from "react-native";
import { NativeStackScreenProps } from "@react-navigation/native-stack";
import type { RootStackParamList } from "@/app/navigation/types";
import { createOrdersFromDraft } from "@/features/floor-order/api/floorOrderApi";
import { useFloorOrder } from "@/features/floor-order/state/FloorOrderContext";
import { groupLinesByWarehouse } from "@/features/floor-order/utils/orderMath";
import { useOperationalContext } from "@/features/operational-context/state/OperationalContext";
import { Card } from "@/shared/ui/Card";
import { PrimaryButton } from "@/shared/ui/PrimaryButton";
import { Screen } from "@/shared/ui/Screen";
import { AppTheme } from "@/app/theme/theme";

type Props = NativeStackScreenProps<RootStackParamList, "OrderConfirmation">;

export function OrderConfirmationScreen({ navigation }: Props) {
  const { sucursal } = useOperationalContext();
  const { draft, reset } = useFloorOrder();
  const [saving, setSaving] = useState(false);
  const groups = groupLinesByWarehouse(draft.lines);

  return (
    <Screen>
      <Card>
        <Text style={styles.title}>Confirmación</Text>
        <Text style={styles.text}>Sucursal: {sucursal?.scl_nombre || "Sin sucursal activa"}</Text>
        <Text style={styles.text}>Cliente: {draft.client?.nombre || "Público en general"}</Text>
        <Text style={styles.text}>Se generarán {groups.length} pedido(s), uno por almacén.</Text>
        <Text style={styles.note}>El cobro ocurre después en POS/caja. No se asume reserva de existencia.</Text>
      </Card>
      {groups.map((group) => (
        <Card key={group.pdp_alm_id}>
          <Text style={styles.group}>{group.almacen}</Text>
          <Text style={styles.text}>{group.items.length} partida(s)</Text>
        </Card>
      ))}
      <PrimaryButton
        label="Generar pedidos"
        loading={saving}
        disabled={!sucursal || !groups.length}
        onPress={() => {
          if (!sucursal) {
            Alert.alert("Contexto faltante", "Selecciona la sucursal antes de generar pedidos.");
            return;
          }
          setSaving(true);
          void createOrdersFromDraft(sucursal.scl_id, draft)
            .then((result) => {
              reset();
              navigation.replace("Ticket", result);
            })
            .catch((error: unknown) => {
              Alert.alert("No fue posible generar", error instanceof Error ? error.message : "Error desconocido.");
            })
            .finally(() => setSaving(false));
        }}
      />
    </Screen>
  );
}

const styles = StyleSheet.create({
  title: { fontSize: 22, fontWeight: "800", color: AppTheme.colors.text },
  group: { fontSize: 18, fontWeight: "800", color: AppTheme.colors.text },
  text: { color: AppTheme.colors.textMuted },
  note: { color: AppTheme.colors.warning, lineHeight: 20 },
});
