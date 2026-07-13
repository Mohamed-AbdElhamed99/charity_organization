<?php

namespace App\DTOs;

use Illuminate\Http\UploadedFile;

readonly class StoreMeetingAttachmentDTO
{
    public function __construct(
        public UploadedFile $file,
        public ?string $description,
        public int $uploadedBy,
    ) {}
}
