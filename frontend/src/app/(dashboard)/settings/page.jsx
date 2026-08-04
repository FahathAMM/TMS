"use client";

import { useState, useEffect } from "react";
import { useSettings, useUpdateSettings } from "@/hooks/useSettings";
import { Store, Phone, MapPin, Share2, Clock, Image, Loader2, Save, DollarSign } from "lucide-react";

import { GeneralPanel  } from "./_components/panels/GeneralPanel";
import { ContactPanel  } from "./_components/panels/ContactPanel";
import { AddressPanel  } from "./_components/panels/AddressPanel";
import { SocialPanel   } from "./_components/panels/SocialPanel";
import { HoursPanel    } from "./_components/panels/HoursPanel";
import { MediaPanel    } from "./_components/panels/MediaPanel";
import { CurrencyPanel } from "./_components/panels/CurrencyPanel";

const TABS = [
  { key: "general",  label: "General",        Icon: Store      },
  { key: "contact",  label: "Contact",        Icon: Phone      },
  { key: "address",  label: "Address",        Icon: MapPin     },
  { key: "social",   label: "Social Media",   Icon: Share2     },
  { key: "hours",    label: "Business Hours", Icon: Clock      },
  { key: "media",    label: "Media",          Icon: Image      },
  { key: "currency", label: "Currency",       Icon: DollarSign },
];

function Skeleton() {
  return (
    <div className="space-y-4 animate-pulse">
      {[1, 2, 3, 4].map((i) => (
        <div key={i} className="space-y-2">
          <div className="h-4 w-24 bg-gray-200 rounded" />
          <div className="h-9 w-full bg-gray-100 rounded-lg" />
        </div>
      ))}
    </div>
  );
}

export default function SettingsPage() {
  const [activeTab, setActiveTab] = useState("general");
  const { data: settings, isLoading } = useSettings();
  const { mutate: save, isPending }   = useUpdateSettings(activeTab);

  const [form, setForm] = useState({});

  useEffect(() => {
    if (!settings) return;
    const group = settings[activeTab] ?? [];
    const vals = {};
    group.forEach((s) => { vals[s.key] = s.value ?? ""; });
    setForm(vals);
  }, [settings, activeTab]);

  const handleChange = (key, value) => setForm((f) => ({ ...f, [key]: value }));
  const handleSave   = ()            => save(form);

  const renderPanel = () => {
    if (isLoading) return <Skeleton />;
    const props = { settings, form, onChange: handleChange };
    switch (activeTab) {
      case "general":  return <GeneralPanel  {...props} />;
      case "contact":  return <ContactPanel  {...props} />;
      case "address":  return <AddressPanel  {...props} />;
      case "social":   return <SocialPanel   {...props} />;
      case "hours":    return <HoursPanel    {...props} />;
      case "media":    return <MediaPanel    {...props} />;
      case "currency": return <CurrencyPanel form={form} onChange={handleChange} />;
      default:         return null;
    }
  };

  return (
    <div className="space-y-6">
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-2xl font-bold">Settings</h1>
          <p className="text-sm text-muted-foreground mt-0.5">Configure your shop information and preferences</p>
        </div>
        <button
          onClick={handleSave}
          disabled={isPending || isLoading}
          className="flex items-center gap-2 px-4 py-2 bg-primary text-primary-foreground rounded-lg text-sm font-medium hover:bg-primary/90 transition-colors disabled:opacity-50"
        >
          {isPending ? <Loader2 className="h-4 w-4 animate-spin" /> : <Save className="h-4 w-4" />}
          Save Changes
        </button>
      </div>

      <div className="flex gap-6">
        {/* Tab sidebar */}
        <div className="w-48 shrink-0 space-y-1">
          {TABS.map(({ key, label, Icon }) => (
            <button
              key={key}
              onClick={() => setActiveTab(key)}
              className={`w-full flex items-center gap-2.5 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors text-left ${
                activeTab === key
                  ? "bg-primary/10 text-primary"
                  : "text-gray-600 hover:bg-gray-100 hover:text-gray-900"
              }`}
            >
              <Icon className="h-4 w-4 shrink-0" />
              {label}
            </button>
          ))}
        </div>

        {/* Active panel */}
        <div className="flex-1 bg-white rounded-xl border p-6">
          {renderPanel()}
        </div>
      </div>
    </div>
  );
}
