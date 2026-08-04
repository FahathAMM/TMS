"use client";

import { useState } from "react";
import { useOrderNotifications, useNotifyOrder } from "@/hooks/useOrders";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { TextareaInput } from "@/components/forms/FormField";
import { Mail } from "lucide-react";

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
      <CardHeader className="pb-3"><CardTitle className="text-base">Customer Notifications</CardTitle></CardHeader>
      <CardContent className="space-y-3">
        {!hasEmail && (
          <p className="text-xs text-muted-foreground">
            Customer has no email on file — notifications are logged but not delivered.
          </p>
        )}

        <div className="rounded-md border divide-y">
          {isLoading && <p className="p-2 text-xs text-muted-foreground">Loading…</p>}
          {!isLoading && (notifications ?? []).length === 0 && (
            <p className="p-2 text-xs text-muted-foreground">No notifications sent yet.</p>
          )}
          {(notifications ?? []).map((n) => (
            <div key={n.id} className="p-2 text-sm">
              <p className="font-medium">{n.headline}</p>
              <p className="text-xs text-muted-foreground">{n.body}</p>
              <p className="text-[10px] text-muted-foreground mt-1">
                {n.created_at ? new Date(n.created_at).toLocaleString() : ""}
              </p>
            </div>
          ))}
        </div>

        <div className="space-y-2 pt-2 border-t">
          <TextareaInput
            label="Custom message (optional)"
            placeholder="Leave blank to send a generic status update"
            value={message}
            onChange={(e) => setMessage(e.target.value)}
          />
          <Button size="sm" className="w-full" onClick={handleSend} disabled={notify.isPending}>
            <Mail className="h-4 w-4" /> Send Update Email
          </Button>
        </div>
      </CardContent>
    </Card>
  );
}
