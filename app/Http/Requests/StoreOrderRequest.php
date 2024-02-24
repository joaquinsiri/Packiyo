<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\Inventory;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\Log;

class StoreOrderRequest extends FormRequest
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
            'data.type' => 'required|in:orders',
            'data.relationships.customer.data.id' => 'required|exists:customers,id',
            'data.relationships.lineItems.data.*.type' => 'required|in:line-items',
            'data.relationships.lineItems.data.*.attributes.product_id' => [
                'required',
                'exists:products,id',
                function ($attribute, $value, $fail) {
                    if (!Inventory::where('product_id', $value)->exists()) {
                        $fail('The product with id ' . $value . ' does not exist in the inventory.');
                    }
                },
            ],
            'data.relationships.lineItems.data.*.attributes.quantity' => [
                'required',
                'integer',
                'min:1',
                function ($attribute, $value, $fail) {
                    $productIdKey = str_replace('quantity', 'product_id', $attribute);
                    $productId = $this->input($productIdKey);

                    $inventory = Inventory::where('product_id', $productId)->first();
                    if ($inventory && $value > $inventory->quantity) {
                        $fail('The quantity for the product with id ' . $productId . ' exceeds the available inventory.');
                    }
                },
            ],
        ];
    }

    public function messages()
    {
        return [
            'data.relationships.customer.data.id.exists' => 'Customer with id :input not found',
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        // Write the error messages to the log
        Log::info($validator->errors());

        // Call the parent method to keep the default behavior
        throw new HttpResponseException(response()->json($validator->errors(), 422));
    }
}
