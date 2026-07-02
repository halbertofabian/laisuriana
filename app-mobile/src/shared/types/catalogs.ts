export type Sucursal = {
  scl_id: number;
  scl_nombre: string;
  scl_clave?: string;
};

export type Almacen = {
  alm_id: number;
  alm_scl_id: number;
  alm_nombre: string;
  alm_clave?: string;
};
