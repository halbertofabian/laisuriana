const loginScreen = document.getElementById('login-screen');
const dashboardScreen = document.getElementById('dashboard-screen');
const loginForm = document.getElementById('login-form');
const logoutButton = document.getElementById('logout-button');
const navLinks = Array.from(document.querySelectorAll('.nav-link'));
const directViewButtons = Array.from(document.querySelectorAll('[data-view-target]'));
const views = {
  home: document.getElementById('view-home'),
  pedido: document.getElementById('view-pedido'),
  inventario: document.getElementById('view-inventario'),
};

const viewMeta = {
  home: {
    eyebrow: 'Home',
    title: 'Panel comercial',
  },
  pedido: {
    eyebrow: 'Pedido',
    title: 'Inicio de pedido',
  },
  inventario: {
    eyebrow: 'Inventario',
    title: 'Consulta de existencias',
  },
};

const viewEyebrow = document.getElementById('view-eyebrow');
const viewTitle = document.getElementById('view-title');

function activateView(viewName) {
  Object.entries(views).forEach(([key, element]) => {
    element.classList.toggle('view--active', key === viewName);
  });

  navLinks.forEach((button) => {
    button.classList.toggle('is-active', button.dataset.view === viewName);
  });

  viewEyebrow.textContent = viewMeta[viewName].eyebrow;
  viewTitle.textContent = viewMeta[viewName].title;
}

loginForm.addEventListener('submit', (event) => {
  event.preventDefault();
  loginScreen.classList.remove('screen--active');
  dashboardScreen.classList.add('screen--active');
  activateView('home');
});

logoutButton.addEventListener('click', () => {
  dashboardScreen.classList.remove('screen--active');
  loginScreen.classList.add('screen--active');
});

navLinks.forEach((button) => {
  button.addEventListener('click', () => activateView(button.dataset.view));
});

directViewButtons.forEach((button) => {
  button.addEventListener('click', () => {
    dashboardScreen.classList.add('screen--active');
    loginScreen.classList.remove('screen--active');
    activateView(button.dataset.viewTarget);
  });
});
