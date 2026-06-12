import React, { useState } from 'react';
import { IonApp, IonRouterOutlet, setupIonicReact } from '@ionic/react';
import { IonReactRouter } from '@ionic/react-router';
import { Redirect, Route, Switch } from 'react-router-dom';
import LoginPage from './pages/LoginPage';
import MainTabs from './pages/MainTabs';

setupIonicReact({
  mode: 'md',
  animated: true,
});

const App: React.FC = () => {
  const [isLoggedIn, setIsLoggedIn] = useState(false);

  return (
    <IonApp>
      <IonReactRouter>
        <IonRouterOutlet>
          <Switch>
            <Route
              exact
              path="/login"
              render={() => (
                isLoggedIn
                  ? <Redirect to="/tabs/home" />
                  : <LoginPage onLogin={() => setIsLoggedIn(true)} />
              )}
            />
            <Route
              path="/tabs"
              render={() => (
                isLoggedIn
                  ? <MainTabs onLogout={() => setIsLoggedIn(false)} />
                  : <Redirect to="/login" />
              )}
            />
            <Redirect exact from="/" to="/login" />
          </Switch>
        </IonRouterOutlet>
      </IonReactRouter>
    </IonApp>
  );
};

export default App;
