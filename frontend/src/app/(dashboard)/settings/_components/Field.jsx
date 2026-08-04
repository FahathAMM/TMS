// Shared field primitives used across all setting panels

export function Field({ setting, value, onChange }) {
  if (!setting) return null;

  const base =
    "w-full px-3 py-2 border rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary transition-colors bg-white";

  if (setting.type === "textarea") {
    return (
      <textarea
        rows={3}
        value={value ?? ""}
        onChange={(e) => onChange(setting.key, e.target.value)}
        className={`${base} resize-y min-h-[80px]`}
        placeholder={setting.label}
      />
    );
  }

  const inputType =
    setting.type === "email"  ? "email"
    : setting.type === "url"  ? "url"
    : setting.type === "time" ? "time"
    : setting.type === "number" ? "number"
    : "text";

  return (
    <input
      type={inputType}
      value={value ?? ""}
      onChange={(e) => onChange(setting.key, e.target.value)}
      className={base}
      placeholder={setting.label}
    />
  );
}
