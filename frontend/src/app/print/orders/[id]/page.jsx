"use client";

import { useParams, useSearchParams } from "next/navigation";
import { useOrder } from "@/hooks/useOrders";
import { useSettings } from "@/hooks/useSettings";
import { Button } from "@/components/ui/button";
import { Loader2, Printer } from "lucide-react";

function pluck(settings, group, key, fallback = "") {
  return settings?.[group]?.find((s) => s.key === key)?.value ?? fallback;
}

const ORDER_TYPE_LABEL = { custom_stitching: "Custom Stitching", alteration: "Alteration" };

export default function OrderPrintPage() {
  const { id } = useParams();
  const searchParams = useSearchParams();
  const forTailor = searchParams.get("type") === "tailor";

  const { data: order, isLoading } = useOrder(id);
  const { data: settings } = useSettings();

  if (isLoading) {
    return <div className="flex justify-center py-24"><Loader2 className="h-8 w-8 animate-spin text-muted-foreground" /></div>;
  }
  if (!order) {
    return <div className="text-center py-24 text-muted-foreground">Order not found.</div>;
  }

  const storeName = pluck(settings, "general", "store_name", "Tailor Shop");
  const storePhone = pluck(settings, "contact", "contact_phone") || pluck(settings, "contact", "contact_mobile");
  const storeAddress = [pluck(settings, "address", "address_line_1"), pluck(settings, "address", "city")].filter(Boolean).join(", ");

  return (
    <div className="max-w-2xl mx-auto p-6 bg-white text-black">
      <div className="flex justify-end mb-4 print:hidden">
        <Button size="sm" onClick={() => window.print()}><Printer className="h-4 w-4" />Print</Button>
      </div>

      <div className="text-center space-y-1 mb-6">
        <h1 className="text-xl font-bold">{storeName}</h1>
        {storeAddress && <p className="text-sm text-muted-foreground">{storeAddress}</p>}
        {storePhone && <p className="text-sm text-muted-foreground">{storePhone}</p>}
        <p className="text-xs font-semibold uppercase tracking-wide pt-1">
          {forTailor ? "Tailor Job Card" : "Customer Receipt"}
        </p>
      </div>

      <div className="flex justify-between text-sm mb-4 border-y py-2">
        <div>
          <p className="font-semibold">{order.order_number}</p>
          <p className="text-muted-foreground">{ORDER_TYPE_LABEL[order.order_type] ?? order.order_type}</p>
          <p className="text-muted-foreground">Expected delivery: {order.expected_delivery_date ?? "—"}</p>
        </div>
        <div className="text-right">
          <p className="font-semibold">{order.customer?.name}</p>
          <p className="text-muted-foreground">{order.customer?.mobile}</p>
        </div>
      </div>

      {forTailor ? (
        <div className="space-y-4">
          {order.items.map((item) => (
            <div key={item.id} className="border rounded-md p-3 break-inside-avoid">
              <div className="flex justify-between items-start">
                <div>
                  <p className="font-semibold">{item.garment_type}</p>
                  <p className="text-xs text-muted-foreground font-mono">{item.job_card_number}</p>
                </div>
                <p className="text-sm">Qty {item.quantity}</p>
              </div>

              <p className="text-xs mt-1">
                Fabric: {item.fabric_source === "in_house" ? "Shop fabric — see reserved materials below" : "Customer's own fabric"}
              </p>

              {item.materials?.length > 0 && (
                <ul className="text-xs mt-1 list-disc list-inside">
                  {item.materials.map((m) => (
                    <li key={m.id}>{m.product_name} — {m.quantity_required} {m.unit_of_measure}</li>
                  ))}
                </ul>
              )}

              {item.measurements?.length > 0 && (
                <div className="mt-2">
                  <p className="text-xs font-semibold">
                    {item.measurement_type?.name ? `${item.measurement_type.name} Measurements` : "Measurements"}
                  </p>
                  <table className="w-full text-xs mt-1">
                    <tbody>
                      {item.measurements.map((m) => (
                        <tr key={m.id} className="border-b">
                          <td className="py-0.5 w-6">{m.number}.</td>
                          <td className="py-0.5">{m.name}</td>
                          <td className="py-0.5 text-right font-semibold">{m.value ?? "—"} {m.unit}</td>
                        </tr>
                      ))}
                    </tbody>
                  </table>
                </div>
              )}

              {item.style_specifications && Object.keys(item.style_specifications).length > 0 && (
                <div className="mt-2">
                  <p className="text-xs font-semibold">Style Details</p>
                  <ul className="text-xs list-disc list-inside">
                    {Object.entries(item.style_specifications).map(([k, v]) => (
                      <li key={k}>{k}: {v}</li>
                    ))}
                  </ul>
                </div>
              )}
            </div>
          ))}

          <div className="pt-6 flex justify-between text-xs">
            <p>Tailor: _______________________</p>
            <p>Date: _______________</p>
          </div>
        </div>
      ) : (
        <>
          <table className="w-full text-sm mb-4">
            <thead>
              <tr className="border-b text-left">
                <th className="py-1">Garment</th>
                <th className="py-1 text-right">Qty</th>
                <th className="py-1 text-right">Price</th>
                <th className="py-1 text-right">Total</th>
              </tr>
            </thead>
            <tbody>
              {order.items.map((item) => (
                <tr key={item.id} className="border-b">
                  <td className="py-1">
                    {item.garment_type}
                    <span className="block text-[10px] text-muted-foreground font-mono">{item.job_card_number}</span>
                  </td>
                  <td className="py-1 text-right">{item.quantity}</td>
                  <td className="py-1 text-right">{Number(item.unit_price).toFixed(2)}</td>
                  <td className="py-1 text-right">{Number(item.total).toFixed(2)}</td>
                </tr>
              ))}
            </tbody>
          </table>

          <div className="flex justify-end">
            <div className="w-56 space-y-1 text-sm">
              <div className="flex justify-between"><span>Subtotal</span><span>{Number(order.subtotal).toFixed(2)}</span></div>
              {order.discount_amount > 0 && <div className="flex justify-between"><span>Discount</span><span>-{Number(order.discount_amount).toFixed(2)}</span></div>}
              {order.tax_amount > 0 && <div className="flex justify-between"><span>Tax</span><span>{Number(order.tax_amount).toFixed(2)}</span></div>}
              <div className="flex justify-between font-semibold border-t pt-1"><span>Total</span><span>{Number(order.total_amount).toFixed(2)}</span></div>
              <div className="flex justify-between"><span>Paid</span><span>{Number(order.paid_amount).toFixed(2)}</span></div>
              <div className="flex justify-between font-semibold"><span>Balance Due</span><span>{Number(order.balance_due).toFixed(2)}</span></div>
            </div>
          </div>

          <p className="text-center text-xs text-muted-foreground mt-6">Please bring this receipt when collecting your item(s).</p>
        </>
      )}
    </div>
  );
}
