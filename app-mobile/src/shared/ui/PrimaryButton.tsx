import { ActivityIndicator, Pressable, StyleSheet, Text } from "react-native";
import { AppTheme } from "@/app/theme/theme";

type Props = {
  label: string;
  onPress: () => void;
  disabled?: boolean;
  loading?: boolean;
};

export function PrimaryButton({ label, onPress, disabled, loading }: Props) {
  return (
    <Pressable style={[styles.button, disabled && styles.disabled]} onPress={onPress} disabled={disabled || loading}>
      {loading ? <ActivityIndicator color="#fff" /> : <Text style={styles.label}>{label}</Text>}
    </Pressable>
  );
}

const styles = StyleSheet.create({
  button: {
    backgroundColor: AppTheme.colors.primary,
    borderRadius: AppTheme.radius.sm,
    minHeight: 48,
    alignItems: "center",
    justifyContent: "center",
  },
  disabled: { opacity: 0.6 },
  label: { color: "#fff", fontWeight: "700", fontSize: 15 },
});
