<?php

return [
    'entities_namespace' => 'App\Entities',

    // Http
    'middlewares' => [],

    'criteria' => [
        'params' => [
            'with' => '_with',
            'filter' => '_filter',
            'search' => '_search',
            'search_fields' => '_search_fields',
            'order' => 'order',
        ],
    ],
];
