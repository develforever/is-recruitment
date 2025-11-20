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
  const [workStartHour, setWorkStartHour] = React.useState<number | undefined>(undefined);
  const [workEndHour, setWorkEndHour] = React.useState<number | undefined>(undefined);

  const report = (e: React.MouseEvent<HTMLButtonElement>) => {
    e.preventDefault();

    const workTime = {
      employeeId: '123', // This should be dynamic based on the logged-in user. For security reasons this will be filled by api call overlaying session data.
      startAt: `2025-11-01T${workStartHour?.toString().padStart(2, '0')}:00:00+01:00`,
      endAt: `2025-11-01T${workEndHour?.toString().padStart(2, '0')}:00:00+01:00`
    };

    apiService.reportWork(workTime).then(response => {
      if (response.ok) {
        console.log('Praca została zgłoszona pomyślnie.');
      } else {
        console.error('Błąd podczas zgłaszania pracy.');
      }
    }).catch(error => {
      console.error('Błąd sieci:', error);
    });
    console.log('Zgłoszono pracę');
  };

  return <div>
    <p>Witaj {name} {surname}!</p>
    <p>To jest komponent ReportWork.</p>
    <form>
      <label>
        Opis pracy:
        <textarea name="workDescription" />
      </label>
      <br />
      <label>
        Start pracy (godzina):
        <input type="number" name="workStartHour" />
      </label>
      <br />
      <label>
        Koniec pracy (godzina):
        <input type="number" name="workEndHour" />
      </label>
      <br />

      <button type="submit" onClick={report}>Zgłoś pracę</button>
    </form>
  </div>;
}