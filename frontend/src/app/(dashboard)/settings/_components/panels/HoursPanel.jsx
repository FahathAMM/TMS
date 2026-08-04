"use client";

import { Field } from "../Field";

const DAYS = [
  { key: "mon", label: "Mon" },
  { key: "tue", label: "Tue" },
  { key: "wed", label: "Wed" },
  { key: "thu", label: "Thu" },
  { key: "fri", label: "Fri" },
  { key: "sat", label: "Sat" },
  { key: "sun", label: "Sun" },
];

const TIME_FIELDS = ["hours_opening", "hours_closing", "hours_closed_message"];

export function HoursPanel({ settings, form, onChange }) {
  const activeDays = (() => {
    try { return JSON.parse(form.hours_days || '["mon","tue","wed","thu","fri","sat"]'); }
    catch { return []; }
  })();

  const toggleDay = (day) => {
    const updated = activeDays.includes(day)
      ? activeDays.filter((d) => d !== day)
      : [...activeDays, day];
    onChange("hours_days", JSON.stringify(updated));
  };

  return (
    <div className="space-y-6">
      {/* Day picker */}
      <div>
        <label className="block text-sm font-medium text-gray-700 mb-3">Working Days</label>
        <div className="flex flex-wrap gap-2">
          {DAYS.map(({ key, label }) => (
            <button
              key={key}
              type="button"
              onClick={() => toggleDay(key)}
              className={`px-4 py-2 rounded-lg text-sm font-medium border transition-colors ${
                activeDays.includes(key)
                  ? "bg-primary text-primary-foreground border-primary"
                  : "bg-white text-gray-600 border-gray-300 hover:border-primary/50"
              }`}
            >
              {label}
            </button>
          ))}
        </div>
      </div>

      {/* Time fields */}
      <div className="grid grid-cols-1 sm:grid-cols-2 gap-5">
        {TIME_FIELDS.map((key) => {
          const s = settings?.hours?.find((s) => s.key === key);
          if (!s) return null;
          return (
            <div key={key} className={key === "hours_closed_message" ? "sm:col-span-2" : ""}>
              <label className="block text-sm font-medium text-gray-700 mb-1.5">{s.label}</label>
              <Field setting={s} value={form[key]} onChange={onChange} />
            </div>
          );
        })}

        <div className="sm:col-span-2">
          <label className="flex items-center gap-2 cursor-pointer w-fit">
            <input
              type="checkbox"
              checked={form.hours_is_24h === "1" || form.hours_is_24h === true}
              onChange={(e) => onChange("hours_is_24h", e.target.checked ? "1" : "0")}
              className="w-4 h-4 rounded text-primary"
            />
            <span className="text-sm font-medium text-gray-700">Open 24 Hours</span>
          </label>
        </div>
      </div>
    </div>
  );
}
