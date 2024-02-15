<?php

namespace App\JsonApi\V1\Orders;

use Illuminate\Validation\Rule;
use LaravelJsonApi\Laravel\Http\Requests\ResourceRequest;
use LaravelJsonApi\Validation\Rule as JsonApiRule;

class OrderRequest extends ResourceRequest
{

    /**
     * Get the validation rules for the resource.
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            'customer' => JsonApiRule::toOne(),
            'line_items' => 'required|array',
            'line_items.*.product_id' => 'required|exists:products,id',
            'line_items.*.quantity' => 'required|integer|min:1',
        ];
    }

}
