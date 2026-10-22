<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BookResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'author' => $this->author,
            'isbn' => $this->isbn,
            'published_date' => $this->published_date?->format('Y-m-d'),
            'image_url' => $this->image_url,

            'genres' => $this->whenLoaded('genres', function () {
                return $this->genres->map(function ($genre) {
                    return [
                        'id' => $genre->id,
                        'name' => $genre->name,
                    ];
                });
            }),

            'reviews_avg_rating' => $this->reviews_avg_rating !== null
                ? (float) $this->reviews_avg_rating
                : null,

            'reviews_count' => $this->reviews_count,
        ];
    }
}
