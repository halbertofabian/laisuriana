import { PropsWithChildren } from "react";
import { NavigationContainer, DefaultTheme } from "@react-navigation/native";
import { GestureHandlerRootView } from "react-native-gesture-handler";
import { SafeAreaProvider } from "react-native-safe-area-context";
import { AuthProvider } from "@/features/auth/state/AuthContext";
import { FloorOrderProvider } from "@/features/floor-order/state/FloorOrderContext";
import { OperationalContextProvider } from "@/features/operational-context/state/OperationalContext";
import { AppTheme } from "@/app/theme/theme";

const navigationTheme = {
  ...DefaultTheme,
  colors: {
    ...DefaultTheme.colors,
    background: AppTheme.colors.background,
    card: AppTheme.colors.surface,
    text: AppTheme.colors.text,
    border: AppTheme.colors.border,
    primary: AppTheme.colors.primary,
  },
};

export function AppProviders({ children }: PropsWithChildren) {
  return (
    <GestureHandlerRootView style={{ flex: 1 }}>
      <SafeAreaProvider>
        <NavigationContainer theme={navigationTheme}>
          <AuthProvider>
            <OperationalContextProvider>
              <FloorOrderProvider>{children}</FloorOrderProvider>
            </OperationalContextProvider>
          </AuthProvider>
        </NavigationContainer>
      </SafeAreaProvider>
    </GestureHandlerRootView>
  );
}
