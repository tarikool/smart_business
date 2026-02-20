<?php

namespace App\Http\Requests\Transaction\Purchase;

use App\Enums\ContactType;
use App\Enums\PaymentMethod;
use App\Enums\TxnType;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class MachineryPurchaseRequest extends FormRequest
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
            'user_id' => 'required|integer|exists:users,id',
            'products' => ['required', 'array', 'min:1'],
            'products.*.product_id' => [
                'required',
                'integer',
                Rule::exists('product_user', 'product_id')
                    ->where('user_id', $this->user_id)
                    ->where('category_id', $this->machinery_category_id),
            ],
            'products.*.quantity' => [
                'required',
                'integer',
                'gt:0',
            ],
            'products.*.unit_price' => [
                'required',
                'numeric',
                'gte:0',
            ],
            'products.*.unit_option_id' => [
                'required',
                'integer',
                'exists:unit_options,id',
            ],
            'products.*.max_stock' => [
                'required',
                'integer',
                'gte:0',
            ],

            'txn_date' => ['required', 'date_format:Y-m-d H:i:s'],
            'is_new_customer' => ['required', 'boolean'],

            'customer_id' => [
                'exclude_if:is_new_customer,true',
                'required',
                'integer',
                Rule::exists('contacts', 'id')
                    ->where('user_id', $this->user_id),
            ],
            'customer' => [
                'exclude_if:is_new_customer,false',
                'required',
                'array',
            ],
            'customer.name' => [
                'nullable',
                'string',
                'max:100',
            ],
            'customer.phone_number' => [
                'required_if:is_new_customer,true',
                'string',
                'max:16',
            ],
            'customer.address' => [
                'nullable',
                'string',
                'max:255',
            ],
            'customer.contact_type' => [
                'required_if:is_new_customer,true',
                Rule::enum(ContactType::class),
            ],
            'payment_method' => [
                'required',
                Rule::enum(PaymentMethod::class),
            ],
            'total' => [
                'required',
                'numeric',
                'gte:0',
            ],
            'is_fixed_discount' => [
                'required',
                'boolean',
            ],
            'discount_value' => [
                'required',
                'numeric',
                'gte:0',
            ],
            'discount_amount' => [
                'required',
                'numeric',
                'gte:0',
                'lte:total',
            ],
            'due' => [
                'nullable',
                'numeric',
                'gte:0',
                'lte:'.($this->total - $this->discount_amount),
            ],
            'due_date' => [
                Rule::excludeIf(! $this->due),
                'nullable',
                'date',
                'after_or_equal:transaction_date',
            ],
        ];
    }

    protected function prepareForValidation()
    {
        $userId = auth()->id() ?: $this->user_id;
        $user = User::findOrFail($userId);
        $baseUnit = getMachineryBaseUnit();

        $total = collect($this->products)->sum(function ($item) {
            return $item['quantity'] * $item['unit_price'];
        });

        $machinesInput = collect($this->products)->map(function ($item) use ($baseUnit) {
            $item['unit_option_id'] = $baseUnit->defaultUnit?->id;

            return $item;
        });

        $this->merge([
            'machinery_category_id' => getMachineryCatgory($user)->id,
            'total' => $total,
            'user_id' => $user->id,
            'discount_amount' => getDiscount($this->is_fixed_discount, $total, $this->discount_value),
            'products' => $machinesInput->toArray(),
        ]);

    }

    public function validated($key = null, $default = null)
    {
        $user = User::find($this->user_id);

        $extraData = [
            'txn_type' => TxnType::MACHINERY_PURCHASE->value,
            'is_due' => $this->due > 0,
            'currency_id' => $user->country->currency_id,
        ];

        $data = parent::validated() + $extraData;

        return $key ? $data[$key] ?? null : $data;
    }

    public function messages()
    {
        return [
            //            'due.lte' => 'Invalid due amount',
        ];
    }
}
