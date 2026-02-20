<?php

namespace App\Http\Requests\Transaction;

use App\Enums\ContactType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateContactRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'user_id' => 'required|exists:users,id',
            'name' => 'nullable|string|max:100',
            'address' => 'nullable|string|max:255',
            'contact_type' => ['required', Rule::enum(ContactType::class)],
            'phone_number' => ['required', 'string', 'max:16'],
        ];
    }

    protected function prepareForValidation()
    {
        $this->merge(['user_id' => $this->transaction->user_id]);
    }
}
