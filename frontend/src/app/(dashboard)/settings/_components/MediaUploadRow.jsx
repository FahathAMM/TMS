"use client";

import { useRef } from "react";
import { Upload, Loader2 } from "lucide-react";
import { useUploadSettingMedia } from "@/hooks/useSettings";

export function MediaUploadRow({ setting, value, onChange }) {
  const { mutate: upload, isPending } = useUploadSettingMedia();
  const inputRef = useRef(null);

  const handleFile = (e) => {
    const file = e.target.files?.[0];
    if (!file) return;
    upload(
      { key: setting.key, file },
      { onSuccess: (res) => onChange(setting.key, res.data.data.url) }
    );
    e.target.value = "";
  };

  return (
    <div className="space-y-2">
      {value && (
        <div className="w-20 h-20 rounded-lg border overflow-hidden bg-gray-50">
          <img src={value} alt={setting.label} className="w-full h-full object-contain p-1" />
        </div>
      )}
      <div className="flex items-center gap-2">
        <input
          type="text"
          value={value ?? ""}
          onChange={(e) => onChange(setting.key, e.target.value)}
          className="flex-1 px-3 py-2 border rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary transition-colors"
          placeholder="Paste URL or upload file"
        />
        <button
          type="button"
          onClick={() => inputRef.current?.click()}
          disabled={isPending}
          className="flex items-center gap-1.5 px-3 py-2 border rounded-lg text-sm hover:bg-muted transition-colors disabled:opacity-50"
        >
          {isPending ? <Loader2 className="h-4 w-4 animate-spin" /> : <Upload className="h-4 w-4" />}
          Upload
        </button>
        <input ref={inputRef} type="file" accept="image/*,.ico,.svg" className="hidden" onChange={handleFile} />
      </div>
    </div>
  );
}
