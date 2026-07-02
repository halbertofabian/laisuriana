import { PropsWithChildren } from "react";
import { StyleSheet, View } from "react-native";
import { AppTheme } from "@/app/theme/theme";

export function Card({ children }: PropsWithChildren) {
  return <View style={styles.card}>{children}</View>;
}

const styles = StyleSheet.create({
  card: {
    backgroundColor: AppTheme.colors.surface,
    borderRadius: AppTheme.radius.md,
    borderWidth: 1,
    borderColor: AppTheme.colors.border,
    padding: AppTheme.spacing.md,
    gap: AppTheme.spacing.sm,
  },
});
