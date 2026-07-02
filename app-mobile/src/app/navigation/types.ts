export type RootStackParamList = {
  MainTabs: undefined;
  BranchContext: undefined;
  ProductSearch: undefined;
  Cart: undefined;
  ClientSelection: undefined;
  OrderConfirmation: undefined;
  Ticket: { orderIds?: number[]; folios?: string[] } | undefined;
};

export type MainTabParamList = {
  Home: undefined;
  History: undefined;
  Profile: undefined;
};
