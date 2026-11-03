<?php

return [
    /*
    | Which gateway takes online payments:
    |   test   - a built-in pretend gateway (no real money). Only works while allow_test is on.
    |   payway - ABA PayWay (added once you have a merchant account).
    */
    'driver' => env('PAYMENT_DRIVER', 'test'),

    // The test gateway is switched off on a live site unless you set PAYMENT_ALLOW_TEST=true.
    'allow_test' => (bool) env('PAYMENT_ALLOW_TEST', env('APP_ENV', 'production') === 'local'),

    'currency' => 'USD',
];