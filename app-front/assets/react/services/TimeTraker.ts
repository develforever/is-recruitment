
import keycloak from '../services/Keycloak';

interface WorkTime {
    startAt: string;
    endAt: string;
    description?: string;
    workDay?: string;
}


export class TimeTraker {

    reportWork(workTime: WorkTime): Promise<Response> {

        return keycloak().then((auth) => {
            const token = auth.token;

            const headers: HeadersInit = {
                'Content-Type': 'application/json',
            };

            if (token) {
                headers.Authorization = `Bearer ${token}`;
            }

            return fetch('/api/remote-worktimes/create', {
                method: "POST",
                mode: "same-origin",
                headers,
                body: JSON.stringify(workTime)
            });
        });
    }

    listWork(): Promise<Response> {

        return keycloak().then((auth) => {
            const token = auth.token;
            const headers: HeadersInit = {
                'Content-Type': 'application/json',
            };

            if (token) {
                headers.Authorization = `Bearer ${token}`;
            }

            return fetch('/api/remote-worktimes/list', {
                method: "GET",
                mode: "same-origin",
                headers
            });
        });
    }

}