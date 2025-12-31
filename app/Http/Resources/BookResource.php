<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BookResource extends JsonResource
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
            'title' => $this->title,
            'price' => $this->price,
            'category_name' => $this->category->name ?? null,
            'images' => $this->images->map(fn($img) => [
                'id' => $img->id,
                'url' => asset('storage/' . $img->image_path),
                'is_main' => $img->is_main
            ]),
        ];
    }
}
