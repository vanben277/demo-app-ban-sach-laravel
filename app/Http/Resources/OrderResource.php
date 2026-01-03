<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'total_amount' => $this->total_amount,
            'status' => $this->status,
            'shipping_info' => [
                'phone' => $this->phone,
                'address' => $this->address,
                'note' => $this->note,
            ],
            'items' => OrderItemResource::collection($this->whenLoaded('items')),
            'created_at' => $this->created_at->format('Y-m-d H:i:s'),
        ];
    }
}
