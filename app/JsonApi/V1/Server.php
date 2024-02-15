<?php

namespace App\JsonApi\V1;

use App\JsonApi\V1\Orders\OrderSchema;
use App\Models\Customer;
use App\Models\LineItem;
use LaravelJsonApi\Core\Server\Server as BaseServer;

class Server extends BaseServer
{

    /**
     * The base URI namespace for this server.
     *
     * @var string
     */
    protected string $baseUri = '/api/v1';

    /**
     * Bootstrap the server when it is handling an HTTP request.
     *
     * @return void
     */
    public function serving(): void
    {
        // no-op
    }

    /**
     * Get the server's list of schemas.
     *
     * @return array
     */
    protected function allSchemas(): array
    {
        return [
            Customers\CustomerSchema::class,
            Inventories\InventorySchema::class,
            LineItems\LineItemSchema::class,
            Orders\OrderSchema::class,
            Products\ProductSchema::class,

        ];
    }
}
