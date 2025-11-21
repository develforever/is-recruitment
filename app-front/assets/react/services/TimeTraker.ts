
interface WorkTime {
    startAt: string;
    endAt: string;
    description?: string;
    workDay?: string;
}

export class TimeTraker {

    reportWork(workTime: WorkTime): Promise<Response> {
        return fetch('/api/remote-worktimes/create', {
            method: "POST",
            mode: "same-origin",
            headers: {
                "Content-Type": "application/json",
            },
            body: JSON.stringify(workTime)
        });
    }

    listWork(): Promise<Response> {
        return fetch('/api/remote-worktimes/list', {
            method: "GET",
            mode: "same-origin",
            headers: {
                "Content-Type": "application/json",
            },
        });
    }

}