export type OrderStatus = 'pending' | 'paid' | 'cancelled';

export interface Product {
  id: number;
  sku: string;
  barcode: string;
  name: string;
  detail: string;
  price: number;
  unit: string;
  unitCode: string;
  allowsDecimal: boolean;
  requiresWarehouseSelection: boolean;
  warehouseId: number | null;
  warehouses: Warehouse[];
  tone: 'sage' | 'sand' | 'clay' | 'slate';
}

export interface CartLine extends Product {
  cartKey: string;
  warehouseId: number;
  warehouseName: string;
  quantity: number;
  discountType: 'none' | 'percentage' | 'amount';
  discountValue: number;
  discountQuantity: number;
}

export interface Customer {
  id: number | null;
  name: string;
  detail: string;
  initials: string;
}

export interface Warehouse {
  id: number;
  name: string;
}

export interface Order {
  id: number;
  folio: string;
  customer: string;
  itemCount: number;
  total: number;
  time: string;
  status: OrderStatus;
  warehouse: string;
  createdAt: string;
}

export interface OrderLine {
  id: number;
  skuId: number;
  sku: string;
  barcode: string;
  name: string;
  quantity: number;
  price: number;
  subtotal: number;
  discount: number;
  discountType: CartLine['discountType'];
  discountValue: number;
  discountQuantity: number;
  total: number;
  unit: string;
  unitCode: string;
  allowsDecimal: boolean;
}

export interface OrderDetail extends Order {
  customerId: number | null;
  subtotal: number;
  notes: string;
  branchId: number;
  branch: string;
  warehouseId: number;
  seller: string;
  lines: OrderLine[];
}

export interface Branch {
  id: number;
  nombre: string;
  clave: string;
}

export interface AuthUser {
  id: number;
  usuario: string;
  nombre: string;
  sucursal_predeterminada_id: number | null;
  sucursales: Branch[];
  permisos: {
    ver_pedidos: boolean;
    crear_pedidos: boolean;
    cancelar_pedidos: boolean;
  };
}

export interface AuthSession {
  token: string;
  token_type: 'Bearer';
  usuario: AuthUser;
}

export interface UserSuggestion {
  usuario: string;
  nombre: string;
}

export type PrinterLanguage = 'escpos' | 'zpl' | 'cpcl';

export type PrinterPaperWidth = '58' | '80';

export interface BluetoothPrinterDevice {
  address: string;
  name: string;
  type: 'classic' | 'dual' | 'ble' | 'unknown';
}

export interface PrinterConfig extends BluetoothPrinterDevice {
  language: PrinterLanguage;
  paperWidth: PrinterPaperWidth;
}

export type Screen = 'login' | 'orders' | 'catalog' | 'cart' | 'ticket' | 'settings' | 'printer';
