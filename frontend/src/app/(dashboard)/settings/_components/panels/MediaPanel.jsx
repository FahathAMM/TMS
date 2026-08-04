import { Field } from "../Field";
import { MediaUploadRow } from "../MediaUploadRow";

const MEDIA_FIELDS = ["media_logo", "media_favicon", "media_og_image"];

export function MediaPanel({ settings, form, onChange }) {
  const logoUrl    = form["media_logo"];
  const logoWidth  = form["logo_width"]  || "120";
  const logoHeight = form["logo_height"] || "40";

  return (
    <div className="space-y-6">
      {/* Logo upload */}
      {(() => {
        const s = settings?.media?.find((s) => s.key === "media_logo");
        if (!s) return null;
        return (
          <div>
            <label className="block text-sm font-medium text-gray-700 mb-1.5">{s.label}</label>
            <MediaUploadRow setting={s} value={form["media_logo"]} onChange={onChange} />

            {/* Dimension controls + live preview */}
            <div className="mt-3 flex items-end gap-4">
              <div>
                <label className="block text-xs text-gray-500 mb-1">Width (px)</label>
                <input
                  type="number"
                  min={20}
                  max={400}
                  value={logoWidth}
                  onChange={(e) => onChange("logo_width", e.target.value)}
                  className="w-24 px-3 py-1.5 border rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary"
                />
              </div>
              <div>
                <label className="block text-xs text-gray-500 mb-1">Height (px)</label>
                <input
                  type="number"
                  min={16}
                  max={120}
                  value={logoHeight}
                  onChange={(e) => onChange("logo_height", e.target.value)}
                  className="w-24 px-3 py-1.5 border rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary"
                />
              </div>
              {logoUrl && (
                <div className="flex items-center gap-2 border rounded-lg px-3 py-1.5 bg-gray-50">
                  <span className="text-xs text-gray-400 mr-1">Preview:</span>
                  <img
                    src={logoUrl}
                    alt="logo preview"
                    style={{ width: `${logoWidth}px`, height: `${logoHeight}px` }}
                    className="object-contain"
                  />
                </div>
              )}
            </div>
          </div>
        );
      })()}

      {/* Favicon & OG image */}
      {["media_favicon", "media_og_image"].map((key) => {
        const s = settings?.media?.find((s) => s.key === key);
        if (!s) return null;
        return (
          <div key={key}>
            <label className="block text-sm font-medium text-gray-700 mb-1.5">{s.label}</label>
            <MediaUploadRow setting={s} value={form[key]} onChange={onChange} />
          </div>
        );
      })}

      <div>
        <label className="block text-sm font-medium text-gray-700 mb-1.5">
          Announcement Bar Text
        </label>
        <Field
          setting={{ key: "media_announcement", type: "text", label: "Announcement Bar" }}
          value={form["media_announcement"]}
          onChange={onChange}
        />
        <p className="mt-1 text-xs text-gray-500">
          Shown in the admin header top bar. Use &nbsp;·&nbsp; to separate items.
        </p>
      </div>
    </div>
  );
}
