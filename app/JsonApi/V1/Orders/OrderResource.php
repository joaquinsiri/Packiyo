<?php

namespace App\JsonApi\V1\Orders;

use Illuminate\Http\Request;
use LaravelJsonApi\Core\Resources\JsonApiResource;

class OrderResource extends JsonApiResource
{

    /**
     * Get the resource's attributes.
     *
     * @param Request|null $request
     * @return iterable
     */
    public function attributes($request): iterable
    {
        return [
            'readyToShip' => $this->readyToShip,
            'createdAt' => $this->created_at,
            'updatedAt' => $this->updated_at,
        ];
    }

    /**
     * Get the resource's relationships.
     *
     * @param Request|null $request
     * @return iterable
     */
    public function relationships($request): iterable
    {
        return [
            $this->relation('customer'),
            $this->relation('lineItems'),
        ];
    }

}
