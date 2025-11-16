
// Simple Vue app without complex imports
document.addEventListener('DOMContentLoaded', function() {
    
    // Check if Vue is available globally
    if (typeof Vue === 'undefined') {
        return;
    }
    
    try {
        const { createApp } = Vue;
        
        const app = createApp({
            data() {
                return {
                    message: 'HERIME SSO - Simple Vue App',
                    user: null,
                    loading: true
                }
            },
            mounted() {
                this.loading = false;
                
                // Simulate user data
                this.user = {
                    name: 'Test User',
                    email: 'test@example.com'
                };
            },
            template: `
                <div style="padding: 20px; font-family: Arial, sans-serif;">
                    <h1 style="color: #0ea5e9;">{{ message }}</h1>
                    <div v-if="loading" style="text-align: center;">
                        <p>Chargement...</p>
                    </div>
                    <div v-else>
                        <p>Bienvenue, {{ user.name }}!</p>
                        <p>Email: {{ user.email }}</p>
                        <button @click="logout" style="padding: 10px 20px; background: #ef4444; color: white; border: none; border-radius: 5px; cursor: pointer;">Déconnexion</button>
                    </div>
                </div>
            `,
            methods: {
                logout() {
                    alert('Déconnexion simulée');
                    this.user = null;
                }
            }
        });
        
        app.mount('#app');
        
    } catch (error) {
        
        const appElement = document.getElementById('app');
        if (appElement) {
            appElement.innerHTML = `
                <div style="padding: 20px; text-align: center; font-family: Arial, sans-serif;">
                    <h1 style="color: #ef4444;">Erreur Vue</h1>
                    <p>Erreur: ${error.message}</p>
                </div>
            `;
        }
    }
});
