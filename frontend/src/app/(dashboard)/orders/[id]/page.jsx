"use client";

import { useParams, useRouter } from "next/navigation";
import { useOrder, useCompleteOrder } from "@/hooks/useOrders";
import { PageHeader } from "@/components/shared/PageHeader";
import { StatusBadge } from "@/components/shared/StatusBadge";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { OrderItemRow } from "./_components/OrderItemRow";
import { PaymentsPanel } from "./_components/PaymentsPanel";
import { NotificationsPanel } from "./_components/NotificationsPanel";
import { useAuthStore } from "@/store/authStore";
import { Loader2, CheckCircle2 } from "lucide-react";

export default function OrderDetailPage() {
  const { id } = useParams();
  const router = useRouter();
  const { can } = useAuthStore();
  const { data: order, isLoading } = useOrder(id);
  const completeOrder = useCompleteOrder();

  if (isLoading) {
    return <div className="flex justify-center py-24"><Loader2 className="h-8 w-8 animate-spin text-muted-foreground" /></div>;
  }
  if (!order) {
    return <div className="text-center py-24 text-muted-foreground">Order not found.</div>;
  }

  const allDelivered = order.items.every((i) => i.production_status === "delivered");
  const canComplete = order.status !== "completed" && order.status !== "cancelled" && order.balance_due <= 0;

  return (
    <div>
      <PageHeader
        title={order.order_number}
        description={`${order.customer?.name ?? ""} · ${order.order_type?.replace("_", " ")}`}
        action={<StatusBadge status={order.status} />}
      />

      <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div className="lg:col-span-2 space-y-4">
          <Card>
            <CardHeader className="pb-3"><CardTitle className="text-base">Garment Items</CardTitle></CardHeader>
            <CardContent className="space-y-3">
              {order.items.map((item) => <OrderItemRow key={item.id} orderId={order.id} item={item} />)}
            </CardContent>
          </Card>

          {order.status !== "completed" && can("edit tailoring_orders") && (
            <Card>
              <CardContent className="pt-4 flex items-center justify-between">
                <div>
                  <p className="text-sm font-medium">Handover Garment & Complete Order</p>
                  <p className="text-xs text-muted-foreground">
                    {order.balance_due > 0
                      ? `Balance of ${Number(order.balance_due).toFixed(2)} must be collected first.`
                      : allDelivered ? "Ready to complete." : "Recognizes revenue/COGS and marks all items delivered."}
                  </p>
                </div>
                <Button disabled={!canComplete || completeOrder.isPending}
                  onClick={() => completeOrder.mutate({ id: order.id }, { onSuccess: () => router.refresh() })}>
                  <CheckCircle2 className="h-4 w-4" />Complete Order
                </Button>
              </CardContent>
            </Card>
          )}
        </div>

        <div className="space-y-4">
          <Card>
            <CardHeader className="pb-3"><CardTitle className="text-base">Customer</CardTitle></CardHeader>
            <CardContent className="space-y-1 text-sm">
              <p className="font-medium">{order.customer?.name}</p>
              <p className="text-muted-foreground">{order.customer?.mobile}</p>
              <p className="text-muted-foreground">{order.customer?.email}</p>
              <p className="pt-2 text-xs text-muted-foreground">Expected delivery: {order.expected_delivery_date ?? "—"}</p>
              {order.notes && <p className="text-xs text-muted-foreground pt-1">{order.notes}</p>}
            </CardContent>
          </Card>

          <PaymentsPanel order={order} />
          <NotificationsPanel order={order} />
        </div>
      </div>
    </div>
  );
}
