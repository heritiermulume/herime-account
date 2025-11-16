import './bootstrap';
import { createApp } from 'vue';
import { createPinia } from 'pinia';
import App from './components/App.vue';
import router from './router';
import { setRouter } from './bootstrap';



try {
    const app = createApp(App);

    app.use(createPinia());
    
    app.use(router);

    app.mount('#app');
} catch (error) {
    // Fallback: show error message
    const appElement = document.getElementById('app');
    if (appElement) {
        appElement.innerHTML = `
            <div style="padding: 20px; text-align: center; font-family: Arial, sans-serif;">
                <h1 style="color: #ef4444;">Erreur de chargement</h1>
                <p>L'application Vue.js n'a pas pu se charger.</p>
                <p>Erreur: ${error.message}</p>
                <pre style="text-align: left; background: #f5f5f5; padding: 10px; border-radius: 5px; overflow: auto;">${error.stack}</pre>
                <button onclick="location.reload()" style="padding: 10px 20px; background: #0ea5e9; color: white; border: none; border-radius: 5px; cursor: pointer; margin-top: 10px;">Recharger</button>
            </div>
        `;
    }
}
