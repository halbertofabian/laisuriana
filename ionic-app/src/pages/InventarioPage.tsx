import React, { useMemo, useState } from 'react';
import {
  IonBadge,
  IonCard,
  IonCardContent,
  IonChip,
  IonContent,
  IonIcon,
  IonLabel,
  IonPage,
  IonSearchbar,
} from '@ionic/react';
import { cubeOutline, layersOutline, locationOutline } from 'ionicons/icons';
import './InventarioPage.css';

const PRODUCTOS = [
  { id: 1, nombre: 'Cemento gris 50 kg', sku: 'CEM-050', almacen: 'Almacen Central', existencia: 240, unidad: 'bolsas', estado: 'Disponible', tone: 'success' },
  { id: 2, nombre: 'Varilla 3/8 x 6 m', sku: 'VAR-038', almacen: 'Almacen Norte', existencia: 18, unidad: 'piezas', estado: 'Stock bajo', tone: 'warning' },
  { id: 3, nombre: 'Pintura vinilica 19 L', sku: 'PIN-190', almacen: 'Almacen Sur', existencia: 62, unidad: 'cubetas', estado: 'Disponible', tone: 'success' },
  { id: 4, nombre: 'Cable THW calibre 12', sku: 'CAB-012', almacen: 'Almacen Norte', existencia: 4, unidad: 'rollos', estado: 'Critico', tone: 'danger' },
];

const ALMACENES = ['Todos', 'Almacen Central', 'Almacen Norte', 'Almacen Sur'];

const InventarioPage: React.FC = () => {
  const [search, setSearch] = useState('');
  const [almacen, setAlmacen] = useState('Todos');

  const productos = useMemo(
    () => PRODUCTOS.filter((producto) => {
      const matchesSearch = producto.nombre.toLowerCase().includes(search.toLowerCase())
        || producto.sku.toLowerCase().includes(search.toLowerCase());
      const matchesAlmacen = almacen === 'Todos' || producto.almacen === almacen;

      return matchesSearch && matchesAlmacen;
    }),
    [almacen, search],
  );

  return (
    <IonPage className="inventario-page">
      <IonContent fullscreen className="app-shell">
        <section className="sub-hero">
          <span className="eyebrow">Inventario</span>
          <h1>Consulta rapida por producto</h1>
          <p>Vista pensada para revisar existencias por almacen sin salir del flujo de venta.</p>
        </section>

        <section className="content-stack">
          <IonCard className="surface-card">
            <IonCardContent>
              <IonSearchbar
                className="inventory-searchbar"
                value={search}
                placeholder="Buscar por nombre o SKU"
                onIonInput={(event) => setSearch(event.detail.value ?? '')}
              />

              <div className="chip-row">
                {ALMACENES.map((item) => (
                  <IonChip
                    key={item}
                    outline={almacen !== item}
                    color={almacen === item ? 'primary' : 'medium'}
                    onClick={() => setAlmacen(item)}
                    className="filter-chip"
                  >
                    <IonLabel>{item}</IonLabel>
                  </IonChip>
                ))}
              </div>
            </IonCardContent>
          </IonCard>

          <IonCard className="surface-card">
            <IonCardContent>
              <div className="section-heading">
                <div>
                  <span className="eyebrow">Resultados</span>
                  <h3>{productos.length} productos visibles</h3>
                </div>
                <IonBadge color="primary">Mock data</IonBadge>
              </div>

              <div className="inventory-list">
                {productos.map((producto) => (
                  <article key={producto.id} className="inventory-card">
                    <div className="inventory-card__top">
                      <div className="inventory-card__icon">
                        <IonIcon icon={cubeOutline} />
                      </div>
                      <div className="inventory-card__copy">
                        <strong>{producto.nombre}</strong>
                        <span>{producto.sku}</span>
                      </div>
                      <IonBadge color={producto.tone as 'success' | 'warning' | 'danger'}>{producto.estado}</IonBadge>
                    </div>

                    <div className="inventory-card__meta">
                      <div>
                        <IonIcon icon={locationOutline} />
                        <span>{producto.almacen}</span>
                      </div>
                      <div>
                        <IonIcon icon={layersOutline} />
                        <span>{producto.existencia} {producto.unidad}</span>
                      </div>
                    </div>

                    <div className="inventory-card__progress">
                      <div
                        className={`inventory-card__progress-fill tone-${producto.tone}`}
                        style={{ width: `${Math.min(100, (producto.existencia / 250) * 100)}%` }}
                      />
                    </div>
                  </article>
                ))}
              </div>
            </IonCardContent>
          </IonCard>
        </section>
      </IonContent>
    </IonPage>
  );
};

export default InventarioPage;
