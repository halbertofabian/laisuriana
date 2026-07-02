import type { Client } from "@/shared/types/clients";

export type ProductSuggestion = {
  psk_id: number;
  psk_codigo: string;
  psk_codigo_barras?: string | null;
  psk_nombre?: string | null;
  psk_precio?: number | string | null;
  permite_decimal?: boolean;
  producto?: {
    prd_id?: number | null;
    prd_nombre?: string | null;
  } | null;
};

export type WarehouseResolution = {
  valido: boolean;
  requiere_seleccion?: boolean;
  message: string;
  prd_id?: number;
  prd_nombre?: string;
  permite_decimal?: boolean;
  pdp_alm_id?: number;
  almacen?: string;
  almacenes?: { alm_id: number; alm_nombre: string }[];
};

export type DiscountType = "ninguno" | "porcentaje" | "importe";

export type FloorOrderLine = {
  itemKey: string;
  ppd_psk_id: number;
  prd_id?: number;
  pdp_alm_id: number;
  almacen: string;
  sku: string;
  nombre: string;
  cantidad: number;
  precio: number;
  permite_decimal: boolean;
  ppd_usr_id?: number;
  capturista?: string;
  descuento_tipo: DiscountType;
  descuento_valor: number;
  descuento_cantidad?: number | null;
};

export type FloorOrderGroup = {
  pdp_alm_id: number;
  almacen: string;
  items: FloorOrderLine[];
};

export type FloorOrderDraft = {
  client: Client | null;
  observaciones: string;
  lines: FloorOrderLine[];
};

export type PedidoRow = {
  pdp_id: number;
  pdp_folio: string;
  pdp_estatus: string;
  pdp_total: number;
  pdp_fecha?: string | null;
  sucursal?: string | null;
  almacen?: string | null;
  vendedor?: string | null;
  cliente?: Client | null;
};
