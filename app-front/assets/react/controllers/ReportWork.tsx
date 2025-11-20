import * as React from 'react';
import { TimeTraker } from '../services/TimeTraker';

type Props = {
  id: string;
  name?: string;
  surname?: string;
};

function formatDuration(seconds: number): string {
  const hours = seconds / 3600;
  return `${hours.toFixed(2)} h`;
}

export default function ReportWork({ name = 'Świecie', surname = '' }: Props) {

  const apiService: TimeTraker = new TimeTraker();

  const [workDescription, setWorkDescription] = React.useState('');
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
      endAt: `${workEndHour?.toString().padStart(2, '0')}:${workEndMinute?.toString().padStart(2, '0')}:00`
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
    apiService.listWork()
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
    fetchWorkTimes();
  }, []);

  return <div>
    <p>Witaj {name} {surname}!</p>
    <p>To jest komponent ReportWork.</p>
    <p>{status}</p>
    <form>
      <label>
        Opis pracy:
        <textarea name="workDescription" />
      </label>
      <br />
      <label>
        Start pracy (godzina):
        <input type="number" step={1} name="workStartHour" value={workStartHour}
          onChange={event => {
            const num = Number(event.target.value);
            setWorkStartHour(Number.isNaN(num) ? undefined : num);
          }} />
        Start pracy (minuta):
        <input type="number" step={15} min={0} max={59} name="workStartMinute" value={workStartMinute}
          onChange={event => {
            const num = Number(event.target.value);
            setWorkStartMinute(Number.isNaN(num) ? undefined : num);
          }} />
      </label>
      <br />
      <label>
        Koniec pracy (godzina):
        <input type="number" step={1} name="workEndHour" value={workEndHour}
          onChange={event => {
            const num = Number(event.target.value);
            setWorkEndHour(Number.isNaN(num) ? undefined : num);
          }} />
        Koniec pracy (minuta):
        <input type="number" step={15} min={0} max={59} name="workEndMinute" value={workEndMinute}
          onChange={event => {
            const num = Number(event.target.value);
            setWorkEndMinute(Number.isNaN(num) ? undefined : num);
          }} />
      </label>
      <br />

      <button type="submit" onClick={report} disabled={workTimes.length > 0}>Zgłoś pracę</button>
      <br />

      <div>
        <p>Zarejestrowana praca:</p>
        {workTimes.map(wt => (
          <div key={wt.id} className="worktime">
            <strong>Dzień:</strong> {wt.startDay}<br />
            <strong>Od:</strong> {new Date(wt.startAt).toLocaleTimeString(undefined, {
              hour: '2-digit',
              minute: '2-digit',
              timeZone: 'UTC',          // <-- kluczowe
            })}<br />
            <strong>Do:</strong> {new Date(wt.endAt).toLocaleTimeString(undefined, {
              hour: '2-digit',
              minute: '2-digit',
              timeZone: 'UTC',          // <-- kluczowe
            })}  <br />
            <strong>Godziny:</strong> {formatDuration(wt.duration)} <br />
            <button type="button" onClick={deleteWork} >Usuń - TODO</button>
          </div>
        ))}
      </div>

    </form>
  </div>;
}