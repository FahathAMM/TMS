"use client";

import { useState } from "react";
import { Label } from "@/components/ui/label";
import { Badge } from "@/components/ui/badge";
import { cn } from "@/lib/utils";
import { Search } from "lucide-react";
import { Input } from "@/components/ui/input";

/**
 * TagSelector — displays all available tags as toggleable badges.
 * selected: number[]  IDs of selected tags
 * onChange:  (ids: number[]) => void
 */
export function TagSelector({ label = "Tags", tags = [], selected = [], onChange, error }) {
  const [search, setSearch] = useState("");

  const filtered = search.trim()
    ? tags.filter((t) => t.name.toLowerCase().includes(search.toLowerCase()))
    : tags;

  const toggle = (id) => {
    const numId = Number(id);
    onChange(
      selected.includes(numId)
        ? selected.filter((s) => s !== numId)
        : [...selected, numId]
    );
  };

  return (
    <div className="space-y-2">
      {label && <Label>{label}</Label>}

      {tags.length > 8 && (
        <div className="relative">
          <Search className="absolute left-2.5 top-2.5 h-3.5 w-3.5 text-muted-foreground" />
          <Input
            type="text"
            placeholder="Search tags…"
            value={search}
            onChange={(e) => setSearch(e.target.value)}
            className="h-8 pl-8 text-sm"
          />
        </div>
      )}

      {filtered.length === 0 ? (
        <p className="text-xs text-muted-foreground py-2">
          {search ? "No tags match your search." : "No tags available."}
        </p>
      ) : (
        <div className="flex flex-wrap gap-1.5 max-h-36 overflow-y-auto py-0.5 pr-1">
          {filtered.map((tag) => {
            const isSelected = selected.includes(Number(tag.id));
            return (
              <button
                key={tag.id}
                type="button"
                onClick={() => toggle(tag.id)}
                className={cn(
                  "inline-flex items-center rounded-full border px-2.5 py-0.5 text-xs font-medium transition-colors",
                  isSelected
                    ? "border-transparent bg-primary text-primary-foreground hover:bg-primary/80"
                    : "border-border text-muted-foreground hover:border-primary/60 hover:text-foreground"
                )}
              >
                {tag.name}
              </button>
            );
          })}
        </div>
      )}

      {selected.length > 0 && (
        <p className="text-xs text-muted-foreground">
          {selected.length} tag{selected.length !== 1 ? "s" : ""} selected
        </p>
      )}

      {error && <p className="text-xs text-destructive">{error}</p>}
    </div>
  );
}
