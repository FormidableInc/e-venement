Promotions API
==================

These endpoints will allow you to manage promotions. Base URI is '/api/v2/promotions'.

Promotions API response structure
-----------------------------

When you get a collection of resources, "Default" serialization group will be used and the following fields will be exposed:

+------------------+---------------------------------------------------+
| Field            | Description                                       |
+==================+===================================================+
| id               | Id of the promotion                               |
+------------------+---------------------------------------------------+
| type             | Type of the promotion                             |
+------------------+---------------------------------------------------+
| translations     | Collection of translations (name and description) |
+------------------+---------------------------------------------------+
| createdAt        | Date of creation                                  |
+------------------+---------------------------------------------------+
| expiresAt        | Date of expiration                                |
+------------------+---------------------------------------------------+
| state            | State of the promotion                            |
+------------------+---------------------------------------------------+
| value            | Value of the promotion (if applicable)            |
+------------------+---------------------------------------------------+
| prices           | Collection of available prices                    |
+------------------+---------------------------------------------------+
| events           | Collection of available events                    |
+------------------+---------------------------------------------------+

Available actions to interact with a promotion
----------------------------------------------

+------------------+----------------------------------------------+
| Action           | Description                                  |
+==================+==============================================+
| List             | Retrieve a collection of promotions          |
+------------------+----------------------------------------------+
| Show             | Getting a single promotion                   |
+------------------+----------------------------------------------+

Collection of promotions
--------------------

To retrieve a collection of promotions you will need to call the /api/v2/promotions endpoint with the GET method.

Definition
^^^^^^^^^^

.. code-block:: text

    GET /api/v2/promotions

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

.. code-block:: bash

    $ curl http://e-venement.local/api/v2/promotions \
        -H "Authorization: Bearer SampleToken" \
        -H "Content-Type: application/json" \
        -X GET \

Sample Response
^^^^^^^^^^^^^^^^^^

.. code-block:: text

    STATUS: 200 OK

.. code-block:: json

    {
        "page": 1,
        "limit": 4,
        "pages": 2,
        "total": 7,
        "_links": {
            "self": {
                "href": "\/api\/v2\/events\/?page=1&limit=4"
            },
            "first": {
                "href": "\/api\/v2\/events\/?page=1&limit=4"
            },
            "last": {
                "href": "\/api\/v2\/events\/?page=2&limit=4"
            },
            "next": {
                "href": "\/api\/v2\/events\/?page=2&limit=4"
            }
        },
        "_embedded": {
            "items": [
                {
                    "id":628,
                    "type":"Member card",
                    "translations":{
                        "en_US":{
                            "id":628,
                            "name":"Member card 16-17"
                        }
                    },
                    "createdAt":"2016-10-05T10:56:43+0100",
                    "expiresAt":"2017-09-01:00:00+0100",
                    "state":"active",
                    "value":0.00
                },
                {
                    "id":437,
                    "type":"Member card",
                    "translations":{
                        "en_US":{
                            "id":437,
                            "name":"Member card 15-16"
                        }
                    },
                    "createdAt":"2015-08-30T09:29:18+0100",
                    "expiresAt":"2016-09-01:00:00+0100",
                    "state":"expired",
                    "value":0.00
                }
            ]
        }
    }


Getting a single promotion
------------------------------

To retrieve the detail of a single promotion you will need to call the /api/v2/promotions/{id} endpoint with the GET method.

Definition
^^^^^^^^^^

.. code-block:: text

    GET /api/v2/promotions/{id}

+---------------+----------------+-------------------------------------------------------------------+
| Parameter     | Parameter type | Description                                                       |
+===============+================+===================================================================+
| Authorization | header         | Token received during authentication                              |
+---------------+----------------+-------------------------------------------------------------------+
| id            | query          | Id of the promotion                                               |
+---------------+----------------+-------------------------------------------------------------------+


Example
^^^^^^^

.. code-block:: bash

    $ curl http://e-venement.local/api/v2/promotions/628 \
        -H "Authorization: Bearer SampleToken" \
        -H "Content-Type: application/json" \
        -X GET \

Sample Response
^^^^^^^^^^^^^^^^^^

.. code-block:: text

    STATUS: 200 OK

.. code-block:: json

    {
        "id":628,
        "type":"Member card",
        "translations":{
            "en_US":{
                "id":628,
                "name":"Member card 16-17"
            }
        },
        "createdAt":"2016-10-05T10:56:43+0100",
        "expiresAt":"2017-09-01:00:00+0100",
        "state":"active",
        "value":0.00,
        "prices": [
            {
                "id":3,
                "translations":{
                    "en_US":{
                        "id":3,
                        "name":"Member price",
                        "description":"Member access"
                    }
                },
                "value":5.00
            }
        ],
        "events": []
    }
