import { useEffect, useState } from "react";
import { Alert, FlatList, Pressable, StyleSheet, Text, TextInput, View } from "react-native";
import { NativeStackScreenProps } from "@react-navigation/native-stack";
import type { RootStackParamList } from "@/app/navigation/types";
import { useAuth } from "@/features/auth/state/AuthContext";
import { searchProducts, resolveProductWarehouse } from "@/features/floor-order/api/floorOrderApi";
import { useFloorOrder } from "@/features/floor-order/state/FloorOrderContext";
import { sanitizeQuantity } from "@/features/floor-order/utils/orderMath";
import { useOperationalContext } from "@/features/operational-context/state/OperationalContext";
import type { ProductSuggestion, WarehouseResolution } from "@/features/floor-order/types";
import { Card } from "@/shared/ui/Card";
import { PrimaryButton } from "@/shared/ui/PrimaryButton";
import { Screen } from "@/shared/ui/Screen";
import { AppTheme } from "@/app/theme/theme";

type Props = NativeStackScreenProps<RootStackParamList, "ProductSearch">;

export function ProductSearchScreen({ navigation }: Props) {
  const { session } = useAuth();
  const { sucursal } = useOperationalContext();
  const { addLine, draft } = useFloorOrder();
  const [query, setQuery] = useState("");
  const [results, setResults] = useState<ProductSuggestion[]>([]);
  const [lastResolution, setLastResolution] = useState<WarehouseResolution | null>(null);

  useEffect(() => {
    const q = query.trim();
    if (q.length < 2) {
      setResults([]);
      return;
    }
    const timer = setTimeout(() => {
      void searchProducts(q).then(setResults).catch(() => setResults([]));
    }, 180);
    return () => clearTimeout(timer);
  }, [query]);

  const addProduct = async (item: ProductSuggestion) => {
    if (!sucursal) {
      Alert.alert("Sucursal requerida", "Selecciona primero el contexto operativo.");
      return;
    }

    try {
      const resolution = await resolveProductWarehouse(item.psk_id, sucursal.scl_id);
      setLastResolution(resolution);

      const selectedWarehouse =
        resolution.requiere_seleccion && resolution.almacenes?.length
          ? resolution.almacenes[0]
          : { alm_id: resolution.pdp_alm_id || 0, alm_nombre: resolution.almacen || "Sin almacén" };

      if (!selectedWarehouse.alm_id) {
        throw new Error("No fue posible identificar el almacén del producto.");
      }

      addLine({
        itemKey: `${selectedWarehouse.alm_id}:${item.psk_id}:${session?.usuario || "usr"}`,
        ppd_psk_id: item.psk_id,
        prd_id: resolution.prd_id,
        pdp_alm_id: selectedWarehouse.alm_id,
        almacen: selectedWarehouse.alm_nombre,
        sku: item.psk_codigo,
        nombre: item.psk_nombre || item.producto?.prd_nombre || item.psk_codigo,
        cantidad: sanitizeQuantity(resolution.permite_decimal ? 0.01 : 1, Boolean(resolution.permite_decimal)),
        precio: Number(item.psk_precio || 0),
        permite_decimal: Boolean(resolution.permite_decimal),
        capturista: session?.usuario,
        descuento_tipo: "ninguno",
        descuento_valor: 0,
      });

      Alert.alert(
        "Producto agregado",
        resolution.requiere_seleccion
          ? `Se agregó usando la primera opción de almacén disponible: ${selectedWarehouse.alm_nombre}.`
          : `Asignado a ${selectedWarehouse.alm_nombre}.`,
      );
    } catch (error) {
      Alert.alert("No se pudo agregar", error instanceof Error ? error.message : "Error desconocido.");
    }
  };

  return (
    <Screen>
      <Card>
        <Text style={styles.title}>Buscar producto</Text>
        <TextInput
          style={styles.input}
          value={query}
          onChangeText={setQuery}
          placeholder="Nombre, SKU o código de barras"
          autoCapitalize="none"
        />
        {lastResolution?.requiere_seleccion ? (
          <Text style={styles.warning}>Pendiente UX: selector móvil dedicado cuando el backend devuelve múltiples almacenes.</Text>
        ) : null}
      </Card>
      <FlatList
        data={results}
        keyExtractor={(item) => String(item.psk_id)}
        renderItem={({ item }) => (
          <Pressable style={styles.result} onPress={() => void addProduct(item)}>
            <Text style={styles.resultTitle}>{item.psk_nombre || item.producto?.prd_nombre || item.psk_codigo}</Text>
            <Text style={styles.resultMeta}>
              {item.psk_codigo} | ${Number(item.psk_precio || 0).toFixed(2)}
            </Text>
          </Pressable>
        )}
        ListEmptyComponent={<Card><Text style={styles.note}>Escribe al menos 2 caracteres para consultar el catálogo.</Text></Card>}
        scrollEnabled={false}
      />
      <PrimaryButton label={`Ir al carrito (${draft.lines.length})`} onPress={() => navigation.navigate("Cart")} disabled={!draft.lines.length} />
    </Screen>
  );
}

const styles = StyleSheet.create({
  title: { fontSize: 22, fontWeight: "800", color: AppTheme.colors.text },
  input: {
    borderWidth: 1,
    borderColor: AppTheme.colors.border,
    borderRadius: AppTheme.radius.sm,
    backgroundColor: "#fff",
    paddingHorizontal: 12,
    paddingVertical: 12,
  },
  result: {
    backgroundColor: "#fff",
    borderWidth: 1,
    borderColor: AppTheme.colors.border,
    borderRadius: AppTheme.radius.sm,
    padding: 14,
    marginHorizontal: AppTheme.spacing.md,
    marginBottom: AppTheme.spacing.sm,
  },
  resultTitle: { color: AppTheme.colors.text, fontWeight: "700" },
  resultMeta: { color: AppTheme.colors.textMuted, marginTop: 4 },
  note: { color: AppTheme.colors.textMuted },
  warning: { color: AppTheme.colors.warning, lineHeight: 20 },
});
