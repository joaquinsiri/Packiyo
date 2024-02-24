<?php

use Behat\Behat\Context\Context;
use Behat\Gherkin\Node\TableNode;
use App\Models\Order;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Inventory;
use Behat\Gherkin\Node\PyStringNode;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\Assert;
use GuzzleHttp\Exception\ClientException;

class FeatureContext implements Context
{

    private $client;
    private $headers;
    private $response;
    private $body;
    private $customer;
    private $products = [];
    private $quantities = [];
    private $initialInventoryQuantities;
    private $orderId;
    private $customerId;
    private $productId;


    public function __construct()
    {
        $this->createApplication();
        $this->client = new Client(['base_uri' => 'http://localhost']);
        $this->headers = [];
    }

    protected function createApplication()
    {
        $app = require __DIR__ . '/../../bootstrap/app.php';
        $app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();
        $app->make('db')->connection()->beginTransaction();
    }

    public function sendRequest($method, $url, $data = [], $headers = [])
    {
        $response = $this->client->request($method, $url, [
            'headers' => array_merge($this->headers, $headers),
            'json' => $data
        ]);

        return $response;
    }

    /**
     * @Given there is a customer
     */
    public function thereIsACustomer()
    {
        $customer = Customer::factory()->create();
        $this->customer = $customer;
        app('db')->connection()->commit();
    }

    /**
     * @Given there is a product in the inventory
     */
    public function thereIsAProductInTheInventory()
    {
        // Define the number of products you want to create
        $numProducts = 3;
        $this->initialInventoryQuantities = [];

        for ($i = 0; $i < $numProducts; $i++) {
            $product = Product::factory()->create();
            Inventory::factory()->create(['product_id' => $product->id]);
            $this->products[] = $product;

            $inventory = Inventory::where('product_id', $product->id)->first();
            $this->initialInventoryQuantities[$product->id] = $inventory->quantity;
        }

        app('db')->connection()->commit();
    }

    /**
     * @When I create an order for the customer with the product
     */
    public function iCreateAnOrderForTheCustomerWithTheProduct()
    {
        $lineItems = [];
        $this->quantities = [];
        foreach ($this->products as $product) {
            $maxQuantity = min(50, $this->initialInventoryQuantities[$product->id]);
            $quantity = rand(1, $maxQuantity);
            $this->quantities[$product->id] = $quantity;
            $lineItems[] = [
                'type' => 'line-items',
                'attributes' => [
                    'product_id' => $product->id,
                    'quantity' => $quantity
                ]
            ];
        }

        $data = [
            'type' => 'orders',
            'relationships' => [
                'customer' => [
                    'data' => [
                        'id' => $this->customer->id
                    ]
                ],
                'lineItems' => [
                    'data' => $lineItems
                ]
            ]
        ];



        $this->response = $this->sendRequest('POST', '/api/v1/orders', ['data' => $data], ['Accept' => 'application/vnd.api+json', 'Content-Type' => 'application/vnd.api+json']);
        app('db')->connection()->commit();
    }

    /**
     * @Then the order should be created in the database
     */
    public function theOrderShouldBeCreatedInTheDatabase()
    {
        $response = json_decode($this->response->getBody(), true);
        $orderId = $response['data']['id'];
        Assert::assertTrue(Order::where('id', $orderId)->exists());
    }

    /**
     * @Then the order should indicate its shipping readiness
     */
    public function theOrderShouldIndicateItsShippingReadiness()
    {
        Assert::assertIsBool(Order::find(json_decode($this->response->getBody(), true)['data']['id'])->ready_to_ship);
    }

    /**
     * @Then the inventory for each product should be decreased
     */
    public function theInventoryForEachProductShouldBeDecreased()
    {
        foreach ($this->products as $product) {
            $actualQuantity = Inventory::where('product_id', $product->id)->first()->quantity;
            $expectedQuantity = $this->initialInventoryQuantities[$product->id] - $this->quantities[$product->id];
            Assert::assertEquals($expectedQuantity, $actualQuantity);
        }
    }

    /**
     * @Given there is an order
     */
    public function thereIsAnOrder()
    {
        $order = Order::factory()->withLineItems()->create();
        $this->orderId = $order->id;
        app('db')->connection()->commit();
    }

    /**
     * @When I retrieve the order
     */
    public function iRetrieveTheOrder()
    {
        try {
            $this->response = $this->sendRequest('GET', "/api/v1/orders/{$this->orderId}", [], ['Accept' => 'application/vnd.api+json']);
        } catch (ClientException $e) {
            $this->response = $e->getResponse();
        }
    }

    /**
     * @Then the retrieval should be successful
     */
    public function theRetrievalShouldBeSuccessful()
    {
        Assert::assertEquals(200, $this->response->getStatusCode());
    }

    /**
     * @Then the response should contain the order
     */
    public function theResponseShouldContainTheOrder()
    {
        $response = json_decode($this->response->getBody(), true);
        Assert::assertEquals($this->orderId, $response['data']['id']);
    }

    /**
     * @Given there is no order
     */
    public function thereIsNoOrder()
    {
        $this->orderId = 999999;
    }

    /**
     * @Then the response should not be successful
     */
    public function theResponseShouldNotBeSuccessful()
    {
        Assert::assertNotEquals(200, $this->response->getStatusCode());
    }

    /**
     * @Then the response status code should be :statusCode
     */
    public function theResponseStatusCodeShouldBe($statusCode)
    {
        if ($this->response instanceof Response) {
            Assert::assertEquals($statusCode, $this->response->getStatusCode());
        } else {
            $this->response->assertStatus($statusCode);
        }
    }

    /**
     * @When I create an order with the following invalid data:
     */
    public function iCreateAnOrderWithTheFollowingInvalidData(TableNode $table)
    {
        $data = [];
        foreach ($table->getRows() as $row) {
            $keys = explode('.', $row[0]);
            $value = $row[1];

            if ($value === 'customer_id') {
                $value = $this->customer->id;
            } elseif ($value === 'product_id') {
                $value = $this->products[0]->id;
            } elseif ($value === 'quantity_exceeds_stock') {
                $value = $this->products[0]->inventory->quantity + 1;
                $this->productId = $this->products[0]->id;
            }

            $temp = &$data;
            foreach ($keys as $key) {
                $temp = &$temp[$key];
            }
            $temp = $value;
        }
        $customerId = $data['relationships']['customer']['data']['id'] ?? null;
        if ($customerId) {
            try {
                $this->response = $this->sendRequest('POST', '/api/v1/orders', ['data' => $data], ['Accept' => 'application/vnd.api+json', 'Content-Type' => 'application/vnd.api+json']);
                app('db')->connection()->commit();
            } catch (\GuzzleHttp\Exception\ClientException $e) {
                app('db')->connection()->rollBack();
                $this->response = $e->getResponse();
                $errorBody = (string) $this->response->getBody();
                if ($this->response->getStatusCode() !== 422) {
                    throw $e;
                }
            }
        } else {
            throw new \Exception("Customer ID is not provided.");
        }
    }

    /**
     * @Given there is a product with id :productId in the inventory with quantity :quantity
     */
    public function thereIsAProductWithIdInTheInventoryWithQuantity($productId, $quantity)
    {
        if (!Product::find($productId)) {
            $product = Product::factory()->create(['id' => $productId]);
            Inventory::factory()->create(['product_id' => $product->id, 'quantity' => $quantity]);
        }
    }

    /**
     * @Then the response should contain the error message :errorMessage
     */
    public function theResponseShouldContainTheErrorMessage($errorMessage)
    {
        $body = (string) $this->response->getBody();
        if (strpos($body, $errorMessage) === false) {
            throw new \Exception("The response does not contain the error message: $errorMessage");
        }
    }

    /**
     * @Given there is no customer in the database
     */
    public function thereIsNoCustomerInTheDatabase()
    {
        Customer::find(999999);
    }

    /**
     * @Given there is no product in the inventory
     */
    public function thereIsNoProductInTheInventory()
    {
        Product::find(999999);
    }

    /**
     * @When I create an order for the non-existing customer with the product
     */
    public function iCreateAnOrderForTheNonExistingCustomerWithTheProduct()
    {
        $product = Product::factory()->create();
        Inventory::factory()->create(['product_id' => $product->id]);
        $this->products[] = $product;

        $lineItems = [
            [
                'type' => 'line-items',
                'attributes' => [
                    'product_id' => $product->id,
                    'quantity' => 1
                ]
            ]
        ];

        $this->customerId = 999999;

        $data = [
            'type' => 'orders',
            'relationships' => [
                'customer' => [
                    'data' => [
                        'id' => $this->customerId
                    ]
                ],
                'lineItems' => [
                    'data' => $lineItems
                ]
            ]
        ];

        try {
            $this->response = $this->sendRequest('POST', '/api/v1/orders', ['data' => $data], ['Accept' => 'application/vnd.api+json', 'Content-Type' => 'application/vnd.api+json']);
            app('db')->connection()->commit();
        } catch (ClientException $e) {

            app('db')->connection()->rollBack();
            $this->response = $e->getResponse();
        }
    }

    /**
     * @Then the response should contain the customer not found error message
     */
    public function theResponseShouldContainTheCustomerNotFoundErrorMessage()
    {
        $errorMessage = "Customer with id {$this->customerId} not found";

        $body = (string) $this->response->getBody();
        $bodyArray = json_decode($body, true);

        if (!isset($bodyArray['data.relationships.customer.data.id']) || !in_array($errorMessage, $bodyArray['data.relationships.customer.data.id'])) {
            throw new \Exception("The response does not contain the error message: $errorMessage");
        }
    }

    /**
     * @When I create an order for the customer with the non-existing product
     */
    public function iCreateAnOrderForTheCustomerWithTheNonExistingProduct()
    {
        $lineItems = [
            [
                'type' => 'line-items',
                'attributes' => [
                    'product_id' => 999999,
                    'quantity' => 1
                ]
            ]
        ];

        $data = [
            'type' => 'orders',
            'relationships' => [
                'customer' => [
                    'data' => [
                        'id' => $this->customer->id
                    ]
                ],
                'lineItems' => [
                    'data' => $lineItems
                ]
            ]
        ];

        try {
            $this->response = $this->sendRequest('POST', '/api/v1/orders', ['data' => $data], ['Accept' => 'application/vnd.api+json', 'Content-Type' => 'application/vnd.api+json']);
            app('db')->connection()->commit();
        } catch (ClientException $e) {
            app('db')->connection()->rollBack();
            $this->response = $e->getResponse();
        }
    }

    /**
     * @Then the response should contain the error messages
     */
    public function theResponseShouldContainTheErrorMessages(TableNode $table)
    {
        $body = (string) $this->response->getBody();
        $errorMessages = $table->getColumn(0);
        foreach ($errorMessages as $errorMessage) {
            if (strpos($body, $errorMessage) === false) {
                throw new \Exception("The response does not contain the error message: $errorMessage");
            }
        }
    }

    /**
     * @Then the response should contain the error message for the product
     */
    public function theResponseShouldContainTheErrorMessageForProduct()
    {
        $expectedErrorMessage = "The quantity for the product with id $this->productId exceeds the available inventory.";
        $body = (string) $this->response->getBody();
        if (strpos($body, $expectedErrorMessage) === false) {
            throw new \Exception("The response does not contain the error message: $expectedErrorMessage");
        }
    }

    /**
     * @When I make a GET request to the order's API endpoint with non-existing entities
     */
    public function iRequestWithIncludeNonExistingEntitiesUsingHttpGet()
    {
        try {
            $this->response = $this->sendRequest('GET', "/api/v1/orders/{$this->orderId}?include=non_existing_entities", [], ['Accept' => 'application/vnd.api+json']);
        } catch (ClientException $e) {
            if ($this->response !== null) {
            }
            $this->response = $e->getResponse();
        }
    }

    /**
     * @Given the :header request header is :value
     */
    public function theRequestHeaderIs($header, $value)
    {
        $this->headers[$header] = $value;
    }

    /**
     * @Given the request body is:
     */
    public function theRequestBodyIs(PyStringNode $body)
    {
        $this->body = json_decode($body->getRaw(), true);
    }

    /**
     * @When I request :url using HTTP POST
     */
    public function iRequestUsingHttpPost($url)
    {
        $this->response = $this->sendRequest('POST', $url, $this->body, $this->headers);
    }

    /**
     * @When I request :url using HTTP GET
     */
    public function iRequestUsingHttpGet($url)
    {
        $this->response = $this->sendRequest('GET', $url, [], $this->headers);
    }
}
