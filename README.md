<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

## Setup Instructions

1. Ensure Docker is installed. Run `docker -v` to check.
2. Clone this repository: `git clone https://github.com/joaquinsiri/Packiyo.git`.
3. Navigate to the project directory: `cd Packiyo`.
4. Install Laravel dependencies: `./vendor/bin/sail composer install`.
5. Copy `.env.example` to `.env`.
6. Start the Laravel Sail Docker environment: `./vendor/bin/sail up`.
7. Generate app key: `./vendor/bin/sail artisan key:generate`.
8. Run the database migrations: `./vendor/bin/sail artisan migrate`.
9. Seed the database: `./vendor/bin/sail artisan db:seed`.

After following these steps, the application should be accessible at http://localhost.

## API Endpoints

### Retrieve an Order

GET http://localhost/api/v1/orders/{orderId}

You can include related entities in the response by adding the `include` query parameter. For example, to include the customer, line items, product, and inventory for an order, use:

http://localhost/api/v1/orders/115?include=customer,lineItems.product,lineItems.product.inventory

Set the following header:

- `Accept`: `application/vnd.api+json`

### Create an Order

POST http://localhost/api/v1/orders

Use the following example for the raw JSON request body:

```json
{
    "data": {
        "type": "orders",
        "relationships": {
            "customer": {
                "data": {
                    "type": "customers",
                    "id": "<customer_id>"
                }
            },
            "lineItems": {
                "data": [
                    {
                        "type": "line-items",
                        "attributes": {
                            "product_id": "<product_id_1>",
                            "quantity": "<quantity_1>"
                        }
                    },
                    {
                        "type": "line-items",
                        "attributes": {
                            "product_id": "<product_id_2>",
                            "quantity": "<quantity_2>"
                        }
                    }
                ]
            }
        }
    }
}
```
Replace <customer_id>, <product_id_1>, <quantity_1>, <product_id_2>, and <quantity_2> with the actual values you want to use for the order.

Set the following headers:

Accept: application/vnd.api+json
Content-Type: application/vnd.api+json


## ER Diagram and Model Relations

```
Customer 1..* Order 1..* LineItem *..1 Product 1..1 Inventory

+----------------+     +----------------+     +----------------+
|   Customer     |     |     Order      |     |   LineItem    |
+----------------+     +----------------+     +----------------+
| - id           | 1  *| - id           |*   *| - id          |
| - name         |-----| - customer_id  |-----| - order_id    |
+----------------+     | - created_at   |     | - product_id  |
                       | - updated_at   |     | - quantity    |
                       +----------------+     +----------------+
                                                           |
                                                           |1
                                                           |
                                                           |*
                                               +----------------+
                                               |   Product      |
                                               +----------------+
                                               | - id           |
                                               | - name         |
                                               | - price        |
                                               +----------------+
                                                           |
                                                           |1
                                                           |
                                                           |1
                                               +----------------+
                                               |   Inventory    |
                                               +----------------+
                                               | - id           |
                                               | - product_id   |
                                               | - quantity     |
                                               | - allocated_quantity |
                                               +----------------+
