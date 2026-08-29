"use client";

import Link from "next/link";
import { useAlterationOrders } from "@/hooks/useAlterationOrders";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { StatusBadge } from "@/components/shared/StatusBadge";
import { Button } from "@/components/ui/button";
import { Eye, Loader2 } from "lucide-react";

export function AlterationHistoryCard({ customerId }) {
  const { data, isLoading } = useAlterationOrders({ customer_id: customerId, per_page: 10 });
  const orders = data?.data ?? [];

  return (
    <Card>
      <CardHeader className="pb-3"><CardTitle className="text-base">Alteration History</CardTitle></CardHeader>
      <CardContent>
        {isLoading && <div className="flex justify-center py-4"><Loader2 className="h-5 w-5 animate-spin text-muted-foreground" /></div>}
        {!isLoading && orders.length === 0 && <p className="text-sm text-muted-foreground">No alteration orders yet.</p>}
        {!isLoading && orders.length > 0 && (
          <div className="rounded-md border divide-y">
            {orders.map((o) => (
              <div key={o.id} className="flex items-center justify-between p-2 text-sm">
                <div>
                  <p className="font-medium font-mono text-xs">{o.order_number}</p>
                  <p className="text-xs text-muted-foreground">{o.garment_count} garment(s) · {o.total_amount.toFixed(2)}</p>
                </div>
                <div className="flex items-center gap-2">
                  <StatusBadge status={o.status} />
                  <Button variant="ghost" size="icon" asChild><Link href={`/alteration-orders/${o.id}`}><Eye className="h-4 w-4" /></Link></Button>
                </div>
              </div>
            ))}
          </div>
        )}
      </CardContent>
    </Card>
  );
}
