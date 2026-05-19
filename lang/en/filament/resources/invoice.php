<?php
declare(strict_types=1);
return [
    'label' => 'invoice',
    'plural_label' => 'invoices',
    'navigation_label' => 'Invoices',
    'fields' => [
        'invoice_number' => ['label' => 'Invoice #'],
        'client_type' => [
            'label' => 'Client Type',
            'people' => 'Person',
            'company' => 'Company',
        ],
        'client' => ['label' => 'Client'],
        'date' => ['label' => 'Date'],
        'due_date' => ['label' => 'Due Date'],
        'status' => ['label' => 'Status'],
        'total' => ['label' => 'Total'],
        'creator' => ['label' => 'Created By'],
        'team' => ['label' => 'Team'],
        'item_description' => ['label' => 'Description'],
        'quantity' => ['label' => 'Qty'],
        'unit_price' => ['label' => 'Unit Price'],
        'created_at' => ['label' => 'Created'],
        'updated_at' => ['label' => 'Updated'],
        'deleted_at' => ['label' => 'Deleted'],
    ],
    'sections' => [
        'general' => 'General',
        'items' => 'Invoice Items',
    ],
    'actions' => [
        'add_item' => 'Add Item',
    ],
    'filters' => [
        'date_range' => [
            'label' => 'Date Range',
            'from' => 'From',
            'until' => 'Until',
        ],
    ],
];