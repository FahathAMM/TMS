import { Inter } from "next/font/google";
import "@/lib/globals.css";
import { Providers } from "./providers";
import { Toaster } from "sonner";

const inter = Inter({ subsets: ["latin"] });

export const metadata = {
  title: "Tailor Management System",
  description: "Admin Panel & Point of Sale for Tailor Shop",
};

export default function RootLayout({ children }) {
  return (
    <html lang="en" suppressHydrationWarning>
      <body className={inter.className}>
        <Providers>
          {children}
          <Toaster position="top-right" richColors closeButton />
        </Providers>
      </body>
    </html>
  );
}
