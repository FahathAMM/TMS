import { clsx } from "clsx";
import { twMerge } from "tailwind-merge";

export function cn(...inputs) {
  return twMerge(clsx(inputs));
}

export function formatCurrency(amount, settings = null) {
  const num = amount ?? 0;

  if (!settings || !Object.keys(settings).length) {
    return new Intl.NumberFormat("en-US", {
      style: "currency",
      currency: "USD",
      minimumFractionDigits: 2,
    }).format(num);
  }

  const symbol   = settings.currency_symbol    ?? "$";
  const position = settings.currency_position  ?? "left";
  const decimals = Math.max(0, parseInt(settings.decimal_places    ?? "2", 10));
  const decSep   = settings.decimal_separator  ?? ".";
  const thouSep  = settings.thousand_separator ?? ",";

  const abs      = Math.abs(num);
  const fixed    = abs.toFixed(decimals);
  const [intPart, decPart = ""] = fixed.split(".");
  const intFmt   = intPart.replace(/\B(?=(\d{3})+(?!\d))/g, thouSep);
  const numStr   = decimals > 0 ? `${intFmt}${decSep}${decPart}` : intFmt;
  const sign     = num < 0 ? "-" : "";

  return position === "right" ? `${sign}${numStr}${symbol}` : `${sign}${symbol}${numStr}`;
}

export function formatDate(date, options = {}) {
  if (!date) return "—";
  return new Intl.DateTimeFormat("en-US", {
    year: "numeric",
    month: "short",
    day: "numeric",
    ...options,
  }).format(new Date(date));
}

export function formatDateTime(date) {
  return formatDate(date, { hour: "2-digit", minute: "2-digit" });
}

export function getInitials(name) {
  return name
    ?.split(" ")
    .map((n) => n[0])
    .slice(0, 2)
    .join("")
    .toUpperCase() ?? "?";
}
