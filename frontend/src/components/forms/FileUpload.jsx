"use client";

import { useRef, useState } from "react";
import { Label } from "@/components/ui/label";
import { Button } from "@/components/ui/button";
import { Upload, X } from "lucide-react";
import Image from "next/image";
import { cn } from "@/lib/utils";

export function FileUpload({ label, error, value, onChange, accept = "image/*", className }) {
  const inputRef = useRef(null);
  const [preview, setPreview] = useState(typeof value === "string" ? value : null);

  const handleFile = (file) => {
    if (!file) return;
    setPreview(URL.createObjectURL(file));
    onChange(file);
  };

  const handleClear = () => {
    setPreview(null);
    onChange(null);
    if (inputRef.current) inputRef.current.value = "";
  };

  return (
    <div className={cn("space-y-1", className)}>
      {label && <Label>{label}</Label>}
      <div
        className={cn(
          "border-2 border-dashed rounded-lg p-4 text-center cursor-pointer hover:border-primary transition-colors",
          error && "border-destructive"
        )}
        onClick={() => inputRef.current?.click()}
        onDragOver={(e) => e.preventDefault()}
        onDrop={(e) => { e.preventDefault(); handleFile(e.dataTransfer.files[0]); }}
      >
        {preview ? (
          <div className="relative">
            <div className="relative w-32 h-32 mx-auto">
              <Image src={preview} alt="Preview" fill className="object-contain rounded" />
            </div>
            <Button
              type="button"
              variant="destructive"
              size="icon"
              className="absolute top-0 right-0 h-6 w-6"
              onClick={(e) => { e.stopPropagation(); handleClear(); }}
            >
              <X className="h-3 w-3" />
            </Button>
          </div>
        ) : (
          <div className="flex flex-col items-center gap-2 py-4">
            <Upload className="h-8 w-8 text-muted-foreground" />
            <p className="text-sm text-muted-foreground">Click or drag to upload</p>
          </div>
        )}
      </div>
      <input
        ref={inputRef}
        type="file"
        accept={accept}
        className="hidden"
        onChange={(e) => handleFile(e.target.files[0])}
      />
      {error && <p className="text-xs text-destructive">{error}</p>}
    </div>
  );
}
