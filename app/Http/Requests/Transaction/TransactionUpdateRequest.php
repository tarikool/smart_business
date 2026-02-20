<?php

namespace App\Http\Requests\Transaction;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Arr;
use Illuminate\Validation\Rule;

class TransactionUpdateRequest extends FormRequest
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
            'items' => [
                'required',
                'array',
                'size:'.$this->total_items,
                function ($attribute, $value, $fail) {
                    $existingItemIds = $this->transaction->transactionItems->pluck('id')->toArray();
                    $requestedIds = Arr::pluck($value, 'id');
                    $isValid = Arr::every($existingItemIds, fn ($id) => in_array($id, $requestedIds));

                    if (! $isValid) {
                        $fail('All transaction items must be present in the items array.');
                    }
                },
            ],
            'items.*.id' => ['required', 'integer',
                Rule::exists('transaction_items', 'id')
                    ->where('txn_id', $this->transaction->id),
            ],
            'items.*.new_qty' => ['required', 'numeric', 'min:0'],
            'items.*.new_unit_price' => ['required', 'numeric', 'min:0'],
            'return_amount' => ['required', 'numeric', "between:$this->min_return,$this->min_return"],
            'note' => ['nullable', 'string', 'max:255'],
        ];
    }

    protected function prepareForValidation()
    {
        $itemsInput = collect($this->items)->map(function ($input) {
            $input['new_price'] = ($input['new_qty'] ?? 0) * ($input['new_unit_price'] ?? 0);

            return $input;
        });
        $newTotal = $itemsInput->sum('new_price');
        $discount = $newTotal * $this->transaction->discountPercentage * 0.01;
        $netTotal = $newTotal - $discount;
        // When paid_amt is greater than new net_total, must return amount
        $minimumReturn = $this->transaction->totalPaid - $netTotal;

        $this->merge([
            'min_return' => max(0, $minimumReturn),
            'new_total' => $itemsInput->sum('new_price'),
            'new_net_total' => $netTotal,
            'total_paid' => $this->transaction->total_paid,
            'items' => $itemsInput->toArray(),
            'total_items' => $this->transaction->transactionItems->count(),
        ]);
    }

    public function messages()
    {
        return [
            'return_amount.between' => "The return amount field must be $this->min_return.",
        ];
    }
}
