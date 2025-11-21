import Keycloak from 'keycloak-js';

let keycloakToken: any = null;
let keycloak: any = null;
export default async () => {

  if (!keycloakToken) {

    keycloak = new (Keycloak as any)({
      url: 'http://localhost:8080',   // w dockerze za reverse proxy możesz dać URL od nginx
      realm: 'time-work-realm',
      clientId: 'app-front',
    });

    let auth = await keycloak.init({ onLoad: 'login-required' })

    if (auth) {
      keycloakToken = keycloak.token;
      console.log(`Keycloak initialized`);
      return keycloak;
    } else {
      keycloak.login();
    }
  }else{
    return Promise.resolve(keycloak);
  }

}