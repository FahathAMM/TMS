<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                     => $this->id,
            'order_id'               => $this->order_id,
            'garment_type'           => $this->garment_type,
            'measurement_type_id'    => $this->measurement_type_id,
            'measurement_type'       => $this->whenLoaded('measurementType', fn () => $this->measurementType ? [
                'id' => $this->measurementType->id, 'name' => $this->measurementType->name, 'image_url' => $this->measurementType->image_url,
            ] : null),
            'measurements'           => OrderItemMeasurementResource::collection($this->whenLoaded('measurements')),
            'fabric_source'          => $this->fabric_source,
            'style_specifications'   => $this->style_specifications,
            'production_status'      => $this->production_status,
            'job_card_number'        => $this->job_card_number,
            'qc_notes'               => $this->qc_notes,
            'qc_passed_at'           => $this->qc_passed_at?->toISOString(),
            'quantity'               => $this->quantity,
            'unit_price'             => (float) $this->unit_price,
            'discount'               => (float) $this->discount,
            'total'                  => (float) $this->total,
            'product'                => $this->whenLoaded('product', fn () => $this->product ? [
                'id' => $this->product->id, 'name' => $this->product->name, 'sku' => $this->product->sku,
            ] : null),
            'materials'              => $this->whenLoaded('materials', fn () =>
                $this->materials->map(fn ($m) => [
                    'id'                => $m->id,
                    'product_id'        => $m->product_id,
                    'product_name'      => $m->product?->name,
                    'quantity_required' => (float) $m->quantity_required,
                    'unit_of_measure'   => $m->product?->unit_of_measure,
                    'status'            => $m->status,
                    'consumed_at'       => $m->consumed_at?->toISOString(),
                ])
            ),
            'current_tailor'         => $this->whenLoaded('assignments', function () {
                $tailor = $this->currentTailor();
                return $tailor ? ['id' => $tailor->id, 'name' => $tailor->full_name] : null;
            }),
            'created_at'             => $this->created_at?->toISOString(),
        ];
    }
}
