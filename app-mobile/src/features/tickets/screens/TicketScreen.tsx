import * as Linking from "expo-linking";
import { StyleSheet, Text } from "react-native";
import { RouteProp, useRoute } from "@react-navigation/native";
import type { RootStackParamList } from "@/app/navigation/types";
import { getTicketUrl } from "@/features/floor-order/api/floorOrderApi";
import { Card } from "@/shared/ui/Card";
import { PrimaryButton } from "@/shared/ui/PrimaryButton";
import { Screen } from "@/shared/ui/Screen";
import { AppTheme } from "@/app/theme/theme";

export function TicketScreen() {
  const route = useRoute<RouteProp<RootStackParamList, "Ticket">>();
  const orderIds = route.params?.orderIds || [];
  const folios = route.params?.folios || [];

  return (
    <Screen>
      <Card>
        <Text style={styles.title}>Pedido(s) generado(s)</Text>
        <Text style={styles.note}>Cada folio mantiene el flujo crítico para cobro posterior en POS.</Text>
      </Card>
      {folios.map((folio, index) => (
        <Card key={`${folio}-${index}`}>
          <Text style={styles.folio}>{folio}</Text>
          <Text style={styles.note}>Ticket PDF reutilizado desde Laravel.</Text>
          <PrimaryButton
            label="Abrir ticket PDF"
            onPress={() => {
              const orderId = orderIds[index];
              if (orderId) {
                void Linking.openURL(getTicketUrl(orderId));
              }
            }}
            disabled={!orderIds[index]}
          />
        </Card>
      ))}
    </Screen>
  );
}

const styles = StyleSheet.create({
  title: { fontSize: 22, fontWeight: "800", color: AppTheme.colors.text },
  folio: { fontSize: 18, fontWeight: "800", color: AppTheme.colors.primary },
  note: { color: AppTheme.colors.textMuted, lineHeight: 20 },
});
