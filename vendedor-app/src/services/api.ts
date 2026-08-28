import type { AuthSession, AuthUser, CartLine, Customer, Order, OrderDetail, OrderStatus, Product, UserSuggestion, Warehouse } from '../types';
import { getAuthToken } from './sessionStorage';

type ValidationErrors = Record<string, string[]>;
export const SESSION_EXPIRED_EVENT = 'suriana:session-expired';

interface ApiFailure {
  message?: string;
  errors?: ValidationErrors;
}

export class ApiError extends Error {
  constructor(
    public readonly status: number,
    message: string,
    public readonly errors: ValidationErrors = {},
  ) {
    super(message);
    this.name = 'ApiError';
  }
}

const API_BASE_URL = (import.meta.env.VITE_API_BASE_URL ?? '/api/v1/mobile').replace(/\/$/, '');

export const systemApi = {
  async health(): Promise<void> {
    await request<{ data: { status: 'ok'; service: string; version: string } }>('/health');
  },
};

async function request<T>(path: string, options: RequestInit = {}, token?: string): Promise<T> {
  let response: Response;

  try {
    response = await fetch(`${API_BASE_URL}${path}`, {
      ...options,
      headers: {
        Accept: 'application/json',
        ...(options.body ? { 'Content-Type': 'application/json' } : {}),
        ...(token ? { Authorization: `Bearer ${token}` } : {}),
        ...options.headers,
      },
    });
  } catch {
    throw new ApiError(0, 'No pudimos conectar con el servidor.');
  }

  const payload = await response.json().catch(() => ({})) as ApiFailure & T;

  if (!response.ok) {
    throw new ApiError(
      response.status,
      payload.message ?? 'No pudimos completar la solicitud.',
      payload.errors ?? {},
    );
  }

  return payload;
}

async function authenticatedRequest<T>(path: string, options: RequestInit = {}): Promise<T> {
  const token = await getAuthToken();
  if (!token) throw new ApiError(401, 'Tu sesión terminó. Vuelve a iniciar sesión.');
  try {
    return await request<T>(path, options, token);
  } catch (error) {
    if (error instanceof ApiError && error.status === 401) {
      window.dispatchEvent(new Event(SESSION_EXPIRED_EVENT));
    }
    throw error;
  }
}

export const authApi = {
  async searchUsers(query: string, signal?: AbortSignal): Promise<UserSuggestion[]> {
    const response = await request<{ data: UserSuggestion[] }>(
      `/auth/usuarios?q=${encodeURIComponent(query)}`,
      { signal },
    );

    return response.data;
  },

  async login(usuario: string, password: string): Promise<AuthSession> {
    const response = await request<{ data: AuthSession }>('/auth/login', {
      method: 'POST',
      body: JSON.stringify({ usuario, password }),
    });

    return response.data;
  },

  async session(token: string): Promise<AuthUser> {
    const response = await request<{ data: { usuario: AuthUser } }>('/auth/sesion', {}, token);
    return response.data.usuario;
  },

  async logout(token: string): Promise<void> {
    await request('/auth/logout', { method: 'POST' }, token);
  },
};

interface ApiProduct {
  id: number;
  sku: string;
  barcode: string;
  name: string;
  detail: string;
  price: number;
  unit: { code: string; name: string };
  allows_decimal: boolean;
  requires_warehouse_selection: boolean;
  warehouse_id: number | null;
  warehouses: Warehouse[];
}

interface ApiCustomer {
  id: number;
  name: string;
  detail: string;
}

const productTones: Product['tone'][] = ['sage', 'sand', 'clay', 'slate'];

function initials(name: string): string {
  return name
    .split(/\s+/)
    .filter(Boolean)
    .slice(0, 2)
    .map((part) => part[0])
    .join('')
    .toUpperCase();
}

export const orderCatalogApi = {
  async searchProducts(query: string, branchId: number, signal?: AbortSignal): Promise<Product[]> {
    const response = await authenticatedRequest<{ data: ApiProduct[] }>(
      `/products?q=${encodeURIComponent(query)}&branch_id=${branchId}`,
      { signal },
    );

    return response.data.map((product) => ({
      id: product.id,
      sku: product.sku,
      barcode: product.barcode,
      name: product.name,
      detail: product.detail,
      price: Number(product.price),
      unit: product.unit.name || product.unit.code || 'unidad',
      unitCode: product.unit.code,
      allowsDecimal: product.allows_decimal,
      requiresWarehouseSelection: product.requires_warehouse_selection,
      warehouseId: product.warehouse_id,
      warehouses: product.warehouses,
      tone: productTones[product.id % productTones.length],
    }));
  },

  async searchClients(query: string, signal?: AbortSignal): Promise<Customer[]> {
    const response = await authenticatedRequest<{ data: ApiCustomer[] }>(
      `/clients?q=${encodeURIComponent(query)}`,
      { signal },
    );

    return response.data.map((customer) => ({
      id: customer.id,
      name: customer.name,
      detail: customer.detail,
      initials: initials(customer.name),
    }));
  },
};

interface ApiOrderSummary {
  id: number;
  folio: string;
  status: OrderStatus;
  customer: string;
  item_count: number;
  total: number;
  created_at: string | null;
  warehouse: string;
}

interface ApiOrderLine {
  id: number;
  sku_id: number;
  sku: string;
  barcode: string;
  name: string;
  quantity: number;
  price: number;
  subtotal: number;
  discount: number;
  discount_type: CartLine['discountType'];
  discount_value: number;
  discount_quantity: number;
  total: number;
  unit: string;
  unit_code: string;
  allows_decimal: boolean;
}

interface ApiOrderDetail extends Omit<ApiOrderSummary, 'item_count'> {
  customer_id: number | null;
  subtotal: number;
  notes: string;
  branch_id: number;
  branch: string;
  warehouse_id: number;
  seller: string;
  lines: ApiOrderLine[];
}

function orderTime(createdAt: string | null): string {
  if (!createdAt) return '--:--';
  const date = new Date(createdAt);
  return Number.isNaN(date.getTime())
    ? '--:--'
    : date.toLocaleTimeString('es-MX', { hour: '2-digit', minute: '2-digit', hour12: false });
}

function mapOrder(order: ApiOrderSummary): Order {
  return {
    id: order.id,
    folio: order.folio,
    customer: order.customer,
    itemCount: Number(order.item_count),
    total: Number(order.total),
    time: orderTime(order.created_at),
    status: order.status,
    warehouse: order.warehouse,
    createdAt: order.created_at ?? '',
  };
}

function mapOrderDetail(order: ApiOrderDetail): OrderDetail {
  return {
    id: order.id,
    folio: order.folio,
    customer: order.customer,
    itemCount: order.lines.reduce((sum, line) => sum + Number(line.quantity), 0),
    total: Number(order.total),
    time: orderTime(order.created_at),
    status: order.status,
    warehouse: order.warehouse,
    createdAt: order.created_at ?? '',
    customerId: order.customer_id,
    subtotal: Number(order.subtotal),
    notes: order.notes,
    branchId: Number(order.branch_id),
    branch: order.branch,
    warehouseId: order.warehouse_id,
    seller: order.seller,
    lines: order.lines.map((line) => ({
      id: line.id,
      skuId: line.sku_id,
      sku: line.sku,
      barcode: line.barcode,
      name: line.name,
      quantity: Number(line.quantity),
      price: Number(line.price),
      subtotal: Number(line.subtotal),
      discount: Number(line.discount),
      discountType: line.discount_type,
      discountValue: Number(line.discount_value),
      discountQuantity: Number(line.discount_quantity),
      total: Number(line.total),
      unit: line.unit,
      unitCode: line.unit_code,
      allowsDecimal: line.allows_decimal,
    })),
  };
}

export const floorOrderApi = {
  async list(status: OrderStatus | 'all' = 'all', query = '', branchId?: number): Promise<Order[]> {
    const branch = branchId ? `&branch_id=${branchId}` : '';
    const response = await authenticatedRequest<{ data: ApiOrderSummary[] }>(
      `/floor-orders?status=${status}&q=${encodeURIComponent(query)}${branch}`,
    );
    return response.data.map(mapOrder);
  },

  async show(id: number): Promise<OrderDetail> {
    const response = await authenticatedRequest<{ data: ApiOrderDetail }>(`/floor-orders/${id}`);
    return mapOrderDetail(response.data);
  },

  async create(requestId: string, branchId: number, customerId: number | null, notes: string, cart: CartLine[]): Promise<OrderDetail[]> {
    const response = await authenticatedRequest<{ data: { request_id: string; orders: ApiOrderDetail[] } }>('/floor-orders', {
      method: 'POST',
      body: JSON.stringify({
        request_id: requestId,
        branch_id: branchId,
        customer_id: customerId,
        notes: notes.trim() || null,
        lines: cart.map((line) => ({
          sku_id: line.id,
          warehouse_id: line.warehouseId,
          quantity: line.quantity,
          discount_type: line.discountType,
          discount_value: line.discountValue,
          discount_quantity: line.discountType === 'none' ? 0 : line.discountQuantity,
        })),
      }),
    });
    return response.data.orders.map(mapOrderDetail);
  },

  async update(id: number, branchId: number, customerId: number | null, notes: string, cart: CartLine[]): Promise<OrderDetail> {
    const response = await authenticatedRequest<{ data: ApiOrderDetail }>(`/floor-orders/${id}`, {
      method: 'PUT',
      body: JSON.stringify({
        branch_id: branchId,
        customer_id: customerId,
        notes: notes.trim() || null,
        lines: cart.map((line) => ({
          sku_id: line.id,
          warehouse_id: line.warehouseId,
          quantity: line.quantity,
          discount_type: line.discountType,
          discount_value: line.discountValue,
          discount_quantity: line.discountType === 'none' ? 0 : line.discountQuantity,
        })),
      }),
    });
    return mapOrderDetail(response.data);
  },

  async cancel(id: number): Promise<void> {
    await authenticatedRequest(`/floor-orders/${id}`, { method: 'DELETE' });
  },
};
