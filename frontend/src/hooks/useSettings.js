"use client";

import { useQuery, useMutation, useQueryClient } from "@tanstack/react-query";
import { settingService } from "@/services/settingService";
import { toast } from "sonner";

export function useSettings() {
  return useQuery({
    queryKey: ["settings"],
    queryFn: () => settingService.getAll().then((r) => r.data.data),
    // staleTime: 60_000,
  });
}

export function useUpdateSettings(group) {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: (data) => settingService.updateGroup(group, data),
    onSuccess: () => {
      qc.refetchQueries({ queryKey: ["settings"], type: "all" });
      qc.refetchQueries({ queryKey: ["admin-settings"], type: "all" });
      toast.success("Settings saved");
    },
    onError: (err) => toast.error(err.response?.data?.message ?? "Failed to save"),
  });
}

export function useUploadSettingMedia() {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: ({ key, file }) => settingService.uploadMedia(key, file),
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: ["settings"] });
      toast.success("File uploaded");
    },
    onError: (err) => toast.error(err.response?.data?.message ?? "Upload failed"),
  });
}

export function useUploadFile() {
  return useMutation({
    mutationFn: ({ file, folder }) => settingService.uploadFile(file, folder),
    onError: (err) => toast.error(err.response?.data?.message ?? "Upload failed"),
  });
}
