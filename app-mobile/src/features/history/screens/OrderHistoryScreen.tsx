import { useEffect, useState } from "react";
import { FlatList, StyleSheet, Text, TextInput, View } from "react-native";
import { listPendingOrders } from "@/features/floor-order/api/floorOrderApi";
import type { PedidoRow } from "@/features/floor-order/types";
import { useOperationalContext } from "@/features/operational-context/state/OperationalContext";
import { Card } from "@/shared/ui/Card";
import { Screen } from "@/shared/ui/Screen";
import { AppTheme } from "@/app/theme/theme";

export function OrderHistoryScreen() {
  const { sucursal } = useOperationalContext();
  const [query, setQuery] = useState("");
  const [orders, setOrders] = useState<PedidoRow[]>([]);

  useEffect(() => {
    const timer = setTimeout(() => {
      void listPendingOrders({
        buscar: query,
        pdp_scl_id: sucursal?.scl_id,
      }).then(setOrders).catch(() => setOrders([]));
    }, 180);

    return () => clearTimeout(timer);
  }, [query, sucursal?.scl_id]);

  return (
    <Screen>
      <Card>
        <Text style={styles.title}>Historial pendiente de cobro</Text>
        <Text style={styles.note}>Actualmente se lista `pendiente_cobro`, alineado al flujo real de caja.</Text>
        <TextInput style={styles.input} value={query} onChangeText={setQuery} placeholder="Buscar por folio u observación" />
      </Card>
      <FlatList
        data={orders}
        keyExtractor={(item) => String(item.pdp_id)}
        renderItem={({ item }) => (
          <View style={styles.row}>
            <Text style={styles.folio}>{item.pdp_folio}</Text>
            <Text style={styles.meta}>{item.sucursal || "Sin sucursal"} / {item.almacen || "Sin almacén"}</Text>
            <Text style={styles.total}>${Number(item.pdp_total || 0).toFixed(2)}</Text>
          </View>
        )}
        ListEmptyComponent={<Card><Text style={styles.note}>Sin pedidos pendientes para el filtro actual.</Text></Card>}
        scrollEnabled={false}
      />
    </Screen>
  );
}

const styles = StyleSheet.create({
  title: { fontSize: 22, fontWeight: "800", color: AppTheme.colors.text },
  note: { color: AppTheme.colors.textMuted, lineHeight: 20 },
  input: {
    borderWidth: 1,
    borderColor: AppTheme.colors.border,
    borderRadius: AppTheme.radius.sm,
    backgroundColor: "#fff",
    paddingHorizontal: 12,
    paddingVertical: 12,
  },
  row: {
    backgroundColor: "#fff",
    borderWidth: 1,
    borderColor: AppTheme.colors.border,
    borderRadius: AppTheme.radius.sm,
    padding: 14,
    marginHorizontal: AppTheme.spacing.md,
    marginBottom: AppTheme.spacing.sm,
  },
  folio: { fontSize: 16, fontWeight: "800", color: AppTheme.colors.primary },
  meta: { color: AppTheme.colors.textMuted, marginTop: 4 },
  total: { color: AppTheme.colors.text, fontWeight: "800", marginTop: 8 },
});
