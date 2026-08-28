import { ChevronDown, LoaderCircle, MapPin, PackageSearch, ScanBarcode, ShoppingBag, UserRound } from 'lucide-react';
import { useEffect, useMemo, useRef, useState } from 'react';
import { AppHeader } from '../components/AppHeader';
import { BottomSheet } from '../components/BottomSheet';
import { EmptyState, InlineNotice, SkeletonList } from '../components/Feedback';
import { QuantityStepper } from '../components/QuantityStepper';
import { SearchField } from '../components/SearchField';
import { ApiError, orderCatalogApi } from '../services/api';
import { barcodeScanner } from '../services/barcodeScanner';
import type { CartLine, Customer, Product, Warehouse } from '../types';

const money = new Intl.NumberFormat('es-MX', { style: 'currency', currency: 'MXN' });

export const PUBLIC_CUSTOMER: Customer = {
  id: null,
  name: 'Público general',
  detail: 'Sin cliente registrado',
  initials: 'PG',
};

export function CatalogScreen({
  branchId,
  cart,
  customer,
  editingFolio,
  fixedWarehouseId,
  fixedWarehouseName,
  online,
  onBack,
  onCart,
  onCustomer,
  onAdd,
  onQuantity,
}: {
  branchId: number;
  cart: CartLine[];
  customer: Customer;
  editingFolio?: string;
  fixedWarehouseId?: number;
  fixedWarehouseName?: string;
  online: boolean;
  onBack: () => void;
  onCart: () => void;
  onCustomer: (customer: Customer) => void;
  onAdd: (product: Product, warehouse: Warehouse) => void;
  onQuantity: (line: CartLine, quantity: number) => void;
}) {
  const [query, setQuery] = useState('');
  const [products, setProducts] = useState<Product[]>([]);
  const [searching, setSearching] = useState(false);
  const [searchError, setSearchError] = useState<string | null>(null);
  const [scanning, setScanning] = useState(false);
  const [scanMessage, setScanMessage] = useState<{ type: 'success' | 'warning'; text: string } | null>(null);
  const scannedQuery = useRef<string | null>(null);
  const [clientSheet, setClientSheet] = useState(false);
  const [clientQuery, setClientQuery] = useState('');
  const [clients, setClients] = useState<Customer[]>([]);
  const [searchingClients, setSearchingClients] = useState(false);
  const [clientError, setClientError] = useState<string | null>(null);
  const [warehouseProduct, setWarehouseProduct] = useState<Product | null>(null);

  useEffect(() => {
    const normalized = query.trim();
    if (!online) {
      setProducts([]);
      setSearching(false);
      setSearchError(null);
      return;
    }
    if (normalized.length < 2) {
      setProducts([]);
      setSearching(false);
      setSearchError(null);
      return;
    }

    const controller = new AbortController();
    const timeout = window.setTimeout(async () => {
      setSearching(true);
      try {
        const results = await orderCatalogApi.searchProducts(normalized, branchId, controller.signal);
        setProducts(results);
        setSearchError(null);

        if (scannedQuery.current === normalized) {
          scannedQuery.current = null;
          const exactProduct = results.find((product) => product.barcode === normalized || product.sku === normalized)
            ?? (results.length === 1 ? results[0] : null);

          if (!exactProduct) {
            setScanMessage({ type: 'warning', text: `No encontramos un producto con el código ${normalized}.` });
          } else if (fixedWarehouseId) {
            const fixedWarehouse = exactProduct.warehouses.find((warehouse) => warehouse.id === fixedWarehouseId);
            if (!fixedWarehouse) {
              setScanMessage({ type: 'warning', text: `El producto no pertenece a ${fixedWarehouseName ?? 'este almacén'}.` });
              return;
            }
            onAdd(exactProduct, fixedWarehouse);
            setScanMessage({ type: 'success', text: `${exactProduct.name} agregado al pedido.` });
            setQuery('');
          } else if (exactProduct.warehouses.length === 1) {
            onAdd(exactProduct, exactProduct.warehouses[0]);
            setScanMessage({ type: 'success', text: `${exactProduct.name} agregado al pedido.` });
            setQuery('');
          } else {
            setWarehouseProduct(exactProduct);
            setScanMessage({ type: 'success', text: 'Código reconocido. Elige el almacén del producto.' });
            setQuery('');
          }
        }
      } catch (error) {
        if (!controller.signal.aborted) {
          scannedQuery.current = null;
          setProducts([]);
          setSearchError(error instanceof ApiError ? error.message : 'No pudimos buscar productos.');
        }
      } finally {
        if (!controller.signal.aborted) setSearching(false);
      }
    }, 260);

    return () => {
      window.clearTimeout(timeout);
      controller.abort();
    };
  }, [branchId, online, query]);

  useEffect(() => {
    if (!online || !clientSheet || clientQuery.trim().length < 2) {
      setClients([]);
      setSearchingClients(false);
      setClientError(null);
      return;
    }

    const controller = new AbortController();
    const timeout = window.setTimeout(async () => {
      setSearchingClients(true);
      try {
        setClients(await orderCatalogApi.searchClients(clientQuery.trim(), controller.signal));
        setClientError(null);
      } catch (error) {
        if (!controller.signal.aborted) {
          setClients([]);
          setClientError(error instanceof ApiError ? error.message : 'No pudimos buscar clientes.');
        }
      } finally {
        if (!controller.signal.aborted) setSearchingClients(false);
      }
    }, 260);

    return () => {
      window.clearTimeout(timeout);
      controller.abort();
    };
  }, [clientQuery, clientSheet, online]);

  const itemCount = cart.reduce((sum, line) => sum + line.quantity, 0);
  const total = cart.reduce((sum, line) => sum + line.quantity * line.price, 0);
  const linesByProduct = useMemo(() => cart.reduce<Record<number, CartLine[]>>((result, line) => {
    result[line.id] = [...(result[line.id] ?? []), line];
    return result;
  }, {}), [cart]);
  const visibleProducts = useMemo(() => fixedWarehouseId
    ? products.filter((product) => product.warehouses.some((warehouse) => warehouse.id === fixedWarehouseId))
    : products, [fixedWarehouseId, products]);

  const addProduct = (product: Product) => {
    if (fixedWarehouseId) {
      const warehouse = product.warehouses.find((item) => item.id === fixedWarehouseId);
      if (warehouse) onAdd(product, warehouse);
      return;
    }
    if (product.warehouses.length === 1) {
      onAdd(product, product.warehouses[0]);
      return;
    }
    setWarehouseProduct(product);
  };

  const changeQuery = (value: string) => {
    scannedQuery.current = null;
    setScanMessage(null);
    setQuery(value);
  };

  const scanProduct = async () => {
    if (!online) {
      setScanMessage({ type: 'warning', text: 'Necesitas conexión para consultar el producto escaneado.' });
      return;
    }
    setScanning(true);
    setScanMessage(null);

    try {
      const result = await barcodeScanner.scan();
      if (result.cancelled) return;

      const code = result.value?.trim();
      if (!code) {
        setScanMessage({ type: 'warning', text: 'No pudimos leer el código. Inténtalo nuevamente.' });
        return;
      }

      scannedQuery.current = code;
      setQuery(code);
    } catch {
      setScanMessage({
        type: 'warning',
        text: barcodeScanner.isAvailable
          ? 'El lector todavía no está disponible. Revisa tu conexión e inténtalo nuevamente.'
          : 'El lector de cámara está disponible en la aplicación Android.',
      });
    } finally {
      setScanning(false);
    }
  };

  const chooseCustomer = (selected: Customer) => {
    onCustomer(selected);
    setClientSheet(false);
    setClientQuery('');
  };

  return (
    <main className={`screen screen-enter ${itemCount ? 'screen--with-cart' : ''}`}>
      <AppHeader eyebrow={editingFolio ? `EDITANDO ${editingFolio}` : 'NUEVO PEDIDO'} title="Agregar productos" onBack={onBack} />
      <section className="screen-content catalog-content">
        <button className="customer-picker" onClick={() => setClientSheet(true)}>
          <div className="customer-picker__icon"><UserRound size={19} /></div>
          <div><span>Cliente</span><strong>{customer.name}</strong></div>
          <ChevronDown size={19} />
        </button>
        {editingFolio && <div className="editing-context"><MapPin size={16} /><span>Los productos deben pertenecer a</span><strong>{fixedWarehouseName}</strong></div>}
        <div className="catalog-search-row">
          <SearchField value={query} onChange={changeQuery} placeholder="Producto, clave o código" />
          <button className="scan-product-button" onClick={() => void scanProduct()} disabled={scanning || !online} aria-label="Escanear código de barras">
            {scanning ? <LoaderCircle className="spin" size={22} /> : <ScanBarcode size={23} />}
          </button>
        </div>

        {!online && <InlineNotice type="offline">Sin conexión. Puedes revisar lo agregado; la búsqueda volverá al reconectarte.</InlineNotice>}
        {scanMessage && <InlineNotice type={scanMessage.type}>{scanMessage.text}</InlineNotice>}

        {online && searching && <SkeletonList />}
        {online && !searching && searchError && <InlineNotice type="warning">{searchError}</InlineNotice>}
        {online && !searching && !searchError && query.trim().length < 2 && (
          <EmptyState title="Busca un producto" message="Escribe al menos dos caracteres o escanea su código de barras." />
        )}
        {online && !searching && !searchError && query.trim().length >= 2 && (
          <>
            <p className="results-label">{visibleProducts.length} productos disponibles</p>
            <div className="product-list">
              {visibleProducts.map((product) => {
                const productLines = linesByProduct[product.id] ?? [];
                const quantity = productLines.reduce((sum, line) => sum + line.quantity, 0);
                const onlyLine = productLines.length === 1 ? productLines[0] : null;
                return (
                  <article className={`product-row ${quantity ? 'product-row--selected' : ''}`} key={product.id}>
                    <div className={`product-thumb product-thumb--${product.tone}`}><span>{product.name.slice(0, 2).toUpperCase()}</span></div>
                    <div className="product-row__body">
                      <span className="product-row__sku">{product.sku}</span>
                      <h3>{product.name}</h3>
                      <p>{product.detail}</p>
                      <div className="product-row__warehouse"><MapPin size={13} /> {product.warehouses.length === 1 ? product.warehouses[0].name : `${product.warehouses.length} almacenes`}</div>
                      <div className="product-row__footer">
                        <div><strong>{money.format(product.price)}</strong><span> / {product.unit}</span></div>
                        {onlyLine ? (
                          <QuantityStepper value={onlyLine.quantity} step={product.allowsDecimal ? 0.01 : 1} onChange={(value) => onQuantity(onlyLine, value)} compact />
                        ) : (
                          <div className="add-product-wrap">
                            {quantity > 0 && <span>{quantity} en pedido</span>}
                            <button className="add-button" onClick={() => addProduct(product)} aria-label={`Agregar ${product.name}`}>+</button>
                          </div>
                        )}
                      </div>
                    </div>
                  </article>
                );
              })}
            </div>
            {visibleProducts.length === 0 && <EmptyState title="Sin resultados disponibles" message={editingFolio ? `El producto no está disponible en ${fixedWarehouseName}.` : 'El producto puede no estar asignado a un almacén de esta sucursal.'} />}
          </>
        )}
      </section>

      {itemCount > 0 && (
        <button className="cart-dock" onClick={onCart}>
          <span className="cart-dock__count"><ShoppingBag size={18} />{itemCount}</span>
          <span>Ver pedido</span>
          <strong>{money.format(total)}</strong>
        </button>
      )}

      <BottomSheet open={clientSheet} onClose={() => setClientSheet(false)} title="Elegir cliente" description="El cliente es opcional para generar el pedido.">
        <SearchField value={clientQuery} onChange={setClientQuery} placeholder="Nombre, teléfono o RFC" autoFocus />
        <div className="selection-list selection-list--scroll">
          <button className={customer.id === null ? 'selected' : ''} onClick={() => chooseCustomer(PUBLIC_CUSTOMER)}>
            <span className="initials">PG</span>
            <span><strong>Público general</strong><small>Sin cliente registrado</small></span>
            <i />
          </button>
          {searchingClients && <SkeletonList />}
          {!searchingClients && clients.map((item) => (
            <button key={item.id} className={item.id === customer.id ? 'selected' : ''} onClick={() => chooseCustomer(item)}>
              <span className="initials">{item.initials}</span>
              <span><strong>{item.name}</strong><small>{item.detail}</small></span>
              <i />
            </button>
          ))}
        </div>
        {!online && <InlineNotice type="offline">Sin conexión. Puedes usar Público general; la búsqueda de clientes está pausada.</InlineNotice>}
        {clientError && <InlineNotice type="warning">{clientError}</InlineNotice>}
        {clientQuery.trim().length >= 2 && !searchingClients && !clientError && clients.length === 0 && (
          <p className="sheet-empty-copy">No encontramos clientes con esa búsqueda.</p>
        )}
      </BottomSheet>

      <BottomSheet open={warehouseProduct !== null} onClose={() => setWarehouseProduct(null)} title="Elegir almacén" description="Selecciona de dónde saldrá este producto.">
        <div className="selection-list">
          {warehouseProduct?.warehouses.map((warehouse) => (
            <button key={warehouse.id} onClick={() => { onAdd(warehouseProduct, warehouse); setWarehouseProduct(null); }}>
              <span className="initials"><PackageSearch size={19} /></span>
              <span><strong>{warehouse.name}</strong><small>{warehouseProduct.name}</small></span>
              <i />
            </button>
          ))}
        </div>
      </BottomSheet>
    </main>
  );
}
