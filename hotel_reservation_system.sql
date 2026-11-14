-- BELLA VISTA HOTEL & EVENTS PLACE
-- Database schema for MySQL 8.0.16+ (InnoDB, with enforced CHECK constraints)
-- Based on the RBAC ERD and the customer, admin, and housekeeping flowcharts.
--
-- This is a CREATE-ONLY script: it does not DROP existing tables or data.
-- Import into a NEW database. Review any changes before applying to an existing database.
--
-- Intentional changes to the submitted ERD:
--   * Booking.payment_Status_ID removed; payment status belongs to each Payment.
--   * Payment.payment_Type VARCHAR -> payment_Type_ID FK (Payment_Type lookup).
--   * Housekeeping_Task.task_Status VARCHAR -> housekeeping_Status_ID FK.
--   * Users.account_Status VARCHAR -> account_Status_ID FK.
--   * Added Booking.hold_Expires_At for an optional pending-booking expiry policy.
--   * Added Booking_Room.included_Guests_Snapshot to preserve the original
--     pricing/capacity basis, plus actual checked_In_At/checked_Out_At.
--   * Added deleted_At on records that may later be archived/restored.
--   * Added Hotel_Setting for configurable business policies.
--   * All foreign keys use ON DELETE RESTRICT and ON UPDATE RESTRICT.
--   * Future archive-table design has intentionally NOT been implemented.
--
-- IMPORTANT: FK constraints do NOT automatically enforce role authorization,
-- booking-date overlap rules, admin approval or room-cleanliness workflows.

CREATE DATABASE IF NOT EXISTS hotel_reservation_system
CHARACTER SET utf8mb4
COLLATE utf8mb4_unicode_ci;

USE hotel_reservation_system;

-- Set Philippine Standard Time (UTC+08:00) for THIS IMPORT CONNECTION.
-- Important: this does not set the timezone for future PHP/MySQL connections.
-- Configure each application connection to '+08:00' and PHP to 'Asia/Manila'.
-- MySQL DATETIME columns do not automatically convert timezones.
SET SESSION time_zone = '+08:00';

-- ============================================================
-- 1. LOOKUP TABLES
-- ============================================================

CREATE TABLE IF NOT EXISTS User_Role (
    role_ID INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    role_Name VARCHAR(40) NOT NULL,
    is_Active BOOLEAN NOT NULL DEFAULT TRUE,
    CONSTRAINT uq_user_role_name UNIQUE (role_Name)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS Account_Status (
    account_Status_ID INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    account_Status_Name VARCHAR(40) NOT NULL,
    is_Active BOOLEAN NOT NULL DEFAULT TRUE,
    CONSTRAINT uq_account_status_name UNIQUE (account_Status_Name)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS Booking_Status (
    booking_Status_ID INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    booking_Status_Name VARCHAR(50) NOT NULL,
    blocks_Availability BOOLEAN NOT NULL DEFAULT FALSE,
    requires_Hold_Expiry_Check BOOLEAN NOT NULL DEFAULT FALSE,
    is_Active BOOLEAN NOT NULL DEFAULT TRUE,
    CONSTRAINT uq_booking_status_name UNIQUE (booking_Status_Name)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS Payment_Status (
    payment_Status_ID INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    payment_Status_Name VARCHAR(40) NOT NULL,
    counts_As_Paid BOOLEAN NOT NULL DEFAULT FALSE,
    is_Active BOOLEAN NOT NULL DEFAULT TRUE,
    CONSTRAINT uq_payment_status_name UNIQUE (payment_Status_Name)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS Payment_Method (
    payment_Method_ID INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    payment_Method_Name VARCHAR(60) NOT NULL,
    is_Active BOOLEAN NOT NULL DEFAULT TRUE,
    CONSTRAINT uq_payment_method_name UNIQUE (payment_Method_Name)
) ENGINE=InnoDB;

-- balance_Effect=1 adds to amount paid; -1 subtracts a successful refund.
CREATE TABLE IF NOT EXISTS Payment_Type (
    payment_Type_ID INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    payment_Type_Name VARCHAR(40) NOT NULL,
    balance_Effect TINYINT NOT NULL,
    is_Active BOOLEAN NOT NULL DEFAULT TRUE,
    CONSTRAINT uq_payment_type_name UNIQUE (payment_Type_Name),
    CONSTRAINT chk_payment_type_effect CHECK (balance_Effect IN (-1, 1))
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS Room_Status (
    room_Status_ID INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    room_Status_Name VARCHAR(50) NOT NULL,
    ready_For_Checkin BOOLEAN NOT NULL DEFAULT FALSE,
    is_Active BOOLEAN NOT NULL DEFAULT TRUE,
    CONSTRAINT uq_room_status_name UNIQUE (room_Status_Name)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS Housekeeping_Status (
    housekeeping_Status_ID INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    housekeeping_Status_Name VARCHAR(50) NOT NULL,
    is_Active BOOLEAN NOT NULL DEFAULT TRUE,
    CONSTRAINT uq_housekeeping_status_name UNIQUE (housekeeping_Status_Name)
) ENGINE=InnoDB;

-- ============================================================
-- 2. USERS AND CONFIGURATION
-- ============================================================

CREATE TABLE IF NOT EXISTS Users (
    user_ID INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    role_ID INT UNSIGNED NOT NULL,
    account_Status_ID INT UNSIGNED NOT NULL,
    first_Name VARCHAR(80) NOT NULL,
    last_Name VARCHAR(80) NOT NULL,
    email_Address VARCHAR(190) NOT NULL,
    phone_Number VARCHAR(25) NULL,
    password_Hash VARCHAR(255) NULL COMMENT 'NULL for a guest checkout without an account',
    created_At DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    deleted_At DATETIME NULL,
    -- One row per email, including guest checkouts without a password.
    -- Repeat guests need a safe existing-user lookup/identity verification flow;
    -- never authenticate someone merely because they typed a matching email.
    CONSTRAINT uq_users_email UNIQUE (email_Address),
    CONSTRAINT fk_users_role FOREIGN KEY (role_ID)
        REFERENCES User_Role(role_ID) ON DELETE RESTRICT ON UPDATE RESTRICT,
    CONSTRAINT fk_users_account_status FOREIGN KEY (account_Status_ID)
        REFERENCES Account_Status(account_Status_ID) ON DELETE RESTRICT ON UPDATE RESTRICT,
    INDEX idx_users_role (role_ID),
    INDEX idx_users_deleted (deleted_At)
) ENGINE=InnoDB;

-- Flexible system-wide policy values. Keep an explicit interpretation/validation
-- in your application; this table does not enforce pricing business rules.
CREATE TABLE IF NOT EXISTS Hotel_Setting (
    setting_Key VARCHAR(80) PRIMARY KEY,
    setting_Value VARCHAR(255) NOT NULL,
    description VARCHAR(255) NULL,
    updated_At DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ============================================================
-- 3. ROOM CATALOG, FACILITIES AND ADD-ONS
-- ============================================================

CREATE TABLE IF NOT EXISTS Floor (
    floor_ID INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    floor_Number INT NOT NULL,
    deleted_At DATETIME NULL,
    CONSTRAINT uq_floor_number UNIQUE (floor_Number)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS Room_Type (
    room_Type_ID INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    room_Type VARCHAR(90) NOT NULL,
    base_Price DECIMAL(12,2) NOT NULL,
    included_Guests INT UNSIGNED NOT NULL,
    max_Guests INT UNSIGNED NOT NULL,
    extra_Person_Fee DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    deleted_At DATETIME NULL,
    CONSTRAINT uq_room_type_name UNIQUE (room_Type),
    CONSTRAINT chk_room_type_prices CHECK (base_Price >= 0 AND extra_Person_Fee >= 0),
    CONSTRAINT chk_room_type_capacity CHECK (
        included_Guests >= 1 AND max_Guests >= included_Guests
    )
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS Room (
    room_ID INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    room_Type_ID INT UNSIGNED NOT NULL,
    floor_ID INT UNSIGNED NULL COMMENT 'Optional for facilities such as Event Hall',
    room_Status_ID INT UNSIGNED NOT NULL,
    room_Code VARCHAR(40) NOT NULL,
    deleted_At DATETIME NULL,
    CONSTRAINT uq_room_code UNIQUE (room_Code),
    CONSTRAINT fk_room_type FOREIGN KEY (room_Type_ID)
        REFERENCES Room_Type(room_Type_ID) ON DELETE RESTRICT ON UPDATE RESTRICT,
    CONSTRAINT fk_room_floor FOREIGN KEY (floor_ID)
        REFERENCES Floor(floor_ID) ON DELETE RESTRICT ON UPDATE RESTRICT,
    CONSTRAINT fk_room_status FOREIGN KEY (room_Status_ID)
        REFERENCES Room_Status(room_Status_ID) ON DELETE RESTRICT ON UPDATE RESTRICT,
    INDEX idx_room_type (room_Type_ID),
    INDEX idx_room_status (room_Status_ID),
    INDEX idx_room_deleted (deleted_At)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS Bed_Type (
    bed_Type_ID INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    bed_Type_Name VARCHAR(80) NOT NULL,
    deleted_At DATETIME NULL,
    CONSTRAINT uq_bed_type_name UNIQUE (bed_Type_Name)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS Feature (
    feature_ID INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    feature_Name VARCHAR(100) NOT NULL,
    deleted_At DATETIME NULL,
    CONSTRAINT uq_feature_name UNIQUE (feature_Name)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS Addon (
    addon_ID INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    addon_Name VARCHAR(100) NOT NULL,
    addon_Price DECIMAL(12,2) NOT NULL,
    deleted_At DATETIME NULL,
    CONSTRAINT uq_addon_name UNIQUE (addon_Name),
    CONSTRAINT chk_addon_price CHECK (addon_Price >= 0)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS Room_Type_Bed_Type (
    room_Type_ID INT UNSIGNED NOT NULL,
    bed_Type_ID INT UNSIGNED NOT NULL,
    num_Beds INT UNSIGNED NOT NULL,
    deleted_At DATETIME NULL,
    PRIMARY KEY (room_Type_ID, bed_Type_ID),
    CONSTRAINT fk_rtb_room_type FOREIGN KEY (room_Type_ID)
        REFERENCES Room_Type(room_Type_ID) ON DELETE RESTRICT ON UPDATE RESTRICT,
    CONSTRAINT fk_rtb_bed_type FOREIGN KEY (bed_Type_ID)
        REFERENCES Bed_Type(bed_Type_ID) ON DELETE RESTRICT ON UPDATE RESTRICT,
    CONSTRAINT chk_rtb_beds CHECK (num_Beds > 0),
    INDEX idx_rtb_bed_type (bed_Type_ID)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS Room_Type_Feature (
    room_Type_ID INT UNSIGNED NOT NULL,
    feature_ID INT UNSIGNED NOT NULL,
    deleted_At DATETIME NULL,
    PRIMARY KEY (room_Type_ID, feature_ID),
    CONSTRAINT fk_rtf_room_type FOREIGN KEY (room_Type_ID)
        REFERENCES Room_Type(room_Type_ID) ON DELETE RESTRICT ON UPDATE RESTRICT,
    CONSTRAINT fk_rtf_feature FOREIGN KEY (feature_ID)
        REFERENCES Feature(feature_ID) ON DELETE RESTRICT ON UPDATE RESTRICT,
    INDEX idx_rtf_feature (feature_ID)
) ENGINE=InnoDB;

-- ============================================================
-- 4. BOOKINGS AND PRICE SNAPSHOTS
-- ============================================================

CREATE TABLE IF NOT EXISTS Booking (
    booking_ID INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_ID INT UNSIGNED NOT NULL,
    booking_Status_ID INT UNSIGNED NOT NULL,
    checkin_Date DATETIME NOT NULL,
    checkout_Date DATETIME NOT NULL,
    booking_Amount DECIMAL(12,2) NOT NULL,
    required_Downpayment DECIMAL(12,2) NOT NULL,
    confirmation_Code VARCHAR(60) NULL COMMENT 'Generated once confirmed',
    hold_Expires_At DATETIME NULL COMMENT 'Optional time limit for Pending Payment holds',
    created_At DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    deleted_At DATETIME NULL,
    CONSTRAINT uq_booking_confirmation UNIQUE (confirmation_Code),
    CONSTRAINT fk_booking_guest FOREIGN KEY (user_ID)
        REFERENCES Users(user_ID) ON DELETE RESTRICT ON UPDATE RESTRICT,
    CONSTRAINT fk_booking_status FOREIGN KEY (booking_Status_ID)
        REFERENCES Booking_Status(booking_Status_ID) ON DELETE RESTRICT ON UPDATE RESTRICT,
    CONSTRAINT chk_booking_dates CHECK (checkout_Date > checkin_Date),
    CONSTRAINT chk_booking_amounts CHECK (
        booking_Amount >= 0 AND
        required_Downpayment >= 0 AND
        required_Downpayment <= booking_Amount
    ),
    INDEX idx_booking_guest (user_ID),
    INDEX idx_booking_status_dates (booking_Status_ID, checkin_Date, checkout_Date),
    INDEX idx_booking_dates (checkin_Date, checkout_Date),
    INDEX idx_booking_hold (hold_Expires_At),
    INDEX idx_booking_deleted (deleted_At)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS Booking_Room (
    booking_ID INT UNSIGNED NOT NULL,
    room_ID INT UNSIGNED NOT NULL,
    num_Guest INT UNSIGNED NOT NULL,
    nightly_Rate DECIMAL(12,2) NOT NULL,
    extra_Guest_Fee DECIMAL(12,2) NOT NULL DEFAULT 0.00
        COMMENT 'Booking-time extra-person rate; policy must define frequency',
    included_Guests_Snapshot INT UNSIGNED NOT NULL,
    checked_In_At DATETIME NULL COMMENT 'Actual check-in for this room',
    checked_Out_At DATETIME NULL COMMENT 'Actual checkout triggers housekeeping',
    deleted_At DATETIME NULL,
    PRIMARY KEY (booking_ID, room_ID),
    CONSTRAINT fk_booking_room_booking FOREIGN KEY (booking_ID)
        REFERENCES Booking(booking_ID) ON DELETE RESTRICT ON UPDATE RESTRICT,
    CONSTRAINT fk_booking_room_room FOREIGN KEY (room_ID)
        REFERENCES Room(room_ID) ON DELETE RESTRICT ON UPDATE RESTRICT,
    CONSTRAINT chk_booking_room_count CHECK (
        num_Guest > 0 AND included_Guests_Snapshot > 0
    ),
    CONSTRAINT chk_booking_room_rates CHECK (
        nightly_Rate >= 0 AND extra_Guest_Fee >= 0
    ),
    CONSTRAINT chk_booking_room_actual_dates CHECK (
        checked_Out_At IS NULL OR
        (checked_In_At IS NOT NULL AND checked_Out_At >= checked_In_At)
    ),
    INDEX idx_booking_room_room (room_ID, booking_ID)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS Booking_Addon (
    booking_ID INT UNSIGNED NOT NULL,
    addon_ID INT UNSIGNED NOT NULL,
    quantity INT UNSIGNED NOT NULL DEFAULT 1,
    unit_Price DECIMAL(12,2) NOT NULL,
    deleted_At DATETIME NULL,
    PRIMARY KEY (booking_ID, addon_ID),
    CONSTRAINT fk_booking_addon_booking FOREIGN KEY (booking_ID)
        REFERENCES Booking(booking_ID) ON DELETE RESTRICT ON UPDATE RESTRICT,
    CONSTRAINT fk_booking_addon_addon FOREIGN KEY (addon_ID)
        REFERENCES Addon(addon_ID) ON DELETE RESTRICT ON UPDATE RESTRICT,
    CONSTRAINT chk_booking_addon_qty CHECK (quantity > 0),
    CONSTRAINT chk_booking_addon_price CHECK (unit_Price >= 0),
    INDEX idx_booking_addon_addon (addon_ID)
) ENGINE=InnoDB;

-- ============================================================
-- 5. PAYMENTS AND BOOKING AUDIT LOG
-- ============================================================

CREATE TABLE IF NOT EXISTS Payment (
    payment_ID INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    booking_ID INT UNSIGNED NOT NULL,
    payment_Method_ID INT UNSIGNED NOT NULL,
    processed_By INT UNSIGNED NULL,
    payment_Status_ID INT UNSIGNED NOT NULL,
    payment_Type_ID INT UNSIGNED NOT NULL,
    amount DECIMAL(12,2) NOT NULL,
    reference_No VARCHAR(120) NULL,
    payment_Date DATETIME NULL,
    created_At DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    deleted_At DATETIME NULL,
    CONSTRAINT fk_payment_booking FOREIGN KEY (booking_ID)
        REFERENCES Booking(booking_ID) ON DELETE RESTRICT ON UPDATE RESTRICT,
    CONSTRAINT fk_payment_method FOREIGN KEY (payment_Method_ID)
        REFERENCES Payment_Method(payment_Method_ID) ON DELETE RESTRICT ON UPDATE RESTRICT,
    CONSTRAINT fk_payment_processor FOREIGN KEY (processed_By)
        REFERENCES Users(user_ID) ON DELETE RESTRICT ON UPDATE RESTRICT,
    CONSTRAINT fk_payment_status FOREIGN KEY (payment_Status_ID)
        REFERENCES Payment_Status(payment_Status_ID) ON DELETE RESTRICT ON UPDATE RESTRICT,
    CONSTRAINT fk_payment_type FOREIGN KEY (payment_Type_ID)
        REFERENCES Payment_Type(payment_Type_ID) ON DELETE RESTRICT ON UPDATE RESTRICT,
    CONSTRAINT chk_payment_amount CHECK (amount > 0),
    CONSTRAINT uq_payment_reference UNIQUE (payment_Method_ID, reference_No),
    INDEX idx_payment_booking (booking_ID),
    INDEX idx_payment_status (payment_Status_ID),
    INDEX idx_payment_processor (processed_By),
    INDEX idx_payment_type (payment_Type_ID),
    INDEX idx_payment_deleted (deleted_At)
) ENGINE=InnoDB;

-- Booking_Log should be kept as an audit trail, not edited/deleted in normal use.
-- user_ID may be NULL for a SYSTEM-generated status change.
CREATE TABLE IF NOT EXISTS Booking_Log (
    log_ID INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    booking_ID INT UNSIGNED NOT NULL,
    user_ID INT UNSIGNED NULL,
    booking_Status_ID INT UNSIGNED NOT NULL,
    action_Type VARCHAR(60) NOT NULL,
    action_Date DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    remarks VARCHAR(500) NULL,
    CONSTRAINT fk_blog_booking FOREIGN KEY (booking_ID)
        REFERENCES Booking(booking_ID) ON DELETE RESTRICT ON UPDATE RESTRICT,
    CONSTRAINT fk_blog_actor FOREIGN KEY (user_ID)
        REFERENCES Users(user_ID) ON DELETE RESTRICT ON UPDATE RESTRICT,
    CONSTRAINT fk_blog_status FOREIGN KEY (booking_Status_ID)
        REFERENCES Booking_Status(booking_Status_ID) ON DELETE RESTRICT ON UPDATE RESTRICT,
    INDEX idx_blog_booking_date (booking_ID, action_Date),
    INDEX idx_blog_actor (user_ID)
) ENGINE=InnoDB;

-- ============================================================
-- 6. HOUSEKEEPING
-- ============================================================

CREATE TABLE IF NOT EXISTS Housekeeping_Task (
    task_ID INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    room_ID INT UNSIGNED NOT NULL,
    assigned_To INT UNSIGNED NOT NULL,
    assigned_By INT UNSIGNED NOT NULL,
    verified_By INT UNSIGNED NULL,
    housekeeping_Status_ID INT UNSIGNED NOT NULL,
    assigned_At DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    started_At DATETIME NULL,
    completed_At DATETIME NULL,
    verified_At DATETIME NULL,
    remarks VARCHAR(500) NULL,
    deleted_At DATETIME NULL,
    CONSTRAINT fk_hkt_room FOREIGN KEY (room_ID)
        REFERENCES Room(room_ID) ON DELETE RESTRICT ON UPDATE RESTRICT,
    CONSTRAINT fk_hkt_assigned_to FOREIGN KEY (assigned_To)
        REFERENCES Users(user_ID) ON DELETE RESTRICT ON UPDATE RESTRICT,
    CONSTRAINT fk_hkt_assigned_by FOREIGN KEY (assigned_By)
        REFERENCES Users(user_ID) ON DELETE RESTRICT ON UPDATE RESTRICT,
    CONSTRAINT fk_hkt_verified_by FOREIGN KEY (verified_By)
        REFERENCES Users(user_ID) ON DELETE RESTRICT ON UPDATE RESTRICT,
    CONSTRAINT fk_hkt_status FOREIGN KEY (housekeeping_Status_ID)
        REFERENCES Housekeeping_Status(housekeeping_Status_ID)
        ON DELETE RESTRICT ON UPDATE RESTRICT,
    CONSTRAINT chk_hkt_time_started CHECK (
        started_At IS NULL OR started_At >= assigned_At
    ),
    CONSTRAINT chk_hkt_time_completed CHECK (
        completed_At IS NULL OR
        (started_At IS NOT NULL AND completed_At >= started_At)
    ),
    CONSTRAINT chk_hkt_time_verified CHECK (
        verified_At IS NULL OR
        (completed_At IS NOT NULL AND verified_At >= completed_At)
    ),
    INDEX idx_hkt_assigned_staff (assigned_To, housekeeping_Status_ID),
    INDEX idx_hkt_room (room_ID, assigned_At),
    INDEX idx_hkt_assigned_by (assigned_By),
    INDEX idx_hkt_verified_by (verified_By),
    INDEX idx_hkt_deleted (deleted_At)
) ENGINE=InnoDB;

-- ============================================================
-- 7. INITIAL LOOKUP DATA (editable; not tied to fixed IDs)
-- ============================================================

INSERT IGNORE INTO User_Role (role_Name) VALUES
    ('Admin'), ('Housekeeping'), ('Guest');

INSERT IGNORE INTO Account_Status (account_Status_Name) VALUES
    ('Active'), ('Suspended'), ('Disabled');

INSERT IGNORE INTO Booking_Status
    (booking_Status_Name, blocks_Availability, requires_Hold_Expiry_Check) VALUES
    ('Pending Payment', TRUE, TRUE),
    ('Pending Approval', TRUE, FALSE),
    ('Confirmed', TRUE, FALSE),
    ('Declined', FALSE, FALSE),
    ('Cancellation Requested', TRUE, FALSE),
    ('Cancelled', FALSE, FALSE),
    ('Checked In', TRUE, FALSE),
    ('Checked Out', FALSE, FALSE),
    ('Completed', FALSE, FALSE),
    ('Expired', FALSE, FALSE);

INSERT IGNORE INTO Payment_Status
    (payment_Status_Name, counts_As_Paid) VALUES
    ('Pending', FALSE), ('Verified', TRUE),
    ('Failed', FALSE), ('Cancelled', FALSE);

INSERT IGNORE INTO Payment_Method (payment_Method_Name) VALUES
    ('Cash'), ('Bank Transfer'), ('GCash');

INSERT IGNORE INTO Payment_Type (payment_Type_Name, balance_Effect) VALUES
    ('Downpayment', 1), ('Remaining Balance', 1), ('Refund', -1);

INSERT IGNORE INTO Room_Status
    (room_Status_Name, ready_For_Checkin) VALUES
    ('Ready', TRUE), ('Needs Cleaning', FALSE),
    ('Cleaning', FALSE), ('Pending Inspection', FALSE),
    ('Under Maintenance', FALSE), ('Out of Service', FALSE);

INSERT IGNORE INTO Housekeeping_Status (housekeeping_Status_Name) VALUES
    ('Assigned'), ('In Progress'), ('Pending Verification'),
    ('Verified'), ('Needs Recleaning'), ('Cancelled');

-- Hotel requirements: check-in at 2 PM, checkout at 12 noon Philippine time;
-- cancellations less than 3 days before check-in are non-refundable.
-- pending_hold_minutes = 30 is a PROPOSED policy, not in the hotel specification;
-- change it after your group agrees on the payment time limit.
-- INSERT IGNORE seeds missing settings but does not overwrite existing settings.
INSERT IGNORE INTO Hotel_Setting (setting_Key, setting_Value, description) VALUES
    ('hotel_timezone', 'Asia/Manila', 'Official hotel timezone: Philippine Standard Time (UTC+08:00)'),
    ('checkin_time', '14:00:00', 'Standard check-in time: 2:00 PM Philippine time'),
    ('checkout_time', '12:00:00', 'Standard checkout time: 12:00 NN Philippine time'),
    ('late_cancellation_nonrefundable_days', '3', 'Non-refundable when cancellation is less than 3 days before check-in'),
    ('pending_hold_minutes', '30', 'Proposed number of minutes to complete the downpayment before a pending hold expires');

-- A pending hold does NOT expire on its own just because hold_Expires_At passes.
-- Later, the booking logic must release expired holds and update statuses safely.

-- NOT seeded: downpayment_percent. Specification requires a deposit
-- but does not specify the percentage. Enter it only after your group decides.
-- Example after approval:
-- INSERT INTO Hotel_Setting (setting_Key, setting_Value, description)
-- VALUES ('downpayment_percent', '30', 'Required percentage of total booking amount');
--
-- ALSO NOT seeded: room rates, exact room inventories, and extra fee frequency.
-- Insert those only after confirming the official inventory and business rules.
-- Example:
-- INSERT INTO Room_Type (room_Type, base_Price, included_Guests, max_Guests, extra_Person_Fee)
-- VALUES ('Standard Room', 3500.00, 2, 3, 1000.00);
--
-- ALTERING AN EXISTING CONFIGURED RATE DOES NOT CHANGE BOOKING_Room price snapshots.

-- ============================================================
-- 8. OPTIONAL CHECKS AFTER IMPORT
-- ============================================================
-- SHOW TABLES;
-- SELECT role_ID, role_Name FROM User_Role ORDER BY role_ID;
-- SELECT booking_Status_ID, booking_Status_Name FROM Booking_Status;
-- SELECT setting_Key, setting_Value FROM Hotel_Setting;
--
-- SOFT DELETE EXAMPLE (optional future feature; no archive table is created here):
-- UPDATE Users SET deleted_At = NOW() WHERE user_ID = 123 AND deleted_At IS NULL;
-- SELECT * FROM Users WHERE deleted_At IS NULL;  -- active users
-- SELECT * FROM Users WHERE deleted_At IS NOT NULL;  -- archived users
-- UPDATE Users SET deleted_At = NULL WHERE user_ID = 123; -- restore
--
-- ROOM AVAILABILITY MUST BE DATE-AWARE, NOT DERIVED FROM Room_Status ALONE:
--   new_checkin < existing_checkout AND new_checkout > existing_checkin
-- Ignore cancelled/declined/expired bookings; pending holds require expiry handling.
-- Protect availability checks and creation with a transaction/locking strategy.
