import * as React from 'react';
import { TimeTraker } from '../services/TimeTraker';

type Props = {
  id: string;
  name?: string;
  surname?: string;
};

export default function ReportWork({ name = 'Świecie', surname = '' }: Props) {

  const apiService: TimeTraker = new TimeTraker();

  const [workDescription, setWorkDescription] = React.useState('');
  const [workStartHour, setWorkStartHour] = React.useState<number | undefined>(8);
  const [workEndHour, setWorkEndHour] = React.useState<number | undefined>(16);
  const [status, setStatus] = React.useState<string | undefined>(undefined);

  const report = (e: React.MouseEvent<HTMLButtonElement>) => {
    e.preventDefault();

    const workTime = {
      startAt: `2025-11-01T${workStartHour?.toString().padStart(2, '0')}:00:00+01:00`,
      endAt: `2025-11-01T${workEndHour?.toString().padStart(2, '0')}:00:00+01:00`
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
    });
    setStatus('Zgłoszono pracę');
  };

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
        <input type="number" name="workStartHour" value={workStartHour} 
        onChange={event => {
          const num = Number(event.target.value);
            setWorkStartHour(Number.isNaN(num) ? undefined : num);
          }} />
      </label>
      <br />
      <label>
        Koniec pracy (godzina):
        <input type="number" name="workEndHour" value={workEndHour}
        onChange={event => {
            const num = Number(event.target.value);
            setWorkEndHour(Number.isNaN(num) ? undefined : num);
          }} />
      </label>
      <br />

      <button type="submit" onClick={report}>Zgłoś pracę</button>
    </form>
  </div>;
}