import React from 'react';
import {
  IonIcon,
  IonLabel,
  IonRouterOutlet,
  IonTabBar,
  IonTabButton,
  IonTabs,
} from '@ionic/react';
import { Redirect, Route } from 'react-router-dom';
import {
  cubeOutline,
  cubeSharp,
  homeOutline,
  homeSharp,
  receiptOutline,
  receiptSharp,
} from 'ionicons/icons';
import HomePage from './HomePage';
import InventarioPage from './InventarioPage';
import PedidoPage from './PedidoPage';
import './MainTabs.css';

interface Props {
  onLogout: () => void;
}

const MainTabs: React.FC<Props> = ({ onLogout }) => {
  return (
    <IonTabs>
      <IonRouterOutlet>
        <Route exact path="/tabs/pedido" component={PedidoPage} />
        <Route exact path="/tabs/home" render={() => <HomePage onLogout={onLogout} />} />
        <Route exact path="/tabs/inventario" component={InventarioPage} />
        <Redirect exact from="/tabs" to="/tabs/home" />
      </IonRouterOutlet>

      <IonTabBar slot="bottom" className="app-tab-bar">
        <IonTabButton tab="pedido" href="/tabs/pedido">
          <IonIcon ios={receiptOutline} md={receiptSharp} />
          <IonLabel>Pedido</IonLabel>
        </IonTabButton>

        <IonTabButton tab="home" href="/tabs/home">
          <IonIcon ios={homeOutline} md={homeSharp} />
          <IonLabel>Home</IonLabel>
        </IonTabButton>

        <IonTabButton tab="inventario" href="/tabs/inventario">
          <IonIcon ios={cubeOutline} md={cubeSharp} />
          <IonLabel>Inventario</IonLabel>
        </IonTabButton>
      </IonTabBar>
    </IonTabs>
  );
};

export default MainTabs;
