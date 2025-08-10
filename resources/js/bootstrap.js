import axios from 'axios';
window.axios = axios;

// Always send AJAX header
window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
// Prefer JSON responses
window.axios.defaults.headers.common['Accept'] = 'application/json';
// Include cookies on same-origin requests (safe; cross-site not used)
window.axios.defaults.withCredentials = true;

// Configure Axios to use Laravel's XSRF cookie/header pair as a fallback
window.axios.defaults.xsrfCookieName = 'XSRF-TOKEN';
window.axios.defaults.xsrfHeaderName = 'X-XSRF-TOKEN';

// Read CSRF token from meta tag injected by Blade and attach to all requests
const csrfMeta = document.querySelector('meta[name="csrf-token"]');
if (csrfMeta && csrfMeta.content) {
  window.axios.defaults.headers.common['X-CSRF-TOKEN'] = csrfMeta.content;
}
