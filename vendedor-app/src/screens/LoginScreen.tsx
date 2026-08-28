import { Check, Eye, EyeOff, LockKeyhole, UserRound } from 'lucide-react';
import { useEffect, useRef, useState } from 'react';
import { Button } from '../components/Button';
import { ApiError, authApi } from '../services/api';
import { setAuthToken } from '../services/sessionStorage';
import type { AuthSession, UserSuggestion } from '../types';

export function LoginScreen({ message, onAuthenticated }: { message?: string | null; onAuthenticated: (session: AuthSession) => void }) {
  const [query, setQuery] = useState('');
  const [selectedUser, setSelectedUser] = useState<UserSuggestion | null>(null);
  const [suggestions, setSuggestions] = useState<UserSuggestion[]>([]);
  const [searching, setSearching] = useState(false);
  const [password, setPassword] = useState('');
  const [showPassword, setShowPassword] = useState(false);
  const [submitting, setSubmitting] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const passwordRef = useRef<HTMLInputElement>(null);

  useEffect(() => {
    const normalized = query.trim();
    if (selectedUser || normalized.length < 2) {
      setSuggestions([]);
      setSearching(false);
      return;
    }

    const controller = new AbortController();
    const timeout = window.setTimeout(async () => {
      setSearching(true);
      try {
        const results = await authApi.searchUsers(normalized, controller.signal);
        setSuggestions(results);
        setError(null);
      } catch (requestError) {
        if (!controller.signal.aborted) {
          setError(requestError instanceof ApiError ? requestError.message : 'No pudimos buscar usuarios.');
        }
      } finally {
        if (!controller.signal.aborted) setSearching(false);
      }
    }, 260);

    return () => {
      window.clearTimeout(timeout);
      controller.abort();
    };
  }, [query, selectedUser]);

  const chooseUser = (user: UserSuggestion) => {
    setSelectedUser(user);
    setQuery(user.usuario);
    setSuggestions([]);
    setError(null);
    window.setTimeout(() => passwordRef.current?.focus(), 80);
  };

  const changeUser = (value: string) => {
    setQuery(value);
    setSelectedUser(null);
    setPassword('');
    setError(null);
  };

  const submit = async (event: React.FormEvent) => {
    event.preventDefault();
    if (!selectedUser || !password) return;

    setSubmitting(true);
    setError(null);
    try {
      const session = await authApi.login(selectedUser.usuario, password);
      await setAuthToken(session.token);
      onAuthenticated(session);
    } catch (requestError) {
      setError(requestError instanceof ApiError ? requestError.message : 'No pudimos iniciar sesión.');
    } finally {
      setSubmitting(false);
    }
  };

  return (
    <main className="login-screen screen-enter">
      <div className="login-screen__brand">
        <div className="brand-mark brand-mark--large">S</div>
        <p>iSURIANA</p>
      </div>
      <section className="login-panel">
        <div className="login-panel__intro">
          <span className="eyebrow">VENTAS DE PISO</span>
          <h1>Bienvenido</h1>
          <p>Busca tu usuario para comenzar.</p>
        </div>
        {message && <div className="login-session-notice" role="status">{message}</div>}
        <form onSubmit={submit} className="form-stack">
          <label className="field-label">
            <span>Usuario</span>
            <div className={`text-field ${selectedUser ? 'text-field--selected' : ''}`}>
              <UserRound size={19} />
              <input
                value={query}
                onChange={(event) => changeUser(event.target.value)}
                autoComplete="off"
                autoCapitalize="none"
                spellCheck={false}
                placeholder="Escribe tu usuario"
                aria-expanded={suggestions.length > 0}
                aria-controls="user-suggestions"
              />
              {selectedUser && <Check size={19} className="field-check" />}
              {searching && <span className="field-loader" aria-label="Buscando" />}
            </div>
          </label>

          {suggestions.length > 0 && (
            <div className="user-suggestions" id="user-suggestions" role="listbox">
              {suggestions.map((user) => (
                <button type="button" key={user.usuario} onClick={() => chooseUser(user)} role="option">
                  <span className="suggestion-avatar">{user.nombre.slice(0, 2).toUpperCase()}</span>
                  <span><strong>{user.nombre}</strong><small>@{user.usuario}</small></span>
                </button>
              ))}
            </div>
          )}

          {error && !selectedUser && <span className="login-general-error" role="alert">{error}</span>}

          {selectedUser && (
            <div className="selected-user-note">
              <span>{selectedUser.nombre}</span>
              <button type="button" onClick={() => changeUser('')}>Cambiar</button>
            </div>
          )}

          {selectedUser && (
            <label className="field-label password-reveal">
              <span>Contraseña</span>
              <div className={`text-field ${error ? 'text-field--error' : ''}`}>
                <LockKeyhole size={19} />
                <input
                  ref={passwordRef}
                  type={showPassword ? 'text' : 'password'}
                  value={password}
                  onChange={(event) => { setPassword(event.target.value); setError(null); }}
                  autoComplete="current-password"
                  placeholder="Tu contraseña"
                />
                <button type="button" onClick={() => setShowPassword(!showPassword)} aria-label={showPassword ? 'Ocultar contraseña' : 'Mostrar contraseña'}>
                  {showPassword ? <EyeOff size={18} /> : <Eye size={18} />}
                </button>
              </div>
              {error && <span className="field-error" role="alert">{error}</span>}
            </label>
          )}

          <Button type="submit" full loading={submitting} disabled={!selectedUser || !password}>Entrar</Button>
        </form>
      </section>
      <p className="login-screen__branch">Conexión segura · iSuriana</p>
    </main>
  );
}
