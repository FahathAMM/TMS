"use client";

import { useState } from "react";
import {
  useOrdersReport, usePaymentsReport, useOutstandingBalancesReport,
  useStockReport, usePurchasesReport, useExpensesReport, useTailorProductivityReport,
} from "@/hooks/useReports";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { TextInput } from "@/components/forms/FormField";
import { exportCsv } from "@/lib/exportCsv";
import { Download } from "lucide-react";

const TABS = [
  { key: "orders", label: "Orders Summary" },
  { key: "payments", label: "Payments Collected" },
  { key: "outstanding", label: "Outstanding Balances" },
  { key: "stock", label: "Stock Summary" },
  { key: "purchases", label: "Purchases Summary" },
  { key: "expenses", label: "Expenses Summary" },
  { key: "tailors", label: "Tailor Productivity" },
];

// Tabs whose data respects the from/to date filter.
const DATE_SCOPED = new Set(["orders", "payments", "purchases", "expenses", "tailors"]);

function SimpleTable({ columns, rows }) {
  if (!rows || rows.length === 0) {
    return <p className="text-sm text-muted-foreground py-6 text-center">No data for this period.</p>;
  }
  return (
    <table className="w-full text-sm">
      <thead>
        <tr className="text-xs text-muted-foreground border-b">
          {columns.map((c) => (
            <th key={c.key} className={`font-medium py-2 ${c.align === "right" ? "text-right" : "text-left"}`}>{c.header}</th>
          ))}
        </tr>
      </thead>
      <tbody>
        {rows.map((row, i) => (
          <tr key={i} className="border-b last:border-0">
            {columns.map((c) => (
              <td key={c.key} className={`py-2 ${c.align === "right" ? "text-right tabular-nums" : ""}`}>
                {c.render ? c.render(row) : row[c.key]}
              </td>
            ))}
          </tr>
        ))}
      </tbody>
    </table>
  );
}

function ReportCard({ title, rows, columns, csvName, children }) {
  return (
    <Card>
      <CardHeader className="pb-3 flex flex-row items-center justify-between">
        <CardTitle className="text-base">{title}</CardTitle>
        <Button variant="outline" size="sm" disabled={!rows?.length} onClick={() => exportCsv(csvName, rows)}>
          <Download className="h-4 w-4" /> Export CSV
        </Button>
      </CardHeader>
      <CardContent>
        {children ?? <SimpleTable columns={columns} rows={rows} />}
      </CardContent>
    </Card>
  );
}

export default function ReportsPage() {
  const [tab, setTab] = useState("orders");
  const [from, setFrom] = useState("");
  const [to, setTo] = useState("");
  const params = { from: from || undefined, to: to || undefined };

  const orders = useOrdersReport(params);
  const payments = usePaymentsReport(params);
  const outstanding = useOutstandingBalancesReport();
  const stock = useStockReport();
  const purchases = usePurchasesReport(params);
  const expenses = useExpensesReport(params);
  const tailors = useTailorProductivityReport(params);

  return (
    <div className="space-y-4">
      <div className="flex flex-wrap gap-2">
        {TABS.map((t) => (
          <Button key={t.key} size="sm" variant={tab === t.key ? "default" : "outline"} onClick={() => setTab(t.key)}>
            {t.label}
          </Button>
        ))}
      </div>

      {DATE_SCOPED.has(tab) && (
        <div className="flex gap-4">
          <TextInput label="From" type="date" value={from} onChange={(e) => setFrom(e.target.value)} />
          <TextInput label="To" type="date" value={to} onChange={(e) => setTo(e.target.value)} />
        </div>
      )}

      {tab === "orders" && (
        <div className="space-y-4">
          <div className="grid grid-cols-2 gap-4">
            <div className="rounded-lg border p-4">
              <p className="text-xs text-muted-foreground">Total Orders</p>
              <p className="text-2xl font-semibold tabular-nums">{orders.data?.total_orders ?? 0}</p>
            </div>
            <div className="rounded-lg border p-4 bg-muted/30">
              <p className="text-xs text-muted-foreground">Total Value</p>
              <p className="text-2xl font-semibold tabular-nums">{Number(orders.data?.total_value ?? 0).toFixed(2)}</p>
            </div>
          </div>
          <ReportCard
            title="By Status" csvName="orders-by-status" rows={orders.data?.by_status}
            columns={[
              { key: "status", header: "Status" },
              { key: "count", header: "Count", align: "right" },
              { key: "total", header: "Total", align: "right", render: (r) => Number(r.total).toFixed(2) },
            ]}
          />
        </div>
      )}

      {tab === "payments" && (
        <div className="space-y-4">
          <div className="rounded-lg border p-4 bg-muted/30 w-fit">
            <p className="text-xs text-muted-foreground">Total Collected</p>
            <p className="text-2xl font-semibold tabular-nums">{Number(payments.data?.total ?? 0).toFixed(2)}</p>
          </div>
          <ReportCard
            title="By Date" csvName="payments-by-date" rows={payments.data?.by_date}
            columns={[
              { key: "date", header: "Date" },
              { key: "total", header: "Total", align: "right", render: (r) => Number(r.total).toFixed(2) },
            ]}
          />
          <ReportCard
            title="By Payment Method" csvName="payments-by-method" rows={payments.data?.by_method}
            columns={[
              { key: "payment_method", header: "Method" },
              { key: "total", header: "Total", align: "right", render: (r) => Number(r.total).toFixed(2) },
            ]}
          />
        </div>
      )}

      {tab === "outstanding" && (
        <ReportCard
          title="Orders with a Balance Due" csvName="outstanding-balances" rows={outstanding.data}
          columns={[
            { key: "order_number", header: "Order #" },
            { key: "customer", header: "Customer" },
            { key: "status", header: "Status" },
            { key: "total_amount", header: "Total", align: "right", render: (r) => Number(r.total_amount).toFixed(2) },
            { key: "paid_amount", header: "Paid", align: "right", render: (r) => Number(r.paid_amount).toFixed(2) },
            { key: "balance_due", header: "Balance", align: "right", render: (r) => <span className="text-destructive font-medium">{Number(r.balance_due).toFixed(2)}</span> },
          ]}
        />
      )}

      {tab === "stock" && (
        <ReportCard
          title="Current Stock Levels" csvName="stock-summary" rows={stock.data}
          columns={[
            { key: "name", header: "Product" },
            { key: "sku", header: "SKU" },
            { key: "stock_quantity", header: "Stock", align: "right", render: (r) => `${Number(r.stock_quantity).toFixed(2)} ${r.unit_of_measure ?? ""}` },
            { key: "is_low_stock", header: "Status", render: (r) => r.is_low_stock ? <span className="text-destructive font-medium">Low</span> : <span className="text-muted-foreground">OK</span> },
          ]}
        />
      )}

      {tab === "purchases" && (
        <ReportCard
          title="By Supplier" csvName="purchases-by-supplier" rows={purchases.data}
          columns={[
            { key: "supplier", header: "Supplier" },
            { key: "count", header: "Purchases", align: "right" },
            { key: "total", header: "Total", align: "right", render: (r) => Number(r.total).toFixed(2) },
          ]}
        />
      )}

      {tab === "expenses" && (
        <ReportCard
          title="By Category" csvName="expenses-by-category" rows={expenses.data}
          columns={[
            { key: "category", header: "Category" },
            { key: "count", header: "Count", align: "right" },
            { key: "total", header: "Total", align: "right", render: (r) => Number(r.total).toFixed(2) },
          ]}
        />
      )}

      {tab === "tailors" && (
        <ReportCard
          title="Completed Items per Tailor" csvName="tailor-productivity" rows={tailors.data}
          columns={[
            { key: "tailor_name", header: "Tailor" },
            { key: "items_completed", header: "Items Completed", align: "right" },
          ]}
        />
      )}
    </div>
  );
}
