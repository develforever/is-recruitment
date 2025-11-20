import * as React from 'react';

type Props = {
  name?: string;
};

export default function Hello({ name = 'Świecie' }: Props) {
  return <div>Witaj {name} z React + Symfony UX 🎉</div>;
}