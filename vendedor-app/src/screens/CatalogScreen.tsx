import { ChevronRight, CircleCheck, Clock3, LoaderCircle, MapPin, Plus, RefreshCw, ScanBarcode, ShoppingBag, UserRound } from 'lucide-react';
import { useEffect, useMemo, useRef, useState } from 'react';
import { AppHeader } from '../components/AppHeader';
import { BottomSheet } from '../components/BottomSheet';
import { Button } from '../components/Button';
import { CustomerSheet, PUBLIC_CUSTOMER } from '../components/CustomerSheet';
import { EmptyState, InlineNotice, SkeletonList } from '../components/Feedback';
import { QuantityStepper } from '../components/QuantityStepper';
import { SearchField } from '../components/SearchField';
import { ApiError, orderCatalogApi } from '../services/api';
import { barcodeScanner } from '../services/barcodeScanner';
import { countItems, formatMeters, formatQuantity, isMeterProduct, roundMoney, roundQuantity } from '../services/quantity';
import { useScrollMemory } from '../services/scrollMemory';
import type { CartLine, Customer, Product, Warehouse } from '../types';

const money = new Intl.NumberFormat('es-MX', { style: 'currency', currency: 'MXN' });

export { PUBLIC_CUSTOMER };

/** Últimos resultados: al volver del carrito la lista aparece al instante y en la misma posición. */
let resultCache: { key: string; products: Product[] } | null = null;

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
  query,
  onQuery,
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
  /** La búsqueda vive en App para conservarla al volver del carrito. */
  query: string;
  onQuery: (query: string) => void;
}) {
  const setQuery = onQuery;
  const normalizedQuery = query.trim();
  const searchKey = `${branchId}|${normalizedQuery}`;
  const [results, setResults] = useState<{ key: string; products: Product[] }>(() => (
    resultCache?.key === searchKey ? resultCache : { key: '', products: [] }
  ));
  const [searching, setSearching] = useState(false);
  const [searchError, setSearchError] = useState<string | null>(null);
  const [retry, setRetry] = useState(0);
  const [scanning, setScanning] = useState(false);
  const [scanMessage, setScanMessage] = useState<{ type: 'success' | 'warning'; text: string } | null>(null);
  const scannedQuery = useRef<string | null>(null);
  const [clientSheet, setClientSheet] = useState(false);
  const [warehouseProduct, setWarehouseProduct] = useState<Product | null>(null);
  const hasResults = results.key === searchKey;
  const products = hasResults ? results.products : [];
  useScrollMemory(`catalog:${searchKey}`, { ready: normalizedQuery.length < 2 || hasResults || !online });

  useEffect(() => {
    const normalized = query.trim();
    if (!online || normalized.length < 2) {
      setSearching(false);
      setSearchError(null);
      return;
    }

    const key = `${branchId}|${normalized}`;
    const controller = new AbortController();
    const timeout = window.setTimeout(async () => {
      setSearching(true);
      try {
        const found = await orderCatalogApi.searchProducts(normalized, branchId, controller.signal);
        resultCache = { key, products: found };
        setResults(resultCache);
        setSearchError(null);

        if (scannedQuery.current === normalized) {
          scannedQuery.current = null;
          const exactProduct = found.find((product) => product.barcode === normalized || product.sku === normalized)
            ?? (found.length === 1 ? found[0] : null);

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
  }, [branchId, online, query, retry]);

  const itemCount = countItems(cart);
  const pendingMeterLines = cart.filter((line) => isMeterProduct(line) && !(line.quantity > 0)).length;
  const total = cart.reduce((sum, line) => sum + roundMoney(line.quantity * line.price), 0);
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

  const showSkeleton = online && normalizedQuery.length >= 2 && !hasResults && (searching || !searchError);
  const showResults = online && normalizedQuery.length >= 2 && hasResults;

  return (
    <main className={`screen screen-enter ${cart.length ? 'screen--with-cart' : ''}`}>
      <AppHeader eyebrow={editingFolio ? `Editando ${editingFolio}` : 'Nuevo pedido'} title="Agregar productos" onBack={onBack} />
      <section className="screen-content catalog-content">
        <button className="customer-row" onClick={() => setClientSheet(true)} aria-label={`Cliente: ${customer.name}. Cambiar cliente`}>
          <UserRound size={18} aria-hidden="true" />
          <span>Cliente</span>
          <strong>{customer.name}</strong>
          <em>Cambiar</em>
        </button>
        {editingFolio && <div className="editing-context"><MapPin size={15} /><span>Solo productos de</span><strong>{fixedWarehouseName}</strong></div>}
        <div className="catalog-search-row">
          <SearchField value={query} onChange={changeQuery} placeholder="Producto, clave o código" autoFocus={online && query === '' && cart.length === 0} />
          <button className="scan-product-button" onClick={() => void scanProduct()} disabled={scanning || !online} aria-label="Escanear código de barras">
            {scanning ? <LoaderCircle className="spin" size={22} /> : <ScanBarcode size={23} />}
          </button>
        </div>

        {!online && <InlineNotice type="offline">Sin conexión. Puedes revisar el pedido; la búsqueda vuelve al reconectarte.</InlineNotice>}
        {scanMessage && <InlineNotice type={scanMessage.type}>{scanMessage.text}</InlineNotice>}

        {showSkeleton && <SkeletonList />}
        {online && !searching && searchError && !hasResults && (
          <div className="notice-with-action">
            <InlineNotice type="warning">{searchError}</InlineNotice>
            <Button variant="secondary" icon={<RefreshCw size={17} />} onClick={() => setRetry((value) => value + 1)}>Reintentar</Button>
          </div>
        )}
        {online && normalizedQuery.length < 2 && (
          <EmptyState title="Busca un producto" message="Escribe al menos dos letras del nombre o la clave, o escanea el código de barras." />
        )}
        {showResults && (
          <>
            {visibleProducts.length > 0 && <p className="results-label">{visibleProducts.length === 1 ? '1 producto' : `${visibleProducts.length} productos`}</p>}
            <div className="product-list">
              {visibleProducts.map((product) => {
                const productLines = linesByProduct[product.id] ?? [];
                const quantity = roundQuantity(productLines.reduce((sum, line) => sum + line.quantity, 0));
                const onlyLine = productLines.length === 1 ? productLines[0] : null;
                const meter = isMeterProduct(product);
                const targetWarehouseId = fixedWarehouseId ?? (product.warehouses.length === 1 ? product.warehouses[0].id : null);
                const meterLine = meter && targetWarehouseId !== null
                  ? productLines.find((line) => line.warehouseId === targetWarehouseId)
                  : undefined;
                const meterPending = productLines.some((line) => !(line.quantity > 0));
                const warehouseLabel = fixedWarehouseName
                  ?? (product.warehouses.length === 1 ? product.warehouses[0].name : `${product.warehouses.length} almacenes`);
                const showStepper = !meter && onlyLine !== null;
                return (
                  <article className={`product-row ${productLines.length ? 'product-row--in-cart' : ''}`} key={product.id}>
                    <h3>{product.name}</h3>
                    <p className="product-row__meta">
                      <span>{product.sku}</span>
                      <span><MapPin size={12} aria-hidden="true" />{warehouseLabel}</span>
                    </p>
                    {productLines.length > 0 && !showStepper && (
                      <p className={`in-cart-chip ${meterPending ? 'in-cart-chip--pending' : ''}`}>
                        {meterPending ? <Clock3 size={13} aria-hidden="true" /> : <CircleCheck size={13} aria-hidden="true" />}
                        {meter
                          ? (meterPending ? 'En el pedido · falta capturar metros' : `En el pedido · ${formatMeters(quantity)} m`)
                          : `En el pedido · ${formatQuantity(quantity)}`}
                      </p>
                    )}
                    <div className="product-row__footer">
                      <p className="product-row__price"><strong>{money.format(product.price)}</strong> / {meter ? 'm' : product.unit.toLowerCase()}</p>
                      {meter ? (
                        <button
                          className={`row-action ${meterLine ? 'row-action--secondary' : ''}`}
                          onClick={() => addProduct(product)}
                          aria-label={`${meterLine ? 'Editar metros en carrito' : 'Agregar y capturar metros'}: ${product.name}`}
                        >
                          {meterLine ? 'Editar metros en carrito' : 'Agregar y capturar metros'}
                        </button>
                      ) : showStepper ? (
                        <QuantityStepper value={onlyLine.quantity} step={product.allowsDecimal ? 0.01 : 1} onChange={(value) => onQuantity(onlyLine, value)} compact />
                      ) : (
                        <button className="row-action" onClick={() => addProduct(product)} aria-label={`Agregar ${product.name}`}>
                          <Plus size={17} aria-hidden="true" /> Agregar
                        </button>
                      )}
                    </div>
                  </article>
                );
              })}
            </div>
            {visibleProducts.length === 0 && (
              <EmptyState
                title="Sin resultados"
                message={editingFolio
                  ? `Ningún producto de ${fixedWarehouseName} coincide con «${normalizedQuery}».`
                  : `Nada coincide con «${normalizedQuery}» en esta sucursal. Prueba con otra palabra o la clave.`}
              />
            )}
          </>
        )}
      </section>

      {cart.length > 0 && (
        <button className="cart-dock" onClick={onCart}>
          <span className="cart-dock__count"><ShoppingBag size={18} aria-hidden="true" />{itemCount}</span>
          <span>Ver pedido{pendingMeterLines > 0 && <small>{pendingMeterLines === 1 ? '1 sin metros' : `${pendingMeterLines} sin metros`}</small>}</span>
          <strong>{money.format(total)}</strong>
        </button>
      )}

      <CustomerSheet open={clientSheet} customer={customer} online={online} onClose={() => setClientSheet(false)} onSelect={onCustomer} />

      <BottomSheet open={warehouseProduct !== null} onClose={() => setWarehouseProduct(null)} title="¿De qué almacén sale?" description={warehouseProduct?.name}>
        <div className="selection-list">
          {warehouseProduct?.warehouses.map((warehouse) => {
            const inCart = cart.some((line) => line.id === warehouseProduct.id && line.warehouseId === warehouse.id);
            return (
              <button key={warehouse.id} onClick={() => { onAdd(warehouseProduct, warehouse); setWarehouseProduct(null); }}>
                <span className="initials"><MapPin size={18} aria-hidden="true" /></span>
                <span><strong>{warehouse.name}</strong>{inCart && <small>Ya está en el pedido</small>}</span>
                <ChevronRight size={18} aria-hidden="true" />
              </button>
            );
          })}
        </div>
      </BottomSheet>
    </main>
  );
}
