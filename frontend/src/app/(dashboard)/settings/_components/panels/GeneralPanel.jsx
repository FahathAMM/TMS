import { Field } from "../Field";

const FIELDS = [
  "store_name",
  "store_tagline",
  "store_description",
  "tax_number",
  "business_reg",
  "new_arrivals_days",
];

export function GeneralPanel({ settings, form, onChange }) {
  return (
    <div className="grid grid-cols-1 sm:grid-cols-2 gap-5">
      {FIELDS.map((key) => {
        const s = settings?.general?.find((s) => s.key === key);
        if (!s) return null;
        return (
          <div key={key} className={s.type === "textarea" ? "sm:col-span-2" : ""}>
            <label className="block text-sm font-medium text-gray-700 mb-1.5">{s.label}</label>
            <Field setting={s} value={form[key]} onChange={onChange} />
          </div>
        );
      })}
    </div>
  );
}
