
import keycloak from '../services/Keycloak';



export class User {

    info(): Promise<Response> {

        return keycloak().then((auth) => {
            const token = auth.token;
            const headers: HeadersInit = {
                'Content-Type': 'application/json',
            };

            if (token) {
                headers.Authorization = `Bearer ${token}`;
            }

            return fetch('/api/user/info', {
                method: "GET",
                mode: "same-origin",
                headers
            });
        });
    }

}