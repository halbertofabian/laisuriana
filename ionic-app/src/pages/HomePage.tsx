import React from 'react';
import {
  IonAvatar,
  IonBadge,
  IonButton,
  IonCard,
  IonCardContent,
  IonChip,
  IonContent,
  IonIcon,
  IonItem,
  IonLabel,
  IonList,
  IonNote,
  IonPage,
} from '@ionic/react';
import {
  addCircleOutline,
  arrowForwardOutline,
  logOutOutline,
  notificationsOutline,
  timeOutline,
  trailSignOutline,
} from 'ionicons/icons';
import './HomePage.css';

const RESUMEN_DIA = [
  { label: 'Pedidos en curso', value: '08', tone: 'primary' },
  { label: 'Pedidos por confirmar', value: '03', tone: 'warning' },
  { label: 'Tickets atendidos', value: '21', tone: 'success' },
];

const PEDIDOS_RECIENTES = [
  { id: 'PD-2048', cliente: 'Constructora del Norte', almacen: 'Almacen Central', status: 'En captura', amount: '$12,480' },
  { id: 'PD-2047', cliente: 'Ferreteria San Jorge', almacen: 'Almacen Sur', status: 'Listo para separar', amount: '$6,920' },
  { id: 'PD-2046', cliente: 'Acabados Rivera', almacen: 'Almacen Norte', status: 'En validacion', amount: '$4,380' },
];

interface Props {
  onLogout: () => void;
}

const HomePage: React.FC<Props> = ({ onLogout }) => {
  const fecha = new Date().toLocaleDateString('es-MX', {
    weekday: 'long',
    day: 'numeric',
    month: 'long',
  });

  return (
    <IonPage className="home-page">
      <IonContent fullscreen className="app-shell">
        <section className="hero-panel">
          <div className="hero-panel__top">
            <div>
              <IonNote className="hero-panel__date">{fecha}</IonNote>
              <h1>Hola, Carlos</h1>
              <p>Tu panel de venta de piso esta listo para iniciar pedidos y revisar inventario.</p>
            </div>

            <div className="hero-panel__actions">
              <IonButton fill="clear" shape="round" className="hero-icon-button">
                <IonIcon slot="icon-only" icon={notificationsOutline} />
              </IonButton>
              <IonAvatar className="hero-avatar">
                <span>CG</span>
              </IonAvatar>
            </div>
          </div>

          <IonCard className="spotlight-card">
            <IonCardContent>
              <div className="spotlight-card__header">
                <div>
                  <IonChip color="light">
                    <IonIcon icon={trailSignOutline} />
                    <IonLabel>Resumen del dia</IonLabel>
                  </IonChip>
                  <h2>La jornada va 12% arriba del promedio</h2>
                </div>
                <IonBadge color="success">Activo</IonBadge>
              </div>

              <div className="kpi-grid">
                {RESUMEN_DIA.map((item) => (
                  <div key={item.label} className="kpi-tile">
                    <span className="kpi-tile__value">{item.value}</span>
                    <span className="kpi-tile__label">{item.label}</span>
                    <IonBadge color={item.tone as 'primary' | 'warning' | 'success'}>{item.tone === 'warning' ? 'Atenci&oacute;n' : 'OK'}</IonBadge>
                  </div>
                ))}
              </div>

              <IonButton expand="block" shape="round" className="primary-cta" routerLink="/tabs/pedido">
                <IonIcon slot="start" icon={addCircleOutline} />
                Iniciar nuevo pedido
              </IonButton>
            </IonCardContent>
          </IonCard>
        </section>

        <section className="content-stack">
          <IonCard className="surface-card">
            <IonCardContent>
              <div className="section-heading">
                <div>
                  <span className="eyebrow">Prioridad</span>
                  <h3>Pedidos en curso</h3>
                </div>
                <IonButton fill="clear" size="small">
                  Ver todos
                </IonButton>
              </div>

              <IonList lines="none" className="order-list">
                {PEDIDOS_RECIENTES.map((pedido) => (
                  <IonItem key={pedido.id} className="order-item" detail>
                    <div slot="start" className="order-item__pulse" />
                    <IonLabel>
                      <div className="order-item__row">
                        <strong>{pedido.id}</strong>
                        <span>{pedido.amount}</span>
                      </div>
                      <h4>{pedido.cliente}</h4>
                      <p>{pedido.almacen}</p>
                    </IonLabel>
                    <div slot="end" className="order-item__end">
                      <IonBadge color="medium">{pedido.status}</IonBadge>
                      <IonIcon icon={arrowForwardOutline} />
                    </div>
                  </IonItem>
                ))}
              </IonList>
            </IonCardContent>
          </IonCard>

          <IonCard className="surface-card">
            <IonCardContent>
              <div className="section-heading">
                <div>
                  <span className="eyebrow">Seguimiento</span>
                  <h3>Acciones rapidas</h3>
                </div>
              </div>

              <div className="quick-grid">
                <button type="button" className="quick-action">
                  <IonIcon icon={timeOutline} />
                  <span>Retomar pedido</span>
                  <small>3 pendientes</small>
                </button>
                <button type="button" className="quick-action">
                  <IonIcon icon={trailSignOutline} />
                  <span>Ver almacenes</span>
                  <small>Sincronizar rutas</small>
                </button>
              </div>
            </IonCardContent>
          </IonCard>

          <IonButton expand="block" fill="outline" shape="round" className="logout-button" onClick={onLogout}>
            <IonIcon slot="start" icon={logOutOutline} />
            Salir de la demo
          </IonButton>
        </section>
      </IonContent>
    </IonPage>
  );
};

export default HomePage;
