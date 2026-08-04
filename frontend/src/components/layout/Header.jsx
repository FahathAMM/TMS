"use client";

import { useAuth } from "@/hooks/useAuth";
import { Button } from "@/components/ui/button";
import { LogOut, Bell } from "lucide-react";
import { useAuthStore } from "@/store/authStore";
import { getInitials } from "@/lib/utils";

export function Header({ title }) {
  const { logout } = useAuth();
  const { user } = useAuthStore();

  return (
    <header className="h-14 border-b bg-background flex items-center justify-between px-6">
      <h2 className="font-semibold text-sm text-muted-foreground">{title}</h2>
      <div className="flex items-center gap-2">
        <div className="flex items-center gap-3">
          <div className="w-8 h-8 rounded-full bg-primary text-primary-foreground flex items-center justify-center text-xs font-bold">
            {getInitials(user?.name ?? "")}
          </div>
          <div className="hidden sm:block">
            <p className="text-sm font-medium leading-none">{user?.name}</p>
            <p className="text-xs text-muted-foreground capitalize">{user?.role}</p>
          </div>
        </div>
        <Button variant="ghost" size="icon" onClick={logout} title="Logout">
          <LogOut className="h-4 w-4" />
        </Button>
      </div>
    </header>
  );
}
