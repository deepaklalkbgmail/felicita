# Aaravam 2026 — Onam Sadhya Ticketing System
## Complete User Guide

---

## Table of Contents

1. [Overview](#overview)
2. [Getting Started — Installation](#installation)
3. [Login & Roles](#login)
4. [Admin: First-Time Setup](#first-time-setup)
5. [Admin: Dashboard](#dashboard)
6. [Admin: Managing Agents](#agents)
7. [Admin: Tickets & Bookings](#tickets)
8. [Admin: Reports & Export](#reports)
9. [Admin: Settings](#settings)
10. [Agent: Door-to-Door Booking](#agent-booking)
11. [Validator: Dining Hall Check-in](#validator)
12. [Secret Code & QR Code](#codes)
13. [Fraud & Duplicate Handling](#fraud)
14. [Troubleshooting](#troubleshooting)

---

## 1. Overview {#overview}

**Aaravam 2026** is a mobile-first web application for managing Sadhya (feast) ticketing and dining hall validation at the Onam cultural event. It supports three user roles:

| Role | Purpose | Access |
|---|---|---|
| **Admin** | Full control — dashboard, reports, settings | Username + Password |
| **Agent / Volunteer** | Door-to-door booking collection | 4–6 digit PIN |
| **Validator** | Dining hall check-in scanning | PIN (set by Admin) |

**Core flow:**
```
Agent books family → System generates QR + Secret Code
         ↓
Family arrives at venue → Validator scans QR or types code
         ↓
Validator selects relation → Plate marked as served
```

---

## 2. Getting Started — Installation {#installation}

### Step 1 — Upload files
Upload the entire `aaravam2026/` folder to your cPanel hosting under `public_html/`:
```
public_html/
└── aaravam2026/    ← upload here
```

### Step 2 — Create the database
1. Log in to **cPanel → MySQL Databases**
2. Create a new database (e.g. `aaravam2026`)
3. Create a database user with a strong password
4. Add the user to the database with **All Privileges**

### Step 3 — Configure the app
Open `config/database.php` in a text editor and fill in your details:

```php
define('DB_HOST', 'localhost');           // usually localhost
define('DB_USER', 'your_db_username');    // MySQL username
define('DB_PASS', 'your_db_password');    // MySQL password
define('DB_NAME', 'aaravam2026');         // database name

define('APP_URL', 'https://yourdomain.com/aaravam2026');  // ← no trailing slash
```

### Step 4 — Run the installer
Open a browser and navigate to:
```
https://yourdomain.com/aaravam2026/config/install.php
```
You will see a green success message. The installer:
- Creates all database tables
- Seeds the default admin account: **admin / Admin@1234**
- Seeds default settings (price ₹200/plate, validator PIN 999999)

### Step 5 — Delete the installer ⚠
**Immediately delete** `config/install.php` from your server after successful installation. Leaving it accessible is a security risk.

### Step 6 — Log in and change your password
Go to `https://yourdomain.com/aaravam2026/login.php`, log in as admin, then go to **Settings → Change Admin Password**.

---

## 3. Login & Roles {#login}

The login page (`/login.php`) has three tabs:

### Admin Tab
- Enter **Username** and **Password**
- Default credentials: `admin` / `Admin@1234` *(change immediately)*
- Redirects to the Admin Dashboard

### Validator Tab
- Enter the **Validator PIN** (set in Admin → Settings)
- Default PIN: `999999`
- Redirects to the Dining Hall Validator screen
- Give this PIN only to your check-in team at the venue

### Agent Tab
- Enter the agent's **personal 4–6 digit PIN**
- PINs are created per-agent by the Admin (see [Managing Agents](#agents))
- Redirects to the Booking page

### Logging Out
Click the **Logout** button in the top-right navigation bar.

---

## 4. Admin: First-Time Setup {#first-time-setup}

Complete these steps before the event:

### 1. Change Admin Password
**Admin → Settings → Change Admin Password**
- Enter current password (`Admin@1234`)
- Set a strong new password (min. 8 characters)

### 2. Configure Event Details
**Admin → Settings → Event & Pricing**

| Field | Example |
|---|---|
| Event Name | `Aaravam 2026 Onam Sadhya` |
| Event Date | `2026-09-12` |
| Event Venue | `Community Hall, Thrissur` |
| Price Per Plate | `250` |
| Validator PIN | `482910` *(choose a unique PIN)* |

Click **Save Settings**.

> ⚠ Changing the price per plate only affects **new** bookings. Existing bookings retain their original price.

### 3. Add Agents
**Admin → Agents → Add Agent**
- Add each volunteer's name and assign them a unique 4–6 digit PIN
- Share each agent's PIN with them personally
- See [Managing Agents](#agents) for details

---

## 5. Admin: Dashboard {#dashboard}

The dashboard is the first screen after admin login. It shows:

### Summary Statistics
| Card | What it shows |
|---|---|
| 🎟 Total Bookings | Number of booking records |
| 🍽 Total Plates Sold | Sum of all headcounts |
| ✅ Plates Served | Plates consumed at the venue |
| 🔵 Unused Plates | Plates sold but not yet served |
| 💰 Total Revenue | Sum of all booking amounts |
| 👤 Active Agents | Number of active volunteers |

### Consumption Progress Bar
A visual bar showing what percentage of sold plates have been served. Turns red if approaching 100%.

### Quick Links
Buttons to jump directly to New Booking, VIP Ticket, Reports, and Validator view.

### Recent Bookings Table
The 10 most recent bookings with Order ID, house name, served/total count, amount, agent, and **secret code** visible.

---

## 6. Admin: Managing Agents {#agents}

**Admin → Agents**

### Adding an Agent
1. Click the **Add Agent** tab
2. Enter the volunteer's full name
3. Enter a 4–6 digit numeric PIN (must be unique)
4. Click **Add Agent**

The agent can now log in at `/login.php` (Agent tab) using their PIN to start taking bookings.

### Agent Summary Table
The **All Agents** tab shows each agent with:
- Their PIN *(visible to admin only)*
- Number of bookings they have collected
- Total amount collected
- Active/Inactive status

### Activating / Deactivating
Click **Deactivate** to block an agent from logging in (e.g. if they are no longer volunteering). Click **Activate** to re-enable them.

> Deactivating an agent does **not** delete their booking records.

---

## 7. Admin: Tickets & Bookings {#tickets}

**Admin → Tickets**

### Viewing All Bookings
The **All Bookings** tab lists every booking. Use the filter bar to narrow results:

| Filter | Use |
|---|---|
| Search | Order ID, house name, owner name, or secret code |
| Agent | Show only one agent's bookings |
| Type | `Agent` (door-to-door) or `Ad-hoc/VIP` |

### Generating a VIP / Ad-hoc Ticket
1. Click the **New VIP / Ad-hoc** tab
2. Fill in the guest/house name, contact number, and plate count
3. Click **Generate Ad-hoc Ticket**
4. A ticket with a QR code and secret code is displayed — print or share it

Ad-hoc tickets are recorded with type `adhoc` and attributed to Admin (no agent).

### Secret Code Visibility
Every booking's secret code is visible in the table. If a family has lost their code, look it up here and share it with them.

---

## 8. Admin: Reports & Export {#reports}

**Admin → Reports**

### Summary Bar
At the top of the reports page, filtered totals update dynamically:
- Bookings, Plates Sold, Plates Served, Unused Plates, Revenue

### Filter Options

| Filter | Options |
|---|---|
| Search | Free text across order, name, contact, code |
| Agent | Filter by specific agent |
| Booking Type | Agent / Ad-hoc |
| Status | **Unused** (0 served), **Partial** (some served), **Fully Consumed** |
| Date From / To | Filter by booking creation date |

### Agent-wise Summary Table
Shows totals per agent: bookings, plates sold, plates served, revenue collected. Useful for accountability.

### Detailed Bookings Table
Full data grid with all fields including secret codes and a colour-coded **Remaining** badge:
- 🟢 Green = all plates unused
- 🟡 Yellow = partially consumed
- 🔴 Red = fully consumed

### CSV Export
Click **📥 CSV** (visible in the filter bar) to download the current filtered results as a `.csv` file compatible with Excel and Google Sheets.

The CSV includes: Order ID, House Name, Owner Name, Contact, Plates, Served, Remaining, Amount, Agent, Type, Secret Code, Date.

### Check-in Log
The bottom section shows the 100 most recent plate servings with timestamp, order ID, house name, and relation.

---

## 9. Admin: Settings {#settings}

**Admin → Settings**

### Event & Pricing Settings

| Setting | Description |
|---|---|
| Event Name | Shown on tickets and the app header |
| Event Date | Displayed on the booking page |
| Event Venue | Informational |
| Price Per Plate | Auto-calculates booking totals (new bookings only) |
| Validator PIN | PIN for dining hall check-in staff |

### Change Admin Password
Enter your current password, then set and confirm a new password (minimum 8 characters).

### Access URLs (Reference)
The settings page lists the direct URLs for each role:
- **Agent Booking**: `/agent/index.php`
- **Validator**: `/validator/index.php`
- **Admin**: `/admin/index.php`

---

## 10. Agent: Door-to-Door Booking {#agent-booking}

Agents visit households and take Sadhya orders on their phone.

### Logging In
1. Go to `https://yourdomain.com/aaravam2026/login.php`
2. Tap the **Agent** tab
3. Enter your personal PIN → tap **Start Booking**

### Booking a Family
Fill in the form:

| Field | Notes |
|---|---|
| House Name | Name of the house/property (e.g. "Kaveri Nivas") |
| Owner Name | Name of the head of household |
| Contact Number | Mobile number (7–15 digits) |
| No. of Plates | How many Sadhya plates they are buying |
| Notes | Optional (e.g. "paying later", "two children included") |

**Total Amount** is calculated automatically as you type the plate count. It uses the current price per plate configured by the admin.

### Confirming the Booking
Click **Confirm & Generate Ticket**. The system will:
1. Create a unique Order ID (e.g. `ARV3F4A2B1`)
2. Generate a unique **6-character Secret Code** (e.g. `A7R3ZK`)
3. Generate a **QR Code** that encodes the secret code
4. Display the full ticket

### Sharing the Ticket
- **Print**: Opens the browser print dialog — prints just the ticket
- **Share** (📤): Opens the device's native share sheet (WhatsApp, SMS, etc.) with the ticket details as text
  - On desktop: copies the text to clipboard
- **New Booking** (➕): Clears the form for the next household

> The family should save the secret code or screenshot the ticket. They will present it at the Sadhya venue.

---

## 11. Validator: Dining Hall Check-in {#validator}

Validators are stationed at the dining hall entrance. They scan or verify each family's ticket.

### Logging In
1. Go to `https://yourdomain.com/aaravam2026/login.php`
2. Tap the **Validator** tab
3. Enter the Validator PIN (given by admin) → tap **Enter Validator Mode**

### Scanning a QR Code
1. Tap **📷 Scan QR** tab
2. Tap **▶ Start Camera** — your phone camera will open
3. Point the camera at the family's QR code
4. The ticket details will appear automatically

### Manual Code Entry
1. Tap **⌨️ Manual Entry** tab
2. Type the 6-character secret code (or order ID) — it auto-uppercases
3. Tap **🔍 Look Up**

### The Booking Result Card
When a code is found, a card displays:

- 🏠 House Name and Owner Name
- Plates remaining vs total (with a progress bar)
- Visual plate dots: 🟢 available / 🍽 already served
- History of who has been served so far

### Serving a Plate
1. In the **Mark Plate as Served** section, select the **Relation to House Owner** from the dropdown:
   - Self, Spouse, Son, Daughter, Father, Mother, Brother, Sister, Guest, Neighbour, Relative, Other
2. If **Other** is selected, a text box appears — type the specific relation
3. Tap **✅ Confirm & Serve Plate**
4. The card refreshes showing the updated count

### Serving Multiple Plates
Each family member enters one at a time. After serving one plate, the card refreshes — select the next relation and confirm again until all plates for that family are served.

### Today's Log
The **Today's Log** section at the bottom of the validator screen shows how many plates have been served in this session (resets when the page is refreshed).

---

## 12. Secret Code & QR Code {#codes}

### Secret Code Format
- **6 uppercase characters**
- Characters used: `2 3 4 5 6 7 8 9 A B C D E F G H I J K L M N P Q R S T U V W X Y Z`
- **Deliberately excluded** to avoid confusion: `0` (zero), `1` (one), `O` (capital O), `l` (lowercase L), `o` (lowercase o)

This means a family can safely read their code aloud or write it down without ambiguity.

### QR Code
The QR code encodes the **6-character secret code**. Scanning it is equivalent to typing the code manually. The QR code uses high error-correction (Level H) so it can be read even if slightly damaged.

### If the Family Loses Their Code
Admin can look up any booking by house name or owner name in **Admin → Tickets** and share the secret code with them.

---

## 13. Fraud & Duplicate Handling {#fraud}

### What happens when all plates are used?
If someone tries to use a code after all plates have been served, the validator screen shows a high-visibility red alert:

```
⛔ LIMIT REACHED
All 4 plate(s) for "Kaveri Nivas" have been served.

• Self     — 11:32:04 AM
• Spouse   — 11:32:51 AM
• Son      — 11:45:10 AM
• Daughter — 11:45:38 AM
```

The validator should **not** serve an additional plate and should ask the family to speak with the event coordinator.

### What if someone enters a wrong code?
The system returns: *"Invalid code. No booking found."* — the validator should ask the family to double-check their code or contact the booking agent.

### Audit Trail
Every plate serving is permanently recorded in the database with:
- Exact timestamp
- Relation to owner
- Booking ID

This data is visible in **Admin → Reports → Check-in Log**.

---

## 14. Troubleshooting {#troubleshooting}

### "Database connection failed" on every page
- Check `config/database.php` — ensure `DB_HOST`, `DB_USER`, `DB_PASS`, `DB_NAME` are correct
- Confirm the MySQL user has been granted privileges to the database in cPanel

### Camera doesn't work on the validator page
- The browser must have camera permission — look for the camera icon in the address bar and allow it
- On iOS Safari, camera access requires HTTPS. Ensure your site has an SSL certificate
- If using an older browser, switch to the **Manual Entry** tab instead

### Agent PIN not working
- Check that the agent is marked **Active** in Admin → Agents
- Confirm the PIN in the Agents table (visible to admin)
- PINs are case-sensitive digits only (4–6 numbers)

### Booking form says "Network error"
- Check that the `api/` folder is uploaded and accessible
- Ensure the `APP_URL` in `config/database.php` matches your actual domain exactly (no trailing slash, correct http/https)
- Check that mod_rewrite is enabled in cPanel (required for `.htaccess` rules)

### CSV export opens garbled in Excel
- The file includes a UTF-8 BOM to signal encoding to Excel
- If still garbled: open Excel → Data → From Text/CSV → select UTF-8 encoding manually

### Price changed but bookings show old price
- This is by design. The price per plate is locked in at booking time. Each booking stores the exact price that was used. Only new bookings use the updated price.

### I need to reset the admin password
Run this SQL in cPanel → phpMyAdmin (replace `NewPassword123` with your desired password):
```sql
UPDATE admins
SET    password_hash = '$2y$10$...'
WHERE  username = 'admin';
```
Or more safely, run this PHP one-liner in a temporary file:
```php
<?php
require_once 'config/database.php';
$hash = password_hash('YourNewPassword', PASSWORD_DEFAULT);
getDB()->prepare("UPDATE admins SET password_hash=? WHERE username='admin'")->execute([$hash]);
echo "Done: " . $hash;
```
Then delete the file.

---

## Quick Reference Card

### URLs to bookmark/share
| Role | URL |
|---|---|
| Admin login | `https://yourdomain.com/aaravam2026/login.php?role=admin` |
| Agent login | `https://yourdomain.com/aaravam2026/login.php?role=agent` |
| Validator login | `https://yourdomain.com/aaravam2026/login.php?role=validator` |

### Day-of-event checklist
- [ ] Confirm price per plate is correct in Settings
- [ ] Confirm event date and venue are set
- [ ] All agents have been added and given their PINs
- [ ] Validator PIN has been shared with check-in staff
- [ ] Validator staff have tested camera scanning on their device
- [ ] Admin has tested a sample booking end-to-end

### Emergency contacts for families
If a family cannot find their ticket at the venue, a validator or admin can look up their booking using:
- House name or owner name → Admin → Tickets → Search
- Their mobile number → Admin → Reports → Search

---

*Aaravam 2026 — Onam Sadhya Ticketing System*
*For technical issues contact your system administrator.*
