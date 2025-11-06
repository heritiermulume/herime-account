
// Test if Vue is available
if (typeof Vue !== 'undefined') {
  console.log('Vue is available');
} else {
  console.log('Vue is not available');
}

// Test if the app element exists
const appElement = document.getElementById('app');
if (appElement) {
  console.log('App element found:', appElement);
} else {
  console.log('App element not found');
}

// Test if we can create a simple Vue app
try {
  const { createApp } = Vue;
  const app = createApp({
    template: '<div>Test Vue App</div>'
  });
  app.mount('#app');
  console.log('Vue app mounted successfully');
} catch (error) {
  console.error('Error mounting Vue app:', error);
}
