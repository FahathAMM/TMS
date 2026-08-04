import api from "@/lib/axios";

export const settingService = {
  getAll: () => api.get("/settings"),
  updateGroup: (group, data) => api.put(`/settings/${group}`, data),
  uploadMedia: (key, file) => {
    const fd = new FormData();
    fd.append("key", key);
    fd.append("file", file);
    return api.post("/settings/media", fd, {
      headers: { "Content-Type": "multipart/form-data" },
    });
  },
  uploadFile: (file, folder = "general") => {
    const fd = new FormData();
    fd.append("file", file);
    fd.append("folder", folder);
    return api.post("/settings/upload", fd, {
      headers: { "Content-Type": "multipart/form-data" },
    });
  },
};
