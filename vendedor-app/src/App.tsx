import { App as CapacitorApp } from '@capacitor/app';
import { Capacitor } from '@capacitor/core';
import { RefreshCw } from 'lucide-react';
import { useCallback, useEffect, useRef, useState } from 'react';
import { Button } from './components/Button';
import { CatalogScreen, PUBLIC_CUSTOMER } from './screens/CatalogScreen';
import { CartScreen } from './screens/CartScreen';
import { LoginScreen } from './screens/LoginScreen';
import { OrdersScreen } from './screens/OrdersScreen';
import { PrinterSettingsScreen } from './screens/PrinterSettingsScreen';
import { SettingsScreen } from './screens/SettingsScreen';
import { TicketScreen } from './screens/TicketScreen';
import { ApiError, authApi, floorOrderApi, SESSION_EXPIRED_EVENT } from './services/api';
import { persistActiveBranchId, resolveActiveBranchId } from './services/branchStorage';
import { clearOrderDraft, getOrderDraft, saveOrderDraft, type OrderDraft } from './services/orderDraftStorage';
import { initialNetworkStatus, observeNetworkStatus } from './services/networkStatus';
import { clearAuthToken, clearCachedAuthUser, getAuthToken, getCachedAuthUser, setCachedAuthUser } from './services/sessionStorage';
import { getPrinterConfig, setPrinterConfig } from './services/printerStorage';
import type { AuthSession, AuthUser, CartLine, Customer, Order, OrderDetail, PrinterConfig, Product, Screen, Warehouse } from './types';

export default function App() {
  const [screen, setScreen] = useState<Screen>('orders');
  const [authUser, setAuthUser] = useState<AuthUser | null>(null);
  const [activeBranchId, setActiveBranchId] = useState<number | null>(null);
  const [booting, setBooting] = useState(true);
  const [bootError, setBootError] = useState<string | null>(null);
  const [loginMessage, setLoginMessage] = useState<string | null>(null);
  const [orders, setOrders] = useState<Order[]>([]);
  const [ordersLoading, setOrdersLoading] = useState(false);
  const [ordersError, setOrdersError] = useState<string | null>(null);
  const [openingOrderId, setOpeningOrderId] = useState<number | null>(null);
  const [cart, setCart] = useState<CartLine[]>([]);
  const [customer, setCustomer] = useState<Customer>(PUBLIC_CUSTOMER);
  const [generatedOrders, setGeneratedOrders] = useState<OrderDetail[]>([]);
  const [editingOrder, setEditingOrder] = useState<OrderDetail | null>(null);
  const [ticketMode, setTicketMode] = useState<'generated' | 'detail' | 'updated'>('detail');
  const [draftNotes, setDraftNotes] = useState('');
  const [draftRequestId, setDraftRequestId] = useState<string>(() => crypto.randomUUID());
  const [draftActive, setDraftActive] = useState(false);
  const [savedDraft, setSavedDraft] = useState<OrderDraft | null>(null);
  const [networkStatus, setNetworkStatus] = useState(initialNetworkStatus);
  const [serverReachable, setServerReachable] = useState<boolean | null>(null);
  const [printerConfig, setActivePrinterConfig] = useState<PrinterConfig | null>(() => getPrinterConfig());
  const [printerReturnScreen, setPrinterReturnScreen] = useState<'settings' | 'ticket'>('settings');
  const draftEpoch = useRef(0);

  useEffect(() => {
    let stop: (() => Promise<void>) | undefined;
    let cancelled = false;

    void observeNetworkStatus((status) => {
      if (!cancelled) setNetworkStatus(status);
    }).then((removeListener) => {
      if (cancelled) void removeListener();
      else stop = removeListener;
    }).catch(() => undefined);

    return () => {
      cancelled = true;
      if (stop) void stop();
    };
  }, []);

  useEffect(() => {
    const sessionExpired = () => {
      draftEpoch.current += 1;
      setDraftActive(false);
      setSavedDraft(null);
      setAuthUser(null);
      setActiveBranchId(null);
      setOrders([]);
      setCart([]);
      setCustomer(PUBLIC_CUSTOMER);
      setGeneratedOrders([]);
      setEditingOrder(null);
      setScreen('orders');
      setLoginMessage('Tu sesión terminó. Vuelve a entrar para continuar.');
      void Promise.all([clearAuthToken(), clearCachedAuthUser()]);
    };
    window.addEventListener(SESSION_EXPIRED_EVENT, sessionExpired);
    return () => window.removeEventListener(SESSION_EXPIRED_EVENT, sessionExpired);
  }, []);

  const restoreSession = useCallback(async () => {
    setBooting(true);
    setBootError(null);
    const [token, cachedUser] = await Promise.all([getAuthToken(), getCachedAuthUser()]);

    if (!token) {
      setBooting(false);
      return;
    }

    try {
      const user = await authApi.session(token);
      setAuthUser(user);
      setActiveBranchId(resolveActiveBranchId(user));
      await setCachedAuthUser(user);
      setServerReachable(true);
    } catch (error) {
      if (error instanceof ApiError && error.status === 401) {
        await Promise.all([clearAuthToken(), clearCachedAuthUser()]);
        setLoginMessage('Tu sesión terminó. Vuelve a entrar para continuar.');
      } else if (cachedUser) {
        setAuthUser(cachedUser);
        setActiveBranchId(resolveActiveBranchId(cachedUser));
        setServerReachable(false);
      } else {
        setBootError('No pudimos comprobar tu sesión. Revisa la conexión.');
      }
    } finally {
      setBooting(false);
    }
  }, []);

  useEffect(() => {
    void restoreSession();
  }, [restoreSession]);

  const loadOrders = useCallback(async (background = false) => {
    if (!activeBranchId) return;
    if (!networkStatus.connected) {
      setOrdersLoading(false);
      setOrdersError(null);
      setServerReachable(false);
      return;
    }
    if (!background) setOrdersLoading(true);
    setOrdersError(null);
    try {
      setOrders(await floorOrderApi.list('all', '', activeBranchId));
      setServerReachable(true);
    } catch (error) {
      setOrdersError(error instanceof ApiError ? error.message : 'No pudimos cargar los pedidos.');
      if (error instanceof ApiError && error.status === 0) setServerReachable(false);
    } finally {
      if (!background) setOrdersLoading(false);
    }
  }, [activeBranchId, networkStatus.connected]);

  useEffect(() => {
    if (authUser && activeBranchId) void loadOrders();
  }, [activeBranchId, authUser, loadOrders]);

  useEffect(() => {
    if (screen !== 'orders' || !authUser || !activeBranchId || !networkStatus.connected) return;

    const refreshInBackground = () => void loadOrders(true);
    const interval = window.setInterval(refreshInBackground, 30_000);
    const refreshWhenVisible = () => {
      if (document.visibilityState === 'visible') refreshInBackground();
    };
    document.addEventListener('visibilitychange', refreshWhenVisible);

    return () => {
      window.clearInterval(interval);
      document.removeEventListener('visibilitychange', refreshWhenVisible);
    };
  }, [activeBranchId, authUser, loadOrders, networkStatus.connected, screen]);

  useEffect(() => {
    if (!Capacitor.isNativePlatform()) return;

    let cancelled = false;
    let removeListener: (() => Promise<void>) | undefined;
    void CapacitorApp.addListener('backButton', () => {
      if (screen === 'settings') {
        setScreen('orders');
        return;
      }
      if (screen === 'printer') {
        setScreen(printerReturnScreen);
        return;
      }
      if (screen === 'cart') {
        setScreen('catalog');
        return;
      }
      if (screen === 'catalog') {
        setScreen(editingOrder ? 'ticket' : 'orders');
        return;
      }
      if (screen === 'ticket') {
        setEditingOrder(null);
        setScreen('orders');
        void loadOrders();
        return;
      }

      void CapacitorApp.minimizeApp();
    }).then((listener) => {
      if (cancelled) void listener.remove();
      else removeListener = () => listener.remove();
    }).catch(() => undefined);

    return () => {
      cancelled = true;
      if (removeListener) void removeListener();
    };
  }, [editingOrder, loadOrders, printerReturnScreen, screen]);

  useEffect(() => {
    if (!authUser || !activeBranchId) {
      setSavedDraft(null);
      return;
    }

    let cancelled = false;
    void getOrderDraft(authUser.id, activeBranchId).then((draft) => {
      if (!cancelled) setSavedDraft(draft);
    });
    return () => { cancelled = true; };
  }, [activeBranchId, authUser]);

  useEffect(() => {
    if (!authUser || !activeBranchId || !draftActive) return;
    const epoch = draftEpoch.current;
    const hasContent = cart.length > 0 || customer.id !== null || draftNotes.trim() !== '' || editingOrder !== null;
    const timeout = window.setTimeout(() => {
      if (epoch !== draftEpoch.current) return;
      if (!hasContent) {
        void clearOrderDraft(authUser.id, activeBranchId).then(() => setSavedDraft(null));
        return;
      }

      const draft: OrderDraft = {
        version: 1,
        userId: authUser.id,
        branchId: activeBranchId,
        cart,
        customer,
        notes: draftNotes,
        requestId: draftRequestId,
        editingOrder,
        updatedAt: new Date().toISOString(),
      };
      void saveOrderDraft(draft).then(() => {
        if (epoch === draftEpoch.current) setSavedDraft(draft);
      });
    }, 350);

    return () => window.clearTimeout(timeout);
  }, [activeBranchId, authUser, cart, customer, draftActive, draftNotes, draftRequestId, editingOrder]);

  const setQuantity = (line: CartLine, quantity: number) => {
    setCart((current) => {
      if (quantity <= 0) return current.filter((item) => item.cartKey !== line.cartKey);
      return current.map((item) => item.cartKey === line.cartKey ? {
        ...item,
        quantity,
        discountQuantity: item.discountType === 'none' ? 0 : quantity,
        discountValue: item.discountType === 'amount'
          ? Math.min(item.discountValue, Math.round(item.price * quantity * 100) / 100)
          : item.discountValue,
      } : item);
    });
  };

  const addProduct = (product: Product, warehouse: Warehouse) => {
    const cartKey = `${product.id}:${warehouse.id}`;
    setCart((current) => {
      const existing = current.find((line) => line.id === product.id && line.warehouseId === warehouse.id && line.discountType === 'none');
      if (existing) {
        return current.map((line) => line.cartKey === existing.cartKey
          ? { ...line, quantity: Math.round((line.quantity + (product.allowsDecimal ? 0.01 : 1)) * 100) / 100 }
          : line);
      }

      return [...current, {
        ...product,
        cartKey,
        warehouseId: warehouse.id,
        warehouseName: warehouse.name,
        quantity: product.allowsDecimal ? 0.01 : 1,
        discountType: 'none',
        discountValue: 0,
        discountQuantity: 0,
      }];
    });
  };

  const setLineDiscount = (
    line: CartLine,
    discountType: CartLine['discountType'],
    discountValue: number,
    discountQuantity: number,
  ) => {
    setCart((current) => current.flatMap((item) => {
      if (item.cartKey !== line.cartKey) return [item];
      if (discountType === 'none') {
        return [{ ...item, discountType: 'none', discountValue: 0, discountQuantity: 0 }];
      }

      const appliedQuantity = Math.min(item.quantity, Math.max(item.allowsDecimal ? 0.01 : 1, discountQuantity));
      const discounted: CartLine = {
        ...item,
        cartKey: `${item.id}:${item.warehouseId}:discount:${crypto.randomUUID()}`,
        quantity: Math.round(appliedQuantity * 100) / 100,
        discountType,
        discountValue,
        discountQuantity: Math.round(appliedQuantity * 100) / 100,
      };
      const remaining = Math.round((item.quantity - appliedQuantity) * 100) / 100;

      if (remaining <= 0) return [discounted];
      return [{
        ...item,
        quantity: remaining,
        discountType: 'none',
        discountValue: 0,
        discountQuantity: 0,
      }, discounted];
    }));
  };

  const cancelOrder = async (orderId: number) => {
    await floorOrderApi.cancel(orderId);
    if (authUser && activeBranchId) {
      draftEpoch.current += 1;
      setDraftActive(false);
      setSavedDraft(null);
      await clearOrderDraft(authUser.id, activeBranchId);
    }
    setGeneratedOrders([]);
    setEditingOrder(null);
    setScreen('orders');
    await loadOrders();
  };

  const resumeDraft = (draft: OrderDraft) => {
    draftEpoch.current += 1;
    setCart(draft.cart);
    setCustomer(draft.customer);
    setDraftNotes(draft.notes);
    setDraftRequestId(draft.requestId);
    setEditingOrder(draft.editingOrder);
    setGeneratedOrders(draft.editingOrder ? [draft.editingOrder] : []);
    setDraftActive(true);
    setScreen('catalog');
  };

  const startOrder = () => {
    if (savedDraft) {
      resumeDraft(savedDraft);
      return;
    }
    draftEpoch.current += 1;
    setCart([]);
    setCustomer(PUBLIC_CUSTOMER);
    setDraftNotes('');
    setDraftRequestId(crypto.randomUUID());
    setEditingOrder(null);
    setDraftActive(true);
    setScreen('catalog');
  };

  const discardDraft = async () => {
    if (!authUser || !activeBranchId) return;
    draftEpoch.current += 1;
    setDraftActive(false);
    setSavedDraft(null);
    setCart([]);
    setCustomer(PUBLIC_CUSTOMER);
    setDraftNotes('');
    setDraftRequestId(crypto.randomUUID());
    setEditingOrder(null);
    setGeneratedOrders([]);
    await clearOrderDraft(authUser.id, activeBranchId);
  };

  const submitOrder = async (notes: string, requestId: string) => {
    const activeBranch = authUser?.sucursales.find((branch) => branch.id === activeBranchId);
    const userId = authUser?.id;
    if (!activeBranch || !userId) throw new ApiError(422, 'Tu usuario no tiene una sucursal asignada.');

    if (editingOrder) {
      const updated = await floorOrderApi.update(editingOrder.id, editingOrder.branchId, customer.id, notes, cart);
      draftEpoch.current += 1;
      setDraftActive(false);
      setSavedDraft(null);
      await clearOrderDraft(userId, activeBranch.id);
      setGeneratedOrders([updated]);
      setOrders((current) => [updated, ...current.filter((order) => order.id !== updated.id)]);
      setEditingOrder(null);
      setTicketMode('updated');
      setScreen('ticket');
      return;
    }

    const created = await floorOrderApi.create(requestId, activeBranch.id, customer.id, notes, cart);
    draftEpoch.current += 1;
    setDraftActive(false);
    setSavedDraft(null);
    await clearOrderDraft(userId, activeBranch.id);
    setGeneratedOrders(created);
    setOrders((current) => [
      ...created,
      ...current.filter((order) => !created.some((newOrder) => newOrder.id === order.id)),
    ]);
    setTicketMode('generated');
    setScreen('ticket');
  };

  const openOrder = async (order: Order) => {
    setOpeningOrderId(order.id);
    setOrdersError(null);
    try {
      const detail = await floorOrderApi.show(order.id);
      setGeneratedOrders([detail]);
      setCustomer(detail.customer === PUBLIC_CUSTOMER.name ? PUBLIC_CUSTOMER : {
        id: detail.customerId,
        name: detail.customer,
        detail: 'Cliente del pedido',
        initials: detail.customer.slice(0, 2).toUpperCase(),
      });
      setEditingOrder(null);
      setTicketMode('detail');
      setScreen('ticket');
    } catch (error) {
      setOrdersError(error instanceof ApiError ? error.message : 'No pudimos abrir el pedido.');
    } finally {
      setOpeningOrderId(null);
    }
  };

  const editOrder = (order: OrderDetail) => {
    const toneBySku: Product['tone'][] = ['sage', 'sand', 'clay', 'slate'];
    draftEpoch.current += 1;
    setEditingOrder(order);
    setDraftNotes(order.notes ?? '');
    setDraftRequestId(crypto.randomUUID());
    setDraftActive(true);
    setCustomer(order.customerId === null ? PUBLIC_CUSTOMER : {
      id: order.customerId,
      name: order.customer,
      detail: 'Cliente del pedido',
      initials: order.customer.slice(0, 2).toUpperCase(),
    });
    setCart(order.lines.map((line) => ({
      id: line.skuId,
      sku: line.sku,
      barcode: line.barcode,
      name: line.name,
      detail: '',
      price: line.price,
      unit: line.unit || line.unitCode || 'unidad',
      unitCode: line.unitCode,
      allowsDecimal: line.allowsDecimal,
      requiresWarehouseSelection: false,
      warehouseId: order.warehouseId,
      warehouses: [{ id: order.warehouseId, name: order.warehouse }],
      tone: toneBySku[line.skuId % toneBySku.length],
      cartKey: `${line.skuId}:${order.warehouseId}:existing:${line.id}`,
      warehouseName: order.warehouse,
      quantity: line.quantity,
      discountType: line.discountType,
      discountValue: line.discountValue,
      discountQuantity: line.discountQuantity,
    })));
    setScreen('catalog');
  };

  const authenticated = (session: AuthSession) => {
    setAuthUser(session.usuario);
    setActiveBranchId(resolveActiveBranchId(session.usuario));
    setServerReachable(true);
    setLoginMessage(null);
    void setCachedAuthUser(session.usuario);
    setScreen('orders');
  };

  const selectBranch = (branchId: number) => {
    if (!authUser || !authUser.sucursales.some((branch) => branch.id === branchId)) return;
    persistActiveBranchId(authUser.id, branchId);
    setActiveBranchId(branchId);
    setOrders([]);
    draftEpoch.current += 1;
    setDraftActive(false);
    setSavedDraft(null);
    setCart([]);
    setCustomer(PUBLIC_CUSTOMER);
    setDraftNotes('');
    setDraftRequestId(crypto.randomUUID());
    setGeneratedOrders([]);
    setEditingOrder(null);
  };

  const logout = async () => {
    const token = await getAuthToken();
    try {
      if (token) await authApi.logout(token);
    } finally {
      await Promise.all([clearAuthToken(), clearCachedAuthUser()]);
      draftEpoch.current += 1;
      setDraftActive(false);
      setSavedDraft(null);
      setAuthUser(null);
      setActiveBranchId(null);
      setOrders([]);
      setCart([]);
      setDraftNotes('');
      setGeneratedOrders([]);
      setEditingOrder(null);
      setScreen('orders');
      setLoginMessage(null);
    }
  };

  const updatePrinter = (config: PrinterConfig) => {
    setPrinterConfig(config);
    setActivePrinterConfig(config);
  };

  const openPrinterSettings = (returnScreen: 'settings' | 'ticket') => {
    setPrinterReturnScreen(returnScreen);
    setScreen('printer');
  };

  if (booting || bootError) {
    return (
      <main className="session-boot">
        <div className="brand-mark brand-mark--large">S</div>
        {booting ? (
          <><span className="session-boot__loader" /><p>Preparando tu jornada…</p></>
        ) : (
          <div className="session-boot__error">
            <h1>Sin conexión</h1>
            <p>{bootError}</p>
            <Button onClick={() => void restoreSession()} icon={<RefreshCw size={19} />}>Reintentar</Button>
          </div>
        )}
      </main>
    );
  }

  if (!authUser) return <LoginScreen message={loginMessage} onAuthenticated={authenticated} />;

  const activeBranch = authUser.sucursales.find((branch) => branch.id === activeBranchId)
    ?? authUser.sucursales[0];
  const branchName = activeBranch?.nombre ?? 'Sin sucursal';

  if (screen === 'printer') {
    return <PrinterSettingsScreen config={printerConfig} onChange={updatePrinter} onBack={() => setScreen(printerReturnScreen)} />;
  }

  if (screen === 'settings') {
    return <SettingsScreen user={authUser} activeBranchId={activeBranch?.id ?? 0} branchName={branchName} printerConfig={printerConfig} onBranch={selectBranch} onPrinter={() => openPrinterSettings('settings')} onBack={() => setScreen('orders')} onLogout={logout} />;
  }
  if (screen === 'catalog') {
    return (
      <CatalogScreen
        cart={cart}
        branchId={activeBranch?.id ?? 0}
        customer={customer}
        editingFolio={editingOrder?.folio}
        fixedWarehouseId={editingOrder?.warehouseId}
        fixedWarehouseName={editingOrder?.warehouse}
        onBack={() => setScreen(editingOrder ? 'ticket' : 'orders')}
        onCart={() => setScreen('cart')}
        onCustomer={setCustomer}
        onAdd={addProduct}
        onQuantity={setQuantity}
        online={networkStatus.connected}
      />
    );
  }
  if (screen === 'cart') {
    return <CartScreen cart={cart} customer={customer} editingFolio={editingOrder?.folio} notes={draftNotes} requestId={draftRequestId} online={networkStatus.connected} onNotes={setDraftNotes} onBack={() => setScreen('catalog')} onQuantity={setQuantity} onDiscount={setLineDiscount} onSubmit={submitOrder} />;
  }
  if (screen === 'ticket' && generatedOrders.length > 0) {
    return <TicketScreen orders={generatedOrders} mode={ticketMode} canEdit={authUser.permisos.crear_pedidos} canCancel={authUser.permisos.cancelar_pedidos} printerConfig={printerConfig} onEdit={editOrder} onCancel={cancelOrder} onConfigurePrinter={() => openPrinterSettings('ticket')} onDone={() => { setEditingOrder(null); setScreen('orders'); void loadOrders(); }} />;
  }
  return (
    <OrdersScreen
      orders={orders}
      loading={ordersLoading}
      error={ordersError}
      openingOrderId={openingOrderId}
      branchName={branchName}
      draft={savedDraft}
      connectionState={!networkStatus.connected ? 'offline' : serverReachable === false ? 'server-unavailable' : ordersLoading ? 'syncing' : 'synced'}
      onNewOrder={startOrder}
      onResumeDraft={() => { if (savedDraft) resumeDraft(savedDraft); }}
      onDiscardDraft={() => void discardDraft()}
      onProfile={() => setScreen('settings')}
      onRetry={() => void loadOrders()}
      onOpenOrder={(order) => void openOrder(order)}
    />
  );
}
