Cart API
================

These endpoints will allow you to manage transactions and transaction items. Base URI is ``/api/v2/carts/``.

.. note::

    Remember that a **Transaction** in e-venement is called here a **Cart** that becomes an **Order** as soon
    as it is validated, probably through a payment receipt.

Cart API response structure
----------------------------

If you request a cart via API, you will receive an object with the following fields:

+-------------------+---------------------------------------------------------------------------------------+
| Field             | Description                                                                           |
+===================+=======================================================================================+
| id                | Id of the transaction                                                                 |
+-------------------+---------------------------------------------------------------------------------------+
| items             | List of items in the cart                                                             |
+-------------------+---------------------------------------------------------------------------------------+
| itemsTotal        | Sum of all items prices                                                               |
+-------------------+---------------------------------------------------------------------------------------+
| total             | Sum of items total and adjustments total                                              |
+-------------------+---------------------------------------------------------------------------------------+
| customer          | :doc:`The customer object serialized with the default data </api/customers>` for transaction |
+-------------------+---------------------------------------------------------------------------------------+
| currencyCode      | Currency of the cart                                                                  |
+-------------------+---------------------------------------------------------------------------------------+
| localeCode        | Locale of the cart (by default, the locale set in the API)                            |
+-------------------+---------------------------------------------------------------------------------------+
| checkoutState     | State of the checkout process of the cart (cart -> new -> fulfilled or cancelled      |
+-------------------+---------------------------------------------------------------------------------------+

CartItem API response structure
-------------------------------

Each CartItem in an API response will be build as follows:

+-------------------+--------------------------------------------------------------------------------------------+
| Field             | Description                                                                                |
+===================+============================================================================================+
| id                | Id of the cart item                                                                        |
+-------------------+--------------------------------------------------------------------------------------------+
| type              | Type of item (can be ticket, product or pass)                                              |
+-------------------+--------------------------------------------------------------------------------------------+
| quantity          | Quantity of item units                                                                     |
+-------------------+--------------------------------------------------------------------------------------------+
| declination       | Item family declination                                                                    |
+-------------------+--------------------------------------------------------------------------------------------+
| totalAmount       | Total amount for this item                                                                 |
+-------------------+--------------------------------------------------------------------------------------------+
| unitAmount        | Price of each item unit                                                                    |
+-------------------+--------------------------------------------------------------------------------------------+
| total             | Sum of units total and adjustments total of that cart item                                 |
+-------------------+--------------------------------------------------------------------------------------------+
| vat               | The VAT specific value of this item                                                        |
+-------------------+--------------------------------------------------------------------------------------------+
| units             | A collection of units related to the cart item                                             |
+-------------------+--------------------------------------------------------------------------------------------+
| unitsTotal        | Sum of all units of the cart item                                                          |
+-------------------+--------------------------------------------------------------------------------------------+
| adjustments       | List of adjustments related to the cart item                                               |
+-------------------+--------------------------------------------------------------------------------------------+
| adjustmentsTotal  | Sum of all item adjustments related to that cart item                                      |
+-------------------+--------------------------------------------------------------------------------------------+
| _link[product]    | Relative link to product                                                                   |
+-------------------+--------------------------------------------------------------------------------------------+
| _link[order]      | Relative link to order                                                                     |
+-------------------+--------------------------------------------------------------------------------------------+

CartItemUnit API response structure
-----------------------------------

Each CartItemUnit API response will be build as follows:

+-------------------+------------------------------------------+
| Field             | Description                              |
+===================+==========================================+
| id                | Id of the cart item unit                 |
+-------------------+------------------------------------------+
| adjustments       | List of adjustments related to the unit  |
+-------------------+------------------------------------------+
| adjustmentsTotal  | Sum of all units adjustments of the unit |
+-------------------+------------------------------------------+

Adjustment API response structure
---------------------------------

And each Adjustment will be build as follows:

+--------+----------------------------------------------------------+
| Field  | Description                                              |
+========+==========================================================+
| id     | Id of the adjustment                                     |
+--------+----------------------------------------------------------+
| type   | Type of the adjustment (E.g. *order_promotion* or *tax*) |
+--------+----------------------------------------------------------+
| label  | Label of the adjustment                                  |
+--------+----------------------------------------------------------+
| amount | Amount of the adjustment (value)                         |
+--------+----------------------------------------------------------+

.. note::

    An Adjustment can be VAT, shipping fees, promotion, etc.
    
Creating a cart
-----------------------

To create a new cart you will need to call the ``/api/v2/carts/`` endpoint with the ``POST`` method.

Definition
^^^^^^^^^^

.. code-block:: text

    POST /api/v2/transaction/

+---------------+----------------+----------------------------------------------------------+
| Parameter     | Parameter type | Description                                              |
+===============+================+==========================================================+
| Authorization | header         | Token received during authentication                     |
+---------------+----------------+----------------------------------------------------------+
| localeCode    | request        | Code of the locale in which the cart should be created   |
+---------------+----------------+----------------------------------------------------------+

Example
^^^^^^^

To create a new cart for the ``shop@example.com`` user with the ``en_US`` locale use the below method:

.. code-block:: bash

    $ curl http://e-venement.local/api/v2/cart/ \
        -H "Authorization: Bearer SampleToken" \
        -H "Content-Type: application/json" \
        -X POST \
        --data '
            {
                "localeCode": "en_US"
            }
        '

Sample Response
^^^^^^^^^^^^^^^^^^

.. code-block:: text

    STATUS: 201 Created

.. code-block:: json

    {
        "id":21,
        "items":[

        ],
        "itemsTotal":0,
        "adjustments":[

        ],
        "adjustmentsTotal":0,
        "total":0,
        "customer":{},
        "_links":{},
        "currencyCode":"EUR",
        "localeCode":"en_US",
        "checkoutState":"cart"
    }

.. note::

    A currency code will be added automatically based on the application settings.

.. warning::

    If you try to create a resource without localeCode, you will receive a ``400 Bad Request`` error, that will contain validation errors.

Example
^^^^^^^

.. code-block:: bash

    $ curl http://e-venement.local/api/v1/carts/ \
        -H "Authorization: Bearer SampleToken" \
        -H "Content-Type: application/json" \
        -X POST

Sample Response
^^^^^^^^^^^^^^^^^^

.. code-block:: text

    STATUS: 400 Bad Request

.. code-block:: json

    {
        "code":400,
        "message":"Validation Failed",
        "errors":{
            "children":{
                "localeCode":{
                    "errors":[
                        "This value should not be blank."
                    ]
                },
            }
        }
    }

Collection of Carts
-------------------

To retrieve a paginated list of carts you will need to call the ``/api/v2/carts/`` endpoint with the ``GET`` method.

Definition
^^^^^^^^^^

.. code-block:: text

    GET /api/v2/carts/

+---------------+----------------+------------------------------------------------------------------+
| Parameter     | Parameter type | Description                                                      |
+===============+================+==================================================================+
| Authorization | header         | Token received during authentication                             |
+---------------+----------------+------------------------------------------------------------------+
| page          | query          | *(optional)* Number of the page, by default = 1                  |
+---------------+----------------+------------------------------------------------------------------+
| paginate      | query          | *(optional)* Number of carts displayed per page, by default = 10, max = 100 |
+---------------+----------------+------------------------------------------------------------------+

Example
^^^^^^^

To see the first page of the paginated carts collection use the below method:

.. code-block:: bash

    $ curl http://e-venement.local/api/v2/carts/ \
        -H "Authorization: Bearer SampleToken" \
        -H "Accept: application/json"

Sample Response
^^^^^^^^^^^^^^^^^^

.. code-block:: text

    STATUS: 200 OK

.. code-block:: json

    {
        "page":1,
        "limit":10,
        "pages":1,
        "total":1,
        "_links":{
            "self":{
                "href":"\/api\/v2\/carts\/?page=1&limit=10"
            },
            "first":{
                "href":"\/api\/v2\/carts\/?page=1&limit=10"
            },
            "last":{
                "href":"\/api\/v2\/carts\/?page=1&limit=10"
            }
        },
        "_embedded":{
            "items":[
                {
                    "id":20535,
                    "items":[

                    ],
                    "itemsTotal":0,
                    "adjustments":[

                    ],
                    "adjustmentsTotal":0,
                    "total":0,
                    "customer":{
                        "id":1,
                        "email":"georges@example.com",
                        "firstName":"Georges",
                        "lastName":"MARTIN",
                        "_links":{
                            "self":{
                                "href":"\/api\/v2\/customers\/1"
                            }
                        }
                    },
                    "currencyCode":"EUR",
                    "localeCode":"en_US",
                    "checkoutState":"cart"
                }
            ]
        }
    }

Getting a Single Cart
---------------------

To retrieve details of the cart you will need to call the ``/api/v2/carts/{id}`` endpoint with ``GET`` method.

Definition
^^^^^^^^^^

.. code-block:: text

    GET /api/v2/carts/{id}

+---------------+----------------+--------------------------------------+
| Parameter     | Parameter type | Description                          |
+===============+================+======================================+
| Authorization | header         | Token received during authentication |
+---------------+----------------+--------------------------------------+
| id            | url attribute  | Id of the requested cart             |
+---------------+----------------+--------------------------------------+

Example
^^^^^^^

To see details of the cart with ``id = 21`` use the below method:

.. code-block:: bash

    $ curl http://e-venement.local/api/v2/carts/21 \
        -H "Authorization: Bearer SampleToken" \
        -H "Accept: application/json"

.. note::

    The *21* value was taken from the previous create response. Your value can be different.
    Check in the list of all carts if you are not sure which id should be used.

Sample Response
^^^^^^^^^^^^^^^^^^

.. code-block:: text

    STATUS: 200 OK

.. code-block:: json

    {
        "id":21,
        "items":[

        ],
        "itemsTotal":0,
        "adjustments":[

        ],
        "adjustmentsTotal":0,
        "total":0,
        "customer":{
            "id":1,
            "email":"georges@example.com",
            "firstName":"Georges",
            "lastName":"MARTIN",
            "_links":{
                "self":{
                    "href":"\/api\/v2\/customers\/1"
                }
            }
        },
        "currencyCode":"EUR",
        "localeCode":"en_US",
        "checkoutState":"cart"
    }

Deleting a Cart
---------------

A cart cannot be deleted. It simply has to be abandonned if needed.

Creating a Cart Item
--------------------

To add a new cart item to an existing cart you will need to call the ``/api/v2/carts/{cartId}/items/`` endpoint with ``POST`` method.

Definition
^^^^^^^^^^

.. code-block:: text

    POST /api/v2/carts/{cartId}/items/

+---------------+----------------+----------------------------------------------------------------+
| Parameter     | Parameter type | Description                                                    |
+===============+================+================================================================+
| Authorization | header         | Token received during authentication                           |
+---------------+----------------+----------------------------------------------------------------+
| cartId        | url attribute  | Id of the requested cart                                       |
+---------------+----------------+----------------------------------------------------------------+
| declinationId | request        | Code of the item you want to add to the cart                   |
+---------------+----------------+----------------------------------------------------------------+
| type          | request        | Type of item to add (can be ticket, product or pass)           |
+---------------+----------------+----------------------------------------------------------------+
| quantity      | request        | Amount of variants you want to add to the cart (cannot be < 1) |
+---------------+----------------+----------------------------------------------------------------+
| priceId       | request        | Price aimed for the item                                       |
+---------------+----------------+----------------------------------------------------------------+
| numerotations | request        | An array of specific items of the requested declinations (optional) |
+---------------+----------------+----------------------------------------------------------------+

Example
^^^^^^^

To add a new item of a product to the cart with id = 21 (assuming, that we didn't remove it in the
previous example) use the below method:

.. code-block:: bash

    $ curl http://e-venement.local/api/v2/carts/21/items/ \
        -H "Authorization: Bearer SampleToken" \
        -H "Content-Type: application/json" \
        -X POST \
        --data '
            {
                "type": "ticket",
                "declinationId: 52,
                "quantity": 1,
                "priceId": 3
            }
        '

Sample Response
^^^^^^^^^^^^^^^^^^

.. code-block:: text

    STATUS: 201 Created

.. code-block:: json

    {
        "id":57,
        "type": "ticket",
        "quantity":1,
        "unitAmount":250,
        "total":250,
        "units":[
            {
                "id":165,
                "adjustments":[

                ],
                "adjustmentsTotal":0
            }
        ],
        "unitsTotal":250,
        "adjustments":[

        ],
        "adjustmentsTotal":0,
        "declination":{
            "id": 52,
            "code": "3156844564",
            "position":2,
            "translations":{
                "en_US":{
                    "id":331,
                    "name":"Medium Mug"
                }
            },
        },
        "_links":{
            "order":{
                "href":"\/api\/v2\/carts\/21"
            },
            "declination":{
                "href":"\/api\/v2\/products\52"
            },
            "product":{
                "href":"\/api\/v2\/products\58"
            },
        }
    }
.. tip::

Updating a Cart Item
--------------------

To change the quantity of a cart item you will need to call the ``/api/v1/carts/{cartId}/items/{cartItemId}`` endpoint with the ``PUT`` or ``PATCH`` method.

Definition
^^^^^^^^^^

.. code-block:: text

    PUT /api/v1/carts/{cartId}/items/{cartItemId}

+---------------+----------------+--------------------------------------------------------------+
| Parameter     | Parameter type | Description                                                  |
+===============+================+==============================================================+
| Authorization | header         | Token received during authentication                         |
+---------------+----------------+--------------------------------------------------------------+
| cartId        | url attribute  | Id of the requested cart                                     |
+---------------+----------------+--------------------------------------------------------------+
| declinationId | url attribute  | Id of the requested declination                              |
+---------------+----------------+--------------------------------------------------------------+
| quantity      | request        | Amount of items you want to have in the cart (cannot be < 1) |
+---------------+----------------+--------------------------------------------------------------+
| numerotations | request        | An array of specific items of the requested declinations (optional) |
+---------------+----------------+--------------------------------------------------------------+

Example
^^^^^^^

To change the quantity of the cart item with ``id = 57`` in the cart of ``id = 21`` to 3 use the below method:


.. code-block:: bash

    $ curl http://e-venement.local/api/v2/carts/21/items/57 \
        -H "Authorization: Bearer SampleToken" \
        -H "Content-Type: application/json" \
        -X PUT \
        --data '{"quantity": 3}'

.. tip::

    If you are not sure where does the value **58** come from, check the previous response, and look for the cart item id.


Sample Response
^^^^^^^^^^^^^^^^^^

.. code-block:: text

    STATUS: 204 No Content

Now we can check how does the cart look like after changing the quantity of a cart item.

.. code-block:: bash

    $ curl http://e-venement.local/api/v2/carts/21 \
        -H "Authorization: Bearer SampleToken" \
        -H "Accept: application/json"

Sample Response
^^^^^^^^^^^^^^^^^^

.. code-block:: text

    STATUS: 200 OK

.. code-block:: json

    {
        "id":21,
        "items":[
            {
                "id":57,
                "type": "ticket",
                "quantity":3,
                "unitAmount":250,
                "total":750,
                "units":[
                    {
                        "id":165,
                        "adjustments":[

                        ],
                        "adjustmentsTotal":0
                    },
                    {
                        "id":166,
                        "adjustments":[

                        ],
                        "adjustmentsTotal":0
                    },
                    {
                        "id":167,
                        "adjustments":[

                        ],
                        "adjustmentsTotal":0
                    }
                ],
                "unitsTotal":750,
                "adjustments":[

                ],
                "adjustmentsTotal":0,
                "declination":{
                    "id":331,
                    "code":"MEDIUM_MUG_CUP",
                    "optionValues":[
                        {
                            "code":"mug_type_medium",
                            "translations":{
                                "en_US":{
                                    "id":1,
                                    "value":"Medium mug"
                                }
                            }
                        }
                    ],
                    "position":2,
                    "translations":{
                        "en_US":{
                            "id":331,
                            "name":"Medium Mug"
                        }
                    },
                    "tracked":false
                },
                "_links":{
                    "order":{
                        "href":"\/api\/v1\/orders\/21"
                    },
                    "product":{
                        "href":"\/api\/v1\/products\/07f2044a-855d-3c56-9274-b5167c2d5809"
                    },
                    "variant":{
                        "href":"\/api\/v1\/products\/07f2044a-855d-3c56-9274-b5167c2d5809\/variants\/MEDIUM_MUG_CUP"
                    }
                }
            }
        ],
        "itemsTotal":750,
        "adjustments":[
            {
                "id":181,
                "type":"shipping",
                "label":"UPS",
                "amount":157
            }
        ],
        "adjustmentsTotal":157,
        "total":907,
        "customer":{
            "id":1,
            "email":"shop@example.com",
            "firstName":"John",
            "lastName":"Doe",
            "user":{
                "id":1,
                "username":"shop@example.com",
                "usernameCanonical":"shop@example.com"
            },
            "_links":{
                "self":{
                    "href":"\/api\/v1\/customers\/1"
                }
            }
        },
        "currencyCode":"USD",
        "localeCode":"en_US",
        "checkoutState":"cart"
    }

.. tip::

    In this response you can see that promotion and shipping have been taken into account to calculate the appropriate price.

Deleting a Cart Item
--------------------

To delete a cart item from a cart you will need to call the ``/api/v2/carts/{cartId}/items/{cartItemId}`` endpoint with the ``DELETE`` method.

Definition
^^^^^^^^^^

To delete the cart item with ``id = 58`` from the cart with ``id = 21`` use the below method:

.. code-block:: text

    DELETE /api/v2/carts/{cartId}/items/{cartItemId}

+---------------+----------------+--------------------------------------+
| Parameter     | Parameter type | Description                          |
+===============+================+======================================+
| Authorization | header         | Token received during authentication |
+---------------+----------------+--------------------------------------+
| cartId        | url attribute  | Id of the requested cart             |
+---------------+----------------+--------------------------------------+
| cartItemId    | url attribute  | Id of the requested cart item        |
+---------------+----------------+--------------------------------------+

Example
^^^^^^^

.. code-block:: bash

    $ curl http://e-venement.local/api/v2/items/58 \
        -H "Authorization: Bearer SampleToken" \
        -H "Accept: application/json" \
        -X DELETE

Sample Response
^^^^^^^^^^^^^^^^^^

.. code-block:: text

    STATUS: 204 No Content
