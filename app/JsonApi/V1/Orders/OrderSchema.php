<?php

namespace App\JsonApi\V1\Orders;

use App\Models\Order;
use LaravelJsonApi\Eloquent\Contracts\Paginator;
use LaravelJsonApi\Eloquent\Fields\ID;
use LaravelJsonApi\Eloquent\Filters\WhereIdIn;
use LaravelJsonApi\Eloquent\Pagination\PagePagination;
use LaravelJsonApi\Eloquent\Schema;
use LaravelJsonApi\Eloquent\Fields\Relations\BelongsTo;
use LaravelJsonApi\Eloquent\Fields\Relations\HasMany;
use LaravelJsonApi\Eloquent\Fields\Boolean;


class OrderSchema extends Schema
{

    /**
     * The model the schema corresponds to.
     *
     * @var string
     */
    public static string $model = Order::class;

    /* The maximum include path depth.
     *
     * @var int
     */
    protected int $maxDepth = 3;

    /**
     * The relationships that should be eager loaded.
     *
     * @var array
     */
    protected array $with = ['lineItems.product.inventory'];

    /**
     * Get the resource fields.
     *
     * @return array
     */
    public function fields(): array
    {
        return [
            ID::make(),
            BelongsTo::make('customer')->type('customers'),
            HasMany::make('lineItems')->type('line-items'),
            Boolean::make('readyToShip')->readOnly()
        ];
    }

    

    /**
     * Get the resource filters.
     *
     * @return array
     */
    public function filters(): array
    {
        return [
            WhereIdIn::make($this),
        ];
    }

    /**
     * Get the resource paginator.
     *
     * @return Paginator|null
     */
    public function pagination(): ?Paginator
    {
        return PagePagination::make();
    }

}
