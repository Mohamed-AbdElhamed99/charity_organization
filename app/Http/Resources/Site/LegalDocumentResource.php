<?php

namespace App\Http\Resources\Site;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LegalDocumentResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'type' => $this->type->value,
            'title' => $this->title,
            'body' => $this->body,
            'meta_title' => $this->title,
            'meta_description' => strip_tags((string) $this->body),
            'updated_at' => $this->updated_at?->toDateTimeString(),
        ];
    }
}
