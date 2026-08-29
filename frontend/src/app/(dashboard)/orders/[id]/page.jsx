"use client";

import Link from "next/link";
import { useParams, useRouter } from "next/navigation";
import { useOrder, useCompleteOrder } from "@/hooks/useOrders";
import { StatusBadge } from "@/components/shared/StatusBadge";
import { Card, CardContent } from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { OrderItemRow } from "./_components/OrderItemRow";
import { PaymentsPanel } from "./_components/PaymentsPanel";
import { NotificationsPanel } from "./_components/NotificationsPanel";
import { useAuthStore } from "@/store/authStore";
import {
  Loader2, CheckCircle2, Shirt, Scissors, User, Phone, Mail, Calendar,
  StickyNote, Wallet, Banknote, CreditCard, UserCog, Printer,
} from "lucide-react";

const ORDER_TYPE_LABEL = {
  custom_stitching: { label: "Custom Stitching", icon: Shirt },
  alteration: { label: "Alteration", icon: Scissors },
};

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
  const typeInfo = ORDER_TYPE_LABEL[order.order_type] ?? ORDER_TYPE_LABEL.custom_stitching;
  const TypeIcon = typeInfo.icon;

  const tailorNames = [...new Set(order.items.map((i) => i.current_tailor?.name).filter(Boolean))];
  const tailorLabel = tailorNames.length > 0 ? tailorNames.join(", ") : "Not Assigned";

  return (
    <div className="space-y-4">
      <Card className="border-2 overflow-hidden">
        <div className="bg-primary text-primary-foreground px-4 sm:px-6 py-4 flex flex-wrap items-center justify-between gap-3">
          <div className="flex items-center gap-3">
            <span className="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-white/20">
              <TypeIcon className="h-6 w-6" />
            </span>
            <div>
              <h1 className="text-xl font-bold leading-tight">{order.order_number}</h1>
              <p className="text-sm text-primary-foreground/80">{order.customer?.name ?? ""} · {typeInfo.label}</p>
            </div>
          </div>
          <div className="flex items-center gap-2 flex-wrap">
            <Button asChild variant="ghost" size="sm" className="text-primary-foreground hover:bg-white/20 hover:text-white">
              <Link href={`/print/orders/${order.id}?type=customer`} target="_blank">
                <Printer className="h-4 w-4" />Customer Receipt
              </Link>
            </Button>
            <Button asChild variant="ghost" size="sm" className="text-primary-foreground hover:bg-white/20 hover:text-white">
              <Link href={`/print/orders/${order.id}?type=tailor`} target="_blank">
                <Printer className="h-4 w-4" />Tailor Slip
              </Link>
            </Button>
            <StatusBadge status={order.status} />
          </div>
        </div>

        <CardContent className="pt-4">
          <div className="flex flex-col lg:flex-row lg:items-center gap-4">
            <div className="flex items-center gap-3 lg:pr-4 lg:border-r">
              <span className="flex h-14 w-14 shrink-0 items-center justify-center rounded-full bg-muted text-muted-foreground border">
                <User className="h-7 w-7" />
              </span>
              <div className="min-w-0 space-y-1">
                <p className="font-semibold text-sm">{order.customer?.name}</p>
                <div className="flex flex-wrap gap-x-4 gap-y-1">
                  {order.customer?.mobile && (
                    <p className="flex items-center gap-1.5 text-sm text-muted-foreground"><Phone className="h-3.5 w-3.5 shrink-0" />{order.customer.mobile}</p>
                  )}
                  {order.customer?.email && (
                    <p className="flex items-center gap-1.5 text-sm text-muted-foreground"><Mail className="h-3.5 w-3.5 shrink-0" />{order.customer.email}</p>
                  )}
                </div>
                {order.notes && (
                  <p className="flex items-start gap-1.5 text-xs text-muted-foreground pt-0.5">
                    <StickyNote className="h-3.5 w-3.5 shrink-0 mt-0.5" />{order.notes}
                  </p>
                )}
              </div>
            </div>

            <div className="grid grid-cols-2 sm:grid-cols-5 gap-2 text-center flex-1">
              <div className="rounded-lg bg-primary/10 p-3">
                <Wallet className="h-5 w-5 mx-auto text-primary mb-1" />
                <p className="text-xs text-muted-foreground">Total</p>
                <p className="text-base font-bold">{Number(order.total_amount).toFixed(2)}</p>
              </div>
              <div className="rounded-lg bg-green-50 p-3">
                <Banknote className="h-5 w-5 mx-auto text-green-700 mb-1" />
                <p className="text-xs text-muted-foreground">Paid</p>
                <p className="text-base font-bold text-green-700">{Number(order.paid_amount).toFixed(2)}</p>
              </div>
              <div className="rounded-lg bg-orange-50 p-3">
                <CreditCard className="h-5 w-5 mx-auto text-orange-700 mb-1" />
                <p className="text-xs text-muted-foreground">Balance Due</p>
                <p className={`text-base font-bold ${order.balance_due > 0 ? "text-orange-700" : "text-green-700"}`}>
                  {Number(order.balance_due).toFixed(2)}
                </p>
              </div>
              <div className="rounded-lg bg-sky-50 p-3">
                <Calendar className="h-5 w-5 mx-auto text-sky-700 mb-1" />
                <p className="text-xs text-muted-foreground">Expected Delivery</p>
                <p className="text-base font-bold text-sky-700">{order.expected_delivery_date ?? "—"}</p>
              </div>
              <div className="rounded-lg bg-violet-50 p-3">
                <UserCog className="h-5 w-5 mx-auto text-violet-700 mb-1" />
                <p className="text-xs text-muted-foreground">Assigned Tailor</p>
                <p className={`text-base font-bold truncate ${tailorNames.length > 0 ? "text-violet-700" : "text-muted-foreground"}`}>
                  {tailorLabel}
                </p>
              </div>
            </div>
          </div>
        </CardContent>
      </Card>

      <div className="grid grid-cols-1 lg:grid-cols-3 gap-4">
        <div className="lg:col-span-2 space-y-4">
          <div>
            <h2 className="text-base font-semibold flex items-center gap-2 mb-2 px-1">
              <Shirt className="h-4 w-4 text-muted-foreground" />
              {order.order_type === "alteration" ? "Alteration Items" : "Garment Items"} ({order.items.length})
            </h2>
            <div className="space-y-3">
              {order.items.map((item) => (
                <OrderItemRow key={item.id} orderId={order.id} orderType={order.order_type} item={item} />
              ))}
            </div>
          </div>

          {order.status !== "completed" && can("edit tailoring_orders") && (
            <Card className={canComplete ? "border-2 border-green-200 bg-green-50/60" : "border-2"}>
              <CardContent className="pt-4 flex items-center justify-between gap-3 flex-wrap">
                <div className="flex items-center gap-3">
                  <span className={`flex h-10 w-10 shrink-0 items-center justify-center rounded-full ${canComplete ? "bg-green-100 text-green-700" : "bg-muted text-muted-foreground"}`}>
                    <CheckCircle2 className="h-5 w-5" />
                  </span>
                  <div>
                    <p className="text-sm font-semibold">Handover Garment & Complete Order</p>
                    <p className="text-xs text-muted-foreground">
                      {order.balance_due > 0
                        ? `Balance of ${Number(order.balance_due).toFixed(2)} must be collected first.`
                        : allDelivered ? "Ready to complete." : "Recognizes revenue/COGS and marks all items delivered."}
                    </p>
                  </div>
                </div>
                <Button variant="success" size="lg" disabled={!canComplete || completeOrder.isPending}
                  onClick={() => completeOrder.mutate({ id: order.id }, { onSuccess: () => router.refresh() })}>
                  <CheckCircle2 className="h-5 w-5" />Complete Order
                </Button>
              </CardContent>
            </Card>
          )}
        </div>

        <div className="space-y-4">
          <PaymentsPanel order={order} />
          <NotificationsPanel order={order} />
        </div>
      </div>
    </div>
  );
}
