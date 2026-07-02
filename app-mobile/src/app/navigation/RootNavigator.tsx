import { MaterialCommunityIcons } from "@expo/vector-icons";
import { createBottomTabNavigator } from "@react-navigation/bottom-tabs";
import { createNativeStackNavigator } from "@react-navigation/native-stack";
import { useAuth } from "@/features/auth/state/AuthContext";
import { LoginScreen } from "@/features/auth/screens/LoginScreen";
import { BranchContextScreen } from "@/features/operational-context/screens/BranchContextScreen";
import { HomeScreen } from "@/features/home/screens/HomeScreen";
import { ProductSearchScreen } from "@/features/floor-order/screens/ProductSearchScreen";
import { CartScreen } from "@/features/floor-order/screens/CartScreen";
import { ClientSelectionScreen } from "@/features/clients/screens/ClientSelectionScreen";
import { OrderConfirmationScreen } from "@/features/floor-order/screens/OrderConfirmationScreen";
import { TicketScreen } from "@/features/tickets/screens/TicketScreen";
import { OrderHistoryScreen } from "@/features/history/screens/OrderHistoryScreen";
import { ProfileScreen } from "@/features/auth/screens/ProfileScreen";
import type { MainTabParamList, RootStackParamList } from "@/app/navigation/types";
import { AppTheme } from "@/app/theme/theme";

const Stack = createNativeStackNavigator<RootStackParamList>();
const Tabs = createBottomTabNavigator<MainTabParamList>();

function MainTabs() {
  return (
    <Tabs.Navigator
      screenOptions={{
        headerShown: false,
        tabBarActiveTintColor: AppTheme.colors.primary,
        tabBarInactiveTintColor: AppTheme.colors.textMuted,
      }}
    >
      <Tabs.Screen
        name="Home"
        component={HomeScreen}
        options={{
          title: "Inicio",
          tabBarIcon: ({ color, size }) => (
            <MaterialCommunityIcons name="view-dashboard-outline" color={color} size={size} />
          ),
        }}
      />
      <Tabs.Screen
        name="History"
        component={OrderHistoryScreen}
        options={{
          title: "Historial",
          tabBarIcon: ({ color, size }) => (
            <MaterialCommunityIcons name="clipboard-text-clock-outline" color={color} size={size} />
          ),
        }}
      />
      <Tabs.Screen
        name="Profile"
        component={ProfileScreen}
        options={{
          title: "Perfil",
          tabBarIcon: ({ color, size }) => (
            <MaterialCommunityIcons name="account-circle-outline" color={color} size={size} />
          ),
        }}
      />
    </Tabs.Navigator>
  );
}

export function RootNavigator() {
  const { status } = useAuth();

  if (status !== "authenticated") {
    return <LoginScreen />;
  }

  return (
    <Stack.Navigator
      screenOptions={{
        headerShadowVisible: false,
        contentStyle: { backgroundColor: AppTheme.colors.background },
      }}
    >
      <Stack.Screen name="MainTabs" component={MainTabs} options={{ headerShown: false }} />
      <Stack.Screen name="BranchContext" component={BranchContextScreen} options={{ title: "Contexto operativo" }} />
      <Stack.Screen name="ProductSearch" component={ProductSearchScreen} options={{ title: "Buscar producto" }} />
      <Stack.Screen name="Cart" component={CartScreen} options={{ title: "Carrito por almacén" }} />
      <Stack.Screen name="ClientSelection" component={ClientSelectionScreen} options={{ title: "Seleccionar cliente" }} />
      <Stack.Screen name="OrderConfirmation" component={OrderConfirmationScreen} options={{ title: "Confirmar pedido" }} />
      <Stack.Screen name="Ticket" component={TicketScreen} options={{ title: "Ticket" }} />
    </Stack.Navigator>
  );
}
