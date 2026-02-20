<?php

namespace App\Http\Requests\Transaction;

use App\Models\TransactionItem;
use Illuminate\Foundation\Http\FormRequest;

class ReturnRequest extends FormRequest
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
            'items' => 'required|array|min:1',
            'items.*.id' => ['required', 'integer'],
            'items.*.return_qty' => ['required', 'numeric', 'gt:0', 'lte:items.*.current_qty'],
            'items.*.current_qty' => ['required', 'numeric', 'gt:0'],
            'return_amount' => ['required', 'numeric', "between:$this->min_return,$this->total_paid"],
            'net_total' => ['required', 'numeric', 'gt:0'],
            'note' => ['nullable', 'string', 'max:255'],
        ];
    }

    protected function prepareForValidation()
    {
        $itemsInput = collect($this->items)->map(function ($input) {
            $returnQty = $input['return_qty'] ?? 0;
            $item = TransactionItem::where([
                'id' => $input['id'] ?? null,
                'txn_id' => $this->transaction->id,
            ])->firstOrFail();

            abort_if(($item->quantity - $returnQty) < 0, 422, 'Quantity is out of range.');

            $input['current_qty'] = $item->quantity;
            $input['price'] = $returnQty * $item->unit_price;

            return $input;
        });

        $total = $itemsInput->sum('price');
        $discount = $total * $this->transaction->discountPercentage * 0.01;
        $netTotal = $total - $discount;
        $minimumReturn = $netTotal - $this->transaction->due;
        $minimumReturn = max($minimumReturn, 0); // To handle minus value

        $this->merge([
            'min_return' => $minimumReturn,
            'total' => $total,
            'net_total' => $netTotal,
            'total_paid' => $this->transaction->total_paid,
            'items' => $itemsInput->toArray(),
        ]);
    }

    public function messages()
    {
        $returnMsg = $this->min_return == $this->total_paid
            ? "The return amount field must be $this->min_return."
            : "The return amount field must be between $this->min_return and $this->total_paid.";

        return [
            'items.*.current_qty.gt' => 'Quantity is out of range.',
            'return_amount.between' => $returnMsg,
            'net_total.gt' => 'Total items price must be greater than 0.',
        ];
    }
}
