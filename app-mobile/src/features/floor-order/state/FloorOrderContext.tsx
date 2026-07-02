import { createContext, PropsWithChildren, useContext, useMemo, useReducer } from "react";
import type { Client } from "@/shared/types/clients";
import type { FloorOrderDraft, FloorOrderLine } from "@/features/floor-order/types";

type Action =
  | { type: "addLine"; payload: FloorOrderLine }
  | { type: "updateQuantity"; itemKey: string; cantidad: number }
  | { type: "updateDiscount"; itemKey: string; descuento_tipo: FloorOrderLine["descuento_tipo"]; descuento_valor: number }
  | { type: "removeLine"; itemKey: string }
  | { type: "setClient"; payload: Client | null }
  | { type: "setNotes"; payload: string }
  | { type: "reset" };

const initialState: FloorOrderDraft = {
  client: null,
  observaciones: "",
  lines: [],
};

function reducer(state: FloorOrderDraft, action: Action): FloorOrderDraft {
  switch (action.type) {
    case "addLine": {
      const index = state.lines.findIndex((line) => line.itemKey === action.payload.itemKey);
      if (index >= 0) {
        const updated = [...state.lines];
        updated[index] = {
          ...updated[index],
          cantidad: Number((updated[index].cantidad + action.payload.cantidad).toFixed(2)),
        };
        return { ...state, lines: updated };
      }
      return { ...state, lines: [...state.lines, action.payload] };
    }
    case "updateQuantity":
      return {
        ...state,
        lines: state.lines.map((line) =>
          line.itemKey === action.itemKey ? { ...line, cantidad: action.cantidad } : line,
        ),
      };
    case "updateDiscount":
      return {
        ...state,
        lines: state.lines.map((line) =>
          line.itemKey === action.itemKey
            ? {
                ...line,
                descuento_tipo: action.descuento_tipo,
                descuento_valor: action.descuento_valor,
              }
            : line,
        ),
      };
    case "removeLine":
      return { ...state, lines: state.lines.filter((line) => line.itemKey !== action.itemKey) };
    case "setClient":
      return { ...state, client: action.payload };
    case "setNotes":
      return { ...state, observaciones: action.payload };
    case "reset":
      return initialState;
    default:
      return state;
  }
}

type ContextValue = {
  draft: FloorOrderDraft;
  addLine: (line: FloorOrderLine) => void;
  updateQuantity: (itemKey: string, cantidad: number) => void;
  updateDiscount: (itemKey: string, descuento_tipo: FloorOrderLine["descuento_tipo"], descuento_valor: number) => void;
  removeLine: (itemKey: string) => void;
  setClient: (client: Client | null) => void;
  setNotes: (notes: string) => void;
  reset: () => void;
};

const FloorOrderContext = createContext<ContextValue | null>(null);

export function FloorOrderProvider({ children }: PropsWithChildren) {
  const [draft, dispatch] = useReducer(reducer, initialState);

  const value = useMemo<ContextValue>(
    () => ({
      draft,
      addLine: (line) => dispatch({ type: "addLine", payload: line }),
      updateQuantity: (itemKey, cantidad) => dispatch({ type: "updateQuantity", itemKey, cantidad }),
      updateDiscount: (itemKey, descuento_tipo, descuento_valor) =>
        dispatch({ type: "updateDiscount", itemKey, descuento_tipo, descuento_valor }),
      removeLine: (itemKey) => dispatch({ type: "removeLine", itemKey }),
      setClient: (client) => dispatch({ type: "setClient", payload: client }),
      setNotes: (notes) => dispatch({ type: "setNotes", payload: notes }),
      reset: () => dispatch({ type: "reset" }),
    }),
    [draft],
  );

  return <FloorOrderContext.Provider value={value}>{children}</FloorOrderContext.Provider>;
}

export function useFloorOrder() {
  const context = useContext(FloorOrderContext);
  if (!context) throw new Error("useFloorOrder must be used within provider.");
  return context;
}
