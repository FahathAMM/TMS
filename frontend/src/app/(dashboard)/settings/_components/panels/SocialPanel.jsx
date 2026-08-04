import { Facebook, Instagram, Twitter, Youtube, Linkedin, Share2 } from "lucide-react";
import { Field } from "../Field";

const SOCIAL_FIELDS = {
  social_facebook:  { Icon: Facebook,  color: "text-blue-600" },
  social_instagram: { Icon: Instagram, color: "text-pink-500" },
  social_twitter:   { Icon: Twitter,   color: "text-sky-500"  },
  social_tiktok:    { Icon: Share2,    color: "text-gray-800" },
  social_linkedin:  { Icon: Linkedin,  color: "text-blue-700" },
  social_youtube:   { Icon: Youtube,   color: "text-red-600"  },
};

export function SocialPanel({ settings, form, onChange }) {
  return (
    <div className="grid grid-cols-1 sm:grid-cols-2 gap-5">
      {Object.entries(SOCIAL_FIELDS).map(([key, { Icon, color }]) => {
        const s = settings?.social?.find((s) => s.key === key);
        if (!s) return null;
        return (
          <div key={key}>
            <label className="flex items-center gap-1.5 text-sm font-medium text-gray-700 mb-1.5">
              <Icon className={`h-4 w-4 ${color}`} />
              {s.label}
            </label>
            <Field setting={s} value={form[key]} onChange={onChange} />
          </div>
        );
      })}
    </div>
  );
}
