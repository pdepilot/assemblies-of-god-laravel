<?php

namespace App\Http\Requests\Members;

use App\Models\Member;
use App\Services\Members\MemberReadService;
use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Throwable;

class SaveMemberRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth('admin')->check();
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'portal_enabled' => $this->boolean('portal_enabled'),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $married = $this->input('marital_status') === 'married';
        $child = $this->isChildOrTeen();
        $photoRequired = $this->photoIsRequired();

        return [
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['required', 'string', 'max:30'],
            'phone_alt' => ['nullable', 'string', 'max:30'],
            'gender' => ['required', Rule::in(['male', 'female', 'other'])],
            'date_of_birth' => ['required', 'date', 'before_or_equal:today'],
            'marital_status' => ['required', Rule::in(['single', 'married', 'divorced', 'widowed', 'widow', 'widower', 'separated'])],
            'wedding_date' => [$married ? 'required' : 'nullable', 'date', 'before_or_equal:today'],
            'parent_name' => [Rule::requiredIf($child), 'nullable', 'string', 'max:255'],
            'parent_email' => [Rule::requiredIf($child), 'nullable', 'email', 'max:255'],
            'parent_phone' => [Rule::requiredIf($child), 'nullable', 'string', 'max:30'],
            'occupation' => ['required', 'string', 'max:120'],
            'address_line1' => ['required', 'string', 'max:255'],
            'address_line2' => ['required', 'string', 'max:255'],
            'city' => ['required', 'string', 'max:100'],
            'state' => ['required', 'string', 'max:100'],
            'postal_code' => ['required', 'string', 'max:20'],
            'country' => ['required', 'string', 'max:100'],
            'department' => ['required', 'string', 'max:100'],
            'status' => ['required', Rule::in(MemberReadService::STATUSES)],
            'joined_date' => ['required', 'date'],
            'death_notes' => ['nullable', 'string'],
            'notes' => ['nullable', 'string'],
            'portal_enabled' => ['sometimes', 'boolean'],
            'portal_password' => ['nullable', 'string', 'min:6', 'max:72'],
            'photo' => [$photoRequired ? 'required' : 'nullable', 'image', 'mimes:jpeg,png,jpg,webp', 'max:2048'],
            'remove_photo' => ['nullable', 'boolean'],
        ];
    }

    private function isChildOrTeen(): bool
    {
        $dob = trim((string) $this->input('date_of_birth', ''));
        if ($dob === '') {
            return false;
        }

        try {
            return Carbon::parse($dob)->age <= 19;
        } catch (Throwable) {
            return false;
        }
    }

    private function photoIsRequired(): bool
    {
        if ($this->boolean('remove_photo')) {
            return true;
        }

        if ($this->isMethod('POST')) {
            return true;
        }

        $member = $this->route('member');

        return ! ($member instanceof Member && filled($member->photo_path));
    }
}
