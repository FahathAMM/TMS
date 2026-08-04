"use client";

import { useState } from "react";
import { useRouter } from "next/navigation";
import { useAuth } from "@/hooks/useAuth";
import { Button } from "@/components/ui/button";
import { TextInput } from "@/components/forms/FormField";
import { ValidationError } from "@/components/forms/FormField";
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from "@/components/ui/card";
import { Store, Loader2 } from "lucide-react";

export default function LoginPage() {
  const { login } = useAuth();
  const router = useRouter();
  const [form, setForm] = useState({ email: "admin@pos.com", password: "password" });
  const [errors, setErrors] = useState({});
  const [loading, setLoading] = useState(false);

  const handleSubmit = async (e) => {
    e.preventDefault();
    setErrors({});
    setLoading(true);
    try {
      await login(form);
      router.push("/dashboard");
    } catch (err) {
      const message = err.networkError ?? err.response?.data?.message ?? "Login failed";
      setErrors(err.response?.data?.errors ?? { email: [message] });
    } finally {
      setLoading(false);
    }
  };

  return (
    <div className="min-h-screen flex items-center justify-center bg-muted/30 p-4">
      <Card className="w-full max-w-sm">
        <CardHeader className="text-center">
          <div className="w-12 h-12 rounded-xl bg-primary mx-auto flex items-center justify-center mb-2">
            <Store className="h-6 w-6 text-primary-foreground" />
          </div>
          <CardTitle className="text-2xl">Mobile Shop POS</CardTitle>
          <CardDescription>Sign in to your account</CardDescription>
        </CardHeader>
        <CardContent>
          <form onSubmit={handleSubmit} className="space-y-4">
            <ValidationError errors={errors} />
            <TextInput
              label="Email"
              type="email"
              placeholder="admin@pos.com"
              value={form.email}
              onChange={(e) => setForm((p) => ({ ...p, email: e.target.value }))}
              required
              error={errors.email?.[0]}
            />
            <TextInput
              label="Password"
              type="password"
              placeholder="••••••••"
              value={form.password}
              onChange={(e) => setForm((p) => ({ ...p, password: e.target.value }))}
              required
              error={errors.password?.[0]}
            />
            <Button type="submit" className="w-full" disabled={loading}>
              {loading && <Loader2 className="mr-2 h-4 w-4 animate-spin" />}
              Sign In
            </Button>
          </form>
          <p className="text-xs text-center text-muted-foreground mt-4">
            Admin: admin@pos.com / password
          </p>
        </CardContent>
      </Card>
    </div>
  );
}
