import axios from 'axios';
window.axios = axios;

// --- Axios Global Configuration (WITHOUT INTERCEPTOR) ---
// Set the base URL for all API requests. That's it.
window.axios.defaults.baseURL = '/api/v1/';
window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
window.axios.defaults.headers.common['Accept'] = 'application/json';

console.log("Global Axios instance created without interceptors.");