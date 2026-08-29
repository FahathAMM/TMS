"use client";

import Link from "next/link";
import { useAuth } from "@/hooks/useAuth";
import { Button } from "@/components/ui/button";
import { LogOut, Menu } from "lucide-react";
import { useAuthStore } from "@/store/authStore";
import { getInitials, cn } from "@/lib/utils";
import { getIcon } from "@/lib/iconMap";
import { TILE_COLORS } from "@/lib/tileColors";

function ModuleTab({ item, isActive, colorClass }) {
  const Icon = getIcon(item.icon);
  const href = item.children?.[0]?.route_name;
  if (!href) return null;

  return (
    <Link
      href={href}
      className={cn(
        "flex w-24 h-14 shrink-0 flex-col items-center justify-center gap-1 rounded-md text-white shadow-sm transition-transform hover:scale-[1.05]",
        colorClass,
        isActive && "ring-2 ring-primary ring-offset-2 ring-offset-background"
      )}
    >
      <Icon className="h-5 w-5 shrink-0" />
      <span className="text-[10px] font-semibold leading-tight text-center px-1 line-clamp-2">{item.name}</span>
    </Link>
  );
}

export function Header({ navigation, activeModuleId, onMenuClick }) {
  const { logout } = useAuth();
  const { user } = useAuthStore();

  const modules = navigation?.filter((item) => !item.route_name && item.children?.length) ?? [];

  return (
    <header className="border-b bg-background">
      <div className="flex items-center gap-3 px-4 sm:px-6 py-2">
        <Button variant="ghost" size="icon" onClick={onMenuClick} className="lg:hidden shrink-0" title="Open menu">
          <Menu className="h-5 w-5" />
        </Button>

        <nav className="flex-1 flex items-center gap-2 overflow-x-auto no-scrollbar min-w-0 py-1">
          {modules.map((item, i) => (
            <ModuleTab key={item.id} item={item} isActive={item.id === activeModuleId} colorClass={TILE_COLORS[i % TILE_COLORS.length]} />
          ))}
        </nav>

        <div className="flex items-center gap-2 shrink-0">
          <div className="flex items-center gap-3">
            <div className="w-8 h-8 rounded-full bg-primary text-primary-foreground flex items-center justify-center text-xs font-bold">
              {getInitials(user?.name ?? "")}
            </div>
            <div className="hidden xl:block">
              <p className="text-sm font-medium leading-none">{user?.name}</p>
              <p className="text-xs text-muted-foreground capitalize">{user?.role}</p>
            </div>
          </div>
          <Button variant="ghost" size="icon" onClick={logout} title="Logout">
            <LogOut className="h-4 w-4" />
          </Button>
        </div>
      </div>
    </header>
  );
}
