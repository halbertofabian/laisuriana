import { Alert, Pressable, StyleSheet, Text, TextInput, View } from "react-native";
import { NativeStackScreenProps } from "@react-navigation/native-stack";
import type { RootStackParamList } from "@/app/navigation/types";
import { useFloorOrder } from "@/features/floor-order/state/FloorOrderContext";
import { calculateDraftTotal, calculateLineSubtotal, groupLinesByWarehouse, sanitizeQuantity } from "@/features/floor-order/utils/orderMath";
import { Card } from "@/shared/ui/Card";
import { PrimaryButton } from "@/shared/ui/PrimaryButton";
import { Screen } from "@/shared/ui/Screen";
import { AppTheme } from "@/app/theme/theme";

type Props = NativeStackScreenProps<RootStackParamList, "Cart">;

export function CartScreen({ navigation }: Props) {
  const { draft, updateQuantity, updateDiscount, removeLine, setNotes } = useFloorOrder();
  const groups = groupLinesByWarehouse(draft.lines);

  if (!draft.lines.length) {
    return (
      <Screen>
        <Card><Text style={styles.note}>No hay partidas en el borrador.</Text></Card>
      </Screen>
    );
  }

  return (
    <Screen>
      {groups.map((group) => (
        <Card key={group.pdp_alm_id}>
          <Text style={styles.groupTitle}>{group.almacen}</Text>
          <Text style={styles.groupMeta}>{group.items.length} partida(s) en este almacén.</Text>
          {group.items.map((line) => (
            <View key={line.itemKey} style={styles.line}>
              <View style={{ flex: 1 }}>
                <Text style={styles.lineName}>{line.nombre}</Text>
                <Text style={styles.lineMeta}>{line.sku}</Text>
                <Text style={styles.lineMeta}>Subtotal: ${calculateLineSubtotal(line).toFixed(2)}</Text>
              </View>
              <View style={styles.lineActions}>
                <TextInput
                  style={styles.qtyInput}
                  keyboardType="decimal-pad"
                  value={String(line.cantidad)}
                  onChangeText={(value) => {
                    const next = sanitizeQuantity(Number(value), line.permite_decimal);
                    updateQuantity(line.itemKey, next);
                  }}
                />
                <TextInput
                  style={styles.discountInput}
                  keyboardType="decimal-pad"
                  value={String(line.descuento_valor)}
                  onChangeText={(value) => updateDiscount(line.itemKey, "porcentaje", Number(value || 0))}
                  placeholder="% desc."
                />
                <Pressable style={styles.deleteButton} onPress={() => removeLine(line.itemKey)}>
                  <Text style={styles.deleteLabel}>Quitar</Text>
                </Pressable>
              </View>
            </View>
          ))}
        </Card>
      ))}
      <Card>
        <Text style={styles.groupTitle}>Cliente y notas</Text>
        <PrimaryButton label={draft.client ? `Cliente: ${draft.client.nombre}` : "Seleccionar cliente"} onPress={() => navigation.navigate("ClientSelection")} />
        <TextInput
          style={styles.notes}
          value={draft.observaciones}
          onChangeText={setNotes}
          multiline
          placeholder="Observaciones del pedido"
        />
        <Text style={styles.note}>El descuento por línea queda modelado. Falta UX final para tipo `importe` vs `porcentaje`.</Text>
      </Card>
      <Card>
        <Text style={styles.total}>Total estimado: ${calculateDraftTotal(draft.lines).toFixed(2)}</Text>
        <PrimaryButton
          label="Confirmar pedidos por almacén"
          onPress={() => {
            if (!groups.length) {
              Alert.alert("Sin partidas", "Agrega productos antes de continuar.");
              return;
            }
            navigation.navigate("OrderConfirmation");
          }}
        />
      </Card>
    </Screen>
  );
}

const styles = StyleSheet.create({
  groupTitle: { fontSize: 18, fontWeight: "800", color: AppTheme.colors.text },
  groupMeta: { color: AppTheme.colors.textMuted },
  line: { flexDirection: "row", gap: 10, alignItems: "center", paddingTop: 8 },
  lineName: { fontWeight: "700", color: AppTheme.colors.text },
  lineMeta: { color: AppTheme.colors.textMuted, marginTop: 2 },
  lineActions: { alignItems: "flex-end", gap: 8 },
  qtyInput: {
    width: 72,
    borderWidth: 1,
    borderColor: AppTheme.colors.border,
    borderRadius: AppTheme.radius.sm,
    backgroundColor: "#fff",
    paddingHorizontal: 10,
    paddingVertical: 8,
    textAlign: "center",
  },
  discountInput: {
    width: 72,
    borderWidth: 1,
    borderColor: AppTheme.colors.border,
    borderRadius: AppTheme.radius.sm,
    backgroundColor: "#fff",
    paddingHorizontal: 10,
    paddingVertical: 8,
    textAlign: "center",
  },
  deleteButton: { paddingVertical: 8, paddingHorizontal: 10 },
  deleteLabel: { color: AppTheme.colors.danger, fontWeight: "700" },
  notes: {
    minHeight: 90,
    borderWidth: 1,
    borderColor: AppTheme.colors.border,
    borderRadius: AppTheme.radius.sm,
    backgroundColor: "#fff",
    padding: 12,
    textAlignVertical: "top",
  },
  note: { color: AppTheme.colors.warning, lineHeight: 20 },
  total: { fontSize: 20, fontWeight: "800", color: AppTheme.colors.text },
});
