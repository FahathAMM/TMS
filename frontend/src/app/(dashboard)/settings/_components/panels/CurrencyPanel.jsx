import { formatCurrency } from "@/lib/utils";

const POSITION_OPTIONS = [
  { value: "left",  label: "Left  ($100)" },
  { value: "right", label: "Right (100$)" },
];

const DECIMAL_OPTIONS = ["0", "1", "2", "3", "4"];

function FieldGroup({ label, hint, children }) {
  return (
    <div>
      <label className="block text-sm font-medium text-gray-700 mb-1">{label}</label>
      {children}
      {hint && <p className="text-xs text-muted-foreground mt-1">{hint}</p>}
    </div>
  );
}

function TextInput({ value, onChange, placeholder, maxLength = 10 }) {
  return (
    <input
      type="text"
      value={value ?? ""}
      onChange={(e) => onChange(e.target.value)}
      placeholder={placeholder}
      maxLength={maxLength}
      className="w-full rounded-lg border border-input bg-background px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-ring"
    />
  );
}

export function CurrencyPanel({ form, onChange }) {
  const preview = formatCurrency(1234567.89, {
    currency_symbol:    form.currency_symbol    || "$",
    currency_position:  form.currency_position  || "left",
    decimal_places:     form.decimal_places     || "2",
    decimal_separator:  form.decimal_separator  || ".",
    thousand_separator: form.thousand_separator || ",",
  });

  return (
    <div className="space-y-6">
      <div>
        <h2 className="text-base font-semibold">Currency Settings</h2>
        <p className="text-sm text-muted-foreground mt-0.5">
          Configure how prices are displayed across the admin panel and POS.
        </p>
      </div>

      {/* Live preview */}
      <div className="rounded-xl border bg-muted/30 px-5 py-4 flex items-center justify-between">
        <div>
          <p className="text-xs font-medium text-muted-foreground uppercase tracking-wide">Preview</p>
          <p className="text-2xl font-bold text-primary mt-0.5">{preview}</p>
        </div>
        <div className="text-right text-xs text-muted-foreground space-y-0.5">
          <p>Code: <span className="font-mono font-medium text-foreground">{form.currency_code || "USD"}</span></p>
          <p>Symbol: <span className="font-mono font-medium text-foreground">{form.currency_symbol || "$"}</span></p>
        </div>
      </div>

      <div className="grid grid-cols-1 sm:grid-cols-2 gap-5">
        <FieldGroup label="Currency Code" hint="ISO 4217 code, e.g. USD, AED, EUR">
          <TextInput
            value={form.currency_code}
            onChange={(v) => onChange("currency_code", v.toUpperCase())}
            placeholder="USD"
            maxLength={5}
          />
        </FieldGroup>

        <FieldGroup label="Currency Symbol" hint="Symbol shown next to amounts, e.g. $, د.إ, €">
          <TextInput
            value={form.currency_symbol}
            onChange={(v) => onChange("currency_symbol", v)}
            placeholder="$"
            maxLength={5}
          />
        </FieldGroup>

        <FieldGroup label="Symbol Position" hint="Where the symbol appears relative to the number">
          <div className="flex gap-3">
            {POSITION_OPTIONS.map(({ value, label }) => (
              <label key={value} className="flex items-center gap-2 cursor-pointer">
                <input
                  type="radio"
                  name="currency_position"
                  value={value}
                  checked={(form.currency_position || "left") === value}
                  onChange={() => onChange("currency_position", value)}
                  className="accent-primary"
                />
                <span className="text-sm">{label}</span>
              </label>
            ))}
          </div>
        </FieldGroup>

        <FieldGroup label="Decimal Places" hint="Number of digits after the decimal point">
          <select
            value={form.decimal_places ?? "2"}
            onChange={(e) => onChange("decimal_places", e.target.value)}
            className="w-full rounded-lg border border-input bg-background px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-ring"
          >
            {DECIMAL_OPTIONS.map((n) => (
              <option key={n} value={n}>{n} decimal{n !== "1" ? "s" : ""}</option>
            ))}
          </select>
        </FieldGroup>

        <FieldGroup label="Decimal Separator" hint='Character between integer and fraction parts (e.g. "." → 1.99)'>
          <select
            value={form.decimal_separator ?? "."}
            onChange={(e) => onChange("decimal_separator", e.target.value)}
            className="w-full rounded-lg border border-input bg-background px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-ring"
          >
            <option value=".">Period  ( 1.99 )</option>
            <option value=",">Comma   ( 1,99 )</option>
          </select>
        </FieldGroup>

        <FieldGroup label="Thousand Separator" hint='Character between groups of three digits (e.g. "," → 1,234)'>
          <select
            value={form.thousand_separator ?? ","}
            onChange={(e) => onChange("thousand_separator", e.target.value)}
            className="w-full rounded-lg border border-input bg-background px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-ring"
          >
            <option value=",">Comma   ( 1,234 )</option>
            <option value=".">Period  ( 1.234 )</option>
            <option value=" ">Space   ( 1 234 )</option>
            <option value="">None    ( 1234 )</option>
          </select>
        </FieldGroup>
      </div>
    </div>
  );
}
