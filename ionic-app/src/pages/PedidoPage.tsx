import React, { useMemo, useState } from 'react';
import {
  IonBadge,
  IonButton,
  IonCard,
  IonCardContent,
  IonChip,
  IonContent,
  IonIcon,
  IonInput,
  IonItem,
  IonLabel,
  IonList,
  IonNote,
  IonPage,
} from '@ionic/react';
import {
  addOutline,
  arrowForwardOutline,
  businessOutline,
  informationCircleOutline,
  peopleOutline,
  searchOutline,
  storefrontOutline,
} from 'ionicons/icons';
import './PedidoPage.css';

const CLIENTES_MOCK = [
  { id: 1, nombre: 'Constructora del Norte', rfc: 'CDN120312AA1' },
  { id: 2, nombre: 'Ferreteria San Jorge', rfc: 'FSJ040521MM2' },
  { id: 3, nombre: 'Acabados Rivera', rfc: 'ARI980112LU3' },
  { id: 4, nombre: 'Materiales del Valle', rfc: 'MDV150804TR4' },
];

const ALMACENES_MOCK = [
  { id: 'ALM-01', nombre: 'Almacen Central', ciudad: 'Monterrey', productos: 6, status: 'Listo para separar' },
  { id: 'ALM-02', nombre: 'Almacen Norte', ciudad: 'Saltillo', productos: 2, status: 'Pendiente de captura' },
  { id: 'ALM-03', nombre: 'Almacen Sur', ciudad: 'Linares', productos: 0, status: 'Sin productos' },
];

const PedidoPage: React.FC = () => {
  const [search, setSearch] = useState('');
  const [clienteSeleccionado, setClienteSeleccionado] = useState(CLIENTES_MOCK[0]);

  const clientes = useMemo(
    () => CLIENTES_MOCK.filter((cliente) => cliente.nombre.toLowerCase().includes(search.toLowerCase())),
    [search],
  );

  return (
    <IonPage className="pedido-page">
      <IonContent fullscreen className="app-shell">
        <section className="sub-hero">
          <span className="eyebrow">Pedido</span>
          <h1>Base visual para crear pedidos</h1>
          <p>La separacion por almacen esta representada como flujo futuro, sin logica real por ahora.</p>
        </section>

        <section className="content-stack">
          <IonCard className="surface-card">
            <IonCardContent>
              <div className="section-heading">
                <div>
                  <span className="eyebrow">Cliente</span>
                  <h3>Seleccionar comprador</h3>
                </div>
                <IonChip outline>
                  <IonIcon icon={peopleOutline} />
                  <IonLabel>{clientes.length} opciones</IonLabel>
                </IonChip>
              </div>

              <IonItem lines="none" className="app-input search-input">
                <IonIcon slot="start" icon={searchOutline} />
                <IonLabel position="stacked">Buscar cliente</IonLabel>
                <IonInput
                  value={search}
                  placeholder="Nombre o razon social"
                  onIonInput={(event) => setSearch(event.detail.value ?? '')}
                />
              </IonItem>

              <IonList lines="none" className="client-list">
                {clientes.map((cliente) => (
                  <button
                    type="button"
                    key={cliente.id}
                    className={`client-option ${clienteSeleccionado.id === cliente.id ? 'is-active' : ''}`}
                    onClick={() => setClienteSeleccionado(cliente)}
                  >
                    <div className="client-option__avatar">{cliente.nombre.charAt(0)}</div>
                    <div className="client-option__copy">
                      <strong>{cliente.nombre}</strong>
                      <span>{cliente.rfc}</span>
                    </div>
                    {clienteSeleccionado.id === cliente.id && <IonBadge color="primary">Actual</IonBadge>}
                  </button>
                ))}
              </IonList>
            </IonCardContent>
          </IonCard>

          <IonCard className="surface-card highlight-card">
            <IonCardContent>
              <div className="section-heading">
                <div>
                  <span className="eyebrow">Accion</span>
                  <h3>Nuevo pedido</h3>
                </div>
              </div>

              <p className="helper-copy">La experiencia futura permitira elegir productos, detectar su almacen y dividir el pedido automaticamente.</p>

              <IonButton expand="block" shape="round" className="primary-cta">
                <IonIcon slot="start" icon={addOutline} />
                Agregar productos al pedido
              </IonButton>
            </IonCardContent>
          </IonCard>

          <IonCard className="surface-card">
            <IonCardContent>
              <div className="section-heading">
                <div>
                  <span className="eyebrow">Almacenes</span>
                  <h3>Ordenes por almacen</h3>
                </div>
                <IonBadge color="tertiary">Mock</IonBadge>
              </div>

              <div className="warehouse-callout">
                <IonIcon icon={informationCircleOutline} />
                <p>Si el cliente elige productos de 2 almacenes, el vendedor terminara generando 2 pedidos separados.</p>
              </div>

              <div className="warehouse-list">
                {ALMACENES_MOCK.map((almacen) => (
                  <div key={almacen.id} className="warehouse-card">
                    <div className="warehouse-card__top">
                      <div className="warehouse-card__icon">
                        <IonIcon icon={businessOutline} />
                      </div>
                      <div className="warehouse-card__copy">
                        <strong>{almacen.nombre}</strong>
                        <span>{almacen.ciudad}</span>
                      </div>
                      <IonBadge color={almacen.productos > 0 ? 'success' : 'medium'}>
                        {almacen.productos} prod.
                      </IonBadge>
                    </div>

                    <div className="warehouse-card__footer">
                      <IonNote>{almacen.status}</IonNote>
                      <button type="button" className="ghost-link">
                        Ver detalle
                        <IonIcon icon={arrowForwardOutline} />
                      </button>
                    </div>
                  </div>
                ))}
              </div>

              <div className="warehouse-summary">
                <IonIcon icon={storefrontOutline} />
                <span>Preparado para conectar la logica real de separacion mas adelante.</span>
              </div>
            </IonCardContent>
          </IonCard>
        </section>
      </IonContent>
    </IonPage>
  );
};

export default PedidoPage;
