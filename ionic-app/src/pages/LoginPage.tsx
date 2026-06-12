import React, { useState } from 'react';
import {
  IonButton,
  IonCard,
  IonCardContent,
  IonContent,
  IonIcon,
  IonInput,
  IonItem,
  IonLabel,
  IonPage,
  IonSpinner,
  IonText,
  IonToggle,
} from '@ionic/react';
import {
  arrowForwardOutline,
  lockClosedOutline,
  mailOutline,
  shieldCheckmarkOutline,
  storefrontOutline,
} from 'ionicons/icons';
import './LoginPage.css';

interface Props {
  onLogin: () => void;
}

const LoginPage: React.FC<Props> = ({ onLogin }) => {
  const [usuario, setUsuario] = useState('');
  const [password, setPassword] = useState('');
  const [rememberSession, setRememberSession] = useState(true);
  const [loading, setLoading] = useState(false);

  const handleLogin = () => {
    setLoading(true);

    window.setTimeout(() => {
      setLoading(false);
      onLogin();
    }, 700);
  };

  return (
    <IonPage className="login-page">
      <IonContent fullscreen className="login-content">
        <div className="login-screen">
          <div className="login-hero">
            <div className="login-hero__badge">
              <IonIcon icon={shieldCheckmarkOutline} />
              <span>Acceso vendedor</span>
            </div>

            <div className="login-brand">
              <div className="login-brand__logo">
                <IonIcon icon={storefrontOutline} />
              </div>
              <div>
                <h1>La Suriana</h1>
                <p>Pedidos de piso para venta asistida</p>
              </div>
            </div>

            <div className="login-copy">
              <h2>Opera pedidos desde el piso de venta con una experiencia movil clara y rapida.</h2>
              <p>Esta primera version es una demo visual pensada para Android, sin backend ni autenticacion real todavia.</p>
            </div>
          </div>

          <IonCard className="login-card">
            <IonCardContent>
              <div className="login-card__header">
                <IonText color="dark">
                  <h3>Iniciar sesion</h3>
                </IonText>
                <p>Usa tu usuario o correo corporativo para continuar.</p>
              </div>

              <div className="login-form">
                <IonItem lines="none" className="app-input">
                  <IonIcon slot="start" icon={mailOutline} />
                  <IonLabel position="stacked">Usuario o correo</IonLabel>
                  <IonInput
                    value={usuario}
                    inputmode="email"
                    placeholder="vendedor@lasuriana.mx"
                    autocomplete="username"
                    onIonInput={(event) => setUsuario(event.detail.value ?? '')}
                  />
                </IonItem>

                <IonItem lines="none" className="app-input">
                  <IonIcon slot="start" icon={lockClosedOutline} />
                  <IonLabel position="stacked">Contrasena</IonLabel>
                  <IonInput
                    value={password}
                    type="password"
                    placeholder="Ingresa tu contrasena"
                    autocomplete="current-password"
                    onIonInput={(event) => setPassword(event.detail.value ?? '')}
                  />
                </IonItem>

                <div className="login-meta">
                  <div className="login-meta__remember">
                    <IonToggle
                      checked={rememberSession}
                      onIonChange={(event) => setRememberSession(event.detail.checked)}
                    />
                    <span>Recordar sesion</span>
                  </div>

                  <button type="button" className="login-meta__link">
                    Soporte
                  </button>
                </div>

                <IonButton
                  expand="block"
                  shape="round"
                  size="large"
                  className="login-submit"
                  onClick={handleLogin}
                  disabled={loading}
                >
                  {loading ? (
                    <>
                      <IonSpinner name="crescent" />
                      <span>Entrando...</span>
                    </>
                  ) : (
                    <>
                      <span>Iniciar sesion</span>
                      <IonIcon icon={arrowForwardOutline} />
                    </>
                  )}
                </IonButton>

                <div className="login-note">
                  <IonIcon icon={shieldCheckmarkOutline} />
                  <p>Demo visual sin autenticacion real. El acceso solo navega al home para revisar UX.</p>
                </div>
              </div>
            </IonCardContent>
          </IonCard>
        </div>
      </IonContent>
    </IonPage>
  );
};

export default LoginPage;
