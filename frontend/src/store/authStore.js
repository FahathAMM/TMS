import { create } from "zustand";
import { persist } from "zustand/middleware";

export const useAuthStore = create(
  persist(
    (set, get) => ({
      user: null,
      token: null,
      isAuthenticated: false,
      _hasHydrated: false,

      setHasHydrated: (value) => set({ _hasHydrated: value }),

      setAuth: (user, token) => {
        if (typeof window !== "undefined") {
          localStorage.setItem("pos_token", token);
        }
        set({ user, token, isAuthenticated: true });
      },

      logout: () => {
        if (typeof window !== "undefined") {
          localStorage.removeItem("pos_token");
          localStorage.removeItem("pos_user");
        }
        set({ user: null, token: null, isAuthenticated: false });
      },

      updateUser: (user) => set({ user }),

      isAdmin: () => (get().user?.roles ?? []).includes("admin"),

      // Check a Spatie permission string e.g. "create products"
      can: (permission) => {
        const perms = get().user?.permissions ?? [];
        return perms.includes(permission);
      },

      // Check a Spatie role name
      hasRole: (role) => {
        const roles = get().user?.roles ?? [];
        return roles.includes(role);
      },
    }),
    {
      name: "pos-auth",
      partialize: (state) => ({ user: state.user, token: state.token, isAuthenticated: state.isAuthenticated }),
      onRehydrateStorage: () => (state) => {
        state?.setHasHydrated(true);
      },
    }
  )
);
