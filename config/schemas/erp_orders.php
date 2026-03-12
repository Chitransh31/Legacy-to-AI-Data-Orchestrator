<?php

declare(strict_types=1);

return [
    'table' => 'orders',
    'mappings' => [
        'ORD_ID' => 'order_id',
        'CUST_NM' => 'customer_name',
        'CUST_EMAIL' => 'customer_email',
        'ORD_DT' => 'order_date',
        'SHIP_DT' => 'ship_date',
        'ORD_TOT' => 'order_total',
        'ORD_STATUS' => 'status',
        'PROD_DESC' => 'product_description',
        'QTY' => 'quantity',
        'UNIT_PRC' => 'unit_price',
    ],
    'required' => ['ORD_ID', 'CUST_NM', 'ORD_DT', 'ORD_TOT'],
    'types' => [
        'ORD_ID' => 'string',
        'ORD_TOT' => 'float',
        'QTY' => 'int',
        'UNIT_PRC' => 'float',
        'ORD_DT' => 'date',
        'SHIP_DT' => 'date',
    ],
    'exclude' => ['INTERNAL_ID', 'AUDIT_TS', 'MODIFIED_BY'],
    'max_lengths' => [
        'PROD_DESC' => 200,
        'CUST_NM' => 100,
    ],
    'importance' => ['order_id', 'customer_name', 'order_date', 'order_total', 'status', 'quantity'],
];
