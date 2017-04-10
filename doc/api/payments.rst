Payments API
============

These endpoints will allow you to easily present payments. Base URI is '/api/v2/payments'.

Product API response structure
------------------------------

+------------------+----------------------------------------------------------------------------------------+
| Field            | Description                                                                            |
+==================+========================================================================================+
| id               | Unique id of the payment                                                               |
+------------------+----------------------------------------------------------------------------------------+
| method           | Payment method name                                                                    |
+------------------+----------------------------------------------------------------------------------------+
| amount           | The amount of payment                                                                  |
+------------------+----------------------------------------------------------------------------------------+
| state            | State of the payment process (pending, "completed" is the only implementated state yet)|
+------------------+----------------------------------------------------------------------------------------+
| createAt         | The date and time of creation (`ISO 8601 Extended Format <https://fr.wikipedia.org/wiki/ISO_8601>`)|
+------------------+----------------------------------------------------------------------------------------+
| _link[order]     | Link to the related order                                                              |
+------------------+----------------------------------------------------------------------------------------+

Getting a single payment
------------------------

To retrieve the details of a payment:

Definition
^^^^^^^^^^

.. code-block:: text

    GET /api/v2/payments/{id}
    
+------------------------------+----------------+-----------------------------------------------------------------------------------------------------+
| Parameter                    | Parameter type | Description                                                                                         |
+==============================+================+=====================================================================================================+
| Authorization                | header         | Token received during authentication                                                                |
+------------------------------+----------------+-----------------------------------------------------------------------------------------------------+
| Id                           | url attribute  | Id of the requested payment                                                                         |
+------------------------------+----------------+-----------------------------------------------------------------------------------------------------+

Example
^^^^^^^

To retrieve the payment 20, the following snippet can be used:

.. code-block:: bash

    $ curl http://e-venement.local/api/v2/payments/20 \
        -H "Authorization: Bearer SampleToken" \
        -H "Content-Type: application/json" \
        -X POST \

Sample Response
^^^^^^^^^^^^^^^^^^

.. code-block:: text

    STATUS: 200 OK
    
.. code-block:: json

    {
        "id":20,
        "method":"bank_transfer",
        "amount":4507,
        "state":"completed",
        "createdAt":"2017-04-07T12:42:02Z",
        "_links":{
            "order":{
                "href":"\/api\/v2\/orders\/21"
            }
        }
    }

Collection of payments
----------------------

To retrieve a paginated list of payments:

Definition
^^^^^^^^^^

.. code-block:: text

    GET /api/v2/payments
    
+---------------+----------------+-------------------------------------------------------------------+
| Parameter     | Parameter type | Description                                                       |
+===============+================+===================================================================+
| Authorization | header         | Token received during authentication                              |
+---------------+----------------+-------------------------------------------------------------------+
| page          | query          | *(optional)* Number of the page, by default = 1                   |
+---------------+----------------+-------------------------------------------------------------------+
| limit         | query          | *(optional)* Number of items to display per page, by default = 10 |
+---------------+----------------+-------------------------------------------------------------------+


Example
^^^^^^^

To see the first page of the paginated list of payments with two payments on each page use the below snippet:

.. code-block:: bash

    $ curl http://e-venement.local/api/v2/payments/?limit=2 \
        -H "Authorization: Bearer SampleToken" \
        -H "Content-Type: application/json" \
        -X POST \
        
Sample Response
^^^^^^^^^^^^^^^^^^

.. code-block:: text

    STATUS: 200 OK
    
.. code-block:: json

    {
        "page":1,
        "limit":2,
        "pages":10,
        "total":20,
        "_links":{
            "self":{
                "href":"\/api\/v2\/payments\/?page=1&limit=2"
            },
            "first":{
                "href":"\/api\/v2\/payments\/?page=1&limit=2"
            },
            "last":{
                "href":"\/api\/v2\/payments\/?page=10&limit=2"
            },
            "next":{
                "href":"\/api\/v2\/payments\/?page=2&limit=2"
            }
        },
        "_embedded":{
            "items":[
                {
                    "id":20,
                    "method":"bank_transfer",
                    "amount":4507,
                    "createdAt":"2017-04-07T12:42:02Z",
                    "state":"completed",
                    "_links":{
                        "order":{
                            "href":"\/api\/v2\/orders\/21"
                        }
                    }
                },
                {
                    "id":21,
                    "method":"bank_transfer",
                    "amount":3812,
                    "createdAt":"2017-03-05T10:01:04Z",
                    "state":"completed",
                    "_links":{
                        "order":{
                            "href":"\/api\/v2\/orders\/22"
                        }
                    }
                },
            ]
        }
    }
