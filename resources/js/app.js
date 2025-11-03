console.log('Starting app.js...');

// Wrap everything in an async function
(async function() {
    try {
        console.log('Importing bootstrap...');
        await import('./bootstrap');
        
        console.log('Importing Vue...');
        const { createApp } = await import('vue');
        console.log('Vue imported:', createApp);
        
        console.log('Importing Pinia...');
        const { createPinia } = await import('pinia');
        console.log('Pinia imported:', createPinia);
        
        console.log('Importing App component...');
        const App = await import('./components/App.vue');
        console.log('App component imported:', App);
        
        console.log('Importing router...');
        const router = await import('./router');
        console.log('Router imported:', router);
        
        console.log('Importing axios...');
        const axios = await import('axios');
        console.log('Axios imported:', axios);

        console.log('All dependencies imported successfully');

        console.log('Creating Vue app...');
        // Create Vue app
        const app = createApp(App.default);

        console.log('Adding plugins...');
        // Use plugins
        app.use(createPinia());
        app.use(router.default);

        console.log('Mounting app...');
        // Mount the app
        app.mount('#app');
        
        console.log('App mounted successfully!');
    } catch (error) {
        console.error('Error in app.js:', error);
        
        // Fallback: show error message
        const appElement = document.getElementById('app');
        if (appElement) {
            appElement.innerHTML = `
                <div style="padding: 20px; text-align: center; font-family: Arial, sans-serif;">
                    <h1 style="color: #ef4444;">Erreur de chargement</h1>
                    <p>L'application Vue.js n'a pas pu se charger.</p>
                    <p>Erreur: ${error.message}</p>
                    <button onclick="location.reload()" style="padding: 10px 20px; background: #0ea5e9; color: white; border: none; border-radius: 5px; cursor: pointer;">Recharger</button>
                </div>
            `;
        }
    }
})();
