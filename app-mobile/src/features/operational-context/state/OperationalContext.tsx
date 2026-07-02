import { createContext, PropsWithChildren, useContext, useMemo, useState } from "react";
import type { Almacen, Sucursal } from "@/shared/types/catalogs";

type ContextValue = {
  sucursal: Sucursal | null;
  almacenCatalog: Almacen[];
  setSucursal: (value: Sucursal | null) => void;
  setAlmacenCatalog: (value: Almacen[]) => void;
};

const OperationalContext = createContext<ContextValue | null>(null);

export function OperationalContextProvider({ children }: PropsWithChildren) {
  const [sucursal, setSucursal] = useState<Sucursal | null>(null);
  const [almacenCatalog, setAlmacenCatalog] = useState<Almacen[]>([]);

  const value = useMemo(
    () => ({ sucursal, almacenCatalog, setSucursal, setAlmacenCatalog }),
    [almacenCatalog, sucursal],
  );

  return <OperationalContext.Provider value={value}>{children}</OperationalContext.Provider>;
}

export function useOperationalContext() {
  const context = useContext(OperationalContext);
  if (!context) throw new Error("useOperationalContext must be used within provider.");
  return context;
}
