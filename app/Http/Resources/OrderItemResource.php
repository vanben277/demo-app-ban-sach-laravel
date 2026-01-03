<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderItemResource extends JsonResource
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
            'book_title' => $this->book->title ?? 'Sách đã bị xóa',
            'quantity' => $this->quantity,
            'price' => $this->price,
            'subtotal' => $this->quantity * $this->price,
        ];
    }
}
