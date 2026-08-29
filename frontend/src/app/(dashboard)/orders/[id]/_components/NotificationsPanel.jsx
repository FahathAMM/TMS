"use client";

import { useState } from "react";
import { useOrderNotifications, useNotifyOrder } from "@/hooks/useOrders";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { SectionBox, SECTION_THEME as THEME } from "@/components/shared/SectionBox";
import { TextareaInput } from "@/components/forms/FormField";
import { Mail, BellRing } from "lucide-react";

export function NotificationsPanel({ order }) {
  const { data: notifications, isLoading } = useOrderNotifications(order.id);
  const notify = useNotifyOrder();
  const [message, setMessage] = useState("");

  const hasEmail = !!order.customer?.email;

  const handleSend = () => {
    notify.mutate(
      { id: order.id, data: { message: message || undefined } },
      { onSuccess: () => setMessage("") }
    );
  };

  return (
    <Card>
      <CardHeader className="pb-3"><CardTitle className="text-base flex items-center gap-2"><BellRing className="h-5 w-5 text-primary" />Customer Notifications</CardTitle></CardHeader>
      <CardContent className="space-y-3">
        {!hasEmail && (
          <p className="text-xs text-muted-foreground rounded-md border border-orange-200 bg-orange-50 px-2 py-1.5">
            Customer has no email on file — notifications are logged but not delivered.
          </p>
        )}

        <div className="rounded-md border divide-y">
          {isLoading && <p className="p-2 text-xs text-muted-foreground">Loading…</p>}
          {!isLoading && (notifications ?? []).length === 0 && (
            <p className="p-2 text-xs text-muted-foreground">No notifications sent yet.</p>
          )}
          {(notifications ?? []).map((n) => (
            <div key={n.id} className="flex items-start gap-2 p-2 text-sm">
              <span className="flex h-6 w-6 items-center justify-center rounded-full bg-blue-100 text-blue-700 shrink-0 mt-0.5">
                <Mail className="h-3.5 w-3.5" />
              </span>
              <div className="min-w-0">
                <p className="font-medium">{n.headline}</p>
                <p className="text-xs text-muted-foreground">{n.body}</p>
                <p className="text-[10px] text-muted-foreground mt-1">
                  {n.created_at ? new Date(n.created_at).toLocaleString() : ""}
                </p>
              </div>
            </div>
          ))}
        </div>

        <SectionBox theme={THEME.notes} icon={Mail} title="Send an Update" subtitle="Optional — leave blank for a generic status message">
          <TextareaInput
            placeholder="e.g. Your dress is ready for pickup!"
            value={message}
            onChange={(e) => setMessage(e.target.value)}
          />
          <Button variant="info" className="w-full" onClick={handleSend} disabled={notify.isPending}>
            <Mail className="h-4 w-4" /> Send Update Email
          </Button>
        </SectionBox>
      </CardContent>
    </Card>
  );
}
