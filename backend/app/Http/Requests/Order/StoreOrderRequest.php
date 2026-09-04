<?php

namespace App\Http\Requests\Order;

use Illuminate\Foundation\Http\FormRequest;

class StoreOrderRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'customer_id'                          => 'required_without:new_customer|nullable|exists:customers,id',
            'new_customer'                          => 'required_without:customer_id|nullable|array',
            'new_customer.name'                     => 'required_with:new_customer|string|max:255',
            'new_customer.mobile'                   => 'nullable|string|max:20',
            'new_customer.address'                  => 'nullable|string',
            'new_customer.email'                    => 'nullable|email|unique:customers,email',

            'order_type'                            => 'required|in:custom_stitching,alteration',
            'expected_delivery_date'                => 'nullable|date|after_or_equal:today',
            'is_urgent'                             => 'nullable|boolean',
            'discount_amount'                       => 'nullable|numeric|min:0',
            'tax_amount'                             => 'nullable|numeric|min:0',
            'payment_method'                         => 'nullable|string|max:30',
            'notes'                                   => 'nullable|string',

            'initial_payment'                        => 'nullable|array',
            'initial_payment.amount'                 => 'required_with:initial_payment|numeric|min:0.01',
            'initial_payment.payment_method'         => 'nullable|string|max:30',
            'initial_payment.payment_type'           => 'required_with:initial_payment|in:deposit,balance,full',

            'items'                                   => 'required|array|min:1',
            'items.*.garment_type'                    => 'required|string|max:100',
            'items.*.fabric_source'                   => 'required|in:customer_provided,in_house',
            'items.*.product_id'                      => 'nullable|exists:products,id',
            'items.*.quantity'                         => 'required|integer|min:1',
            'items.*.unit_price'                       => 'required|numeric|min:0',
            'items.*.discount'                          => 'nullable|numeric|min:0',
            'items.*.style_specifications'              => 'nullable|array',

            'items.*.materials'                         => 'nullable|array',
            'items.*.materials.*.product_id'            => 'required_with:items.*.materials|exists:products,id',
            'items.*.materials.*.quantity_required'     => 'required_with:items.*.materials|numeric|min:0.01',

            'items.*.measurement_type_id'                       => 'nullable|exists:measurement_types,id',
            'items.*.measurements'                              => 'nullable|array',
            'items.*.measurements.*.measurement_field_id'       => 'required_with:items.*.measurements|exists:measurement_fields,id',
            'items.*.measurements.*.value'                      => 'nullable|numeric|min:0|max:9999.99',
        ];
    }
}
