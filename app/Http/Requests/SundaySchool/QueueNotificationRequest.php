<?php

namespace App\Http\Requests\SundaySchool;

use Illuminate\Foundation\Http\FormRequest;

class QueueNotificationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth('admin')->check();
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'notification_type' => ['nullable', 'string', 'max:60'],
            'recipient_type' => ['nullable', 'in:student,teacher,parent,class,all'],
            'recipient_id' => ['nullable', 'integer'],
            'channel' => ['nullable', 'in:email,sms,whatsapp'],
            'subject' => ['required', 'string', 'max:255'],
            'body' => ['required', 'string'],
        ];
    }
}
