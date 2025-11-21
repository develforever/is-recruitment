import * as React from 'react';
import { TimeTraker } from '../services/TimeTraker';
import keycloak from '../services/Keycloak';


function formatDuration(seconds: number): string {
  const totalMinutes = Math.floor(seconds / 60);
  const hours = Math.floor(totalMinutes / 60);
  const minutes = totalMinutes % 60;

  return `${hours.toString().padStart(2, '0')}h ${minutes
    .toString()
    .padStart(2, '0')}m`;
}

export default function ReportWork() {

  const apiService: TimeTraker = new TimeTraker();

  const [name, setName] = React.useState<string | null>(null);
  const [surname, setSurname] = React.useState<string | null>(null);
  const [workDescription, setWorkDescription] = React.useState('');
  const [workDay, setWorkDay] = React.useState<string | undefined>(new Date().toISOString().split('T')[0]);
  const [workStartHour, setWorkStartHour] = React.useState<number | undefined>(8);
  const [workStartMinute, setWorkStartMinute] = React.useState<number | undefined>(0);
  const [workEndHour, setWorkEndHour] = React.useState<number | undefined>(16);
  const [workEndMinute, setWorkEndMinute] = React.useState<number | undefined>(0);
  const [status, setStatus] = React.useState<string | undefined>(undefined);
  const [workTimes, setWorkTimes] = React.useState<Array<any>>([]);

  const deleteWork = (e: React.MouseEvent<HTMLButtonElement>) => {
    e.preventDefault();
    setStatus('Usuwanie pracy - TODO');
  };

  const report = (e: React.MouseEvent<HTMLButtonElement>) => {
    e.preventDefault();

    const workTime = {
      startAt: `${workStartHour?.toString().padStart(2, '0')}:${workStartMinute?.toString().padStart(2, '0')}:00`,
      endAt: `${workEndHour?.toString().padStart(2, '0')}:${workEndMinute?.toString().padStart(2, '0')}:00`,
      description: workDescription,
      workDay: workDay,
    };

    apiService.reportWork(workTime).then(async response => {

      const data = await response.json();

      if (response.ok) {
        setStatus(data.message || 'Praca zgłoszona pomyślnie.');
      } else {
        setStatus('Błąd serwera: ' + (data.error || data.message || response.statusText));
      }
    }).catch(error => {
      setStatus('Błąd sieci:' + error.message);
    })
      .finally(() => {
        fetchWorkTimes();
      });
    setStatus('Zgłoszono pracę');
  };

  const fetchWorkTimes = React.useCallback(() => {
    return apiService.listWork()
      .then(async response => {
        const data = await response.json();

        if (response.ok) {
          setWorkTimes(data.response?.data || []);
        } else {
          setStatus('Błąd serwera: ' + (data.error || data.message || response.statusText));
        }
      })
      .catch(error => {
        setStatus('Błąd sieci: ' + error.message);
      });
  }, []);

  React.useEffect(() => {
    keycloak().then((auth) => {
      const data = auth.tokenParsed as any;
      setName(data.given_name);
      setSurname(data.family_name);
      fetchWorkTimes();
    });
  }, []);

  return <div>
    {name === null && <div className='p4'>Przekierowanie do logowania...</div>}
    {name !== null && <div>
      <div>
        <p>Witaj {name} {surname}!</p>
        <button className='btn btn-secondary' onClick={() => {
          keycloak().then((kc) => {
            kc.logout();
          });
        }}>Wyloguj</button>
      </div>
      <p>To jest komponent ReportWork.</p>
      {status && <p className='alert alert-primary'>{status}</p>}
      <form>
        <div className="mb-3">
          <label className='form-label'>Dzień:</label>
          <input type="date" name="workDay" className='form-control' value={workDay}
            max={new Date().toISOString().split('T')[0]}
            onChange={event => {
              setWorkDay(event.target.value);
            }} />
        </div>

        <div className="mb-3">
          <label className='form-label'>Start pracy (godzina):</label>
          <input type="number" step={1} className='form-control' name="workStartHour" value={workStartHour}
            onChange={event => {
              const num = Number(event.target.value);
              setWorkStartHour(Number.isNaN(num) ? undefined : num);
            }} />
          <label className='form-label'>Start pracy (minuta):</label>
          <input type="number" step={15} min={0} max={59} className='form-control' name="workStartMinute" value={workStartMinute}
            onChange={event => {
              const num = Number(event.target.value);
              setWorkStartMinute(Number.isNaN(num) ? undefined : num);
            }} />
        </div>

        <div className="mb-3">
          <label className='form-label'>Koniec pracy (godzina):</label>
          <input type="number" step={1} className='form-control' name="workEndHour" value={workEndHour}
            onChange={event => {
              const num = Number(event.target.value);
              setWorkEndHour(Number.isNaN(num) ? undefined : num);
            }} />
          <label className='form-label'>Koniec pracy (minuta):</label>
          <input type="number" step={15} min={0} max={59} className='form-control' name="workEndMinute" value={workEndMinute}
            onChange={event => {
              const num = Number(event.target.value);
              setWorkEndMinute(Number.isNaN(num) ? undefined : num);
            }} />
        </div>

        <div className="mb-3">
          <label className='form-label'>Opis pracy:</label>
          <textarea name="workDescription" className='form-control' value={workDescription}
            placeholder='Enter report description'
            onChange={event => {
              setWorkDescription(event.target.value);
            }} />
        </div>

        <button type="submit" onClick={report} className="btn btn-primary">Zgłoś pracę</button>

        <div className="worktimes-list mt-4">
          <h4>Zarejestrowana praca:</h4>
          <table className="table table-striped">
            <thead>
              <tr>
                <th>Dzień</th>
                <th>Od</th>
                <th>Do</th>
                <th>Godziny</th>
                <th>Opis</th>
                <th>Akcje</th>
              </tr>
            </thead>
            <tbody>
              {workTimes.map(wt => (
                <tr key={wt.id}>
                  <td>{wt.startDay}</td>
                  <td>{new Date(wt.startAt).toLocaleTimeString(undefined, {
                    hour: '2-digit',
                    minute: '2-digit',
                    timeZone: 'UTC',          // <-- kluczowe
                  })}</td>
                  <td>{new Date(wt.endAt).toLocaleTimeString(undefined, {
                    hour: '2-digit',
                    minute: '2-digit',
                    timeZone: 'UTC',          // <-- kluczowe
                  })}</td>
                  <td>{formatDuration(wt.duration)}</td>
                  <td>{wt.description || '-'}</td>
                  <td>
                    <button type="button" onClick={deleteWork} className="btn btn-danger btn-sm">Usuń - TODO</button>
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      </form>
    </div>}
  </div>;
}