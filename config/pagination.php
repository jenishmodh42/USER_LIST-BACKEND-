<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Pagination Default Per Page
    |--------------------------------------------------------------------------
    |
    | This value controls the default number of items displayed per page
    | for pagination throughout the application. This can be overridden
    | on a per-request basis using the per_page query parameter.
    |
    */

    'default_per_page' => 5,

    /*
    |--------------------------------------------------------------------------
    | Pagination Maximum Per Page
    |--------------------------------------------------------------------------
    |
    | This value controls the maximum number of items that can be requested
    | per page to prevent excessive database queries.
    |
    */

    'max_per_page' => 10,
];
