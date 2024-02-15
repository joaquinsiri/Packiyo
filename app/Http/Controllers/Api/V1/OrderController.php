<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\JsonApi\V1\Orders\OrderResource;
use App\JsonApi\V1\Orders\OrderSchema;
use App\JsonApi\V1\Server;
use LaravelJsonApi\Laravel\Http\Controllers\Actions;
use App\Models\Order;
use App\Models\Inventory;
use App\Models\LineItem;
use App\Services\OrderService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use LaravelJsonApi\Core\Resources\JsonApiResource;
use LaravelJsonApi\Core\Support\AppResolver;

class OrderController extends Controller
{

    use Actions\FetchMany;
    use Actions\FetchOne;
    use Actions\Store;
    use Actions\Update;
    use Actions\Destroy;
    use Actions\FetchRelated;
    use Actions\FetchRelationship;
    use Actions\UpdateRelationship;
    use Actions\AttachRelationship;
    use Actions\DetachRelationship;

    protected $orderService;

    public function __construct(OrderService $orderService)
    {
        $this->orderService = $orderService;
    }

    public function store(Request $request)
    {
        $validatedData = $request->validate([
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
            'data.relationships.lineItems.data.*.attributes.quantity' => 'required|integer|min:1',
        ]);

        try {
            $order = $this->orderService->createOrder($validatedData);

            $appResolver = app(AppResolver::class);
            $serverName = 'v1';
            $server = new Server($appResolver, $serverName);
            $schema = new OrderSchema($server);

            return new OrderResource($schema, $order);
        } catch (\Exception $e) {
            return response()->json([
                'errors' => [
                    [
                        'status' => '500',
                        'title' => 'Internal Server Error',
                        'detail' => $e->getMessage(),
                    ],
                ],
            ], 500);
        }
    }
}