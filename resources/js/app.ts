import { createPinia } from 'pinia';
import { createApp, h } from 'vue';

import App from './App.vue';
import './bootstrap';
import router from './router';

createApp({ render: () => h(App) })
    .use(createPinia())
    .use(router)
    .mount('#app');
