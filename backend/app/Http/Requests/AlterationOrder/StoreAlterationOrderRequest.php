<?php

namespace App\Http\Requests\AlterationOrder;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

class StoreAlterationOrderRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'customer_id'                              => 'required_without:new_customer|nullable|exists:customers,id',
            'new_customer'                              => 'required_without:customer_id|nullable|array',
            'new_customer.name'                         => 'required_with:new_customer|string|max:255',
            'new_customer.mobile'                       => 'nullable|string|max:20',
            'new_customer.address'                      => 'nullable|string',
            'new_customer.email'                        => 'nullable|email|unique:customers,email',

            'priority'                                   => 'nullable|in:normal,urgent',
            'promised_date'                              => 'nullable|date|after_or_equal:today',
            'discount_amount'                            => 'nullable|numeric|min:0',
            'tax_amount'                                  => 'nullable|numeric|min:0',
            'notes'                                        => 'nullable|string',

            'initial_payment'                             => 'nullable|array',
            'initial_payment.amount'                      => 'required_with:initial_payment|numeric|min:0.01',
            'initial_payment.payment_method'              => 'nullable|string|max:30',
            'initial_payment.payment_type'                => 'required_with:initial_payment|in:advance,balance,full',

            'garments'                                    => 'required|array|min:1',
            'garments.*.garment_type'                     => 'required|string|max:100',
            'garments.*.description'                      => 'nullable|string|max:255',
            'garments.*.quantity'                          => 'nullable|integer|min:1',
            'garments.*.measurements_required'             => 'nullable|boolean',
            'garments.*.notes'                             => 'nullable|string',

            'garments.*.tasks'                             => 'required|array|min:1',
            'garments.*.tasks.*.alteration_type_id'        => 'nullable|exists:alteration_types,id',
            'garments.*.tasks.*.description'               => 'nullable|string|max:255',
            'garments.*.tasks.*.price'                     => 'nullable|numeric|min:0',
            'garments.*.tasks.*.quantity'                  => 'nullable|integer|min:1',
            'garments.*.tasks.*.notes'                     => 'nullable|string',

            'garments.*.measurements'                                 => 'nullable|array',
            'garments.*.measurements.*.measurement_type_id'           => 'required_with:garments.*.measurements|exists:measurement_types,id',
            'garments.*.measurements.*.current_value'                 => 'nullable|numeric',
            'garments.*.measurements.*.target_value'                  => 'nullable|numeric',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            foreach ($this->input('garments', []) as $gi => $garment) {
                foreach (($garment['tasks'] ?? []) as $ti => $task) {
                    $hasType   = !empty($task['alteration_type_id']);
                    $hasCustom = !empty($task['description']) && isset($task['price']);
                    if (!$hasType && !$hasCustom) {
                        $validator->errors()->add(
                            "garments.$gi.tasks.$ti",
                            'Each task needs either an alteration type or a custom description and price.'
                        );
                    }
                }
            }
        });
    }
}
