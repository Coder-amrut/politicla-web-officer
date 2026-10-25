/**
 * Token Storage Utility for Worker Portal
 * Manages client-side storage of the Laravel Sanctum bearer token.
 */

const TOKEN_KEY = 'worker_auth_token';

export const getToken = () => {
  try {
    return localStorage.getItem(TOKEN_KEY);
  } catch (error) {
    console.error('Error reading auth token from storage:', error);
    return null;
  }
};

export const setToken = (token) => {
  try {
    if (token) {
      localStorage.setItem(TOKEN_KEY, token);
    }
  } catch (error) {
    console.error('Error saving auth token to storage:', error);
  }
};

export const removeToken = () => {
  try {
    localStorage.removeItem(TOKEN_KEY);
  } catch (error) {
    console.error('Error removing auth token from storage:', error);
  }
};

export const hasToken = () => {
  return Boolean(getToken());
};
