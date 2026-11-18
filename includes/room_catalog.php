
<?php

$catalogRooms = [];
$roomCatalogError = '';

// Existing room photographs
$catalogImages = [
    1 => 'assets/images/standard room.jfif',
    2 => 'assets/images/family room.webp',
    3 => 'assets/images/event hall.jfif'
];

try {

    require_once __DIR__ . '/database.php';

    // ========================================
    // RETRIEVE ROOM TYPES
    // ========================================

    $stmt = $pdo->query("
        SELECT
            room_Type_ID,
            room_Type,
            base_Price,
            included_Guests,
            max_Guests,
            extra_Person_Fee
        FROM room_type
        WHERE deleted_At IS NULL
        ORDER BY room_Type_ID
    ");

    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $roomType) {

        $id = (int)$roomType['room_Type_ID'];

        $roomType['image'] =
            $catalogImages[$id] ?? 'assets/images/hero.webp';

        $roomType['features'] = [];
        $roomType['beds'] = [];

        $catalogRooms[$id] = $roomType;

    }

    // ========================================
    // RETRIEVE ROOM FEATURES
    // ========================================

    $stmt = $pdo->query("
        SELECT
            rtf.room_Type_ID,
            f.feature_Name
        FROM room_type_feature rtf

        JOIN feature f
            ON rtf.feature_ID = f.feature_ID

        WHERE rtf.deleted_At IS NULL
        AND f.deleted_At IS NULL

        ORDER BY f.feature_ID
    ");

    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $feature) {

        $id = (int)$feature['room_Type_ID'];

        if (isset($catalogRooms[$id])) {

            $catalogRooms[$id]['features'][] =
                $feature['feature_Name'];

        }

    }

    // ========================================
    // RETRIEVE BED CONFIGURATIONS
    // ========================================

    $stmt = $pdo->query("
        SELECT
            rtb.room_Type_ID,
            bt.bed_Type_Name,
            rtb.num_Beds
        FROM room_type_bed_type rtb

        JOIN bed_type bt
            ON rtb.bed_Type_ID = bt.bed_Type_ID

        WHERE rtb.deleted_At IS NULL
        AND bt.deleted_At IS NULL

        ORDER BY bt.bed_Type_ID
    ");

    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $bed) {

        $id = (int)$bed['room_Type_ID'];

        if (isset($catalogRooms[$id])) {

            $catalogRooms[$id]['beds'][] = [
                'type' => $bed['bed_Type_Name'],
                'quantity' => (int)$bed['num_Beds']
            ];

        }

    }

} catch (Throwable $e) {

    error_log('Room catalog error: ' . $e->getMessage());

    $catalogRooms = [];

    $roomCatalogError =
        'Unable to retrieve room information. Please try again later.';

}

// Safely display database values
function catalogEscape($value): string {

    return htmlspecialchars(
        (string)$value,
        ENT_QUOTES | ENT_SUBSTITUTE,
        'UTF-8'
    );

}
