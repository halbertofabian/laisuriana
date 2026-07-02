import { useEffect, useState } from "react";
import { Alert, FlatList, Pressable, StyleSheet, Text, View } from "react-native";
import { fetchAlmacenes, fetchSucursales } from "@/features/operational-context/api/operationalContextApi";
import { useOperationalContext } from "@/features/operational-context/state/OperationalContext";
import { Card } from "@/shared/ui/Card";
import { PrimaryButton } from "@/shared/ui/PrimaryButton";
import { Screen } from "@/shared/ui/Screen";
import { AppTheme } from "@/app/theme/theme";
import type { Almacen, Sucursal } from "@/shared/types/catalogs";

export function BranchContextScreen() {
  const { sucursal, setSucursal, almacenCatalog, setAlmacenCatalog } = useOperationalContext();
  const [sucursales, setSucursales] = useState<Sucursal[]>([]);
  const [selectedAlmacenId, setSelectedAlmacenId] = useState<number | null>(null);

  useEffect(() => {
    void fetchSucursales().then(setSucursales).catch((error: unknown) => {
      Alert.alert("Error", error instanceof Error ? error.message : "No se pudieron cargar sucursales.");
    });
  }, []);

  const onSelectSucursal = async (item: Sucursal) => {
    setSucursal(item);
    setSelectedAlmacenId(null);
    const almacenes = await fetchAlmacenes(item.scl_id);
    setAlmacenCatalog(almacenes);
  };

  return (
    <Screen>
      <Card>
        <Text style={styles.title}>Sucursal activa</Text>
        <Text style={styles.help}>La sucursal afecta la resolución de almacenes y el historial operativo.</Text>
        <FlatList
          data={sucursales}
          keyExtractor={(item) => String(item.scl_id)}
          renderItem={({ item }) => (
            <OptionRow label={item.scl_nombre} active={sucursal?.scl_id === item.scl_id} onPress={() => void onSelectSucursal(item)} />
          )}
          scrollEnabled={false}
        />
      </Card>
      <Card>
        <Text style={styles.title}>Almacenes disponibles</Text>
        <Text style={styles.help}>El pedido se podrá dividir por almacén al confirmar.</Text>
        <FlatList
          data={almacenCatalog}
          keyExtractor={(item) => String(item.alm_id)}
          renderItem={({ item }) => (
            <OptionRow label={item.alm_nombre} active={selectedAlmacenId === item.alm_id} onPress={() => setSelectedAlmacenId(item.alm_id)} />
          )}
          ListEmptyComponent={<Text style={styles.help}>Selecciona primero una sucursal.</Text>}
          scrollEnabled={false}
        />
        <PrimaryButton label="Guardar contexto" onPress={() => Alert.alert("Contexto guardado", "La app ya puede operar sobre la sucursal seleccionada.")} disabled={!sucursal} />
      </Card>
    </Screen>
  );
}

function OptionRow({ label, active, onPress }: { label: string; active: boolean; onPress: () => void }) {
  return (
    <Pressable style={[styles.option, active && styles.optionActive]} onPress={onPress}>
      <Text style={[styles.optionText, active && styles.optionTextActive]}>{label}</Text>
    </Pressable>
  );
}

const styles = StyleSheet.create({
  title: { fontSize: 20, fontWeight: "800", color: AppTheme.colors.text },
  help: { color: AppTheme.colors.textMuted, lineHeight: 20 },
  option: {
    borderWidth: 1,
    borderColor: AppTheme.colors.border,
    borderRadius: AppTheme.radius.sm,
    padding: 12,
  },
  optionActive: {
    borderColor: AppTheme.colors.primary,
    backgroundColor: AppTheme.colors.primarySoft,
  },
  optionText: { color: AppTheme.colors.text, fontWeight: "600" },
  optionTextActive: { color: AppTheme.colors.primary },
});
