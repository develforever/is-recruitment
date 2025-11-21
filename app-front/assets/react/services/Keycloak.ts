import Keycloak from 'keycloak-js';

const keycloak = new (Keycloak as any)({
  url: 'http://localhost:8080',   // w dockerze za reverse proxy możesz dać URL od nginx
  realm: 'time-work-realm',
  clientId: 'app-front',
});

export default keycloak;