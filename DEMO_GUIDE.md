# Aaravam 2026 — Demo & Go-Live Guide

This document covers **three things**:

1. How to clear the database and start the demo fresh
2. How to walk through the demo (step-by-step script)
3. How to clean everything and make the app live after the demo

> 📱 **Note:** Agents and Validators will mostly work from **mobile phones**. This guide
> is written with that in mind — bookmark the login page on each phone's home screen.

---

## 🔑 Credentials at a Glance

There are **no pre-made agent or validator accounts** — the admin creates them. Only the
**admin** has a default login (created by the installer).

| Role | Login page | Credential | Where it comes from |
|------|-----------|------------|---------------------|
| **Admin** | `login.php` → Admin tab | Username: `admin`  ·  Password: `Admin@1234` | Created by installer (`config/install.php`). **Change it after first login.** |
| **Agent** | `login.php` → Agent tab | A 4–6 digit **PIN** | Admin creates each agent in **Admin → Agents** |
| **Validator** | `login.php` → Validator tab | A personal 4–6 digit **PIN** | Admin creates each validator in **Admin → Validators** |

### Suggested demo accounts to create

Create these in the admin panel before the demo (you choose any name/PIN — these are just examples):

| Role | Name | Demo PIN |
|------|------|----------|
| Agent | Demo Agent 1 | `1122` |
| Agent | Demo Agent 2 | `3344` |
| Validator | Demo Validator 1 | `5566` |
| Validator | Demo Validator 2 | `7788` |

> ⚠️ These are **demo** PINs. For the live event, delete/deactivate them and create real ones
> with PINs that are **not** written in any shared document.

---

## Part 1 — Clear the Database & Start the Demo Fresh

You have two easy ways to reset. **Option A** (the built-in reset page) is easiest.

### Option A — Built-in Reset page (recommended)

1. In a browser, open:
   `https://YOUR-DOMAIN/aaravam2026/config/reset_demo.php`
2. Pick one:
   - **Clear bookings only** — wipes all bookings + check-in records, but keeps your
     agents, validators, settings, and admin login.
   - **Wipe everything** — also removes all agents and validators (settings + admin stay).
3. You'll see a green confirmation of how many records were removed.

> For starting a **demo** from scratch, use **Clear bookings only** if you've already created
> the demo agents/validators, or **Wipe everything** if you want to create them live during
> the demo.

### Option B — phpMyAdmin (manual SQL)

In cPanel → phpMyAdmin → select your database → **SQL** tab, run:

```sql
SET FOREIGN_KEY_CHECKS = 0;

-- Always: clear bookings + check-ins
TRUNCATE TABLE consumption;
TRUNCATE TABLE bookings;

-- Optional: also remove all agents & validators (for a full clean slate)
-- TRUNCATE TABLE validators;
-- TRUNCATE TABLE agents;

SET FOREIGN_KEY_CHECKS = 1;
```

> This **never** touches `settings` (event name, price) or `admins` (your login).

### After clearing — set the stage for the demo

1. Log in as admin (`admin` / `Admin@1234`).
2. **Admin → Settings** — set Event Name, Date, Venue, and **Price Per Plate** (e.g. ₹200).
3. **Admin → Agents** — add your demo agent(s) (see suggested accounts above).
4. **Admin → Validators** — add your demo validator(s).

You're ready to present.

---

## Part 2 — Demo Walkthrough (Script)

A clean 6-step story showing the full life of a ticket. Keep three things ready:
- A **laptop/phone as Admin** (dashboard on screen)
- A **phone as Agent**
- A **phone as Validator**

### Step 1 — Admin overview (30 sec)
- Log in as admin → show the **Dashboard**: total bookings, plates sold, served, revenue.
- Point out the nav: **Dashboard · Agents · Validators · Tickets · Reports · Settings**.
- Mention price per plate is set in **Settings**.

### Step 2 — Admin creates the team (1 min)
- **Admin → Agents → Add Agent**: name + PIN → show it appears in the list.
- **Admin → Validators → Add Validator**: name + PIN → show the list.
- Explain: each person gets their own PIN; you can deactivate anyone anytime.

### Step 3 — Agent books a family door-to-door 📱 (2 min)
- On the **Agent phone**: open login → **Agent tab** → enter PIN → **Start Booking**.
- Fill House Name, Owner Name, Contact, and **number of plates** (e.g. 4).
- Show the **total auto-calculates** (4 × ₹200 = ₹800).
- Tap **Confirm & Generate Ticket** → a ticket appears with:
  - Order ID (e.g. `ARV…`)
  - **6-character secret code**
  - **QR code**
- Tap **Share** to send it over WhatsApp — this is how a family receives their ticket.
- Point out the **Logout** button in the header.

### Step 4 — Validator checks the family in 📱 (2 min)
- On the **Validator phone**: login → **Validator tab** → enter PIN → **Enter Validator Mode**.
- Show two ways to look up:
  - **📷 Scan QR** → Start Camera → scan the ticket.
  - **⌨️ Manual Entry** → type the 6-char code → Look Up.
- The booking card shows plates remaining (e.g. 4 available).

### Step 5 — Serve multiple plates in one scan (the key feature) (1 min)
- Show the **Mark Plates as Served** section — one relation row is shown.
- Pick a relation (Self), then tap **+ Add Another Person** to add more rows
  (Spouse, Son, Daughter) — because families often arrive together and scan once.
- Tap **✅ Confirm & Serve** — all plates recorded in one step; counter updates instantly.
- Try to add more rows than remaining → show the system **blocks over-serving**.

### Step 6 — Fraud protection + reporting (1 min)
- Scan the **same** code again → show the red **⛔ LIMIT REACHED** alert with the full
  served history and timestamps.
- Back on **Admin → Reports** → show the **Check-in Log** (who served what, when) and the
  **CSV export**.
- Optionally show **Admin → Validators** where each validator's plates-served count updates.

**Wrap-up line:** *"Agent books on the phone → family gets a QR + code → validator scans and
serves on the phone → admin sees everything live and can export it."*

### Admin can act as a validator too
On **Admin → Validators**, the **Enter Validator Mode** button opens the validator screen
without a separate PIN — handy if you're demoing solo. Use **Back to Admin** to return.

---

## Part 3 — After the Demo: Make the App Live

Do these in order.

### 1. Clear all demo data
- Open `config/reset_demo.php` → **Wipe everything** (removes demo bookings, agents,
  validators). Settings and admin login are kept.
  *(Or use the phpMyAdmin SQL in Part 1, uncommenting the agents/validators lines.)*

### 2. Delete the setup/reset scripts from the server
These are one-time tools and must **not** stay on a live site:
- `config/install.php`
- `config/migrate_validators.php` (if still present)
- `config/reset_demo.php`  ← **delete after go-live**

### 3. Secure the admin account
- Log in as admin → **Admin → Settings → Change Password**.
- Set a strong password. **Never** reuse `Admin@1234` on the live site.

### 4. Load the real event configuration
- **Admin → Settings**: final Event Name, Date, Venue, and **Price Per Plate**.

### 5. Create the real team
- **Admin → Agents**: add every real volunteer with a unique PIN. Share each PIN
  **privately** (not in a group message).
- **Admin → Validators**: add every real check-in staff member with a unique PIN.
- Ask each person to test their PIN on their **own phone** and bookmark the login page.

### 6. Final go-live checklist
- [ ] `install.php`, `migrate_validators.php`, `reset_demo.php` all deleted from server
- [ ] Admin password changed from the default
- [ ] Site runs on **HTTPS** (required for phone camera QR scanning on iOS/Android)
- [ ] Price per plate confirmed
- [ ] All real agents added, PINs shared privately, each tested on their phone
- [ ] All real validators added, PINs shared privately, each tested on their phone
- [ ] One real end-to-end test booking → scan → serve, then **clear that test booking**
      (`reset_demo.php` → Clear bookings only, then delete the file again)

> 💡 If you run a final live test **after** deleting `reset_demo.php`, just delete that single
> test booking manually via phpMyAdmin, or briefly re-upload the reset file, clear bookings,
> and delete it once more.

---

## Quick Reference — URLs

| Purpose | URL |
|---------|-----|
| Login (all roles) | `https://YOUR-DOMAIN/aaravam2026/login.php` |
| Reset / clear data | `https://YOUR-DOMAIN/aaravam2026/config/reset_demo.php` |
| Installer (first-time only) | `https://YOUR-DOMAIN/aaravam2026/config/install.php` |
| Printable user guide | `https://YOUR-DOMAIN/aaravam2026/user-guide.php` |

*Replace `YOUR-DOMAIN` and the `/aaravam2026/` folder with your actual values.*
