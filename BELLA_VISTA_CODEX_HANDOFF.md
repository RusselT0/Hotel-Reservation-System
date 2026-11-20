# Bella Vista Hotel & Events Place — Codex project handoff

**Project:** Hotel Reservation System (BSIT course project)  
**Handoff date:** 2026-11-19  
**Primary objective:** Continue the existing application, not rebuild it. **Step 13.3B - Reservation Approval and Decline** is implemented. Offline Guest password recovery works per user confirmation (Sections 32–33). Resume Step 13.3C reservation lifecycle after agreement on the operational rules.

## 1. Instructions for Codex

Treat this document as the project's development context and working backlog. The user's actual local files and live database are the source of truth. This handoff combines the previous development discussions, a review of the reorganized `Hotel_Reservation_System.zip` from November 18, and the previously reviewed database export. The ZIP was created **before** the subsequent instructions for correcting file paths. It is not proof that every edit or seed operation has been applied or tested. Do not overwrite newer local changes with the archived files.

Before changing code:

1. Open the existing project root in the workspace (normally `D:\xampp\htdocs\Hotel_Reservation_System`). Read the relevant files in their current locations; run `git status --short` and inspect existing diffs if Git is available. Preserve uncommitted user changes. Do not reset, force checkout, push, or commit without approval.
2. Inspect the active database schema using read-only queries (`SHOW TABLES`, `SHOW CREATE TABLE`, selected lookup records and counts). Do not import a dump over the existing database. Do not expose credentials, personal information, password hashes, or payment references.
3. Verify the relative-path migration described below. Fix only confirmed path problems first; do not repeat changes already made. Test from the normal XAMPP URL. If the server/database is not available to Codex, say what could not be executed and provide a local test procedure.
4. Implement the roadmap incrementally, one coherent feature at a time. Explain which files will change, make the smallest relevant edit, show a concise change summary and testing results, and stop at a useful checkpoint for the user. Do not automatically implement everything in this document at once.
5. Do not redesign the site, migrate to a framework, change the database engine, introduce unnecessary build tools, or alter payment behavior without the user's direction. Keep working features intact. Schema additions must be justified, reviewed, versioned as additive migrations, and backed up before application. Never drop/truncate existing tables or modify existing bookings just to test.

## 2. Project requirements and scope

Academic project for Bella Vista Hotel & Events Place, a boutique hotel and events venue. The system supports customer reservations and hotel operations. The main technology track is PHP/MySQL, not a commercial payment platform.

Requirements previously reviewed from the course's **NT 3102 - Hotel Reservation.docx(1).pdf** (the user identified this as the correct version) and the grading rubric:

- Landing/customer portal: hotel information, history, photo gallery, rooms/facilities, and reservation request interface.
- Date selection and automated reservation calculations (including extra guests) with real-time availability validation.
- Secure admin dashboard for incoming reservations, approval/cancellation and status management.
- Room/facility availability monitoring and summary reports for bookings and revenue.
- Housekeeping integration supports room readiness; a separate staff dashboard was not explicitly required by the previously reviewed brief. Hybrid reporting was recommended, subject to final user confirmation.

The course files, latest ERD (RBAC version), admin/customer/housekeeping flowcharts and rubric are supporting references. If available locally, read them before interpreting unconfirmed requirements. The exact latest official PDF/rubric were not re-read during this handoff; do not treat the illustrative floor, bed, amenity, add-on, or payment settings below as hotel-provided facts unless confirmed by the user.

## 3. Development environment

- Windows, XAMPP (Apache + PHP + MariaDB/MySQL), VS Code, Git.
- Project directory: `D:\xampp\htdocs\Hotel_Reservation_System\`.
- Local URL: `http://localhost/Hotel_Reservation_System/` (or the user's actual local host/port).
- Database name: `hotel_reservation_system`; PDO connection `$pdo` in `includes/database.php`.
- Use `utf8mb4`, prepared statements, exceptions, associative fetches, PHP timezone `Asia/Manila`, MySQL session timezone `+08:00`.
- Existing code uses some mixed-case SQL table identifiers. Windows MariaDB is typically case-insensitive for table names; use actual schema names (lowercase in SQL export) for new queries and portability.
- This is an academic local simulation. No actual money transfers, live third-party payment gateway, or real payment credentials are required.
- PHP-only include/action files should be UTF-8 without BOM; no whitespace before `<?php`, no unnecessary closing PHP tag. Preserve `session_start()` before output and `exit` after redirects.
- Local database credentials must stay local; avoid copying them into documentation or Git. The SQL dump can contain personal records and password hashes. Move private exports outside the served document root and exclude them from public repositories; do not delete the only backup.

## 4. Current folder structure

Verified from the reorganized ZIP (not necessarily the latest working tree):

```text
Hotel_Reservation_System/
├── index.php
├── actions/
│   ├── check_availability.php
│   ├── save_pending_booking.php
│   └── submit_downpayment.php
├── includes/
│   ├── auth_session.php
│   ├── database.php
│   └── room_catalog.php
├── pages/
│   ├── auth_account.php
│   ├── availability.php
│   ├── booking.php
│   ├── booking_created.php
│   ├── login.php
│   └── register.php
├── assets/
│   ├── css/
│   │   ├── auth.css
│   │   ├── availability_form.css
│   │   ├── availability_page.css
│   │   ├── booking.css
│   │   ├── booking_created.css
│   │   ├── components.css
│   │   ├── global.css
│   │   ├── global_style.css
│   │   ├── landing.css
│   │   ├── login.css
│   │   ├── photo_gallery.css
│   │   ├── responsive_landing.css
│   │   └── rooms_facilities.css
│   ├── js/
│   │   ├── availability_results.js
│   │   ├── availability_validation.js
│   │   ├── booking_created.js
│   │   ├── booking_modal.js
│   │   ├── gallery.js
│   │   ├── login.js
│   │   ├── main.js
│   │   ├── navbar.js
│   │   └── rooms_facilities.js
│   └── images/
│       ├── event hall.jfif
│       ├── family room.webp
│       ├── hero.jfif
│       ├── hero.webp
│       ├── hotel on mountains 2.webp
│       ├── logo-removebg.png
│       ├── logo.jfif
│       └── standard room.jfif
└── hotel_reservation_system.sql  # archived dump; not guaranteed up-to-date
```

Some extra CSS/JS files exist from earlier stages. Do not assume they are in use or delete them; inspect imports and references. Future directories may include `admin/`, `housekeeping/`, `guest/` with dedicated page styles and actions. Keep this straightforward modular design; no MVC rewrite at present.

## 5. Relative paths — completed review, pending runtime verification

The user explicitly prefers relative paths instead of hardcoding `/Hotel_Reservation_System/`. That prefix is a web-server-root-relative path and ties the site to its folder name. Use filesystem paths for PHP includes, document-relative URLs for links/redirects, and stylesheet-relative URLs for CSS images.

| Origin | Destination | Correct pattern |
|---|---|---|
| `index.php` | PHP include | `__DIR__ . '/includes/database.php'` or `room_catalog.php` |
| `pages/*.php`, `actions/*.php` | PHP include | `__DIR__ . '/../includes/database.php'` or `auth_session.php` |
| `includes/room_catalog.php` | Database include | `__DIR__ . '/database.php'` |
| `index.php` | CSS, scripts, static images | `assets/css/...`, `assets/js/...`, `assets/images/...` |
| `index.php` | Login, availability | `pages/login.php`, `pages/availability.php` |
| `pages/*.php` | Home / sections | `../index.php`, `../index.php#about`, etc. |
| `pages/*.php` | CSS, scripts, images | `../assets/css/...`, `../assets/js/...`, `../assets/images/...` |
| `pages/*.php` | Another page in same folder | `login.php`, `booking.php`, etc. |
| `pages/*.php` | Submit action | `../actions/save_pending_booking.php`, `../actions/submit_downpayment.php` |
| `actions/*.php` | Redirect to pages | `../pages/booking_created.php`, `../pages/booking.php?...` |
| `assets/css/login.css` | Background image | `url('../images/hero.webp')` |
| JS executing on `pages/availability.php` | AJAX, images | `../actions/check_availability.php`, `../assets/images/...` |

### File-by-file migration checklist (16 files)

The user stepped through all 16 reviews; actual edits and their runtime behavior must be verified in the local project. Preserve existing query parameters and `303` POST-redirect-GET behavior.

| File | Expected correction |
|---|---|
| `index.php` | Includes in `includes/`; login `pages/login.php`; availability form action `pages/availability.php`; CSS/JS/images under `assets/`. Section anchors remain local. |
| `includes/room_catalog.php` | Database include same directory. Image values `assets/images/...` and fallback `assets/images/hero.webp` because the caller is presently root `index.php`. These are browser paths, not PHP include paths. If reused from nested pages, return an image filename or generate a caller-appropriate URL rather than blindly reusing the root-relative-to-document string. |
| `includes/database.php` | No path change. Remove accidental output/BOM. |
| `includes/auth_session.php` | No path change. PHP-only file, avoid trailing output. |
| `actions/check_availability.php` | `require_once __DIR__ . '/../includes/database.php';` |
| `assets/js/availability_validation.js` | Shared by landing and nested availability page. See snippet below. |
| `pages/availability.php` | Include `../includes/database.php`; assets `../assets/...`; logo/home anchors `../index.php#...`; breadcrumb `../index.php`; form action `availability.php`; login `login.php`. |
| `assets/js/availability_results.js` | Images and fallback `../assets/images/...`; fetch `../actions/check_availability.php`; `booking.php?...` redirect remains same folder. |
| `pages/booking.php` | DB include, all assets and room image mapping/fallback use `../`; home links `../index.php` and anchors; action `../actions/save_pending_booking.php`; self-review `booking.php?...` unchanged. |
| `actions/save_pending_booking.php` | Include DB; success redirect `../pages/booking_created.php`; error redirect `../pages/booking.php?` + existing `http_build_query` values. |
| `pages/booking_created.php` | DB include; missing-session redirect `../index.php`; all home links and assets updated; room image mapping/fallback updated; action `../actions/submit_downpayment.php`. |
| `actions/submit_downpayment.php` | DB include; all three redirects to `../pages/booking_created.php` (303). No payment logic changes. |
| `pages/login.php` | Include both auth session and database via `../includes/`; assets & Back to Home via `../`; `auth_account.php` redirect, `login.php` self-action, and `register.php` link unchanged. |
| `pages/register.php` | Same include/assets/home patterns as login. Registration form self-action and success redirect `login.php` unchanged. |
| `pages/auth_account.php` | Auth include and CSS via `../`; login/logout redirects `login.php` and self-post logout form unchanged. |
| `assets/css/login.css` | `.auth-visual` background `url('../images/hero.webp')`. |

For shared `availability_validation.js`, the previously chosen approach is to resolve the POST URL from `form.action` (where `form` is the `#availabilityForm` element). Both forms must resolve to `pages/availability.php`:

```js
const response = await fetch(
    new URL('../actions/check_availability.php', form.action),
    { method: 'POST', body: formData }
);
// On successful validation (keep original searchParams construction):
const redirectURL = new URL(form.action);
redirectURL.search = searchParams.toString();
window.location.href = redirectURL.href;
```

Avoid copying JS file-relative paths into `fetch`: relative JS URLs resolve from the page, whereas CSS `url(...)` resolves from the stylesheet. Preserve HTML `?v=N` cache-busting syntax; a past bug used `rooms_facilities.cssv=4` and `rooms_facilities.jsv=2`, producing 404s. Use `?v=...`, and increment version if needed. Do not replace existing classes, IDs, form attributes, or variable names merely to fix paths. Check DevTools Network and Console for 404s, JSON parse errors and redirects. PHP syntax checks do not establish that the site runs end-to-end.

## 6. Established business rules

| Room type | Room codes | Base/night (PHP) | Included guests | Maximum guests | Extra fee per additional guest, per reservation |
|---|---|---:|---:|---:|---:|
| Standard Room | Room A, Room B, Room C | 3,500 | 2 | 3 | 1,000 |
| Family Room | FR1, FR2, FR3 | 6,000 | 4 | 5 | 0 |
| Event Hall | Event Hall | 15,000 | 30 | 40 | 500 |

**Do not "correct" Family Room pricing:** the fifth guest is free intentionally. Do not revert the Event Hall maximum to the earlier mockup's 100 guests. The latest agreed maximum is 40. Use the live database for current catalog values rather than hardcoding the rates in rendering logic.

- Check-in 14:00; checkout 12:00 (Asia/Manila). Dates must be valid `Y-m-d`; no check-in in the past; checkout strictly after check-in; guest count positive and not above the selected type's maximum.
- One individual room per current reservation UI; the bridge tables allow future expansion to multiple rooms, but don't invent a multi-room booking flow now.
- Extra guest fee is **one-time per additional guest per reservation**, NOT multiplied by number of nights (explicit project decision).
- Add-ons are charged quantity × booking-time unit price, once per reservation. Current code allows 0–10 units per available add-on; verify constraints when revising.
- Academic working settings: 30% downpayment and 30-minute Pending Payment hold. These are DB-configured, not necessarily official hotel policy. Payment is currently simulated and the user wants to revise it later.
- Hotel terms previously discussed: downpayment to confirm, remaining amount at check-in, late cancellation within three days non-refundable (clarify exact cutoff interpretation before implementing), damage policy, no-smoking rule, quiet hours. Retain the existing terms and avoid inventing fines or refunds.
- Guests may book without signing in or creating an account. Registration/login is optional for convenience and future self-service.

### Calculation

Perform authoritative calculations in PHP using integer cents, then persist DECIMAL values. Do not accept the client-provided price as authoritative. Recalculate under transaction when saving; retain booking-time rate and add-on price snapshots.

```text
nights = date difference (checkout date - check-in date)
roomSubtotal = nights × nightlyRate
extraGuests = max(0, guests - includedGuests)
extraCharge = extraGuests × extraPersonFee
addonSubtotal = sum(quantity × unitPrice)
bookingTotal = roomSubtotal + extraCharge + addonSubtotal
downpayment = round(bookingTotal × configuredPercent / 100) to cents
remainingBalance = bookingTotal - verified payments (net of refunds)
```

For the current review/simulated downpayment screen, the projected balance after required deposit is `bookingTotal - requiredDownpayment`, not proof the deposit was paid. Keep a future accounting distinction between due, submitted, and verified amounts.

Example: Standard Room, two nights, 3 guests, 2 Breakfast add-ons at ₱250 each: ₱7,000 + ₱1,000 + ₱500 = ₱8,500; required deposit ₱2,550; projected balance ₱5,950. Family Room, two nights, 5 guests: ₱12,000 before add-ons, no extra fee.

## 7. Database architecture (24 baseline tables; 26 after Steps 11.4 and 12.6A)

Baseline from the November 18 SQL export `hotel_reservation_system(4).sql` and the archive's database table list. Column names below are the established schema, not commands to recreate the database. IDs usually unsigned and auto-increment primary keys except lookup bridge keys; check `SHOW CREATE TABLE` for exact types, constraints, defaults, indexes, and FK names. Most entity/bridge tables use `deleted_At` for soft deletion. The lookup `is_Active` fields represent enabled lookup rows, not necessarily a user's status; check the actual status name and account row.

### Access and inventory

| Table | Main columns |
|---|---|
| `user_role` | `role_ID`, `role_Name`, `is_Active` |
| `account_status` | `account_Status_ID`, `account_Status_Name`, `is_Active` |
| `users` | `user_ID`, `role_ID`, `account_Status_ID`, `first_Name`, `last_Name`, `email_Address` (unique), `phone_Number`, `password_Hash` (nullable), `created_At`, `deleted_At` |
| `room_type` | `room_Type_ID`, `room_Type`, `base_Price`, `included_Guests`, `max_Guests`, `extra_Person_Fee`, `deleted_At` |
| `room` | `room_ID`, `room_Type_ID`, `floor_ID` (nullable), `room_Status_ID`, `room_Code` (unique), `deleted_At` |
| `room_status` | `room_Status_ID`, `room_Status_Name`, `ready_For_Checkin`, `is_Active` |
| `floor` | `floor_ID`, `floor_Number`, `deleted_At` |
| `bed_type` | `bed_Type_ID`, `bed_Type_Name`, `deleted_At` |
| `room_type_bed_type` | composite PK: `room_Type_ID`, `bed_Type_ID`; `num_Beds`, `deleted_At` |
| `feature` | `feature_ID`, `feature_Name`, `deleted_At` |
| `room_type_feature` | composite PK: `room_Type_ID`, `feature_ID`; `deleted_At` |
| `addon` | `addon_ID`, `addon_Name`, `addon_Price`, `deleted_At` |
| `room_type_addon` (Step 11.4) | composite PK: `room_Type_ID`, `addon_ID`; `deleted_At` |

### Reservations and payment

| Table | Main columns |
|---|---|
| `booking` | `booking_ID`, `user_ID`, `booking_Status_ID`, `checkin_Date`, `checkout_Date`, `booking_Amount`, `required_Downpayment`, `confirmation_Code` (nullable unique), `hold_Expires_At`, `created_At`, `deleted_At` |
| `booking_status` | `booking_Status_ID`, `booking_Status_Name`, `blocks_Availability`, `requires_Hold_Expiry_Check`, `is_Active` |
| `booking_room` | composite PK: `booking_ID`, `room_ID`; `num_Guest`, `nightly_Rate`, `extra_Guest_Fee` (rate snapshot), `included_Guests_Snapshot`, `checked_In_At`, `checked_Out_At`, `deleted_At` |
| `booking_addon` | composite PK: `booking_ID`, `addon_ID`; `quantity`, `unit_Price` (snapshot), `deleted_At` |
| `booking_log` | `log_ID`, `booking_ID`, `user_ID` (actor, nullable where appropriate), `booking_Status_ID`, `action_Type`, `action_Date`, `remarks` |
| `payment` | `payment_ID`, `booking_ID`, `payment_Method_ID`, `processed_By` (nullable user FK), `payment_Status_ID`, `payment_Type_ID`, `amount`, `reference_No`, `payment_Date`, `created_At`, `deleted_At`; unique `(payment_Method_ID, reference_No)` |
| `payment_method` | `payment_Method_ID`, `payment_Method_Name`, `is_Active` |
| `payment_status` | `payment_Status_ID`, `payment_Status_Name`, `counts_As_Paid`, `is_Active` |
| `payment_type` | `payment_Type_ID`, `payment_Type_Name`, `balance_Effect`, `is_Active` |

### Housekeeping and settings

| Table | Main columns |
|---|---|
| `housekeeping_task` | `task_ID`, `room_ID`, `assigned_To`, `assigned_By`, `verified_By`, `housekeeping_Status_ID`, `assigned_At`, `started_At`, `completed_At`, `verified_At`, `remarks`, `deleted_At` |
| `housekeeping_status` | `housekeeping_Status_ID`, `housekeeping_Status_Name`, `is_Active` |
| `hotel_setting` | `setting_Key` (PK), `setting_Value`, `description`, `updated_At` |

Main relationships: one `users` table for all roles; booking belongs to a guest; `booking_room` associates each reservation with rooms; `booking_addon` stores purchased add-ons; payment and logs belong to bookings; room belongs to room type and optionally a floor; features/beds belong to room types via junctions; housekeeping tasks reference a room and users for actual assignee, assigner and verifier. Use foreign keys, unique constraints, and transactions. Do not assume a user's role from a numeric value supplied by a browser.

### Existing lookup seed values

Use names/flags and confirm live IDs rather than baking magic numbers into business logic.

| Lookup | Baseline values (ID: name) |
|---|---|
| `user_role` | 1 Admin; 2 Housekeeping; 3 Guest |
| `account_status` | 1 Active; 2 Suspended; 3 Disabled |
| `room_status` | 1 Ready (ready flag 1); 2 Needs Cleaning; 3 Cleaning; 4 Pending Inspection; 5 Under Maintenance; 6 Out of Service (all other ready flags 0) |
| `booking_status` | 1 Pending Payment; 2 Pending Approval; 3 Confirmed; 4 Declined; 5 Cancellation Requested; 6 Cancelled; 7 Checked In; 8 Checked Out; 9 Completed; 10 Expired |
| `housekeeping_status` | 1 Assigned; 2 In Progress; 3 Pending Verification; 4 Verified; 5 Needs Recleaning; 6 Cancelled |
| `payment_method` | 1 Cash; 2 Bank Transfer; 3 GCash |
| `payment_status` | 1 Pending; 2 Verified (counts as paid); 3 Failed; 4 Cancelled |
| `payment_type` | 1 Downpayment (+1); 2 Remaining Balance (+1); 3 Refund (-1) |

Booking states blocking inventory: Pending Payment (subject to hold expiry); Pending Approval; Confirmed; Cancellation Requested; Checked In. Declined, Cancelled, Checked Out, Completed, Expired are nonblocking. Only Pending Payment has `requires_Hold_Expiry_Check=1` in baseline. Settings: `checkin_time=14:00:00`, `checkout_time=12:00:00`, `downpayment_percent=30`, `hotel_timezone=Asia/Manila`, `late_cancellation_nonrefundable_days=3`, `pending_hold_minutes=30`.

### Database seeding status

**Current verification (2026-11-19):** live room metadata and add-on counts were confirmed; see Section 13. The following describes the historical pre-seeding export and proposed seed values.

The November 18 pre-seeding export had 24 tables; 18 populated and 6 empty (floor, feature, bed type, their two junctions, housekeeping task). The archived ZIP also has no floor/feature inserts. Later seeding instructions were provided; the user proceeded to UI integration but did not explicitly confirm every SQL seed. Inspect the **live** database rather than insert again. Previously proposed demo data (NOT verified hotel inventory):

- Floors 1 and 2; Room A/B/C on 1; FR1/2/3 on 2; Event Hall floor nullable.
- Bed types: Single, Double, Queen, King, Bunk, Extra Bed. Standard: 1 Queen Bed; Family: 2 Queen Beds; Event Hall: no beds.
- Feature catalog: Free Wi-Fi, Air Conditioning, Television, Private Bathroom, Mountain View, Hot and Cold Shower, Seating Area, Parking Access, Sound System, Projector, Dining Area, Function Area. Standard proposed 7: Wi-Fi, AC, TV, bath, mountain view, shower, parking. Family same + seating (8). Hall: Wi-Fi, AC, seating, parking, sound, projector, dining, function (8).
- Existing add-ons: Breakfast ₱250, Extra Pillow ₱100. Proposed: Extra Blanket ₱150, Extra Towel ₱100, Extra Mattress ₱500, Room Decoration ₱1,000, Event Decoration ₱2,500, Additional Projector Rental ₱1,500. Final applicability and actual prices require confirmation.
- Proposed fictional test users: admin, housekeeping, guest, suspended and disabled guest. Only use synthetic `example.test` addresses, distinct strong local test credentials hashed using PHP `password_hash`. Do not hardcode reusable credentials, store plaintext passwords, or expose hashes in frontend or logs. Existing unregistered guest rows legitimately have NULL password hashes.
- For the proposed seed only, expected counts were 2 floors, 6 bed types, 2 bed associations, 12 features, 23 feature associations, 8 add-ons, 15 users (6 previous + 9 fictional users). These are not target invariants; do not force them if the current DB differs. Test records are not production hotel information.

## 8. Existing functionality and code ownership

### Landing page — implemented; user reported fixes

`index.php` retrieves active room type options from MySQL and uses `includes/room_catalog.php` to retrieve room type pricing/capacity, related features and bed quantities in separate queries, grouping by room type ID. Creates dynamic `.room-card` articles in `#roomTrack`, includes `data-room-details` JSON encoded and HTML attribute escaped. `assets/js/rooms_facilities.js` parses the data and populates the `#roomDetailsModal` dialog using `textContent` and list elements. Bed section hidden when no records, important for Event Hall. Check Availability selects matching `#facility` by numeric room type ID, returns to `#availabilityForm`. Image mapping still uses existing files, not DB image records. No individual floor displayed here, because it's a category view. Existing photo gallery and navbar scripts are separate. Catalog DB failure should show modal feedback, not raw SQL error. Original CSS uses `.rooms-carousel` positioning; inspect arrow layout if necessary, not a priority unless it actually breaks.

### Availability - implemented baseline and Step 11.2 enhancement

**Current implementation:** Step 11.2 is complete; the baseline below explains the original search contract, which has been extended additively as documented in Section 13.

`index.php` and `pages/availability.php` use `#availabilityForm` with fields `checkin`, `checkout`, `facility` (numeric room type ID), `guests`, `novalidate`. Shared `assets/js/availability_validation.js` intercepts submission, posts to `actions/check_availability.php` and on success navigates to `pages/availability.php` with query parameters. Invalid input is shown in centered availability error modal (`#availabilityErrorModal`, `#availabilityErrorMessage`). Results page uses `assets/js/availability_results.js` to read parameters and call the same endpoint again so it checks current availability.

Endpoint baseline from the ZIP: POST only; validates required strings, numeric type, guest count, dates, capacity; loads settings and chosen room type; SQL selects individual `r.room_ID`, `r.room_Code` with `room_status.ready_For_Checkin = 1` and no blocking overlapping booking. Result shape:

```json
{
  "success": true,
  "room_type": "Standard Room",
  "checkin": "YYYY-MM-DD",
  "checkout": "YYYY-MM-DD",
  "guests": 3,
  "nightly_rate": "3500.00",
  "included_guests": 2,
  "max_guests": 3,
  "extra_person_fee": "1000.00",
  "available_count": 1,
  "rooms": [{"room_ID": 1, "room_Code": "Room A"}]
}
```

Errors use `{ "success": false, "message": "..." }` and appropriate HTTP status. Return zero rooms as a normal successful search where applicable. Results JS creates DOM elements in `createRoomCard(room, data)` with classes `.avp-room-card`, `.avp-room-image`, `.avp-room-details`, `.avp-available-text`, `.avp-room-price`, `.avp-price-label`, `.avp-price`, `.avp-book-btn`. Containers `#availabilityResults` and `#availabilityCount`. Book Now uses `URLSearchParams({room_id: room.room_ID, checkin: data.checkin, checkout: data.checkout, guests: data.guests})` to go to same-folder `booking.php?...`. Use actual existing variable and markup names.

#### Availability conflict rule

Date overlap uses half-open intervals and actual check-in/out times: existing check-in < requested checkout AND existing checkout > requested check-in. Current predicate: active `booking_room`, `booking_status.blocks_Availability=1`, and (`requires_Hold_Expiry_Check=0` OR `hold_Expires_At IS NULL` OR `hold_Expires_At > NOW()`). Expired Pending Payment holds don't block inventory; null hold on such a status currently blocks defensively. Preserve this during display-only work. Check whether all availability/save queries exclude soft-deleted booking rows (`b.deleted_At IS NULL`); the archived endpoint lacks this explicit predicate. Review separately and align all paths if fixing it. Existing `ready_For_Checkin=1` restriction also excludes rooms currently needing cleaning from future-date searches; later clarify how to separate immediate check-in readiness from future reservability and handle maintenance dates. Do not change this semantic as part of Step 11.2.

### Booking — initial implementation working before migration

`pages/booking.php` validates selected room/date/capacity again, renders guest form and add-ons, reviews the calculated cost in cents, checks accepted terms. Session `booking_review` holds random token, timestamp, validated payload. POST reviewed booking to `actions/save_pending_booking.php`. Action uses transaction and row lock, revalidates room, overlaps, catalog prices/capacity, totals and token, resolves guest (currently rejects existing email), inserts Pending Payment reservation, `booking_room`, `booking_addon`, `booking_log`. Stores `booking_success` session and redirects to `pages/booking_created.php`. Errors use `booking_save_error` session and redirect retaining GET query parameters. Booking display is styled with `.bk-*`; separate `booking_modal.js` and existing responsive rules. Booking information should not be trusted merely because it originated from an availability result.

### Simulated downpayment — initial implementation; change later

`pages/booking_created.php` reads session-backed reservation and queries current booking/payment eligibility. Displays reservation summary and simulated GCash/Bank Transfer form. Session `payment_token`; action `actions/submit_downpayment.php` checks method, reference, eligibility, hold expiry, token and duplicate reference within method, uses transaction, inserts Pending payment with current required downpayment and null verification fields, sets booking Pending Approval, logs transition, redirects. `payment_message` / `payment_error` session feedback is rendered in centered modal. A submitted reference is not proof of verified payment. User specifically requested postponing payment redesign; preserve current academic simulation. Existing test bookings include expired holds and pending approvals; don't delete them or count pending payments as verified revenue.

### Authentication — initial implementation; role-aware redirect pending

`includes/auth_session.php`: session handling, `auth_token`, helpers `authPost`, `validAuthToken`, `authEscape`. `pages/login.php` validates token, fetches user joined to role/status, excludes soft-deleted user, verifies `password_Hash` with `password_verify`, only Active accounts and permitted roles, regenerates session ID and session token, stores `$_SESSION['auth_user']` (`user_id`, `first_name`, `last_name`, `email`, `role`) and redirects to `auth_account.php`. `pages/register.php` validates inputs and CSRF, uses `password_hash(PASSWORD_DEFAULT)`, creates Active Guest; password 12–72 bytes, matching confirmation; login success feedback via session. `assets/js/login.js` shared by register/login, shows password toggle, custom validation and centered feedback; forgot password is a placeholder. `auth_account.php` temporary session test page and CSRF-protected logout, not a final dashboard. Registration of a new guest and initial auth were implemented/tested earlier; test again after path migration.

Known issue: guest checkout can create `users` row with NULL password and unique email; current registration rejects existing email and current checkout rejects existing email. It is unsafe to claim existing records by simply assigning a password when someone types their email. Later implement proof of identity/ownership (admin-assisted academic verification or verified email token flow after approval), prevent account enumeration where possible, integrate authenticated repeat-guest bookings and define safe nonauthenticated repeat booking behavior. Never let one guest access another guest's reservation. Do not bypass identity checks to work around duplicate email.

## 9. UI/UX and coding conventions

- Maintain Bella Vista aesthetic: forest green (`#34402b`/`#353E2B`), muted gold (`#a78c51`/`#A18C64`), cream (`#f7f7f2`), sage (`#e5e8df`), white surfaces. Reuse existing typography, spacing, navbar, logo and imagery. Do not replace the user-provided images with external stock assets.
- Separate feature/page CSS and JS. Put shared resets/variables in the existing `global_style.css` where appropriate. Use `responsive_landing.css` for responsive rules, extending existing 900px tablet and 600px mobile breakpoints rather than creating duplicate blocks. Future dashboard responsive CSS may be separate by explicit design decision.
- User strongly prefers centered `<dialog>` feedback for validation, success and errors; no browser native validation bubbles (`novalidate` + custom validation), no raw error dumps, no blocking browser `alert()` for routine feedback. Preserve focus, keyboard dismissal, readable mobile dialogs and safe user messages. Normal empty-result information may be part of the results UI; keep errors in feedback modals.
- Login/register design: split image/form card on desktop; no full navbar/footer, only Back to Home and copyright. Gold fading divider below logo/brand; simplified single-column form on tablet and mobile, no unnecessary nested card. Keep existing layout intact.
- Escape output with `htmlspecialchars` (ENT_QUOTES, UTF-8) in PHP; use `textContent`/DOM creation for database values in JS; no unsanitized `innerHTML`. JSON attributes must be escaped. Validate server-side even when client validation exists. Use parameterized PDO SQL, authorization, CSRF, transaction checks, and consistent soft deletion. Never display stack traces or sensitive logs to users.
- Prefer reusable helpers, but avoid excessive abstraction. Use meaningful naming consistent with existing code. Explain decisions concisely to the user, list changed files and tests, and distinguish work completed from proposals.

## 10. Development roadmap and checkpoints

The numbering below continues the prior discussions. Folder migration steps 1–16 were a separate maintenance checklist, not the feature roadmap numbering. Do not confuse them with Step 11.2. `[x]` means implementation/review was reached in the conversation, not independently verified in the latest workspace.

- [x] Database architecture, RBAC-oriented ERD, base lookup data.
- [x] Landing page, gallery, carousel and responsive design.
- [x] Room availability search and result cards (basic version).
- [x] Guest information, booking review, authoritative calculations, reservation save.
- [x] Simulated downpayment submission (temporary implementation).
- [x] Login, guest registration and temporary `auth_account.php` test page.
- [x] Populate additional room metadata (live DB verified on 2026-11-19; user count differs from the illustrative seed count, and no additional accounts were created).
- [x] Step 11.1: dynamic landing room cards and details modal.
- [x] File organization and relative path review (16 files reviewed; actual post-migration test pending).
- [~] Baseline smoke test: confirmed path issues resolved; public GET/search paths and redirect behavior verified. Reservation/payment writes and account lifecycle testing remain pending explicit approval.
- [x] **Step 11.2: dynamic room availability results - implemented and core read-only tests passed.** Remaining edge-case fixture checks are noted in Section 13. Await user review.
- [x] Dynamic booking room summary using the same metadata (Step 11.3; live HTTP checks passed; see Section 14).
- [x] Room-type-specific add-ons and server-side validation (Step 11.4, approved migration applied; see Section 15).
- [x] Guest session recognition and account link on public pages (Step 12.1; anonymous HTTP tests passed; authenticated browser testing pending).
- [x] Booking form auto-fill for authenticated Guest accounts (Step 12.2; anonymous regression passed; authenticated browser test pending).
- [x] Safe authenticated repeat reservations (Step 12.3; implemented; signed-in save test pending).
- [x] Step 12.6A: reusable account token schema (approved hybrid approach; migration applied).
- [x] Step 12.6B: Admin-assisted activation token issuance after verified identity (implementation complete; authenticated issuance test pending).
- [x] Step 12.6C: guest activation form and one-time token consumption; preserve existing user ID (implementation complete; valid-code test pending).
- [~] Step 12.6D: live database evidence confirms successful activation and replacement-code revocation; signed-in UI and replay rejection checks require user confirmation.
- [x] Step 12.7A: Admin-assisted Guest password reset code issuance (shared account-token schema and UI; no reset yet).
- [x] Step 12.7B: Guest password reset redemption and invalidation of prior authenticated sessions (implementation; end-to-end testing pending).
- [~] Step 12.7C: user confirms offline password recovery works; individual edge-case results not independently verified.
- [x] Centralized authorization guards (Step 12.4; existing account page and anonymous/API checks passed).
- [x] Role-based dashboard redirects and protected dashboard entry pages (Step 12.5; signed-in verification pending).
- [ ] Remaining session hardening and authenticated regression checks.
- [x] Admin dashboard layout and sidebar (Step 13.1; protected foundation only).
- [x] Admin dashboard live overview/statistics (Step 13.2; live read-only aggregate checks passed).
- [x] Admin reservation listing, search, filters, pagination and read-only details (Step 13.3A).
- [x] Admin manually verifies submitted downpayment and approves or declines pending reservations (Step 13.3B; authenticated write-flow testing pending).
- [ ] Remaining reservation state-changing actions and other management modules.
- [ ] Hybrid housekeeping operations and optional simple staff dashboard, subject to confirmation.
- [ ] Guest dashboard and reservation history/self-service.
- [ ] Payment process refinement (user to provide revised requirements; no premature change).
- [ ] PHPMailer integration for activation, password recovery, and reservation notifications; admin-assisted offline Guest recovery remains available.
- [ ] reCAPTCHA and security hardening (do not silently bypass online verification on network failure).
- [ ] UI/UX polish, interactivity and WCAG accessibility review.
- [ ] Final integration, security, accessibility, responsive tests, academic documentation and evaluation.

### Phase 0 — Migration smoke test

Run PHP lint on relevant `.php` files with available PHP executable (`D:\xampp\php\php.exe -l <path>` if PHP is not on PATH). Check JS syntax if Node is available; no new runtime dependencies needed. Run application through Apache/MySQL, not `file://`. Test:

- Landing page loads; CSS, images, logo, navigation anchors, gallery and room carousel; room details show actual seeded metadata, Event Hall hides beds; Check Availability selects correct type and focuses date.
- Invalid dates/capacity show centered modal; valid search from both landing and availability search forms works; no network 404 or invalid JSON; room images render and repeated searches update results.
- Book Now opens booking.php with correct room ID and search parameters. Review form posts back to same page; save submits to `actions/` and ends at `pages/booking_created.php`; error returns with preserved fields. Verify new test records only when approved; use synthetic test data.
- Existing simulated downpayment shows correct feedback and Pending Approval on valid submission; expired hold or duplicate method/reference is rejected appropriately; no actual payment.
- Login/register CSS and desktop image display; auth validation/success dialogs; existing role authentication; registration and logout; unauthorized account page redirects to login.
- Tablet/mobile at approximately 900px and 600px; no horizontal overflow from the new folder structure or UI. Check `Network`, `Console`, PHP error log. Do not claim successful runtime tests without performing them.

### Phase 1 - Step 11.2: dynamic availability results (implemented; awaiting review)

**Goal:** Show useful, live database-driven details for each available physical room while preserving the current result card layout, search filters, JSON contracts, booking links and availability policy. This is deliberately a presentation/data-enrichment step, not a redesign of the booking or payment state machine.

Files to inspect/change first:

| File | Responsibility |
|---|---|
| `actions/check_availability.php` | Add floor and room type metadata to the existing availability response |
| `assets/js/availability_results.js` | Render details from the JSON while preserving Book Now behavior |
| `pages/availability.php` | Only change markup/asset versions if needed; preserve existing containers, search and sidebar |
| `assets/css/availability_page.css` | Extend `.avp-*` card styles for metadata and amenities |
| `assets/css/responsive_landing.css` | Adjust existing tablet/mobile blocks only where necessary |
| `includes/room_catalog.php` or a small reusable metadata helper | Optional: share query logic if it avoids duplication without causing output/session side effects or wrong document-relative images |

Recommended minimal backend approach:

1. Inspect actual tables/column names and whether floor, feature and bed assignments are populated. Do not seed blindly. Keep existing date, capacity, overlap and room status validation.
2. Extend the physical room query with `LEFT JOIN floor f ON f.floor_ID = r.floor_ID AND f.deleted_At IS NULL` and select `f.floor_Number AS floor_Number`. Use LEFT JOIN, not INNER JOIN, because floor is optional and a missing/deleted floor must not make a valid room disappear. Do not expose internal room maintenance/housekeeper information to customers.
3. Because each search selects one room type, query amenities once for that type; join `room_type_feature` to `feature`, exclude soft-deleted records in both. Query bed configuration once for the type; join `room_type_bed_type` to `bed_type`, exclude soft-deleted records in both. Use bound room type ID and stable ordering. Separate queries prevent feature × bed row multiplication and duplicate result cards. Do not query once per physical room (unnecessary N+1).
4. Keep every existing success JSON field and add `room_type_id`, type-level `features` array of strings, `beds` array of objects, and room-specific `floor_Number` (number or null). Example additive contract, to be reconciled against the actual code:

```json
{
  "success": true,
  "room_type": "Standard Room",
  "room_type_id": 1,
  "checkin": "YYYY-MM-DD",
  "checkout": "YYYY-MM-DD",
  "guests": 3,
  "nightly_rate": "3500.00",
  "included_guests": 2,
  "max_guests": 3,
  "extra_person_fee": "1000.00",
  "available_count": 2,
  "rooms": [
    {"room_ID": 1, "room_Code": "Room A", "floor_Number": 1},
    {"room_ID": 2, "room_Code": "Room B", "floor_Number": 1}
  ],
  "beds": [{"type": "Queen Bed", "quantity": 1}],
  "features": ["Free Wi-Fi", "Air Conditioning"]
}
```

This is an illustrative response, not a claim about live seed values or current room availability. For no metadata, return arrays `[]` and null floor; leave those sections absent or show a neutral unavailable label. Do not invent beds, amenities or floor numbers. `available_count` must count actual distinct rooms.

Recommended front-end approach:

- Enhance `createRoomCard(room, data)` in the current script. Display physical `room_Code`, type, floor if known (e.g., Floor 1), included and max guests, bed quantities, features as a small list/chips, and applicable extra-person fee labeled one-time per guest per reservation. Keep base nightly rate, availability indicator, existing image, Book Now and params. No fabricated photos or claims.
- Render using `document.createElement` / `textContent`; treat the metadata as untrusted. Avoid unsafe HTML interpolation. Preserve `../assets/images/...` and `../actions/...` paths if verified. Since the card is on `pages/availability.php`, a root-page image string from `room_catalog.php` would be wrong unless normalized for this caller.
- Keep existing CSS organization and card grid proportions. Show amenities compactly; if long lists are unwieldy, propose an accessible details expansion rather than crowded cards. No bed section for Event Hall with no beds; no misleading floor for null assignment. Respect mobile layout and keyboard interactions. Preserve sidebar filters as they exist, and do not invent unimplemented filter logic as part of a display-only change.
- `availability_validation.js` must remain compatible with the additive API; it still needs the original success/message and query params. Do not pass display metadata or authoritative prices in Book Now URL.

**Acceptance criteria for Step 11.2:**

- Searches from both forms yield actual available physical rooms, correct count, one card per room, and unchanged Book Now links/validation.
- For a seeded Standard Room, floor and bed quantities match DB and each feature is displayed exactly once; Family max 5 and 0 extra fee; Event Hall max 40, no empty bed section and nullable floor handled; incomplete metadata does not prevent search.
- Overlapping and unexpired-blocking reservations still excluded; expired holds do not block; invalid guest count/date still fails; a zero-availability search yields a graceful empty state.
- Update a demo feature/floor in the database only with user approval or use read-only comparisons; displayed metadata should reflect current DB data on subsequent search.
- Layout remains usable on desktop, tablet, phone. No PHP warnings, browser console errors, 404s, JSON errors, or XSS from database strings; syntax checks reported.
- Report changes and any tests blocked by missing PHP/server/database access. Stop here for user review before implementing the next feature.

### Phase 2 — Complete customer booking data

Dynamic `pages/booking.php` room summary: reuse selected physical room's current floor, type-level beds and amenities; keep authoritative room-by-ID checks, review token and booking-time snapshots. Consider the same metadata in booking confirmation if useful. Do not show historical bookings with current prices as if they were charged; use snapshots for accounting.

Room-specific add-ons: currently `addon` lacks a room-type applicability relationship. A proposed future additive `room_type_addon(room_Type_ID, addon_ID, deleted_At)` junction can associate applicable add-ons with types. Confirm which add-ons apply to each room (e.g., event-only vs room-only), global applicability, deletion/reactivation behavior, quantity limits, and actual prices. Then filter in booking form AND revalidate on save; don't merely hide invalid options in JS. Preserve `booking_addon.unit_Price` snapshots for past bookings. Backfill/seed safely after approval.

### Phase 3 — Authentication and guest account integration

- Address NULL-password guest accounts with proof of ownership and existing-email workflow; don't allow email-only account takeover. Confirm a realistic academic verification path (verified email token if supported, or admin-assisted). Link verified identity to existing guest record without duplication. Implement logged-in guest booking prefill using trustworthy session/DB identity, not a browser-submitted user ID. Handle legitimate repeat booking without exposing prior reservations or forcing registration.
- Introduce reusable guards for Admin, Guest and Housekeeping, session validation and role-based redirect. Authorization must live on server pages and actions as well as UI; verify enabled/active/non-deleted account and role. Check CSRF on state-changing actions, ownership on guest records and assigned staff tasks; regenerate session IDs on login/logout, set appropriate cookie flags (Secure only over HTTPS, HttpOnly, SameSite), address session expiration and login throttling. Keep auth errors generic. Password reset/verification must use secure, expiring one-use tokens or the approved manual flow; don't log secrets.
- `auth_account.php` is temporary; replace its role with appropriate dashboards only after guards exist. Non-logged-in access to public landing and booking remains allowed.

### Phase 4 — Admin dashboard (mandatory course deliverable)

Potential sections: dashboard overview (incoming/pending/confirmed reservations, occupancy/readiness, verified revenue); reservation list/details with filters and pagination; approve/decline/cancel/handle cancellation requests; check-in/out and completion; room status/inventory management (type, floor, beds, features, rates, applicability); housekeeping assignments/inspection; user management; summary reports (date ranges, booked nights, verified revenue/net refunds, status counts). Confirm precise scope and page design with user. Preserve the existing hotel branding. Use clear status values from lookup tables, server-side validation and audit logs. Do not count Pending payments as paid or sum booking totals as cash revenue. Confirmation-code generation and exact approval/payment relationship need explicit rules. Review row locks and state transitions for conflict/double-processing risks. Never let a customer change room prices, status or payment verification through request data.

### Phase 5 — Hybrid housekeeping (recommended design, not yet finalized)

A previous design concern: housekeeping staff may not be tech-savvy; requiring mandatory dashboard updates could delay hotel operations. Keep manual communication available. Admin retains operational control; simple staff UI is optional and useful for reducing re-entry when staff are comfortable using it.

Recommended workflow for confirmation:

1. Actual checkout triggers room `Needs Cleaning` (not merely planned checkout time).
2. Admin assigns a housekeeping task and responsible staff member. Staff can start/complete via simplified mobile-friendly task interface, OR verbally/call admin to report completion.
3. Admin can record the report on behalf of assigned staff; mark task `Pending Verification`, room `Pending Inspection`. This does not imply the admin performed cleaning.
4. Admin inspects room, verifies task and sets room `Ready`, or requests recleaning. Keep unauthorized staff from independently making a room ready if verification is required.
5. Keep assignee, assigner, verifier, timestamps and status. Proposed future fields such as `reported_By`/`reporting_Method` or a task audit log would record who submitted the update, separate from who cleaned. These fields are NOT part of the current schema. Do not add them without approval. Do not fake actual times when a verbal report arrives later.

Non-digital staff may have a housekeeping profile with no password; don't require them to sign in. Admin can update on their behalf. Reports reflect entered/received updates, not automatic detection of actual cleanliness. A separate staff dashboard is not mandated by the prior brief; confirm whether user chooses hybrid or admin-only before implementing. Review future-date availability: readiness for immediate check-in differs from whether room is reservable after cleaning, and maintenance scheduling must be handled explicitly.

### Phase 6 — Guest dashboard and revised payment

Guest dashboard: verified/authenticated guest sees only their own booking history, status, summaries and permitted cancellation requests/self-service. Anonymous guests still require a safe mechanism to look up their own reservation (not a guessable ID alone; finalize reference/verification policy). Protect personal details. Design cancellation policies and admin review in coordination with booking state and verified payments.

Payment refinement is deferred by user. Keep existing simulation intact until revised instructions. Before redesign confirm downpayment percentage, proof submission, verification/approval, cash handling, remaining balance, partial/multiple payments, refunds, cancellations, expiry and reporting. Separate booking and payment statuses. Use signed `balance_Effect` and `counts_As_Paid` when computing verified net payment sums. No real transfers or third-party credentials for this academic app unless a future separately approved scope changes.

## 11. Known open issues and verification priorities

| Priority | Issue | Action |
|---|---|---|
| Resolved | Archive predates final path corrections | Live working copy inspected; two confirmation links corrected |
| Verified | Seed status previously unknown | Live inventory metadata, add-on count, roles and account statuses checked read-only |
| Partial | Migration runtime smoke tests | Public pages, both search forms and unauthenticated redirects passed; write workflows intentionally not exercised |
| Resolved | Availability cards lack floor/beds/features | Step 11.2 additive API/UI extension implemented |
| Next | Reusable catalog image values are document-relative | Normalize only if extending use to nested pages |
| Planned | Inconsistent or incomplete soft-deletion/status filters | Audit all availability and save queries, not just one; align policy |
| Planned | Ready-for-check-in restriction on future bookings | Clarify desired separation of current readiness and future availability |
| Planned | Pending Approval can block dates without hold expiry | Define admin review/expiration workflow without silently changing existing holds |
| Planned | Duplicate email prevents existing unregistered guest activation/repeat checkout | Identity verification and safe integration |
| Planned | Full RBAC guards, auth redirects, session hardening | Implement before privileged dashboards/actions |
| Resolved | Add-ons previously unrestricted by room type | Approved `room_type_addon` migration applied and filtering enforced in review and save |
| Partial | Admin/housekeeping/guest dashboard foundations exist | Role-specific functionality remains pending; follow roadmap and confirm scope |
| Deferred | Payment logic to be changed | Await revised requirements |

## 12. Suggested first Codex task

After opening the current project in Codex, provide this instruction alongside the Markdown:

> Read `BELLA_VISTA_CODEX_HANDOFF.md` first. Inspect my current project files, respect existing changes, and verify the relative-path migration using the actual workspace. Do not rewrite the project or modify my database automatically. Then implement Step 11.2 only: enrich individual room availability results with database-driven floor, bed configuration and amenities while preserving the current search, reservation logic, UI style, and Book Now redirects. Make changes directly to the relevant files, test what you can, list files changed and verification results, and stop before the next feature. Tell me explicitly if you cannot run the application or inspect the live database. Do not modify payment logic yet.

**Maintain this handoff as a living project reference:** as features are completed, record actual changes, schema migration decisions and test outcomes in the project's documentation instead of assuming completion. The project roadmap is a guide, not permission to implement all future changes at once.

## 13. Development milestone record — 2026-11-19

### Phase 0 and Step 11.2 — dynamic room availability results

**Status:** Implemented; core live read-only tests passed. Await the user's approval before beginning Phase 2 (dynamic booking room summary).

The current working copy and Apache/MariaDB were inspected directly rather than relying on the archive. The live database has 24 tables, 7 physical rooms, 3 room types, 2 floors, 6 bed types, 2 room-type bed assignments, 12 features, 23 feature assignments, 8 add-ons, and 9 users. All room types, capacities, fees, room floor assignments and bed configuration agree with the previously agreed inventory. The 9 users differ from the illustrative 15-user seed expectation; no account changes were made. No schema migration or database seed was required.

| File | Actual change |
|---|---|
| `actions/check_availability.php` | Added nullable room floor via LEFT JOIN, and two parameterized type-level metadata queries with soft-deletion filters and stable ordering. Existing JSON fields preserved; added `room_type_id`, `features`, `beds`, and each room's `floor_Number`. The availability/overlap predicate was not changed. |
| `assets/js/availability_results.js` | Render floor, bed configuration, amenities and one-time extra guest fee from the response using DOM/textContent. Omit absent bed/floor fields; explicitly show no extra fee where applicable. Reuse the existing availability error dialog for endpoint/network errors. Preserve image selection, Book Now parameters, and empty-results behavior. |
| `assets/css/availability_page.css` | Added compact wrapping amenity chips and readable fee text; existing layout, branding and responsive grids preserved. |
| `pages/availability.php` | Updated asset cache versions and removed obsolete implementation comment. |
| `pages/booking_created.php` | Fixed two remaining `index.php` links to `../index.php`. No payment or booking behavior changed. |
| `includes/database.php` | Removed leading blank line before PHP to prevent premature output. Connection configuration unchanged. |

**Verification performed against XAMPP:**

- All 13 PHP files passed PHP 8.2.12 lint; `git diff --check` passed. All 81 static local HTML/CSS references checked resolve on disk after migration corrections.
- Homepage, login, register, availability and booking pages loaded via HTTP 200 without displayed PHP errors. Anonymous account and confirmation-page GETs redirected to the expected login/home pages.
- Live POST searches for Dec 3–5, 2026 returned three Standard Rooms, three Family Rooms, and one Event Hall, matching read-only database metadata. Standard: Floor 1, one Queen Bed, seven features, max 3, PHP 1,000 extra fee. Family: Floor 2, two Queen Beds, eight features, max 5, no extra fee. Hall: null floor, no beds, eight features, max 40, PHP 500 extra fee. Counts and room IDs matched the pre-change response.
- A search for Nov 19–20 excluded Room B due to an existing blocking reservation while returning Rooms A and C. Same-day checkout, past dates and excessive guest counts returned HTTP 400.
- Browser tests verified the homepage catalog modal and homepage search, repeated searches from the availability page, Family Room capacity error modal, direct-result error modal, Event Hall omission of floor and beds, Book Now navigation with correct room ID/dates/guests, and no observed console warnings/errors on tested result pages. Browser layout checks at desktop, tablet and mobile effective CSS widths (1067, 640 and 325px) found no horizontal overflow in the cards or amenity chips. Existing responsive CSS did not require changes.
- Existing expired holds were found, but none had a future checkout, so a live forward-date expired-hold test could not be performed. There was no suitable full-occupancy fixture for a zero-results runtime test; the existing empty state remains intact. No new booking, payment, registration or test record was created, and no schema or seed changes were performed. Full write-flow regression tests require explicit approval and appropriate synthetic fixtures.

**Remaining scope:** keep current availability readiness and overlap policy unchanged until the separate policy review; soft-deletion/status consistency, room-specific add-ons, booking summary metadata, guest identity integration, dashboards and payment redesign are subsequent milestones. Do not proceed without user approval.

## 14. Step 11.3 — Dynamic Booking Room Summary

**Status:** Implemented. The selected physical room's optional floor, capacity, bed quantities and expandable amenities list are loaded directly from the database. Queries use the selected room's actual type and exclude deleted metadata. Existing form, booking review, price calculation, payment, and save behavior were not changed. Room metadata errors use the existing booking feedback modal.

- `pages/booking.php`: optional floor join, prepared metadata queries, escaped display, feedback integration and CSS cache version.
- `assets/css/booking.css`: compact responsive amenity list and room metadata styling.
- Verification: PHP lint and `git diff --check` passed. Live GET requests for Standard Room, Family Room and Event Hall returned HTTP 200; floors, beds and amenity counts matched the database. Hall omits absent floor and beds. Standard two-night/three-guest preview remained PHP 8,000 and Hall two-night/40-guest preview remained PHP 35,000. No database records changed. Browser visual and write-flow regression testing remain pending.
- **Existing pricing inconsistency discovered:** the Family Room five-guest preview shows PHP 12,000 room charge and PHP 0 extra fee but incorrectly displays a message that the additional guest fee needs configuration. The live zero fee is intentional. Review both booking review and save validation of zero-valued fees, and correct with user approval before moving to room-type-specific add-ons. No pricing logic was changed in this display-only milestone.

## 15. Step 11.4 — Room-Type-Specific Add-ons (2026-11-19)

**Status:** Implemented after explicit user approval of the applicability matrix and database table. Do not proceed to the next milestone without user approval.

- Added versioned additive migration `migrations/2026-11-19_room_type_addon.sql`. A full pre-migration SQL backup was created outside the served project directory in the Windows temporary directory (`bella_vista_pre_11_4_20261119_203251.sql`); keep it private because it may contain sensitive records. The live schema now has 25 tables.
- `room_type_addon` uses the same unsigned ID types as the existing parent tables, a composite primary key, foreign keys, and soft-deletion support. Existing add-on and booking snapshot records were not modified.
- Approved assignments: Standard Room and Family Room each have Breakfast, Extra Pillow, Extra Blanket, Extra Towel, Extra Mattress and Room Decoration. Event Hall has Event Decoration and Additional Projector Rental. Total: 14 active relationships. All original prices and quantity limits (0–10 in form, 1–10 when saving selections) are unchanged.
- `pages/booking.php` now retrieves only active applicable add-ons using the selected room's database type ID; the existing review lookup validates the same filtered catalog.
- `actions/save_pending_booking.php` retrieves the locked room's type ID and rechecks active applicability and price in its existing transaction before persisting booking add-on snapshots. No payment or room-rate logic changed.
- Tests: migration and relationship counts verified; both changed PHP files passed lint and `git diff --check`. Live booking GETs returned HTTP 200 and exactly six, six, and two applicable options for Standard, Family, and Hall. A Standard Room review with two Breakfast add-ons calculated PHP 8,500 total, PHP 2,550 required deposit, PHP 5,950 projected balance. No test booking was saved; save transaction regression remains pending explicit approval.
- The user reaffirmed the Family Room zero fee is intentional and directed us to proceed with this milestone. The separate existing zero-fee validation inconsistency remains unchanged and deferred; do not alter database pricing.

## 16. Step 12.1 — Guest Session Integration (2026-11-19)

**Status:** Implemented; await approval before Step 12.2 (booking form auto-fill).

- `includes/auth_session.php`: added `authCurrentUser(PDO $pdo)`, which uses the server-side session's authenticated user ID to refresh current name, email and role from the database. It requires an active, non-deleted account, enabled role/status, and existing password. Anonymous users skip the query. Invalid accounts lose only `auth_user`; lookup errors fail closed for account recognition and are logged. No hashes are returned or exposed.
- `index.php`, `pages/availability.php`, `pages/booking.php`, and `pages/booking_created.php`: use the shared session include and `authCurrentUser`. When signed in, the navbar displays the escaped first name linked to the temporary `auth_account.php`; otherwise it retains Sign In / Register. Booking and confirmation pages reuse the shared session initializer, maintaining existing session data; a leading blank line in booking.php was removed to prevent premature output.
- `pages/auth_account.php`: use the same live account lookup rather than checking the cached session alone; retain existing CSRF-protected logout and provide a Back to Home link.
- `assets/css/landing.css`: constrain long account names with ellipsis without redesigning the navbar; bumped imports to version 4.
- Tests: all six changed PHP files linted successfully; `git diff --check` passed (only normal Git LF/CRLF notices). Live anonymous HTTP smoke checks passed for landing, availability and booking; PHP session cookie persisted. Anonymous account and confirmation requests redirected to login/home respectively. Read-only SQL verified the active-account eligibility conditions against the live schema. The authenticated browser branch and actual logout require a legitimate signed-in test account and remain unverified; no credentials were requested or bypassed. No reservations, account records, database schema, payment behavior or booking calculations were changed.
- Step 12.2 will add guest information auto-fill using authenticated identity, not browser-submitted user IDs. Until the subsequent identity/save step is approved, the existing checkout workflow and duplicate-email behavior remain unchanged. Comprehensive role guards and role-specific dashboard routing are later steps.

## 17. Step 12.2 — Guest Information Auto-fill (2026-11-19)

**Status:** Implemented for the booking form and review. Await approval before Step 12.3.

- `includes/auth_session.php`: extended the existing verified account query and refreshed server-side session with `phone_number` from `users.phone_Number`.
- `pages/booking.php`: selects auto-fill data only for a live authenticated `Guest` role; populates first name, last name, email and phone. Name/email are read-only and sourced server-side again on review; the contact phone can be entered or updated for the review without altering the account record. Anonymous and non-Guest accounts retain the manual guest form. Existing validation, add-on filtering, calculations and layout were retained.
- Tests: both changed PHP files passed lint; `git diff --check` passed. Anonymous GET returned empty editable fields; a valid anonymous review returned HTTP 200 and the expected PHP 8,500 total with two Breakfast add-ons. Live read-only SQL confirmed two eligible registered Guest accounts with phone numbers. Authenticated browser auto-fill and locked-field behavior remain pending a legitimate signed-in test. No test reservation was saved or user record changed.
- Known dependency: authenticated guest reuse in `actions/save_pending_booking.php` is not yet implemented; its existing duplicate-email policy can still prevent final submission for a registered guest. Step 12.3 must bind the review and save to the same verified Guest identity and reuse their existing user ID without trusting form identity, while preserving the anonymous booking path. Do not bypass proof of ownership for pre-existing passwordless accounts. No changes were made to save/payment logic or database schema in Step 12.2.

## 18. Step 12.3 — Authenticated Repeat Guest Reservations (2026-11-19)

**Status:** Implemented. Authenticated save-flow regression remains pending a legitimate signed-in guest and explicitly approved test booking. Await approval before Step 12.4.

- `pages/booking.php`: stores `auth_guest_id` in the server-side reviewed reservation (null for anonymous/manual checkout). All four verified account contact fields are now read-only for signed-in Guests and fetched again during review. This keeps phone information consistent: the current schema stores contact details on the user rather than a per-booking contact snapshot; profile changes are a separate feature. Anonymous fields remain editable.
- `includes/auth_session.php`: added optional `$lockForUpdate` to `authCurrentUser(PDO $pdo, bool $lockForUpdate = false)` for the existing reservation save transaction. Ordinary public pages retain the non-locking read.
- `actions/save_pending_booking.php`: uses the shared session; within the existing transaction revalidates and locks the account, compares the verified current Guest ID with the reviewed ID, and checks the reviewed contact details against the live account. If identity or details change, the stale review is invalidated and the existing feedback/redirect path requests a new review. When valid, uses the existing authenticated `user_ID` for booking and booking log, without creating a new user. The anonymous path still checks for existing email records and inserts a new passwordless guest only for a new email; it never associates a pre-existing account based on email alone.
- No schema, account profile, rates, availability, add-on, payment or pricing logic changes. The intentional zero fee for Family Room and its separate existing validation inconsistency remain unchanged.
- Tests: all three modified PHP files passed lint and `git diff --check`. Anonymous GET remained HTTP 200 with all contact inputs editable; a valid anonymous review with two Breakfast add-ons returned HTTP 200 and the expected PHP 8,500 total. A database transaction verified that the eligible registered guest account lookup can lock both existing eligible rows, then rolled back without changes. User count remained 9 and booking count 6. No account was impersonated and no reservation was saved; the signed-in repeat-booking end-to-end test remains pending.
- Next: Step 12.4 reusable server-side role guards, followed by role-specific redirects as separately approved. Existing passwordless account activation still requires proof of ownership; do not implement automatic email-based claiming.

## 19. Step 12.4 — Reusable Role-Based Access Control Guards (2026-11-19)

**Status:** Implemented. Role-specific dashboard creation and login redirection are separate milestones; await approval.

- `includes/auth_session.php`: added `authRoleAllowed(string $role, array $allowedRoles)` (strict recognized-role membership) and `authRequireRoles(PDO $pdo, array $allowedRoles, ?string $loginPath = null, ?string $deniedPath = null): array`. The guard revalidates the account against the live database using `authCurrentUser`, returns only an authorized verified user, and stops execution otherwise. API/action callers without redirect paths receive HTTP 401 (sign-in required) or 403 (role not allowed) with generic JSON. Browser callers can provide fixed local login and account destinations; error messages are carried once in the session. Never accept redirect destinations or allowed roles from user input.
- `pages/auth_account.php`: replaced the inline login check with the shared guard allowing all three recognized account roles; existing account details and CSRF-protected logout retained. Added a reusable-style access-denied dialogue for redirects from future privileged pages.
- `pages/login.php`: consumes the one-time guard feedback and sets the existing dialog's `data-auto-open` flag; login success still uses the temporary account destination. `assets/css/login.css` extends the shared modal color variables; login and account use version 6. `assets/js/auth_guard_feedback.js` opens the account feedback dialog if present.
- Future staff pages must call `authRequireRoles($pdo, ['Admin'], fixedLoginPath, fixedAccountPath)` or `['Housekeeping']` as appropriate, and Guest pages use `['Guest']`. Protected actions may call the same guard without redirect paths, before queries/writes. Role guards alone do not replace resource ownership checks, transaction-time validation or CSRF controls; apply those in the relevant future features. No role-specific pages/actions exist yet, and no new dashboards were built in this milestone.
- Tested: all three changed PHP files linted successfully and `git diff --check` passed (normal line-ending notices only). Pure role matrix checks covered exact matching of Admin, Housekeeping, Guest, unknown and empty lists. Anonymous action-mode helper returned HTTP 401 JSON. HTTP anonymous account request redirected to login, displayed the sign-in message in modal markup with auto-open and consumed it once; landing, availability and booking still return HTTP 200 without account requirements. CLI tests used an allowed temporary session directory because the XAMPP session directory was not writable from the CLI sandbox; Apache session behavior passed. Existing user/booking counts remained 9/6. No authenticated dashboard requests were tested until real protected destinations and legitimate sign-in tests are available. No schema, reservation, pricing or payment changes.

## 20. Step 12.5 — Role-Based Dashboard Redirection (2026-11-19)

**Status:** Implemented protected entry pages and role routing only. Await user approval before developing dashboard functionality.

- `includes/auth_session.php`: added `authDashboardPath(string $role): string`, a fixed project-relative destination mapping (`Admin` -> `admin/dashboard.php`, `Housekeeping` -> `housekeeping/dashboard.php`, `Guest` -> `guest/dashboard.php`; unknown roles fall back to guarded `pages/auth_account.php`). Never derive the role or destination from user-submitted data.
- `pages/login.php`: after successful credential verification and session/token regeneration, uses the verified database role for the 303 redirect. `index.php`, `pages/availability.php`, `pages/booking.php`, `pages/booking_created.php` now link signed-in usernames to the corresponding dashboard while keeping anonymous sign-in links and correct relative paths. `pages/auth_account.php` remains the protected account/logout and role-denial feedback page, with a Go to Dashboard link.
- Added `admin/dashboard.php`, `housekeeping/dashboard.php`, `guest/dashboard.php`. Each requires its exact role using `authRequireRoles` before rendering. Unauthenticated requests redirect to sign-in; other roles redirect to the existing account page with access-denied modal feedback. Dashboard pages include `includes/dashboard_layout.php`, a shared presentation-only layout that refuses direct requests without entry-page context. Logout posts the existing session CSRF token to `pages/auth_account.php`. `assets/css/dashboard.css` supplies separate responsive styling consistent with existing branding.
- The dashboard cards explicitly identify planned sections and do not display fabricated counts or expose operational actions. No reservation management, task management, booking history, or payment operations were implemented in this step. The Guest dashboard provides a link back to rooms/facilities; non-registered public reservations remain available.
- Tests: all 17 PHP files passed lint; `git diff --check` passed (normal line-ending notices). All three anonymous dashboard requests redirected to login with modal feedback and automatic opening markup. Role mapping and destination existence checks passed for all three roles and the guarded fallback. Public homepage, availability, booking, CSS and logo URLs returned HTTP 200. Legitimate signed-in role navigation, wrong-role denial, logout and visual verification remain pending a signed-in browser test. No database records or schema were changed.
- Next: confirm the Admin dashboard's layout, overview metrics and reservation management scope before implementing its live functionality. Do not implement the staff/guest features or redesign payments without further approval.

## 21. Step 13.1 — Admin Dashboard Layout and Sidebar (2026-11-19)

**Status:** Implemented the protected Admin workspace layout only. Await approval before Step 13.2 (live overview/statistics).

- `admin/dashboard.php`: preserves the existing exact Admin role guard and replaces the generic placeholder template with the dedicated Admin layout. Added branded sidebar, active Overview navigation, planned Reservations, Rooms & Facilities, Housekeeping, Users and Reports modules, account and public website links, the existing CSRF-protected sign-out route, and a header displaying the authenticated Admin's escaped name. The overview has four honest placeholder cards (Reservations, Pending requests, Rooms ready, Verified revenue); no fake values, operational buttons or dead links. Planned modules are explicitly disabled until implemented.
- `assets/css/admin_dashboard.css`: added an isolated stylesheet using existing hotel colors, desktop sticky sidebar and responsive tablet/mobile navigation, flexible overview/module grids, focus indicators, and overflow-safe profile text. `assets/css/dashboard.css` and shared layout remain unchanged for Guest and Housekeeping.
- Tests: Admin PHP lint and `git diff --check` passed. Anonymous Admin requests still redirect to login with modal feedback. New CSS, existing shared CSS and logo resolve over HTTP 200; homepage and availability continue to load. Signed-in visual verification remains pending because the observed browser was on sign-in; do not bypass authentication to preview protected content. No database records/schema, payment behavior or reservation logic changed.
- Next: define and implement live overview statistics with explicit status filters and verified payment/revenue semantics. No reservation mutations or other modules should be implemented without approval.

## 22. Step 13.2 — Admin Dashboard Live Overview and Statistics (2026-11-19)

**Status:** Implemented read-only overview metrics. Await approval before Step 13.3 (reservation management).

- Added `includes/admin_dashboard_stats.php`: `adminDashboardStats(PDO $pdo)` retrieves four totals in a single SELECT with independent scalar subqueries, avoiding duplicate counts caused by joins between bookings, rooms and payments. Called only after the exact Admin role guard in `admin/dashboard.php`.
- Total reservations: all non-deleted bookings, including historical/cancelled/expired records. Pending approval: non-deleted bookings with active Pending Approval status (does not count Pending Payment holds or cancellation requests). Rooms & facilities ready: non-deleted physical inventory with non-deleted room type, active room status and `ready_For_Checkin=1`; indicates readiness, not date-based availability. Verified revenue: net all-time receipts, summed as `payment.amount * payment_type.balance_Effect` for non-deleted payments belonging to non-deleted bookings with `payment_status.counts_As_Paid=1`. Refunds subtract; Pending/Failed/Cancelled payment records do not count. Historical verified transactions are not excluded simply because a lookup is later disabled. This is net verified receipts, not accrual revenue or booking contract totals.
- `admin/dashboard.php`: replaced the four placeholders with formatted live values and clear descriptions; leaves all unimplemented module navigation unchanged. On query failure values are unavailable, not misleading zeroes, and a generic modal offers refresh. `assets/css/admin_dashboard.css` adds responsive number wrapping and modal styles (v2); `assets/js/admin_dashboard.js` opens the feedback dialog (v1).
- Tests: PHP lint and `git diff --check` passed. The actual helper executed against live MariaDB inside a read-only transaction. Results: 6 total bookings (3 Pending Approval, 3 Pending Payment), 7 ready rooms/facilities, PHP 0.00 verified revenue; independent grouped queries confirmed the counts and that PHP 8,400 pending downpayments are excluded. Admin anonymous access still redirects to sign-in, and CSS/JS and homepage URLs return HTTP 200. Signed-in visual verification remains pending legitimate Admin login. No data, schema, booking logic or payment logic changed.
- Next: read-only reservation list/details with explicit filters and status display, then separately confirm reservation approval/decline and payment/state-transition rules before adding actions. Do not count unverified payments as collected revenue.

## 23. Step 13.3A — Read-only Reservation Management (2026-11-19)

**Status:** Implemented protected reservation listing and details only. Await approval before defining and implementing approval/decline, payment verification, cancellation and check-in/out transitions.

- Added `admin/reservations.php`, protected by the same live exact Admin role guard. Search by booking ID, confirmation code, guest name/email or room code; database-driven booking-status filter and reset; default 10 records per page, newest first. No deleted bookings are included; historical status values remain visible. Invalid/missing detail requests and query errors produce generic modal feedback. The table distinguishes booking status from recorded payment statuses, displays verified net receipts separately from booked amounts, and labels expired holds without changing their stored status. Empty results are a normal table state.
- `includes/admin_reservations.php` supplies parameterized list/count/detail queries; search wildcard characters are treated literally. One row per booking uses independent room/payment aggregates and avoids multiplying payments through room joins. Details include guest contact, stay and hold dates, historical booked room rate/capacity snapshots, selected add-on prices and quantities, payment method/type/status/reference/date and verified net payment sum. Deleted child records omitted; existing historical room/add-on labels retained. Payment records remain read-only.
- `includes/admin_sidebar.php` centralizes the existing Admin sidebar with Overview/Reservations active states. `admin/dashboard.php` links the now-available Reservations module, retains other planned modules and updates shared CSS version to 3. New `assets/css/admin_reservations.css` styles the responsive horizontal-scroll table, filters, pagination and detail dialog; `assets/js/admin_reservations.js` opens modal feedback/details and removes the selected detail parameter on dialog closure. Existing Guest/Housekeeping pages unchanged.
- Tests: all four affected PHP files passed lint and `git diff --check` passed. Read-only live MariaDB tests returned 6 total reservations, 3 Pending Approval, 1 matching Room A and 0 for a nonmatching search; pagination returned 2 rows for a test page size of 2 and clamped out-of-range pages. All six detail queries succeeded without printing personal data, unique booking IDs verified, missing ID returned null. Anonymous page redirected to login; CSS, JS and public homepage returned HTTP 200. Signed-in visual and role navigation verification remain pending legitimate Admin login. No database rows/schema, payment or reservation calculation logic changed.
- Next: confirm reservation approval/decline rules and exact relationship to verification of pending downpayments; implement explicitly approved, transactional and audited actions only after agreement. Preserve the existing payment simulation until revised requirements are approved.

## 24. Step 13.3B — Reservation Approval and Decline (2026-11-19)

**Status:** Implemented after explicit approval of the combined manual verification/approval and decline workflow. No existing reservation was decided during development testing. Await user approval before cancellation/check-in/out or other modules.

- Approved workflow: Pending Approval + Pending downpayment -> Confirmed + Verified after Admin explicitly confirms manual reference/amount verification. Decline -> Declined, pending payments -> Cancelled, required reason in booking log. Any existing verified payment prevents decline until a separate refund workflow is approved. This remains a simulation; no bank/API verification, fund transfer or refund occurs.
- `admin/reservation_decision.php`: dedicated POST-only Admin role-guarded endpoint, CSRF token verification, manual verification checkbox required for approval, safe PRG back to details and modal session feedback. Invalid requests do not commit any update.
- `includes/admin_reservation_actions.php`: transaction locks assigned rooms in ascending ID order before booking/payment rows (consistent with public save), verifies live Pending Approval status, no existing confirmation code, room assignments/readiness, and overlap exclusion including expiry policy. Approval requires nonpast arrival date/current stay, single selected pending Downpayment, submitted reference, exact centavo comparison of candidate plus existing verified downpayments less refunds against required positive deposit. Review fingerprint binds displayed payment ID, amount, reference and method to session token. Admin identity is refreshed inside transaction. Updates only the relevant locked pending payment(s) and booking, resolves target IDs by active lookup names/flags, generates a random `BV-` confirmation code backed by unique DB constraint, clears obsolete hold, records Admin actor/action/reason in `booking_log`, commits atomically. Verified payment records Admin processor and existing payment date or verification time when absent; decline preserves payment date. No pricing changes.
- `includes/admin_reservations.php` includes payment flags and live stay eligibility for details. `admin/reservations.php` adds the approve and reason-required decline forms within existing details modal, and uses generic modal feedback. Past arrival dates cannot be approved; decline remains possible for an unverified pending booking. `assets/css/admin_reservations.css` and `assets/js/admin_reservations.js` updated (v2) for decision controls and reopening details after feedback.
- Tests: all four changed PHP files linted; `git diff --check` passed. Synthetic centavo conversion and review fingerprint checks passed. Live read-only details verified all three current pending-approval reservations have one sufficient submitted pending downpayment, but none has nonpast arrival dates. Anonymous reservation/action endpoints redirected to login; updated CSS/JS returned HTTP 200. Existing counts remain 6 bookings, 3 payments and 9 booking logs. Authenticated end-to-end approve/decline write-flow testing requires a fresh future-date test reservation and legitimate Admin login; no test data was mutated. Keep existing records intact.
- Next: test with a fresh disposable future reservation, then agree on cancellation and actual check-in/check-out rules. Refunds and payment redesign remain deferred.

## 25. Registration feedback fix (2026-11-20)

- `pages/register.php`: fixed missing `data-auto-open` attribute on the registration feedback dialog, matching the existing login dialog and shared `login.js`. Server-side registration errors now request automatic modal opening. Existing-email conflicts use consistent guidance to sign in or contact the hotel if the guest previously booked without an account.
- Existing passwordless guest records remain protected: registration cannot claim their email, create a duplicate `users` row or assign a password without proof of ownership. Account activation requires a separately approved verification process (e.g. email verification or admin-assisted verification); no password or ownership data changed.
- Verification: PHP lint, `git diff --check`, live registration GET and invalid-form POST passed; error response contained `data-auto-open="true"` and expected validation text. Read-only count found six passwordless Guest records. No registration record was created by the test. The reservation roadmap remains at Step 13.3B; Step 13.3C and activation workflow await approval.

## 26. Step 12.6A — Account Token Schema (2026-11-20)

**Status:** Completed the database-only foundation after user approval of the hybrid offline/online approach. Await approval before Step 12.6B (admin-assisted issuance).

- `migrations/2026-11-20_account_token.sql`: additive `account_token` table with unique token hash, user and optional issuing Admin foreign keys, purpose (`Activation` or `Password Reset`), delivery method (`Email` or `Admin Assisted`), creation, expiry, used and revoked timestamps. Revocation distinguishes invalidated tokens from successfully consumed tokens. Application will enforce one active token per user/purpose transactionally and check issuer role/status; constraints enforce expiry ordering and delivery/issuer consistency. Raw codes must never be stored in the table or logged.
- Pre-migration database backup created outside the web root in the Windows temporary directory (`bella_vista_pre_12_6A_20261120_202108.sql`); keep private. Live schema verified: 26 tables, zero token records, 9 users and 6 bookings. Existing records and passwords unchanged. Migration applied once; other devices need this migration only if using a database export predating it.
- Next substeps: 12.6B guarded Admin issuance only after independent identity verification and private code handover; 12.6C guest activation by expiring single-use token, keeping the existing user ID and bookings. Password reset is a separate future purpose; no token issuance or password updates exist yet. PHPMailer will later reuse the token mechanism for email delivery and notifications. Offline recovery must stay independently verified; reCAPTCHA, remaining security hardening, UI/interactivity and WCAG review are planned after main functionality, as requested. No automatic bypass of online security controls.

## 27. Step 12.6B — Admin-Assisted Activation Issuance (2026-11-20)

**Status:** Implemented issuer only. Do not issue real guest codes until Step 12.6C redemption is implemented. Await user approval before that step.

- `includes/admin_guest_activation.php`: lists only active, non-deleted, passwordless Guest records. Exact email search and booking count, limit 50. Issuance locks the target user, revalidates Admin identity, revokes previous unused activation tokens, generates a random 256-bit code, stores only its SHA-256 hash and issuer, and commits a 30-minute expiration atomically. Password-reset tokens are untouched. No account/password change is made.
- `admin/guest_activation.php`: protected Admin screen, CSRF-protected POST search (avoid email in URLs) and issuance. Admin must attest to independent identity verification before generating/replacing a code; name, email or booking number alone is insufficient. Share only privately with the verified guest. The raw code is presented in a one-time session-backed modal after redirect, with no-store headers; never emitted in URLs or logs. `assets/js/admin_guest_activation.js` clears the code on modal close; `assets/css/admin_guest_activation.css` preserves existing dashboard/table design. Sidebar links the new module.
- Tests: all three modified PHP files passed lint and `git diff --check`. Live read-only query found six eligible guests, exact-email/no-match searches passed (no guest information printed), zero token records, anonymous page redirects to login, CSS and JS return HTTP 200. Authenticated issuance and end-to-end redemption tests remain pending legitimate Admin testing after Step 12.6C. No actual token was issued, no account/person/reservation records modified, and no schema change was required.
- Next: implement secure guest activation redemption form with token expiry and purpose checks, atomic consumption, password validation/hash and existing user ID preserved. PHPMailer and password-reset flows remain future milestones.

## 28. Step 12.6C — Guest Activation Redemption (2026-11-20)

**Status:** Implemented; legitimate valid-code end-to-end testing remains Step 12.6D, awaiting approval.

- `pages/activate.php` reuses the existing authentication design, modal feedback and password visibility. Anonymous guest enters existing booking email, Admin-issued 64-character code, new password and confirmation. POST-only processing, CSRF, no-store/referrer headers, matching registration password policy, generic invalid account/code feedback and basic session-based attempt limit (5 valid-format attempts per 15 minutes; stronger shared limiting remains future hardening). Code/password never echoed back or logged; no automatic login. Signed-in users must sign out first.
- `includes/guest_activation.php`: locks the active passwordless Guest row, then validates matching SHA-256 hash, correct user, purpose `Activation`, `Admin Assisted` delivery, expiration, unused/unrevoked state and active issuer Admin. Password is hashed and assigned only to the existing passwordless user. Token consumption and revocation of other outstanding activation tokens commit in the same transaction, with rollback on error. No duplicate user or ownership reassignment, no password reset, no email issuance, no booking changes.
- `pages/login.php` and `pages/register.php` link to activation; `assets/js/login.js` supports `activateForm` (v3) while retaining existing form validation. Successful activation redirects to login with existing success modal.
- Tests: four modified PHP files passed lint and `git diff --check`; live activation GET, synthetic malformed-code and invalid-account/code POST produced expected modal feedback. Login/register links and JS URL return HTTP 200. Current live counts observed: 10 users, 7 bookings, 1 token (existing data retained; no valid-code POST or record writes performed by these tests). The successful activation/reuse/expiry write path still requires deliberate testing with a disposable guest and a privately issued code. No database migration in this step.
- Next: Step 12.6D test a disposable future guest reservation and legitimate Admin issuance/guest redemption, including one-time use and retained booking history. Do not expose or redeem existing real codes without authorization. PHPMailer, password reset, reCAPTCHA and final UI/accessibility milestones remain deferred.

## 29. Step 12.6D — Activation Verification (2026-11-20)

**Status:** Positive database evidence for an actual completed activation; user-side login/reuse UI checks remain pending confirmation. No new test record or credential change was made by Codex.

- The live database now contains two Admin-assisted Activation tokens, one used and one revoked, with zero active tokens. Aggregate read-only verification confirms the consumed token belongs to a Guest with a password hash, was consumed before expiry, has at least one retained associated booking, and an older unused code for the same guest was revoked. No token is simultaneously consumed and revoked. Existing counts remain 10 users and 7 bookings.
- No email, code, hash, or password was retrieved or printed. Do not generate or consume another token merely to repeat a successful data check. Confirm with the user that the intended disposable guest can sign in under the Guest role, and that the used or replaced code cannot be redeemed again. Successful/expired-code HTTP redemption and dashboard history visual checks remain unverified independently. No schema or application functionality changed during this verification.
- After user confirmation, resume Step 13.3C reservation lifecycle rules; retain future PHPMailer, guest recovery and security/accessibility milestones.

## 30. Login and registration shared-variable isolation fix (2026-11-20)

- User reported successful activation followed by invalid-credentials login even with the intended password. Inspection confirmed a PHP include-scope collision: `includes/database.php` overwrote the submitted form variable before credential verification; the same issue could affect regular registration. Activation loaded the database before reading form inputs, which explains why activation succeeded.
- `includes/database.php` now creates its PDO connection inside a static closure that returns `$pdo`. Connection configuration remains private to that scope; caller form values are unchanged. No database credentials, existing hashes or other records were changed or exposed.
- Regression checks passed: PHP lint, `git diff --check`, synthetic in-memory password preserved across database inclusion, password verification and registration hashing with the preserved value, PDO read-only connection check, and HTTP 200 for homepage/login/registration/activation with no displayed PHP warnings. Read-only diagnostics confirmed both consumed activation records belong to active Guests with recognized bcrypt hashes. Actual signed-in verification remains the user's private test.
- Existing accounts created through the older affected self-registration flow may need individually authorized password recovery; do not rewrite their password hashes blindly, reveal database credentials, or permit bypassing identity verification. Guest accounts activated through the correct flow should not need new codes; retry using the password privately chosen during activation. Preserve the roadmap at activation testing until confirmed.

## 31. Step 12.7A — Admin-Assisted Guest Password Reset Issuance (2026-11-20)

**Status:** Implemented at user's request to handle forgot password before Step 13.3C. Issuance only; do not issue real reset codes until redemption and session revocation are available.

- `includes/admin_guest_activation.php`: generalized the existing issuer/list to `adminGuestTokenCandidates` and `adminIssueGuestToken`, with a strictly enumerated purpose and separate eligibility. Activation requires passwordless active Guest; Password Reset requires an existing nonempty password hash. Both require live active Admin issuance and explicit independent identity verification. Reissuance revokes only unused tokens of the same purpose. Code generation, hash-only database storage and 30-minute expiry remain unchanged. No recovery for privileged Admin/Housekeeping accounts in this Guest workflow; define separate controlled policy if needed.
- `admin/guest_activation.php?mode=reset` reuses the protected page, private one-time display, CSRF POST and modal feedback, while keeping separate selection, labels, flash key and purpose. `includes/admin_sidebar.php` adds Guest password recovery link. Existing activation mode remains available. No new schema or password changes.
- Tests: three changed PHP files linted; `git diff --check` passed. Live read-only results show four eligible passwordless Guests and five eligible reset Guests, no overlap, zero reset tokens, successful exact-email filter and invalid-purpose rejection. Both Admin page modes redirect anonymous requests to login. No actual reset code was issued and no credentials were changed.
- Next: Step 12.7B add public Forgot Password / reset redemption with exact purpose, identity, expiry and single-use checks; require new password confirmation, atomic update, invalidate all prior authenticated sessions for the account (e.g. server-side fingerprint of stored hash; never expose raw hash), and revoke outstanding reset tokens. Step 12.7C test legitimate disposable recovery. Future PHPMailer email delivery and reCAPTCHA remain deferred; no offline bypass of identity verification.

## 32. Step 12.7B - Guest Password Reset Redemption and Session Revocation (2026-11-20)

- `pages/reset_password.php` selects reset mode server-side and reuses `pages/activate.php` for the shared responsive form, modal feedback, CSRF validation, confirmation, and separate per-session attempt tracking. `pages/login.php` links to the reset page; `assets/js/login.js` removes the temporary unavailable message.
- `includes/guest_activation.php` accepts strictly enumerated token purposes, requires a password-bearing active Guest for reset, and validates exact purpose, user, active issuer, expiry, and unused/unrevoked status. Password hashing, token consumption and revocation of outstanding same-purpose tokens remain atomic. Existing activation retains passwordless-only eligibility.
- `pages/login.php` stores a server-side fingerprint of the verified stored password hash; `includes/auth_session.php` checks it on each guarded request. Prior sessions without a fingerprint must sign in again once; sessions with an outdated fingerprint lose authenticated access on their next guarded request. No raw password hash is placed in session data or rendered to the browser.
- Tests: all five changed PHP files passed PHP lint; login, activation and reset pages returned HTTP 200; synthetic malformed-code POST displayed the expected modal. No real passwords or reset tokens were changed during these tests. No schema migration required. Step 12.7C remains pending: legitimate disposable Guest reset, old/new login, prior-session revocation and one-use token verification. Await approval before proceeding.

## 33. Step 12.7C - User verification and next milestone (2026-11-20)

- User confirms password recovery works and requests continuation. Individual expiration, token reuse, prior-session invalidation and booking retention tests were not separately reported; retain these as regression checks rather than claim independent verification.
- Resume Step 13.3C. Confirm actual arrival/departure, cancellation/refund boundaries and room readiness transitions before implementing operational actions. Existing payment simulation remains unchanged pending separate approval.
