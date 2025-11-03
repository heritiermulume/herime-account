console.log('Simple app starting...');

// Test basic functionality
document.addEventListener('DOMContentLoaded', function() {
    console.log('DOM loaded in simple app');
    
    const appElement = document.getElementById('app');
    if (appElement) {
        console.log('App element found, mounting simple content');
        appElement.innerHTML = `
            <div style="padding: 20px; text-align: center;">
                <h1>HERIME SSO - Test Page</h1>
                <p>Si vous voyez ce message, le JavaScript fonctionne.</p>
                <p>L'application Vue.js ne se charge pas correctement.</p>
                <button onclick="alert('JavaScript fonctionne!')">Test Button</button>
            </div>
        `;
    } else {
        console.error('App element not found');
    }
});
