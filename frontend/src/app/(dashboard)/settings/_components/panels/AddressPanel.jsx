import { ExternalLink, MapPin, Link, LayoutTemplate, EyeOff } from "lucide-react";
import { Field } from "../Field";

const ADDRESS_FIELDS = [
  "address_line_1",
  "address_line_2",
  "city",
  "state",
  "country",
  "postal_code",
];

const MAP_OPTIONS = [
  { value: "none",  label: "None",        Icon: EyeOff,        desc: "Hide map on contact page"     },
  { value: "embed", label: "Embed",        Icon: LayoutTemplate, desc: "Show Google Maps iframe"     },
  { value: "link",  label: "Link",         Icon: Link,           desc: "Show a clickable map button" },
];

export function AddressPanel({ settings, form, onChange }) {
  const mapType = form.map_type || "none";

  return (
    <div className="space-y-6">
      {/* Address fields */}
      <div className="grid grid-cols-1 sm:grid-cols-2 gap-5">
        {ADDRESS_FIELDS.map((key) => {
          const s = settings?.address?.find((s) => s.key === key);
          if (!s) return null;
          return (
            <div key={key} className={key === "address_line_1" ? "sm:col-span-2" : ""}>
              <label className="block text-sm font-medium text-gray-700 mb-1.5">{s.label}</label>
              <Field setting={s} value={form[key]} onChange={onChange} />
            </div>
          );
        })}
      </div>

      {/* Map section */}
      <div className="border rounded-xl p-5 space-y-4">
        <div className="flex items-center gap-2">
          <div className="w-7 h-7 rounded-lg bg-primary/10 flex items-center justify-center">
            <MapPin className="h-3.5 w-3.5 text-primary" />
          </div>
          <div>
            <p className="text-sm font-semibold text-gray-800">Map on Contact Page</p>
            <p className="text-xs text-gray-500">Choose how the map appears on your store</p>
          </div>
        </div>

        {/* Type picker */}
        <div className="grid grid-cols-3 gap-3">
          {MAP_OPTIONS.map(({ value, label, Icon, desc }) => (
            <button
              key={value}
              type="button"
              onClick={() => onChange("map_type", value)}
              className={`flex flex-col items-center gap-1.5 p-3 rounded-xl border-2 text-center transition-all ${
                mapType === value
                  ? "border-primary bg-primary/5 text-primary"
                  : "border-gray-200 text-gray-500 hover:border-gray-300 hover:bg-gray-50"
              }`}
            >
              <Icon className="h-5 w-5" />
              <span className="text-xs font-semibold">{label}</span>
              <span className="text-[10px] leading-tight text-gray-400">{desc}</span>
            </button>
          ))}
        </div>

        {/* Conditional input */}
        {mapType === "embed" && (
          <div>
            <label className="block text-sm font-medium text-gray-700 mb-1.5">
              Google Maps Embed Code
            </label>
            <p className="text-xs text-gray-400 mb-2">
              Go to Google Maps → Share → Embed a map → copy the full <code>&lt;iframe&gt;</code> tag or just the src URL.
            </p>
            <textarea
              rows={4}
              value={form.google_map_embed ?? ""}
              onChange={(e) => onChange("google_map_embed", e.target.value)}
              placeholder='<iframe src="https://www.google.com/maps/embed?..." ...></iframe>'
              className="w-full px-3 py-2 border rounded-lg text-sm font-mono focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary transition-colors bg-white resize-y"
            />
          </div>
        )}

        {mapType === "link" && (
          <div>
            <label className="block text-sm font-medium text-gray-700 mb-1.5">
              Google Maps URL
            </label>
            <p className="text-xs text-gray-400 mb-2">
              Go to Google Maps → Share → Copy link. Paste it here.
            </p>
            <input
              type="url"
              value={form.google_map_url ?? ""}
              onChange={(e) => onChange("google_map_url", e.target.value)}
              placeholder="https://maps.google.com/..."
              className="w-full px-3 py-2 border rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary transition-colors bg-white"
            />
            {form.google_map_url && (
              <a
                href={form.google_map_url}
                target="_blank"
                rel="noopener noreferrer"
                className="mt-1.5 inline-flex items-center gap-1 text-xs text-primary hover:underline"
              >
                <ExternalLink className="h-3 w-3" /> Preview link
              </a>
            )}
          </div>
        )}
      </div>
    </div>
  );
}
