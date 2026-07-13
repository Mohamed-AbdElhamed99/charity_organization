<?php

namespace App\Http\Requests\Admin\Meeting;

use Illuminate\Foundation\Http\FormRequest;

class StoreMeetingAttachmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('edit_meetings') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'file' => ['required', 'file', 'max:10240', 'mimes:pdf,doc,docx,xls,xlsx,ppt,pptx,jpg,jpeg,png,webp,txt,csv'],
            'description' => ['nullable', 'string', 'max:255'],
        ];
    }
}
