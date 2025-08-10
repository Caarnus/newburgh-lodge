import './bootstrap';
import 'quill/dist/quill.snow.css'
import '../css/app.css';
import '../css/quill-overrides.css';

import { createApp, h } from 'vue';
import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { ZiggyVue } from 'ziggy-js';
import PrimeVue from 'primevue/config';
import {Ripple, ToastService, DialogService, ConfirmationService, Tooltip} from "primevue";

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

createInertiaApp({
    title: (title) => `${title} - ${appName}`,
    resolve: (name) => resolvePageComponent(`./Pages/${name}.vue`, import.meta.glob('./Pages/**/*.vue')),
    setup({el, App, props, plugin}) {
        return createApp({render: () => h(App, props)})
            .use(plugin)
            .use(ZiggyVue)
            .use(PrimeVue, {
                theme: 'none',
                ripple: true,
                darkModeSelector: 'dark'
            })
            .use(ToastService)
            .use(DialogService)
            .use(ConfirmationService)
            .directive('ripple', Ripple)
            .directive('tooltip', Tooltip)
            .mount(el);
    },
    progress: {
        color: '#4B5563',
    },
}).then(r => {});
