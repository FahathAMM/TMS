"use client";

import { useState } from "react";
import { useParams, useRouter } from "next/navigation";
import { useCustomer, useUpdateCustomer } from "@/hooks/useCustomers";
import { PageHeader } from "@/components/shared/PageHeader";
import { CustomerForm } from "../../_components/CustomerForm";
import { MeasurementsCard } from "../../_components/MeasurementsCard";
import { Loader2 } from "lucide-react";

export default function EditCustomerPage() {
  const { id } = useParams();
  const router = useRouter();
  const [errors, setErrors] = useState({});

  const { data: customer, isLoading } = useCustomer(id);
  const updateCustomer = useUpdateCustomer();

  const handleSubmit = async (data) => {
    setErrors({});
    try {
      await updateCustomer.mutateAsync({ id, data });
      router.push("/customers");
    } catch (err) {
      setErrors(err.response?.data?.errors ?? {});
    }
  };

  if (isLoading) {
    return (
      <div className="flex items-center justify-center py-24">
        <Loader2 className="h-8 w-8 animate-spin text-muted-foreground" />
      </div>
    );
  }

  if (!customer) {
    return <div className="flex items-center justify-center py-24 text-muted-foreground">Customer not found.</div>;
  }

  return (
    <div>
      <PageHeader title="Edit Customer" description={`Editing: ${customer.name}`} />
      <CustomerForm
        key={customer.id}
        initialValues={{
          name:      customer.name ?? "",
          mobile:    customer.mobile ?? "",
          email:     customer.email ?? "",
          address:   customer.address ?? "",
          is_active: customer.is_active ? "1" : "0",
        }}
        onSubmit={handleSubmit}
        isSubmitting={updateCustomer.isPending}
        errors={errors}
        submitLabel="Save Changes"
      />
      <div className="mt-6 max-w-3xl">
        <MeasurementsCard customerId={customer.id} />
      </div>
    </div>
  );
}
