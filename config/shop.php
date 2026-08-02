<?php

return [
    'shipping' => [
        'flat_fee' => (float) env('SHOP_FLAT_SHIPPING_FEE', 150),
        'lalamove_service_type' => env('LALAMOVE_SERVICE_TYPE', 'MOTORCYCLE'),
    ],

    'store' => [
        'name' => env('SHOP_NAME', 'The Daily Cuts by GD'),
        'branches' => [
            [
                'name' => 'Pasay',
                'address' => '161 Vallhalla Extension, Pasay City, Philippines',
                'lat' => 14.5378,
                'lng' => 121.0022,
            ],
            [
                'name' => 'General Trias',
                'address' => 'Phase 10 Block 50 Lot 8 Wellington Place Pascam II, General Trias, Cavite, Philippines',
                'lat' => 14.3336,
                'lng' => 120.8809,
            ],
        ],
    ],

    'metro_manila_cities' => [
        'Caloocan' => ['lat' => 14.6622, 'lng' => 120.9633],
        'Las Piñas' => ['lat' => 14.4497, 'lng' => 120.9813],
        'Makati' => ['lat' => 14.5547, 'lng' => 121.0500],
        'Malabon' => ['lat' => 14.6535, 'lng' => 120.9506],
        'Mandaluyong' => ['lat' => 14.5832, 'lng' => 121.0412],
        'Manila' => ['lat' => 14.5995, 'lng' => 120.9842],
        'Marikina' => ['lat' => 14.6507, 'lng' => 121.1028],
        'Muntinlupa' => ['lat' => 14.3832, 'lng' => 121.0440],
        'Navotas' => ['lat' => 14.6624, 'lng' => 120.9400],
        'Parañaque' => ['lat' => 14.4714, 'lng' => 121.0256],
        'Pasay' => ['lat' => 14.5378, 'lng' => 121.0022],
        'Pasig' => ['lat' => 14.5611, 'lng' => 121.0942],
        'Quezon City' => ['lat' => 14.6760, 'lng' => 121.0437],
        'San Juan' => ['lat' => 14.6017, 'lng' => 121.0256],
        'Taguig' => ['lat' => 14.5176, 'lng' => 121.0500],
        'Valenzuela' => ['lat' => 14.7000, 'lng' => 120.9830],
        'Pateros' => ['lat' => 14.5510, 'lng' => 121.0690],
        'Antipolo' => ['lat' => 14.5872, 'lng' => 121.1760],
    ],
];
