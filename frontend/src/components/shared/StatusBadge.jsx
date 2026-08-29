import { Badge } from "@/components/ui/badge";

export function StatusBadge({ status }) {
  const variants = {
    active: "success",
    inactive: "secondary",
    completed: "success",
    cancelled: "destructive",
    true: "success",
    false: "secondary",

    // Tailoring order status
    pending: "outline",
    in_progress: "default",
    quoted: "secondary",
    deposit_paid: "secondary",
    in_production: "default",
    ready_for_fitting: "default",

    // Production status
    cutting: "default",
    stitching: "default",
    qc: "secondary",
    rework: "destructive",
    ready: "success",
    delivered: "success",

    // Fitting status
    scheduled: "secondary",
    rescheduled: "outline",

    // Payment / purchase status
    paid: "success",
    partial: "secondary",
    unpaid: "destructive",
    draft: "outline",
    ordered: "secondary",
    received: "success",

    // Material / stock movement status
    reserved: "outline",
    consumed: "success",
  };

  const labels = {
    active: "Active",
    inactive: "Inactive",
    completed: "Completed",
    cancelled: "Cancelled",
    true: "Active",
    false: "Inactive",

    pending: "Pending",
    in_progress: "In Progress",
    quoted: "Quoted",
    deposit_paid: "Deposit Paid",
    in_production: "In Production",
    ready_for_fitting: "Ready for Fitting",

    cutting: "Cutting",
    stitching: "Stitching",
    qc: "Quality Check",
    rework: "Rework",
    ready: "Ready",
    delivered: "Delivered",

    scheduled: "Scheduled",
    rescheduled: "Rescheduled",

    paid: "Paid",
    partial: "Partial",
    unpaid: "Unpaid",
    draft: "Draft",
    ordered: "Ordered",
    received: "Received",

    reserved: "Reserved",
    consumed: "Consumed",
  };

  const key = String(status === true ? "true" : status === false ? "false" : status);

  return <Badge variant={variants[key] ?? "outline"}>{labels[key] ?? status}</Badge>;
}
