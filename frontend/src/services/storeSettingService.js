import api from "@/lib/axios";

export const storeSettingService = {
  getPublic: () => api.get("/settings"),
};
