<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once __DIR__ . '/../includes/db.php';

// Initialize database connection
$pdo = getPdo();

// API endpoint to get comprehensive channel data for external systems
$action = $_GET['action'] ?? '';

try {
    switch ($action) {
        case 'get_channel_data':
            // Simulate data that would come from Booking.com, Expedia, etc.
            $channelData = [
                'channels' => [
                    [
                        'id' => 1,
                        'name' => 'booking.com',
                        'display_name' => 'Booking.com',
                        'type' => 'OTA',
                        'status' => 'Active',
                        'commission_rate' => 15.00,
                        'currency' => 'PHP',
                        'contact_person' => 'Booking.com Support',
                        'contact_email' => 'api@booking.com',
                        'api_endpoint' => 'https://api.booking.com/v1',
                        'last_updated' => date('c')
                    ],
                    [
                        'id' => 2,
                        'name' => 'expedia',
                        'display_name' => 'Expedia',
                        'type' => 'OTA',
                        'status' => 'Active',
                        'commission_rate' => 18.00,
                        'currency' => 'PHP',
                        'contact_person' => 'Expedia Partner Support',
                        'contact_email' => 'partners@expedia.com',
                        'api_endpoint' => 'https://api.expedia.com/v1',
                        'last_updated' => date('c')
                    ],
                    [
                        'id' => 3,
                        'name' => 'agoda',
                        'display_name' => 'Agoda',
                        'type' => 'OTA',
                        'status' => 'Active',
                        'commission_rate' => 12.00,
                        'currency' => 'PHP',
                        'contact_person' => 'Agoda Support',
                        'contact_email' => 'support@agoda.com',
                        'api_endpoint' => 'https://api.agoda.com/v1',
                        'last_updated' => date('c')
                    ]
                ],
                'rates' => [
                    [
                        'channel_id' => 1,
                        'room_type' => 'Deluxe',
                        'rate_type' => 'Standard',
                        'base_rate' => 4500.00,
                        'extra_person_rate' => 800.00,
                        'child_rate' => 400.00,
                        'breakfast_included' => true,
                        'breakfast_rate' => 350.00,
                        'valid_from' => date('Y-m-d', strtotime('+1 day')),
                        'valid_to' => date('Y-m-d', strtotime('+30 days')),
                        'minimum_stay' => 1,
                        'maximum_stay' => 7,
                        'closed_to_arrival' => false,
                        'closed_to_departure' => false,
                        'status' => 'Active'
                    ],
                    [
                        'channel_id' => 1,
                        'room_type' => 'Suite',
                        'rate_type' => 'Corporate',
                        'base_rate' => 8500.00,
                        'extra_person_rate' => 1200.00,
                        'child_rate' => 600.00,
                        'breakfast_included' => true,
                        'breakfast_rate' => 450.00,
                        'valid_from' => date('Y-m-d', strtotime('+1 day')),
                        'valid_to' => date('Y-m-d', strtotime('+60 days')),
                        'minimum_stay' => 2,
                        'maximum_stay' => 14,
                        'closed_to_arrival' => false,
                        'closed_to_departure' => false,
                        'status' => 'Active'
                    ],
                    [
                        'channel_id' => 2,
                        'room_type' => 'Deluxe',
                        'rate_type' => 'Standard',
                        'base_rate' => 4200.00,
                        'extra_person_rate' => 750.00,
                        'child_rate' => 380.00,
                        'breakfast_included' => false,
                        'breakfast_rate' => 0.00,
                        'valid_from' => date('Y-m-d', strtotime('+1 day')),
                        'valid_to' => date('Y-m-d', strtotime('+45 days')),
                        'minimum_stay' => 1,
                        'maximum_stay' => 5,
                        'closed_to_arrival' => false,
                        'closed_to_departure' => false,
                        'status' => 'Active'
                    ],
                    [
                        'channel_id' => 3,
                        'room_type' => 'Single',
                        'rate_type' => 'Promotional',
                        'base_rate' => 2800.00,
                        'extra_person_rate' => 500.00,
                        'child_rate' => 250.00,
                        'breakfast_included' => true,
                        'breakfast_rate' => 250.00,
                        'valid_from' => date('Y-m-d', strtotime('+3 days')),
                        'valid_to' => date('Y-m-d', strtotime('+14 days')),
                        'minimum_stay' => 1,
                        'maximum_stay' => 3,
                        'closed_to_arrival' => false,
                        'closed_to_departure' => false,
                        'status' => 'Active'
                    ]
                ],
                'availability' => [
                    [
                        'channel_id' => 1,
                        'room_type' => 'Deluxe',
                        'available_date' => date('Y-m-d', strtotime('+1 day')),
                        'total_rooms' => 10,
                        'booked_rooms' => 3,
                        'blocked_rooms' => 1,
                        'minimum_stay' => 1,
                        'maximum_stay' => 7,
                        'rate' => 4500.00,
                        'status' => 'Open'
                    ],
                    [
                        'channel_id' => 1,
                        'room_type' => 'Deluxe',
                        'available_date' => date('Y-m-d', strtotime('+2 days')),
                        'total_rooms' => 10,
                        'booked_rooms' => 5,
                        'blocked_rooms' => 0,
                        'minimum_stay' => 1,
                        'maximum_stay' => 7,
                        'rate' => 4500.00,
                        'status' => 'Open'
                    ],
                    [
                        'channel_id' => 1,
                        'room_type' => 'Suite',
                        'available_date' => date('Y-m-d', strtotime('+1 day')),
                        'total_rooms' => 5,
                        'booked_rooms' => 1,
                        'blocked_rooms' => 0,
                        'minimum_stay' => 2,
                        'maximum_stay' => 14,
                        'rate' => 8500.00,
                        'status' => 'Open'
                    ],
                    [
                        'channel_id' => 2,
                        'room_type' => 'Deluxe',
                        'available_date' => date('Y-m-d', strtotime('+1 day')),
                        'total_rooms' => 10,
                        'booked_rooms' => 4,
                        'blocked_rooms' => 1,
                        'minimum_stay' => 1,
                        'maximum_stay' => 5,
                        'rate' => 4200.00,
                        'status' => 'Open'
                    ],
                    [
                        'channel_id' => 3,
                        'room_type' => 'Single',
                        'available_date' => date('Y-m-d', strtotime('+3 days')),
                        'total_rooms' => 15,
                        'booked_rooms' => 2,
                        'blocked_rooms' => 1,
                        'minimum_stay' => 1,
                        'maximum_stay' => 3,
                        'rate' => 2800.00,
                        'status' => 'Open'
                    ]
                ],
                'room_mappings' => [
                    [
                        'channel_id' => 1,
                        'room_id' => 1,
                        'channel_room_id' => 'DLX-101',
                        'channel_room_name' => 'Deluxe Room 101',
                        'status' => 'Active'
                    ],
                    [
                        'channel_id' => 1,
                        'room_id' => 2,
                        'channel_room_id' => 'DLX-102',
                        'channel_room_name' => 'Deluxe Room 102',
                        'status' => 'Active'
                    ],
                    [
                        'channel_id' => 1,
                        'room_id' => 3,
                        'channel_room_id' => 'STE-201',
                        'channel_room_name' => 'Executive Suite 201',
                        'status' => 'Active'
                    ],
                    [
                        'channel_id' => 2,
                        'room_id' => 1,
                        'channel_room_id' => 'DX-1001',
                        'channel_room_name' => 'Deluxe King Room',
                        'status' => 'Active'
                    ],
                    [
                        'channel_id' => 3,
                        'room_id' => 4,
                        'channel_room_id' => 'SG-301',
                        'channel_room_name' => 'Single Room 301',
                        'status' => 'Active'
                    ]
                ],
                'reservations' => [
                    [
                        'channel_id' => 1,
                        'channel_reservation_id' => 'BKG-20241016-001',
                        'room_type' => 'Deluxe',
                        'check_in_date' => date('Y-m-d', strtotime('+5 days')),
                        'check_out_date' => date('Y-m-d', strtotime('+7 days')),
                        'guest_name' => 'John Smith',
                        'guest_email' => 'john.smith@email.com',
                        'total_amount' => 13500.00,
                        'status' => 'Confirmed',
                        'created_at' => date('c')
                    ],
                    [
                        'channel_id' => 2,
                        'channel_reservation_id' => 'EXP-20241016-002',
                        'room_type' => 'Deluxe',
                        'check_in_date' => date('Y-m-d', strtotime('+3 days')),
                        'check_out_date' => date('Y-m-d', strtotime('+5 days')),
                        'guest_name' => 'Maria Garcia',
                        'guest_email' => 'maria.garcia@email.com',
                        'total_amount' => 12600.00,
                        'status' => 'Confirmed',
                        'created_at' => date('c')
                    ],
                    [
                        'channel_id' => 3,
                        'channel_reservation_id' => 'AGD-20241016-003',
                        'room_type' => 'Single',
                        'check_in_date' => date('Y-m-d', strtotime('+10 days')),
                        'check_out_date' => date('Y-m-d', strtotime('+12 days')),
                        'guest_name' => 'David Wilson',
                        'guest_email' => 'david.wilson@email.com',
                        'total_amount' => 8400.00,
                        'status' => 'Confirmed',
                        'created_at' => date('c')
                    ]
                ]
            ];

            echo json_encode([
                'success' => true,
                'data' => $channelData,
                'timestamp' => date('c'),
                'total_records' => array_sum(array_map('count', $channelData))
            ]);
            break;

        case 'get_rates_data':
            // Simulate rates data from OTA APIs
            $ratesData = [
                'rates' => [
                    [
                        'channel_code' => 'booking.com',
                        'room_type' => 'Deluxe',
                        'rate_plan' => 'Standard Rate',
                        'currency' => 'PHP',
                        'base_rate' => 4500.00,
                        'extra_adult_rate' => 800.00,
                        'child_rate' => 400.00,
                        'breakfast_included' => true,
                        'breakfast_rate' => 350.00,
                        'valid_from' => date('Y-m-d'),
                        'valid_to' => date('Y-m-d', strtotime('+90 days')),
                        'minimum_stay' => 1,
                        'maximum_stay' => 7,
                        'restrictions' => [
                            'closed_to_arrival' => false,
                            'closed_to_departure' => false,
                            'advance_booking_days' => 0
                        ],
                        'last_updated' => date('c')
                    ],
                    [
                        'channel_code' => 'booking.com',
                        'room_type' => 'Suite',
                        'rate_plan' => 'Executive Rate',
                        'currency' => 'PHP',
                        'base_rate' => 8500.00,
                        'extra_adult_rate' => 1200.00,
                        'child_rate' => 600.00,
                        'breakfast_included' => true,
                        'breakfast_rate' => 450.00,
                        'valid_from' => date('Y-m-d'),
                        'valid_to' => date('Y-m-d', strtotime('+90 days')),
                        'minimum_stay' => 2,
                        'maximum_stay' => 14,
                        'restrictions' => [
                            'closed_to_arrival' => false,
                            'closed_to_departure' => false,
                            'advance_booking_days' => 1
                        ],
                        'last_updated' => date('c')
                    ],
                    [
                        'channel_code' => 'expedia',
                        'room_type' => 'Deluxe',
                        'rate_plan' => 'Standard Rate',
                        'currency' => 'PHP',
                        'base_rate' => 4200.00,
                        'extra_adult_rate' => 750.00,
                        'child_rate' => 380.00,
                        'breakfast_included' => false,
                        'breakfast_rate' => 0.00,
                        'valid_from' => date('Y-m-d'),
                        'valid_to' => date('Y-m-d', strtotime('+60 days')),
                        'minimum_stay' => 1,
                        'maximum_stay' => 5,
                        'restrictions' => [
                            'closed_to_arrival' => false,
                            'closed_to_departure' => false,
                            'advance_booking_days' => 0
                        ],
                        'last_updated' => date('c')
                    ],
                    [
                        'channel_code' => 'agoda',
                        'room_type' => 'Single',
                        'rate_plan' => 'Economy Rate',
                        'currency' => 'PHP',
                        'base_rate' => 2800.00,
                        'extra_adult_rate' => 500.00,
                        'child_rate' => 250.00,
                        'breakfast_included' => true,
                        'breakfast_rate' => 250.00,
                        'valid_from' => date('Y-m-d'),
                        'valid_to' => date('Y-m-d', strtotime('+30 days')),
                        'minimum_stay' => 1,
                        'maximum_stay' => 3,
                        'restrictions' => [
                            'closed_to_arrival' => false,
                            'closed_to_departure' => false,
                            'advance_booking_days' => 0
                        ],
                        'last_updated' => date('c')
                    ]
                ],
                'rate_modifications' => [
                    [
                        'channel_code' => 'booking.com',
                        'room_type' => 'Deluxe',
                        'date' => date('Y-m-d', strtotime('+7 days')),
                        'original_rate' => 4500.00,
                        'modified_rate' => 4000.00,
                        'reason' => 'Promotional discount',
                        'applied_at' => date('c')
                    ],
                    [
                        'channel_code' => 'expedia',
                        'room_type' => 'Deluxe',
                        'date' => date('Y-m-d', strtotime('+14 days')),
                        'original_rate' => 4200.00,
                        'modified_rate' => 3800.00,
                        'reason' => 'Last minute deal',
                        'applied_at' => date('c')
                    ]
                ]
            ];

            echo json_encode([
                'success' => true,
                'data' => $ratesData,
                'timestamp' => date('c'),
                'total_rates' => count($ratesData['rates'])
            ]);
            break;

        case 'get_availability_data':
            // Simulate availability data from OTA APIs
            $availabilityData = [
                'availability' => [],
                'restrictions' => []
            ];

            // Generate availability for next 30 days for different channels and room types
            $roomTypes = ['Single', 'Double', 'Deluxe', 'Suite'];
            $channels = [
                ['id' => 1, 'code' => 'booking.com'],
                ['id' => 2, 'code' => 'expedia'],
                ['id' => 3, 'code' => 'agoda']
            ];

            $currentDate = new DateTime();
            for ($i = 0; $i < 30; $i++) {
                $date = $currentDate->format('Y-m-d');
                $currentDate->add(new DateInterval('P1D'));

                foreach ($channels as $channel) {
                    foreach ($roomTypes as $roomType) {
                        $totalRooms = ($roomType === 'Single') ? 15 : (($roomType === 'Suite') ? 5 : 10);
                        $bookedRooms = rand(0, min(3, $totalRooms - 1));
                        $blockedRooms = rand(0, min(2, $totalRooms - $bookedRooms - 1));

                        $availabilityData['availability'][] = [
                            'channel_id' => $channel['id'],
                            'channel_code' => $channel['code'],
                            'room_type' => $roomType,
                            'date' => $date,
                            'total_rooms' => $totalRooms,
                            'available_rooms' => $totalRooms - $bookedRooms - $blockedRooms,
                            'booked_rooms' => $bookedRooms,
                            'blocked_rooms' => $blockedRooms,
                            'minimum_stay' => rand(1, 3),
                            'maximum_stay' => rand(5, 14),
                            'status' => ($totalRooms - $bookedRooms - $blockedRooms > 0) ? 'Open' : 'Closed',
                            'last_updated' => date('c')
                        ];
                    }
                }
            }

            // Add some restrictions
            $availabilityData['restrictions'] = [
                [
                    'channel_code' => 'booking.com',
                    'room_type' => 'Suite',
                    'date_from' => date('Y-m-d', strtotime('+10 days')),
                    'date_to' => date('Y-m-d', strtotime('+12 days')),
                    'restriction_type' => 'Closed to Arrival',
                    'reason' => 'Maintenance'
                ],
                [
                    'channel_code' => 'expedia',
                    'room_type' => 'Deluxe',
                    'date_from' => date('Y-m-d', strtotime('+20 days')),
                    'date_to' => date('Y-m-d', strtotime('+22 days')),
                    'restriction_type' => 'Minimum Stay 3 nights',
                    'reason' => 'Peak season'
                ]
            ];

            echo json_encode([
                'success' => true,
                'data' => $availabilityData,
                'timestamp' => date('c'),
                'total_records' => count($availabilityData['availability'])
            ]);
            break;

        case 'get_reservations_data':
            // Simulate reservations data from OTA APIs
            $reservationsData = [
                'reservations' => [
                    [
                        'channel_reservation_id' => 'BKG-' . date('Ymd') . '-001',
                        'channel_code' => 'booking.com',
                        'ota_confirmation_number' => 'BKG' . rand(100000, 999999),
                        'guest_name' => 'John Smith',
                        'guest_email' => 'john.smith@email.com',
                        'guest_phone' => '+1-555-0123',
                        'room_type' => 'Deluxe',
                        'room_count' => 1,
                        'check_in_date' => date('Y-m-d', strtotime('+5 days')),
                        'check_out_date' => date('Y-m-d', strtotime('+8 days')),
                        'number_of_guests' => 2,
                        'adults' => 2,
                        'children' => 0,
                        'total_amount' => 13500.00,
                        'currency' => 'PHP',
                        'status' => 'Confirmed',
                        'special_requests' => 'Late check-in after 10 PM',
                        'booking_date' => date('c', strtotime('-2 days')),
                        'last_modified' => date('c')
                    ],
                    [
                        'channel_reservation_id' => 'EXP-' . date('Ymd') . '-002',
                        'channel_code' => 'expedia',
                        'ota_confirmation_number' => 'EXP' . rand(100000, 999999),
                        'guest_name' => 'Maria Garcia',
                        'guest_email' => 'maria.garcia@email.com',
                        'guest_phone' => '+63-917-456789',
                        'room_type' => 'Deluxe',
                        'room_count' => 1,
                        'check_in_date' => date('Y-m-d', strtotime('+3 days')),
                        'check_out_date' => date('Y-m-d', strtotime('+6 days')),
                        'number_of_guests' => 3,
                        'adults' => 2,
                        'children' => 1,
                        'total_amount' => 12600.00,
                        'currency' => 'PHP',
                        'status' => 'Confirmed',
                        'special_requests' => 'Baby crib needed',
                        'booking_date' => date('c', strtotime('-1 days')),
                        'last_modified' => date('c')
                    ],
                    [
                        'channel_reservation_id' => 'AGD-' . date('Ymd') . '-003',
                        'channel_code' => 'agoda',
                        'ota_confirmation_number' => 'AGD' . rand(100000, 999999),
                        'guest_name' => 'David Wilson',
                        'guest_email' => 'david.wilson@email.com',
                        'guest_phone' => '+65-8123-4567',
                        'room_type' => 'Single',
                        'room_count' => 2,
                        'check_in_date' => date('Y-m-d', strtotime('+10 days')),
                        'check_out_date' => date('Y-m-d', strtotime('+14 days')),
                        'number_of_guests' => 2,
                        'adults' => 2,
                        'children' => 0,
                        'total_amount' => 11200.00,
                        'currency' => 'PHP',
                        'status' => 'Confirmed',
                        'special_requests' => 'Non-smoking room',
                        'booking_date' => date('c', strtotime('-3 days')),
                        'last_modified' => date('c')
                    ],
                    [
                        'channel_reservation_id' => 'BKG-' . date('Ymd') . '-004',
                        'channel_code' => 'booking.com',
                        'ota_confirmation_number' => 'BKG' . rand(100000, 999999),
                        'guest_name' => 'Sarah Johnson',
                        'guest_email' => 'sarah.johnson@email.com',
                        'guest_phone' => '+1-555-0789',
                        'room_type' => 'Suite',
                        'room_count' => 1,
                        'check_in_date' => date('Y-m-d', strtotime('+7 days')),
                        'check_out_date' => date('Y-m-d', strtotime('+10 days')),
                        'number_of_guests' => 4,
                        'adults' => 2,
                        'children' => 2,
                        'total_amount' => 25500.00,
                        'currency' => 'PHP',
                        'status' => 'Pending',
                        'special_requests' => 'Connecting rooms if possible',
                        'booking_date' => date('c'),
                        'last_modified' => date('c')
                    ]
                ],
                'summary' => [
                    'total_reservations' => 4,
                    'confirmed_reservations' => 3,
                    'pending_reservations' => 1,
                    'total_revenue' => 62800.00,
                    'average_stay_length' => 3.5,
                    'occupancy_rate' => 75.5
                ]
            ];

            echo json_encode([
                'success' => true,
                'data' => $reservationsData,
                'timestamp' => date('c'),
                'total_reservations' => count($reservationsData['reservations'])
            ]);
            break;

        case 'get_inventory_data':
            // Simulate inventory/room data from OTA APIs
            $inventoryData = [
                'rooms' => [
                    [
                        'hotel_room_id' => 1,
                        'room_number' => '101',
                        'room_type' => 'Deluxe',
                        'room_category' => 'Deluxe King',
                        'max_occupancy' => 3,
                        'bed_type' => 'King Bed',
                        'amenities' => ['WiFi', 'AC', 'TV', 'Mini Bar', 'Balcony'],
                        'size_sqm' => 35,
                        'floor' => 1,
                        'status' => 'Active',
                        'channel_mappings' => [
                            'booking.com' => 'DLX-101',
                            'expedia' => 'DX-1001',
                            'agoda' => 'DLX-101'
                        ]
                    ],
                    [
                        'hotel_room_id' => 2,
                        'room_number' => '102',
                        'room_type' => 'Deluxe',
                        'room_category' => 'Deluxe Twin',
                        'max_occupancy' => 4,
                        'bed_type' => 'Twin Beds',
                        'amenities' => ['WiFi', 'AC', 'TV', 'Mini Bar', 'Balcony'],
                        'size_sqm' => 38,
                        'floor' => 1,
                        'status' => 'Active',
                        'channel_mappings' => [
                            'booking.com' => 'DLX-102',
                            'expedia' => 'DX-1002',
                            'agoda' => 'DLX-102'
                        ]
                    ],
                    [
                        'hotel_room_id' => 3,
                        'room_number' => '201',
                        'room_type' => 'Suite',
                        'room_category' => 'Executive Suite',
                        'max_occupancy' => 4,
                        'bed_type' => 'King Bed + Sofa Bed',
                        'amenities' => ['WiFi', 'AC', 'TV', 'Mini Bar', 'Balcony', 'Living Area', 'Kitchenette'],
                        'size_sqm' => 65,
                        'floor' => 2,
                        'status' => 'Active',
                        'channel_mappings' => [
                            'booking.com' => 'STE-201',
                            'expedia' => 'SU-2001',
                            'agoda' => 'STE-201'
                        ]
                    ],
                    [
                        'hotel_room_id' => 4,
                        'room_number' => '301',
                        'room_type' => 'Single',
                        'room_category' => 'Standard Single',
                        'max_occupancy' => 1,
                        'bed_type' => 'Single Bed',
                        'amenities' => ['WiFi', 'AC', 'TV'],
                        'size_sqm' => 18,
                        'floor' => 3,
                        'status' => 'Active',
                        'channel_mappings' => [
                            'booking.com' => 'SG-301',
                            'expedia' => 'SG-3001',
                            'agoda' => 'SG-301'
                        ]
                    ]
                ],
                'room_types' => [
                    [
                        'type' => 'Single',
                        'description' => 'Comfortable single room for solo travelers',
                        'base_rate' => 2800.00,
                        'max_occupancy' => 1,
                        'amenities' => ['WiFi', 'AC', 'TV'],
                        'total_rooms' => 15,
                        'available_rooms' => 12
                    ],
                    [
                        'type' => 'Deluxe',
                        'description' => 'Spacious deluxe room with modern amenities',
                        'base_rate' => 4500.00,
                        'max_occupancy' => 3,
                        'amenities' => ['WiFi', 'AC', 'TV', 'Mini Bar', 'Balcony'],
                        'total_rooms' => 10,
                        'available_rooms' => 6
                    ],
                    [
                        'type' => 'Suite',
                        'description' => 'Luxurious suite with separate living area',
                        'base_rate' => 8500.00,
                        'max_occupancy' => 4,
                        'amenities' => ['WiFi', 'AC', 'TV', 'Mini Bar', 'Balcony', 'Living Area', 'Kitchenette'],
                        'total_rooms' => 5,
                        'available_rooms' => 4
                    ]
                ]
            ];

            echo json_encode([
                'success' => true,
                'data' => $inventoryData,
                'timestamp' => date('c'),
                'total_rooms' => count($inventoryData['rooms'])
            ]);
            break;

        case 'get_sync_status':
            // Simulate sync status from OTA APIs
            $syncStatus = [
                'last_sync' => date('c', strtotime('-30 minutes')),
                'sync_duration' => 45.2,
                'records_processed' => 1247,
                'records_successful' => 1245,
                'records_failed' => 2,
                'errors' => [
                    'Room type mismatch for room 999',
                    'Invalid rate format for promotional rate'
                ],
                'channels_synced' => [
                    [
                        'channel' => 'booking.com',
                        'status' => 'Success',
                        'records' => 450,
                        'last_sync' => date('c', strtotime('-25 minutes'))
                    ],
                    [
                        'channel' => 'expedia',
                        'status' => 'Success',
                        'records' => 380,
                        'last_sync' => date('c', strtotime('-28 minutes'))
                    ],
                    [
                        'channel' => 'agoda',
                        'status' => 'Partial',
                        'records' => 417,
                        'failed_records' => 2,
                        'last_sync' => date('c', strtotime('-30 minutes'))
                    ]
                ],
                'next_sync_scheduled' => date('c', strtotime('+2 hours'))
            ];

            echo json_encode([
                'success' => true,
                'data' => $syncStatus,
                'timestamp' => date('c')
            ]);
            break;

        default:
            echo json_encode([
                'success' => false,
                'error' => 'Invalid action. Available actions: get_channel_data, get_rates_data, get_availability_data, get_reservations_data, get_inventory_data, get_sync_status'
            ]);
    }

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'timestamp' => date('c')
    ]);
}
?>
