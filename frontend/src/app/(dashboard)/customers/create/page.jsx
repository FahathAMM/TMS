"use client";

import { useState } from "react";
import { useRouter } from "next/navigation";
import { useCreateCustomer } from "@/hooks/useCustomers";
import { PageHeader } from "@/components/shared/PageHeader";
import { CustomerForm } from "../_components/CustomerForm";

export default function CreateCustomerPage() {
  const router = useRouter();
  const [errors, setErrors] = useState({});
  const createCustomer = useCreateCustomer();

  const handleSubmit = async (data) => {
    setErrors({});
    try {
      await createCustomer.mutateAsync(data);
      router.push("/customers");
    } catch (err) {
      setErrors(err.response?.data?.errors ?? {});
    }
  };

  return (
    <div>
      <PageHeader title="Add Customer" description="Register a new customer" />
      <CustomerForm
        onSubmit={handleSubmit}
        isSubmitting={createCustomer.isPending}
        errors={errors}
        submitLabel="Create Customer"
      />
    </div>
  );
}
