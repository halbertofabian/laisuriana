export type LoginRequest = {
  usuario: string;
  password: string;
};

export type Session = {
  usuario: string;
  token?: string;
};

export type UsuarioSugerido = {
  usr_usuario: string;
  usr_nombre: string;
};

export type SucursalOption = {
  scl_id: number;
  scl_nombre: string;
  scl_clave?: string;
};

export type AlmacenOption = {
  alm_id: number;
  alm_scl_id: number;
  alm_nombre: string;
  alm_clave?: string;
};
