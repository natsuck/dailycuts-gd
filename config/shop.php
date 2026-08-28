<?php

return [
    'shipping' => [
        'flat_fee' => (float) env('SHOP_FLAT_SHIPPING_FEE', 150),
        'lalamove_service_type' => env('LALAMOVE_SERVICE_TYPE', 'MOTORCYCLE'),
        'lalamove_special_requests' => env('LALAMOVE_SPECIAL_REQUESTS') ? explode(',', env('LALAMOVE_SPECIAL_REQUESTS')) : ['THERMAL_BAG_1'],
        'lalamove_item_weight' => env('LALAMOVE_ITEM_WEIGHT', 'LESS_THAN_20_KG'),
        'lalamove_item_categories' => env('LALAMOVE_ITEM_CATEGORIES') ? explode(',', env('LALAMOVE_ITEM_CATEGORIES')) : ['FOOD_AND_BEVERAGES'],
        'lalamove_item_handling' => env('LALAMOVE_ITEM_HANDLING') ? explode(',', env('LALAMOVE_ITEM_HANDLING')) : ['KEEP_DRY'],
        'fallback_tiers' => [
            ['max_km' => 5, 'fee' => 100],
            ['max_km' => 15, 'fee' => 150],
            ['max_km' => 30, 'fee' => 200],
            ['max_km' => PHP_FLOAT_MAX, 'fee' => 250],
        ],
    ],

    'store' => [
        'name' => env('SHOP_NAME', 'The Daily Cuts'),
        'branch_phone' => env('SHOP_BRANCH_PHONE', '+639291785657'),
        // Fixed warehouse/pickup location used as the origin for every delivery.
        'warehouse' => [
            'name' => env('SHOP_WAREHOUSE_NAME', 'The Daily Cuts'),
            'phone' => env('SHOP_WAREHOUSE_PHONE', '+639291785657'),
            'address' => env('SHOP_WAREHOUSE_ADDRESS', '161 Vallhalla Extension, Pasay City, Philippines'),
            'lat' => (float) env('SHOP_WAREHOUSE_LAT', 14.5378000),
            'lng' => (float) env('SHOP_WAREHOUSE_LNG', 121.0022000),
        ],
        'branches' => [
            [
                'name' => 'Pasay',
                'address' => '161 Vallhalla Extension, Pasay City, Philippines',
                'lat' => 14.5378000,
                'lng' => 121.0022000,
            ],
            [
                'name' => 'General Trias',
                'address' => 'Phase 10 Block 50 Lot 8 Wellington Place Pascam II, General Trias, Cavite, Philippines',
                'lat' => 14.3336000,
                'lng' => 120.8809000,
            ],
        ],
    ],

    'coverage_cities' => [
        // Metro Manila (NCR)
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
        'Pasay' => ['lat' => 14.5502, 'lng' => 120.9908],
        'Pasig' => ['lat' => 14.5611, 'lng' => 121.0942],
        'Quezon City' => ['lat' => 14.6760, 'lng' => 121.0437],
        'San Juan' => ['lat' => 14.6017, 'lng' => 121.0256],
        'Taguig' => ['lat' => 14.5176, 'lng' => 121.0500],
        'Valenzuela' => ['lat' => 14.7000, 'lng' => 120.9830],
        'Pateros' => ['lat' => 14.5510, 'lng' => 121.0690],

        // Cavite
        'Bacoor' => ['lat' => 14.4590, 'lng' => 120.9289],
        'Imus' => ['lat' => 14.4297, 'lng' => 120.9367],
        'Dasmariñas' => ['lat' => 14.3294, 'lng' => 120.9367],
        'General Trias' => ['lat' => 14.3058, 'lng' => 120.8636],
        'Tanza' => ['lat' => 14.3917, 'lng' => 120.8531],
        'Kawit' => ['lat' => 14.4442, 'lng' => 120.9017],
        'Rosario' => ['lat' => 14.4139, 'lng' => 120.8517],
        'Trece Martires' => ['lat' => 14.2792, 'lng' => 120.8720],
        'Tagaytay' => ['lat' => 14.1153, 'lng' => 120.9622],
        'Silang' => ['lat' => 14.2156, 'lng' => 120.9750],
        'Naic' => ['lat' => 14.3167, 'lng' => 120.7667],
        'Carmona' => ['lat' => 14.3147, 'lng' => 121.0644],
        'General Mariano Alvarez' => ['lat' => 14.2992, 'lng' => 121.0586],

        // Laguna
        'San Pedro' => ['lat' => 14.3594, 'lng' => 121.0556],
        'Biñan' => ['lat' => 14.3328, 'lng' => 121.0811],
        'Santa Rosa' => ['lat' => 14.3122, 'lng' => 121.1114],
        'Cabuyao' => ['lat' => 14.2725, 'lng' => 121.1281],
        'Calamba' => ['lat' => 14.1956, 'lng' => 121.1411],
        'Los Baños' => ['lat' => 14.1667, 'lng' => 121.2167],
        'San Pablo' => ['lat' => 14.0683, 'lng' => 121.3250],
        'Santa Cruz' => ['lat' => 14.2814, 'lng' => 121.4156],

        // Rizal
        'Antipolo' => ['lat' => 14.5872, 'lng' => 121.1760],
        'Cainta' => ['lat' => 14.5786, 'lng' => 121.1167],
        'Taytay' => ['lat' => 14.5592, 'lng' => 121.1333],
        'Binangonan' => ['lat' => 14.4667, 'lng' => 121.2000],
        'Angono' => ['lat' => 14.5264, 'lng' => 121.1533],

        // Batangas
        'Santo Tomas' => ['lat' => 14.1083, 'lng' => 121.1422],
        'Tanauan' => ['lat' => 14.0864, 'lng' => 121.1497],
        'Lipa' => ['lat' => 13.9411, 'lng' => 121.1622],
        'Batangas City' => ['lat' => 13.7564, 'lng' => 121.0583],
        'Nasugbu' => ['lat' => 14.0733, 'lng' => 120.6317],
        'Lemery' => ['lat' => 13.9167, 'lng' => 120.8833],
        'Taal' => ['lat' => 13.8800, 'lng' => 120.9400],
    ],
];
