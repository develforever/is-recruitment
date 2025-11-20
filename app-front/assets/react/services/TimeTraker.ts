
interface WorkTime {
    employeeId: string;
    startAt: string;
    endAt: string;
}

export class TimeTraker {

    reportWork(workTime: WorkTime): Promise<Response> {
        return fetch('/api/worktimes', {
            method: "POST",
            mode: "same-origin",
            headers: {
                "Content-Type": "application/json",
            },
            body: JSON.stringify(workTime)
        });
    }

}