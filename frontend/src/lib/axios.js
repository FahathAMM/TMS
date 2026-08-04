import axios from "axios";

const api = axios.create({
  baseURL: process.env.NEXT_PUBLIC_API_URL,
  headers: {
    "Content-Type": "application/json",
    Accept: "application/json",
  },
});

api.interceptors.request.use((config) => {
  if (typeof window !== "undefined") {
    const token = localStorage.getItem("pos_token");
    if (token) {
      config.headers.Authorization = `Bearer ${token}`;
    }
  }
  return config;
});

api.interceptors.response.use(
  (response) => response,
  (error) => {
    if (!error.response) {
      // Network error or CORS — attach a readable message so catch blocks can display it
      error.networkError = `Cannot reach API at ${process.env.NEXT_PUBLIC_API_URL}. Is the backend running?`;
    }
    if (error.response?.status === 401 && typeof window !== "undefined") {
      localStorage.removeItem("pos_token");
      localStorage.removeItem("pos_user");
      window.location.href = "/login";
    }
    return Promise.reject(error);
  }
);

export default api;
