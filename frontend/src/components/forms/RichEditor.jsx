"use client";

import { useEditor, EditorContent } from "@tiptap/react";
import StarterKit from "@tiptap/starter-kit";
import Underline from "@tiptap/extension-underline";
import Placeholder from "@tiptap/extension-placeholder";
import { useEffect } from "react";
import { cn } from "@/lib/utils";
import {
  Bold, Italic, Underline as UnderlineIcon, Strikethrough,
  Heading2, Heading3, List, ListOrdered, Quote, Code2,
  Undo2, Redo2, Minus,
} from "lucide-react";

function ToolbarButton({ onClick, active, disabled, title, children }) {
  return (
    <button
      type="button"
      onMouseDown={(e) => { e.preventDefault(); onClick(); }}
      disabled={disabled}
      title={title}
      className={cn(
        "p-1.5 rounded text-sm transition-colors",
        active
          ? "bg-primary text-primary-foreground"
          : "text-muted-foreground hover:bg-muted hover:text-foreground",
        disabled && "opacity-30 pointer-events-none",
      )}
    >
      {children}
    </button>
  );
}

function Divider() {
  return <span className="w-px h-5 bg-border mx-0.5 shrink-0" />;
}

function Toolbar({ editor }) {
  if (!editor) return null;

  const btn = (action, active, title, icon, disabled = false) => (
    <ToolbarButton onClick={action} active={active} disabled={disabled} title={title}>
      {icon}
    </ToolbarButton>
  );

  return (
    <div className="flex flex-wrap items-center gap-0.5 px-2 py-1.5 border-b bg-muted/30">
      {btn(() => editor.chain().focus().toggleBold().run(),
        editor.isActive("bold"), "Bold", <Bold className="h-3.5 w-3.5" />)}
      {btn(() => editor.chain().focus().toggleItalic().run(),
        editor.isActive("italic"), "Italic", <Italic className="h-3.5 w-3.5" />)}
      {btn(() => editor.chain().focus().toggleUnderline().run(),
        editor.isActive("underline"), "Underline", <UnderlineIcon className="h-3.5 w-3.5" />)}
      {btn(() => editor.chain().focus().toggleStrike().run(),
        editor.isActive("strike"), "Strikethrough", <Strikethrough className="h-3.5 w-3.5" />)}

      <Divider />

      {btn(() => editor.chain().focus().toggleHeading({ level: 2 }).run(),
        editor.isActive("heading", { level: 2 }), "Heading 2", <Heading2 className="h-3.5 w-3.5" />)}
      {btn(() => editor.chain().focus().toggleHeading({ level: 3 }).run(),
        editor.isActive("heading", { level: 3 }), "Heading 3", <Heading3 className="h-3.5 w-3.5" />)}

      <Divider />

      {btn(() => editor.chain().focus().toggleBulletList().run(),
        editor.isActive("bulletList"), "Bullet List", <List className="h-3.5 w-3.5" />)}
      {btn(() => editor.chain().focus().toggleOrderedList().run(),
        editor.isActive("orderedList"), "Numbered List", <ListOrdered className="h-3.5 w-3.5" />)}

      <Divider />

      {btn(() => editor.chain().focus().toggleBlockquote().run(),
        editor.isActive("blockquote"), "Blockquote", <Quote className="h-3.5 w-3.5" />)}
      {btn(() => editor.chain().focus().toggleCode().run(),
        editor.isActive("code"), "Inline Code", <Code2 className="h-3.5 w-3.5" />)}
      {btn(() => editor.chain().focus().setHorizontalRule().run(),
        false, "Horizontal Rule", <Minus className="h-3.5 w-3.5" />)}

      <Divider />

      {btn(() => editor.chain().focus().undo().run(),
        false, "Undo", <Undo2 className="h-3.5 w-3.5" />, !editor.can().undo())}
      {btn(() => editor.chain().focus().redo().run(),
        false, "Redo", <Redo2 className="h-3.5 w-3.5" />, !editor.can().redo())}
    </div>
  );
}

export function RichEditor({ value = "", onChange, placeholder = "Write something…", error, minHeight = 180 }) {
  const editor = useEditor({
    extensions: [
      StarterKit,
      Underline,
      Placeholder.configure({ placeholder }),
    ],
    content: value,
    onUpdate: ({ editor }) => {
      onChange?.(editor.getHTML());
    },
    editorProps: {
      attributes: {
        class: "outline-none min-h-0 px-3 py-2.5 text-sm leading-relaxed rich-content",
      },
    },
  });

  // Sync external value when the edit page loads pre-filled server data.
  // Skip if value is empty/whitespace — the empty editor already emits <p></p>.
  useEffect(() => {
    if (!editor || !value?.trim()) return;
    if (value !== editor.getHTML()) {
      editor.commands.setContent(value, false);
    }
  }, [value, editor]);

  return (
    <div className={cn("rounded-md border bg-background", error && "border-destructive")}>
      <Toolbar editor={editor} />
      <div style={{ minHeight }}>
        <EditorContent editor={editor} />
      </div>
      {error && <p className="px-3 pb-2 text-xs text-destructive">{error}</p>}
    </div>
  );
}
