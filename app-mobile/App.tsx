import { StatusBar } from "expo-status-bar";
import { AppProviders } from "@/app/providers/AppProviders";
import { RootNavigator } from "@/app/navigation/RootNavigator";

export default function App() {
  return (
    <AppProviders>
      <StatusBar style="dark" />
      <RootNavigator />
    </AppProviders>
  );
}
