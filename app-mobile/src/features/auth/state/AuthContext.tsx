import { createContext, PropsWithChildren, useContext, useEffect, useMemo, useState } from "react";
import { clearSession, getSession, saveSession } from "@/shared/storage/sessionStorage";
import type { Session } from "@/shared/types/auth";
import { loginRequest } from "@/features/auth/api/authApi";

type AuthStatus = "booting" | "anonymous" | "authenticated";

type AuthContextValue = {
  status: AuthStatus;
  session: Session | null;
  signIn: (usuario: string, password: string) => Promise<void>;
  signOut: () => Promise<void>;
};

const AuthContext = createContext<AuthContextValue | null>(null);

export function AuthProvider({ children }: PropsWithChildren) {
  const [status, setStatus] = useState<AuthStatus>("booting");
  const [session, setSession] = useState<Session | null>(null);

  useEffect(() => {
    void (async () => {
      const stored = await getSession();
      setSession(stored);
      setStatus(stored ? "authenticated" : "anonymous");
    })();
  }, []);

  const value = useMemo<AuthContextValue>(
    () => ({
      status,
      session,
      signIn: async (usuario, password) => {
        const nextSession = await loginRequest({ usuario, password });
        await saveSession(nextSession);
        setSession(nextSession);
        setStatus("authenticated");
      },
      signOut: async () => {
        await clearSession();
        setSession(null);
        setStatus("anonymous");
      },
    }),
    [session, status],
  );

  return <AuthContext.Provider value={value}>{children}</AuthContext.Provider>;
}

export function useAuth() {
  const context = useContext(AuthContext);
  if (!context) throw new Error("useAuth must be used within AuthProvider.");
  return context;
}
