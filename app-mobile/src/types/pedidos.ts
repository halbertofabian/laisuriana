export type ProductoSugerencia = {
  psk_id: number;
  psk_codigo: string;
  psk_codigo_barras?: string | null;
  psk_nombre?: string | null;
  psk_precio?: number | string | null;
  producto?: {
    prd_nombre?: string | null;
  } | null;
};

export type PartidaPedido = {
  ppd_psk_id: number;
  sku: string;
  nombre: string;
  cantidad: number;
  precio: number;
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
};
