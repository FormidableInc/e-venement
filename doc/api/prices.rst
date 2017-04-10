Prices API
==========

These endpoints will allow you to manage prices. Base URI is '/api/v2/prices'.

Prices API response structure
-----------------------------

When you get a collection of resources, "Default" serialization group will be used and the following fields will be exposed:

+------------------+----------------------------------------------+
| Field            | Description                                  |
+==================+==============================================+
| id               | Id of the price                              |
+------------------+----------------------------------------------+
| translations     | Collection of translations                   |
+------------------+----------------------------------------------+
| value            | Amount as float                              |
+------------------+----------------------------------------------+

Available actions to interact with a price
------------------------------------------

+------------------+----------------------------------------------+
| Action           | Description                                  |
+==================+==============================================+
| List             | Retrieve a collection of prices              |
+------------------+----------------------------------------------+

Collection of prices
--------------------

To retrieve a collection of prices you will need to call the /api/v2/prices endpoint with the GET method.

Definition
^^^^^^^^^^

.. code-block:: text

    GET /api/v2/prices

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

    $ curl http://e-venement.local/api/v2/prices \
        -H "Authorization: Bearer SampleToken" \
        -H "Content-Type: application/json" \
        -X GET \

Exemplary Response
^^^^^^^^^^^^^^^^^^

.. code-block:: text

    STATUS: 200 OK

.. code-block:: json


  {
    "id":3,
    "translations":{
        "en_US":{
            "id":3,
            "name":"Full rate",
            "description":"Base price"
        }
    },
    "value":8.00
  }
