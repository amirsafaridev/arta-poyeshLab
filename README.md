## Arta PoyeshLab – WooCommerce Lab Portal & Patient Dashboard

Arta PoyeshLab is a **WordPress + WooCommerce extension** that turns a standard store into a **patient-facing lab portal** for medical laboratories.  
It provides OTP-based authentication, appointment scheduling, lab test result delivery, and rich order metadata tailored for diagnostic workflows, all on top of WooCommerce orders and products.

This plugin was built as a **real-world production solution** for a medical lab in Iran, with full support for **Jalali dates**, **Persian UI**, and a custom **/lab-portal** single-page experience.

---

### Features

- **Patient portal at `/lab-portal`**
  - Dedicated lab dashboard that routes all requests through a custom layout template.
  - Tailwind-powered, mobile-friendly UI for login, registration, profile and orders.
  - Switches between the **auth screen** and **dashboard** depending on login state.

- **OTP-based login & registration (SMS)**
  - Passwordless authentication using **one-time passwords (OTP)** sent via SMS.
  - Separate AJAX flows for:
    - **Login** (`apl_send_login_otp`, `apl_verify_login_otp`).
    - **Registration** (`apl_send_register_otp`, `apl_verify_register_otp`).
  - OTP generation, storage, expiration and attempt limits are handled server-side.
  - Security hardening via nonces and detailed logging of login and OTP activity.

- **National Payamak SMS gateway integration**
  - Pluggable SMS layer (`APL_SMS_Handler`) talking to **rest.payamak-panel.com**.
  - Centralized settings for username, password and sender number.
  - Reusable helpers for:
    - Generating and persisting OTP codes.
    - Verifying OTPs with expiry & attempt limits.
    - Sending arbitrary SMS messages when needed.

- **Jalali date support**
  - Custom `APL_Gregorian_Jalali` helper for bidirectional conversion between **Gregorian** and **Jalali**.
  - All user-facing dates in the portal (orders, appointments, invoices) are rendered in Jalali.
  - Back‑office logic (DB queries, WooCommerce internals) continue to use Gregorian dates.

- **Appointment management module**
  - Custom DB table (`wp_apl_appointments`) managed by `APL_Appointments`.
  - Admin UI for:
    - Defining **time slots** in bulk across date ranges.
    - Filtering by status (available, booked, completed, cancelled).
    - Filtering by **service delivery method** (home sampling, lab visit, sample shipping) and Jalali month.
    - Inline edit and bulk delete for available slots.
  - Persian datepicker integration for a localized scheduling experience.
  - Front-end AJAX to fetch **available hours per day** and service method.
  - Orders created through the portal automatically **reserve** the chosen appointment slot.

- **Lab test result workflow**
  - Dedicated custom post type: `apl_lab_test_result`.
  - Admin interface for:
    - Selecting the **related WooCommerce order** with a searchable dropdown.
    - Uploading **single lab result file** (PDF/image/any document) via drag‑and‑drop.
    - Managing storage in a dedicated uploads subdirectory with directory hardening.
  - Dashboard integration:
    - Patients see a list of their lab results mapped back from orders.
    - Unseen results are tracked and can be counted or marked as seen.
    - Files are downloadable only for the owning customer.

- **PDF invoice generation (in Persian, with Jalali date)**
  - `APL_PDF_Generator` builds a **printable Farsi invoice** for WooCommerce orders.
  - Order date converted to Jalali and displayed in the header.
  - Line items, totals, and optional **insurance fee** are included.
  - Supports an optional **company stamp** image (configured from admin settings).
  - Secure access via a signed URL with a nonce; only the logged-in order owner can view their invoice.

- **Extended product metadata for lab workflows**
  - Per-product fields for:
    - **Product type**: `lab_test` or `lab_package`.
    - **Service delivery method**: home sampling / lab visit / sample shipping.
  - For lab packages:
    - Select included test products with **Select2**‑based multi-select.
    - Dynamic, human‑readable summary of included tests in the product editor.
  - Helper (`apl_get_lab_package_products`) exposes package data (pricing, delivery method, included tests) for use in the front-end portal.

- **Rich order metadata (patient, appointment & insurance)**
  - WooCommerce orders are extended with structured fields:
    - Request type: upload prescription / electronic prescription / test packages.
    - Delivery method & location (city, full address).
    - Patient identity: first name, last name, national ID, mobile.
    - Appointment date & time (stored in DB, rendered in Jalali).
    - Basic & supplementary insurance info + tracking code.
    - Applied discount code and electronic prescription data (national ID, doctor name).
    - Prescription files uploaded at order time.
  - All fields are visible and editable in the **WooCommerce admin** on the order edit screen.
  - Dynamic show/hide behavior for fields based on request type and delivery method.

- **Patient profile & media**
  - Profile endpoint to update:
    - First / last name.
    - National ID.
    - Email (if not already set).
    - Mobile number (if not locked).
    - Address.
  - Validation with meaningful, field-specific error messages.
  - Profile picture upload:
    - Stores user avatars in `uploads/profile-pictures`.
    - Resizes to 150×150 and cleans up the previous avatar file on update.

- **Dashboard: orders, invoices, activities**
  - Patient dashboard provides:
    - Order list with Jalali dates, statuses and item summaries.
    - Deep link to **invoice PDF** via secure URLs.
    - Aggregated **recent activities** (order created, results ready, etc.).
  - All dashboard data is served via AJAX handlers with nonce validation and per-user access control.

- **Admin settings & observability**
  - Central `APL_Admin_Settings` class exposes:
    - **SMS settings** page (credentials, test SMS tool).
    - **Login page branding** (logo, title, subtitle, terms text).
    - **Order success message** with placeholders (e.g. `{order_number}`).
    - **Company stamp** upload used in invoices.
  - System logging via `APL_Logger`:
    - Centralized log entries for SMS events, login attempts, registration, OTP validations and profile updates.
    - Lightweight log viewer in the admin (with clear‑logs action).
  - Background cleanup:
    - `APL_Cron` schedules hourly jobs to clean expired OTP records.

---

### Architecture Overview

- **Bootstrap & main loader**
  - `arta-poyeshLab.php` defines plugin constants and includes `include/apl-main.php`.
  - `APL_Main` checks for WooCommerce, loads all function and class files in a fixed order to avoid dependency issues, and registers asset loading for both frontend and admin.

- **Core modules**
  - `APL_Auth` – helpers around current user’s mobile, national ID and login state.
  - `APL_Ajax_Handlers` – all front-end AJAX endpoints for auth, profile, orders, invoices, discounts, appointments and lab results.
  - `APL_Appointments` – custom table, admin UI and AJAX for appointment management.
  - `APL_Lab_Test_Results` – custom post type + file storage for lab results.
  - `APL_PDF_Generator` – invoice HTML/PDF factory with Jalali dates and stamp.
  - `APL_Product_Fields` – product-level metadata and admin UI for lab products.
  - `APL_Order_Meta` – WooCommerce order meta fields & UI.
  - `APL_SMS_Handler` – integration with National Payamak for OTP and SMS.
  - `APL_Logger` – centralized system logger with simple stats.
  - `APL_Cron` – schedules and clears OTP‑related data.
  - `APL_My_Account` – rewrites `/lab-portal` requests to a custom template (`include/template/layout.php`).

- **Presentation layer**
  - `include/template/layout.php` – entry point that renders the full-screen lab portal layout and conditionally includes:
    - `include/template/auth.php` (unauthenticated).
    - `include/template/dashbord.php` (authenticated).
  - `assets/css/style.css` and `assets/js/script.js` are enqueued both globally and inside the layout for a cohesive SPA-like experience.

---

### Requirements

- **WordPress**: 5.8+ (tested on recent 5.x / 6.x)
- **PHP**: 7.4+ (namespaces and modern syntax are used)
- **WooCommerce**: 5.0+ (orders, products and coupons are assumed available)
- **Database**: MySQL / MariaDB (WordPress standard)
- **SMS provider**: Valid **National Payamak** account with REST credentials

---

### Installation

- **1. Copy the plugin into your WordPress installation**
  - Place this folder in `wp-content/plugins/arta-poyeshLab`.

- **2. Activate the plugin**
  - Go to `Plugins → Installed Plugins` and activate **Arta PoyeshLab**.

- **3. Ensure WooCommerce is active**
  - If WooCommerce is not installed or not active, the plugin will show an admin notice and gracefully disable its main functionality.

- **4. (Optional but recommended) Create a “Lab Portal” page**
  - Create a new page with slug `lab-portal` to provide a clean entry URL:
    - URL will typically be `https://yourdomain.com/lab-portal`.
  - The plugin internally intercepts that path and renders its own layout template.

---

### Configuration

- **SMS settings**
  - Go to the plugin’s admin menu (`آزمایشگاه پویش → تنظیمات پیامک`).
  - Set:
    - SMS **username**.
    - SMS **password**.
    - **From number** (sender line).
  - Use the built‑in **“Test SMS”** box to verify connectivity to National Payamak.

- **Branding & login screen**
  - Under `آزمایشگاه پویش → تنظیمات اصلی` you can configure:
    - Login **logo** (also used as favicon in the portal).
    - Main title and subtitle.
    - Terms & privacy text rendered beside the consent checkbox.
    - Order success message, including placeholders like `{order_number}`.
    - Optional **company stamp** image; this appears in the invoice footer.

- **Appointments**
  - Use the `نوبت‌ها` submenu to:
    - Define date ranges in Jalali using the Persian datepicker.
    - Define one or more time slots per day (hours and quarter-hour steps).
    - Choose the **service delivery method** a slot belongs to.
  - Appointments are stored in `wp_apl_appointments` and used when patients create orders via the portal.

- **Products: tests & packages**
  - Edit your WooCommerce products:
    - Set **Product type** to “Lab Test” or “Lab Package”.
    - Choose the **service delivery method** for that product.
    - For lab packages, select which test products are included.
  - The front-end portal can then:
    - Present curated packages for patients.
    - Show which tests are bundled inside each package.

- **Order meta in WooCommerce admin**
  - When editing an order, you will see additional sections for:
    - Request type and delivery method.
    - Patient identity & contact information.
    - Appointment date & time, address and city (for home sampling).
    - Insurance fields and discount code.
    - Any uploaded prescription files (linked as downloadable URLs).

- **Lab test results**
  - In the admin menu, use the `جواب آزمایش‌ها` custom post type:
    - Create a new lab test result.
    - Attach it to an existing WooCommerce order via the advanced searchable dropdown.
    - Upload a single result file (PDF or image) for the patient.
  - When a result is published and has a file:
    - The owning patient sees it in the portal’s **Lab Results** section.
    - Unseen results are counted and can be acknowledged.

---

### Patient Experience

- **1. Access the portal**
  - Visit `https://yourdomain.com/lab-portal`.

- **2. Authenticate**
  - New patients:
    - Fill in first name, last name, national ID and mobile number.
    - Accept the terms and request an OTP via SMS.
    - Enter the 6-digit code to complete registration and login.
  - Existing patients:
    - Enter mobile number.
    - Receive OTP and verify to log in.

- **3. Use the dashboard**
  - Review profile and update identity/address fields.
  - Browse relevant lab packages and services.
  - Book appointments (date/time) according to available slots.
  - Create orders with optional insurance data and discount codes.
  - View order status, invoice PDFs, and available lab test results.

---

### Admin Experience

- **Lab configuration**
  - Design products and packages that reflect your real‑world lab services.
  - Map each package to its underlying test products to keep reporting consistent.

- **Operations & support**
  - Define and maintain appointment availability per delivery method.
  - Attach lab test results to orders after processing samples.
  - Inspect logs when troubleshooting OTP delivery and login/registration issues.
  - Optionally add **insurance fee** as a WooCommerce fee item per order from the admin interface.

---

### Security Considerations

- **Authentication & authorization**
  - All AJAX endpoints are protected using **WordPress nonces** and capability checks where appropriate.
  - Sensitive endpoints (orders, invoices, test results) always validate the **current user’s ownership** of the resource.

- **OTP handling**
  - Codes are:
    - Short‑lived (2 minute expiry).
    - Limited to a small number of verification attempts.
    - Cleaned up regularly via a scheduled cron task.

- **File uploads**
  - Lab results are stored in a dedicated subdirectory under `uploads` and shielded from directory listing.
  - Profile pictures are resized to reasonable dimensions and old files are cleaned up.

- **Data integrity**
  - Appointment slots are updated transactionally when orders are created, to avoid double-booking.
  - All user input is sanitized before being persisted to the database.

---

### Development Notes

- **Code style**
  - Namespaced classes under `APL\Classes`.
  - Separation between:
    - Business logic (appointments, lab results, SMS, orders).
    - Integration layers (WooCommerce, National Payamak).
    - Presentation (Tailwind templates + custom CSS/JS).

- **Extensibility**
  - Most behavior is hooked into WordPress and WooCommerce via standard actions/filters.
  - The SMS handler and OTP logic can be swapped or extended for other gateways.
  - The portal front-end is built as a single-page layout, making it straightforward to re-skin while reusing back-end endpoints.

---

### License

This plugin is released under the **GPL v2 or later**, in line with the WordPress and WooCommerce ecosystems.  
See the plugin header in `arta-poyeshLab.php` for the canonical license declaration.


