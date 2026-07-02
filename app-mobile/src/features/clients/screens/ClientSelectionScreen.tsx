import { useEffect, useState } from "react";
import { FlatList, Pressable, StyleSheet, Text, TextInput } from "react-native";
import { NativeStackScreenProps } from "@react-navigation/native-stack";
import type { RootStackParamList } from "@/app/navigation/types";
import { searchClients } from "@/features/clients/api/clientsApi";
import { useFloorOrder } from "@/features/floor-order/state/FloorOrderContext";
import type { Client } from "@/shared/types/clients";
import { Card } from "@/shared/ui/Card";
import { PrimaryButton } from "@/shared/ui/PrimaryButton";
import { Screen } from "@/shared/ui/Screen";
import { AppTheme } from "@/app/theme/theme";

type Props = NativeStackScreenProps<RootStackParamList, "ClientSelection">;

export function ClientSelectionScreen({ navigation }: Props) {
  const { draft, setClient } = useFloorOrder();
  const [query, setQuery] = useState("");
  const [results, setResults] = useState<Client[]>([]);

  useEffect(() => {
    const q = query.trim();
    if (q.length < 2) {
      setResults([]);
      return;
    }
    const timer = setTimeout(() => {
      void searchClients(q).then(setResults).catch(() => setResults([]));
    }, 220);
    return () => clearTimeout(timer);
  }, [query]);

  return (
    <Screen>
      <Card>
        <Text style={styles.title}>Cliente opcional</Text>
        <Text style={styles.note}>Se reutiliza `/pos/clientes/buscar`. Alta y edición de clientes quedan pendientes para fase siguiente.</Text>
        <TextInput style={styles.input} value={query} onChangeText={setQuery} placeholder="Nombre, RFC, teléfono o email" />
        {draft.client ? <Text style={styles.current}>Cliente actual: {draft.client.nombre}</Text> : null}
        <PrimaryButton label="Continuar sin cliente" onPress={() => {
          setClient(null);
          navigation.goBack();
        }} />
      </Card>
      <FlatList
        data={results}
        keyExtractor={(item) => String(item.cli_id)}
        renderItem={({ item }) => (
          <Pressable style={styles.client} onPress={() => {
            setClient(item);
            navigation.goBack();
          }}>
            <Text style={styles.clientName}>{item.nombre}</Text>
            <Text style={styles.clientMeta}>{item.rfc || item.telefono || "Sin dato adicional"}</Text>
          </Pressable>
        )}
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
  current: { color: AppTheme.colors.primary, fontWeight: "700" },
  client: {
    backgroundColor: "#fff",
    borderWidth: 1,
    borderColor: AppTheme.colors.border,
    borderRadius: AppTheme.radius.sm,
    padding: 14,
    marginHorizontal: AppTheme.spacing.md,
    marginBottom: AppTheme.spacing.sm,
  },
  clientName: { color: AppTheme.colors.text, fontWeight: "700" },
  clientMeta: { color: AppTheme.colors.textMuted, marginTop: 4 },
});
