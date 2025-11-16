
// Test basic functionality
document.addEventListener('DOMContentLoaded', function() {
    
    const appElement = document.getElementById('app');
    if (appElement) {
        appElement.innerHTML = `
            <div style="padding: 20px; text-align: center;">
                <h1>HERIME SSO - Test Page</h1>
                <p>Si vous voyez ce message, le JavaScript fonctionne.</p>
                <p>L'application Vue.js ne se charge pas correctement.</p>
                <button onclick="alert('JavaScript fonctionne!')">Test Button</button>
            </div>
        `;
    } else {
    }
});
