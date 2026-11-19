-- Step 11.4: approved room-type-specific add-ons.
-- Apply once after backing up the hotel_reservation_system database.
-- Existing add-on records, prices, and booking snapshots remain unchanged.

CREATE TABLE room_type_addon (
    room_Type_ID INT UNSIGNED NOT NULL,
    addon_ID INT UNSIGNED NOT NULL,
    deleted_At DATETIME DEFAULT NULL,
    PRIMARY KEY (room_Type_ID, addon_ID),
    KEY idx_rta_addon (addon_ID),
    CONSTRAINT fk_rta_room_type FOREIGN KEY (room_Type_ID)
        REFERENCES room_type (room_Type_ID),
    CONSTRAINT fk_rta_addon FOREIGN KEY (addon_ID)
        REFERENCES addon (addon_ID)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO room_type_addon (room_Type_ID, addon_ID)
SELECT rt.room_Type_ID, a.addon_ID
FROM room_type rt
CROSS JOIN addon a
WHERE rt.deleted_At IS NULL AND a.deleted_At IS NULL
AND (
    (rt.room_Type IN ('Standard Room', 'Family Room')
     AND a.addon_Name IN ('Breakfast', 'Extra Pillow', 'Extra Blanket',
                          'Extra Towel', 'Extra Mattress', 'Room Decoration'))
    OR
    (rt.room_Type = 'Event Hall'
     AND a.addon_Name IN ('Event Decoration', 'Additional Projector Rental'))
);
