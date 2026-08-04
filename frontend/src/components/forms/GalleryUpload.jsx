"use client";

import { useRef, useState, useCallback } from "react";
import Image from "next/image";
import { Button } from "@/components/ui/button";
import { Label } from "@/components/ui/label";
import { cn } from "@/lib/utils";
import { Upload, X, Star, GripVertical, Loader2, ImageIcon } from "lucide-react";

/**
 * GalleryUpload manages two image lists:
 *  - existingImages: server media objects { id, file_url, is_primary, sort_order }
 *  - pendingFiles:   File objects queued for upload after form submit
 *
 * All mutation callbacks are invoked from outside (parent handles API calls).
 */
export function GalleryUpload({
  existingImages = [],
  pendingFiles = [],
  onAddFiles,
  onRemoveExisting,
  onRemovePending,
  onReorderExisting,
  onSetPrimary,
  uploadingCount = 0,
  error,
}) {
  const inputRef = useRef(null);
  const [dragOverId, setDragOverId] = useState(null);
  const [dragSourceId, setDragSourceId] = useState(null);
  const [dropZoneActive, setDropZoneActive] = useState(false);

  // ── Pending file previews ────────────────────────────────────────────────
  const pendingPreviews = pendingFiles.map((file, idx) => ({
    idx,
    url: URL.createObjectURL(file),
    name: file.name,
  }));

  // ── File picker / drop zone ──────────────────────────────────────────────
  const handleFiles = useCallback(
    (fileList) => {
      const accepted = Array.from(fileList).filter((f) =>
        f.type.startsWith("image/") || f.type.startsWith("video/")
      );
      if (accepted.length > 0) onAddFiles(accepted);
    },
    [onAddFiles]
  );

  const handleDrop = (e) => {
    e.preventDefault();
    setDropZoneActive(false);
    handleFiles(e.dataTransfer.files);
  };

  // ── Drag-to-reorder existing ─────────────────────────────────────────────
  const handleDragStart = (e, id) => {
    setDragSourceId(id);
    e.dataTransfer.effectAllowed = "move";
  };

  const handleDragOverCard = (e, id) => {
    e.preventDefault();
    setDragOverId(id);
  };

  const handleDropCard = (e, targetId) => {
    e.preventDefault();
    setDragOverId(null);
    if (dragSourceId === targetId) return;

    const ids = existingImages.map((m) => m.id);
    const fromIdx = ids.indexOf(dragSourceId);
    const toIdx = ids.indexOf(targetId);
    if (fromIdx === -1 || toIdx === -1) return;

    const reordered = [...ids];
    reordered.splice(fromIdx, 1);
    reordered.splice(toIdx, 0, dragSourceId);
    onReorderExisting(reordered);
    setDragSourceId(null);
  };

  const handleDragEnd = () => {
    setDragOverId(null);
    setDragSourceId(null);
  };

  const totalCount = existingImages.length + pendingFiles.length;

  return (
    <div className="space-y-2">
      <div className="flex items-center justify-between">
        <Label>Gallery Images</Label>
        {totalCount > 0 && (
          <span className="text-xs text-muted-foreground">{totalCount} image{totalCount !== 1 ? "s" : ""}</span>
        )}
      </div>

      {/* Grid of images */}
      {totalCount > 0 && (
        <div className="grid grid-cols-3 gap-2">
          {/* Existing images (draggable) */}
          {existingImages.map((media) => (
            <div
              key={media.id}
              draggable
              onDragStart={(e) => handleDragStart(e, media.id)}
              onDragOver={(e) => handleDragOverCard(e, media.id)}
              onDrop={(e) => handleDropCard(e, media.id)}
              onDragEnd={handleDragEnd}
              className={cn(
                "group relative aspect-square rounded-lg overflow-hidden border bg-muted cursor-grab",
                dragOverId === media.id && dragSourceId !== media.id && "ring-2 ring-primary",
                dragSourceId === media.id && "opacity-50"
              )}
            >
              <Image
                src={media.file_url}
                alt={media.alt ?? "Gallery image"}
                fill
                className="object-cover"
                unoptimized
              />

              {/* Primary badge */}
              {media.is_primary && (
                <span className="absolute top-1 left-1 bg-amber-400 text-amber-900 text-[10px] font-semibold px-1.5 py-0.5 rounded-full leading-none">
                  Primary
                </span>
              )}

              {/* Hover overlay with actions */}
              <div className="absolute inset-0 bg-black/50 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center gap-1">
                {/* Drag handle hint */}
                <div className="absolute top-1 right-1 text-white/70">
                  <GripVertical className="h-3.5 w-3.5" />
                </div>

                {!media.is_primary && onSetPrimary && (
                  <Button
                    type="button"
                    size="icon"
                    variant="ghost"
                    className="h-7 w-7 text-amber-300 hover:text-amber-200 hover:bg-white/10"
                    onClick={() => onSetPrimary(media.id)}
                    title="Set as primary"
                  >
                    <Star className="h-3.5 w-3.5" />
                  </Button>
                )}

                <Button
                  type="button"
                  size="icon"
                  variant="ghost"
                  className="h-7 w-7 text-red-300 hover:text-red-200 hover:bg-white/10"
                  onClick={() => onRemoveExisting(media.id)}
                  title="Remove image"
                >
                  <X className="h-3.5 w-3.5" />
                </Button>
              </div>
            </div>
          ))}

          {/* Pending (new) images */}
          {pendingPreviews.map(({ idx, url, name }) => (
            <div
              key={`pending-${idx}`}
              className="group relative aspect-square rounded-lg overflow-hidden border-2 border-dashed border-primary/50 bg-muted"
            >
              <Image src={url} alt={name} fill className="object-cover" unoptimized />

              {/* "New" badge */}
              <span className="absolute top-1 left-1 bg-primary text-primary-foreground text-[10px] font-semibold px-1.5 py-0.5 rounded-full leading-none">
                New
              </span>

              <div className="absolute inset-0 bg-black/50 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center">
                <Button
                  type="button"
                  size="icon"
                  variant="ghost"
                  className="h-7 w-7 text-red-300 hover:text-red-200 hover:bg-white/10"
                  onClick={() => onRemovePending(idx)}
                  title="Remove"
                >
                  <X className="h-3.5 w-3.5" />
                </Button>
              </div>
            </div>
          ))}
        </div>
      )}

      {/* Upload drop zone */}
      <div
        className={cn(
          "border-2 border-dashed rounded-lg p-4 text-center cursor-pointer transition-colors",
          dropZoneActive ? "border-primary bg-primary/5" : "border-border hover:border-primary/60",
          error && "border-destructive"
        )}
        onClick={() => inputRef.current?.click()}
        onDragOver={(e) => { e.preventDefault(); setDropZoneActive(true); }}
        onDragLeave={() => setDropZoneActive(false)}
        onDrop={handleDrop}
      >
        {uploadingCount > 0 ? (
          <div className="flex flex-col items-center gap-1.5 py-2">
            <Loader2 className="h-6 w-6 animate-spin text-primary" />
            <p className="text-xs text-muted-foreground">Uploading {uploadingCount} image{uploadingCount > 1 ? "s" : ""}…</p>
          </div>
        ) : (
          <div className="flex flex-col items-center gap-1.5 py-2">
            <div className="flex items-center gap-1.5 text-muted-foreground">
              <Upload className="h-4 w-4" />
              <ImageIcon className="h-4 w-4" />
            </div>
            <p className="text-xs text-muted-foreground">
              Click or drag &amp; drop images here
            </p>
            <p className="text-[11px] text-muted-foreground/60">
              JPG, PNG, WebP, GIF — multiple supported
            </p>
          </div>
        )}
      </div>

      <input
        ref={inputRef}
        type="file"
        accept="image/*,video/*"
        multiple
        className="hidden"
        onChange={(e) => handleFiles(e.target.files)}
      />

      {error && <p className="text-xs text-destructive">{error}</p>}
    </div>
  );
}
