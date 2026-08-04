import { Field } from "../Field";

const FIELDS = [
  "contact_phone",
  "contact_mobile",
  "contact_whatsapp",
  "contact_email",
  "support_email",
];

export function ContactPanel({ settings, form, onChange }) {
  return (
    <div className="grid grid-cols-1 sm:grid-cols-2 gap-5">
      {FIELDS.map((key) => {
        const s = settings?.contact?.find((s) => s.key === key);
        if (!s) return null;
        return (
          <div key={key}>
            <label className="block text-sm font-medium text-gray-700 mb-1.5">{s.label}</label>
            <Field setting={s} value={form[key]} onChange={onChange} />
          </div>
        );
      })}
    </div>
  );
}
