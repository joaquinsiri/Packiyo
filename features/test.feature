Feature: Order management
  In order to manage inventory and fulfill customer orders
  As a warehouse manager
  I need to be able to create and retrieve orders

  Scenario: Creating a valid order
    Given there is a customer
    And there is a product in the inventory
    When I create an order for the customer with the product
    Then the response status code should be 201
    And the order should be created in the database
    And the order should indicate its shipping readiness
    And the inventory for each product should be decreased

  Scenario: Retrieving an existing order
    Given there is an order
    When I retrieve the order
    Then the retrieval should be successful
    And the response should contain the order

  Scenario: Retrieving a non-existing order
    Given there is no order
    When I retrieve the order
    Then the response should not be successful



  Scenario: Creating an order with invalid data
  Given there is a customer
  And there is a product in the inventory
  When I create an order with the following invalid data:
    | type | orders |
    | relationships.customer.data.id | customer_id |
    | relationships.lineItems.data.0.type | line-items |
    | relationships.lineItems.data.0.attributes.product_id | product_id |
    | relationships.lineItems.data.0.attributes.quantity | -1 |
  Then the response status code should be 422
  And the response should contain the error message "The data.relationships.lineItems.data.0.attributes.quantity field must be at least 1."

  Scenario: Creating an order with a non-existing customer
    Given there is no customer in the database
    And there is a product in the inventory
    When I create an order for the non-existing customer with the product
    Then the response status code should be 422
    And the response should contain the customer not found error message

  Scenario: Creating an order with a non-existing product
    Given there is a customer
    And there is no product in the inventory
    When I create an order for the customer with the non-existing product
    Then the response status code should be 422
    And the response should contain the error messages
      | The selected data.relationships.lineItems.data.0.attributes.product_id is invalid. |
      | The product with id 999999 does not exist in the inventory. |

  Scenario: Creating an order with a quantity that exceeds the available stock
    Given there is a customer
    And there is a product in the inventory
    When I create an order with the following invalid data:
      | type | orders |
      | relationships.customer.data.id | customer_id |
      | relationships.lineItems.data.0.type | line-items |
      | relationships.lineItems.data.0.attributes.product_id | product_id |
      | relationships.lineItems.data.0.attributes.quantity | quantity_exceeds_stock |
    Then the response status code should be 422
    And the response should contain the error message for the product

  Scenario: Retrieving an order with included entities that do not exist
    Given there is an order
    When I make a GET request to the order's API endpoint with non-existing entities
    Then the response status code should be 400
    And the response should contain the error message "Include path non_existing_entities is not allowed."

  Scenario Outline: Create an order with multiple line items
    Given the "Accept" request header is "application/vnd.api+json"
    And the "Content-Type" request header is "application/vnd.api+json"
    And the request body is:
    """
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
                    "data": <line_items>
                }
            }
        }
    }
    """
    When I request "/api/v1/orders" using HTTP POST
    Then the response status code should be 201

    Examples:
      | customer_id | line_items                                                                 |
      | 1           | [{"type": "line-items", "attributes": {"product_id": "1", "quantity": "1"}}] |
      | 2           | [{"type": "line-items", "attributes": {"product_id": "2", "quantity": "2"}}, {"type": "line-items", "attributes": {"product_id": "3", "quantity": "3"}}] |

  Scenario Outline: Retrieve an order with multiple included entities
    Given the "Accept" request header is "application/vnd.api+json"
    When I request "/api/v1/orders/<order_id>?include=<include_entities>" using HTTP GET
    Then the response status code should be 200

    Examples:
      | order_id | include_entities                                         |
      | 115      | customer,lineItems.product,lineItems.product.inventory   |
      | 116      | customer                                                 |
      | 117      | lineItems.product,lineItems.product.inventory            |
