import { registerReactControllerComponents } from '@symfony/ux-react';
import './stimulus_bootstrap.ts';
import 'bootstrap/dist/css/bootstrap.min.css';
import 'bootstrap/dist/js/bootstrap.bundle.min.js';
import keycloak from './react/services/Keycloak';

keycloak.init({ onLoad: 'login-required' }).then((authenticated: boolean) => {
  if (!authenticated) {
    keycloak.login();
  } else {
    console.log('Authenticated: ' + keycloak.token);
  }
});

/*
 * Welcome to your app's main JavaScript file!
 *
 * This file will be included onto the page via the importmap() Twig function,
 * which should already be in your base.html.twig.
 */
import './styles/app.css';

console.log('This log comes from assets/app.ts - welcome to AssetMapper! 🎉');

registerReactControllerComponents(require.context('./react/controllers', true, /\.(j|t)sx?$/));