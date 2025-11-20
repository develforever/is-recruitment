// POWINNO BYĆ:
import { startStimulusApp } from '@symfony/stimulus-bridge';

export const app = startStimulusApp(
    require.context(
        '@symfony/stimulus-bridge/lazy-controller-loader!./react/controllers',
        true,
        /\.(j|t)sx?$/
    )
);
