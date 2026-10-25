import axios from 'axios';
import { getToken, removeToken } from '../utils/tokenStorage';

/**
 * Centralized Axios instance for Worker Portal.
 * Uses Sanctum Bearer token authentication.
 */
const apiClient = axios.create({
  baseURL: import.meta.env.VITE_API_BASE_URL || 'http://127.0.0.1:8000/api',
  headers: {
    'Accept': 'application/json',
    'Content-Type': 'application/json',
  },
  timeout: 15000,
});

// Request Interceptor: Attach Sanctum bearer token to outgoing requests
apiClient.interceptors.request.use(
  (config) => {
    const token = getToken();
    if (token) {
      config.headers.Authorization = `Bearer ${token}`;
    }
    return config;
  },
  (error) => {
    return Promise.reject(error);
  }
);

// Response Interceptor: Normalized error extraction
apiClient.interceptors.response.use(
  (response) => {
    return response;
  },
  (error) => {
    const status = error.response ? error.response.status : null;
    const responseData = error.response ? error.response.data : null;

    const parsedError = {
      status,
      message: 'An unexpected error occurred. Please try again.',
      errors: null,
      raw: error,
    };

    if (responseData) {
      if (responseData.message) {
        parsedError.message = responseData.message;
      }
      if (responseData.errors) {
        parsedError.errors = responseData.errors;
      }
    } else if (error.message) {
      parsedError.message = error.message;
    }

    if (status === 401) {
      if (getToken()) {
        removeToken();
        window.dispatchEvent(new CustomEvent('auth:unauthorized'));
      }
    }

    return Promise.reject(parsedError);
  }
);

export default apiClient;
