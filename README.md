# Project Handover Documentation: Ovarias Database & Portal System

This document outlines the complete technical architecture, user flows, and integration settings implemented for the Ovarias platform.

---

## 1. System Overview & Authentication Architecture

To protect user privacy and ensure absolute database integrity, the platform operates on a strict **role-based security architecture** managed via WordPress and Ultimate Member. 

### Portal Access Matrix ("Who Can See What")

| User State | Navigation Menu Links Shown | Page Access Allowed |
| :--- | :--- | :--- |
| **Logged Out (Visitor)** | Registration & Login links for both portals | Public informational pages only. |
| **Logged-In Egg Donor** | Donor Dashboard, Logout | `/donor-dashboard/` only. |
| **Logged-In Intended Parent** | Intended Parent Dashboard, Logout | `/intended-parent-dashboard/` only. |
| **Ovarias Admin / Team** | Admin Dashboard Link (Private), Logout | Full management access, dashboard edit bypass. |

### Security Separation Controls
* **On-Form Login Restrictions:** If an Intended Parent tries to enter their credentials on the Egg Donor login page, the form instantly rejects the submission and displays a secure, generic error message: *"Invalid login credentials for this portal."* This prevents account enumeration attacks (outsiders guessing what type of account a user has).
* **Smart Cross-Redirections:** If a logged-in Donor attempts to manually type or visit the Intended Parent page, the plugin detects the role mismatch and instantly redirects them back to their own `/donor-dashboard/` URL automatically.

---

## 2. Public Site Pages & Informational Forms

The public portion of the Ovarias website contains front-facing pages to introduce services, share information, collect leads, and route registrants.

### Core Public Pages
1. **Home:** Main branding landing page containing calls to action for both prospective donors and parents.
2. **About Us:** Background on Ovarias and company mission statement.
3. **FAQ:** Answers frequently asked questions about the egg donation and matching processes.
4. **Contact Us:** Public contact page containing a inquiry submission form. Inquiries sent here route directly to the **General Inquiries** panel in your Admin Dashboard.

### Portal Entry Dropdown Pages
* **Donor Dropdown Menu:**
  * **Donor Registration (`/register/donor/`):** Contains the Ultimate Member signup form for new egg donors. Signing up registers them in the system under the `um_egg-donor` role, locks their account as "New Registration," and triggers their sync token.
  * **Donor Login (`/login/donor/`):** Dedicated entry portal for registered donors. 
  * **Why Egg Donation & Become an Egg Donor:** Informative pages detailing compensation, criteria, and workflow with buttons redirecting to registration.
* **Intended Parent Dropdown Menu:**
  * **Intended Parent Registration (`/register/intended-parent/`):** Public registration form for parents. Users signing up here are assigned the `parent` role and defaulted to unpaid status.
  * **Intended Parent Login (`/login/intended-parent/`):** Entrance portal for registered parents.
  * **Intended Parents:** General overview page describing database benefits and database access options.

---

## 3. Intended Parent Portal

The Intended Parent Portal allows parents to access the Ovarias donor database, manage membership, and handle secure payments.

### Key Features
* **Premium Access Levels:** The dashboard checks if the parent has paid. Paid parents get a "Premium Active" badge and full donor database access. Unpaid parents see a restricted status and are prompted to pay.
* **Stripe Payment Integration:**
  * **Current Setup (Testing/Dummy Mode):** A Stripe Elements checkout form is integrated into the dashboard using Stripe's official API in **Test Mode** (using dummy cards like `4242...`).
  * **Transitioning to Live Payments:** To go live, your developer just needs to replace the Stripe Test API keys with your **Live Stripe Secret Key & Publishable Key** in the parent plugin settings (or `wp-config.php`).
* **Match Inquiries:** Parents can submit match inquiries directly from the dashboard to request donor matches.

---

## 4. Egg Donor Dashboard

The Egg Donor Dashboard is a secure portal where donors build and edit their profile data.

### Key Features
* **Edit-Lock Protection (Read-Only by Default):** On page load, all donor inputs, selections, and textareas are disabled to prevent accidental data entry or loss. Clicking the **`Edit Profile`** button in the header unlocks the form, displays file upload options, and reveals the Save button.
* **Dynamic Photo Upload Card:**
  * If a photo has been uploaded, it displays the preview with a **`Change Photo`** button.
  * Clicking `Change Photo` prompts a file browser. When a new image is selected, it instantly previews local changes in both the upload box and the top-left header avatar in real-time.
* **Conditional Stock Categories:** The form dynamically detects if the category is set to "Frozen" or "Both" and reveals conditional input fields (Number of Eggs Available, Storage Country) only when needed.

### Zoho CRM Sync & Integration
* **Data Mapping:** When a donor clicks **`Save Profile Information`**, their data is validated, stored locally, and instantly synchronized to Zoho CRM.
* **Token Expiration Safeguard:** Zoho uses secure OAuth 2.0 protocols. Access tokens expire every hour. The plugin contains a background client handler that automatically requests a new Zoho access token using your secure **Zoho Refresh Token** without interrupting the user.
* **CRM Debugger (Admin Only):** A secure debugger container is visible in the donor dashboard only to Administrators, displaying the timestamp and server response of the latest Zoho CRM API calls to aid in diagnosis.

---

## 5. Admin Dashboard (Standalone Coordinator Panel)

The Admin Dashboard provides a standalone, mobile-responsive management console for the Ovarias internal team to view and manage inquiries and user accounts without using the cluttered WordPress backend.

### Key Features
* **Four Management Tabs:** 
  1. **Intended Parents:** Shows parent details, email, country, payment status, transaction ID, and match inquiries.
  2. **Donors:** Shows Donor IDs, age, stock type, number of eggs, storage country, and profile completion percentage.
  3. **Match Inquiries:** View match request details from parents.
  4. **General Inquiries:** General inquiries submitted via public contact pages.
* **Real-time AJAX Deletion Counters:** Clicking "Delete" on any row prompts a confirmation and dynamically updates the database. The total count badges (e.g., `Intended Parents (12)`) and the top metrics cards decrease **instantly** without reloading the page.
* **Mobile scrolling:** All tables are wrapped in touch-responsive scrolling panels with fixed column alignments to ensure long words and data columns do not break on mobile phones.

---

## 6. Detailed Dashboard Contents (Views & Fields)

Here is a complete inventory of the visual containers, inputs, and fields displayed inside each of the three dashboards:

### 1. Intended Parent Dashboard
* **Membership Status Card:** Displays the user's active membership level:
  * *Paid Parents:* Displays a green "Premium Active" badge.
  * *Unpaid Parents:* Displays a red "Restricted Access" notice with a payment checkout button.
* **Stripe Elements Payment Gateway:** Built-in form container for entering card details, expiration date, and CVC securely.
* **Egg Donor Database Browser (Premium Feature):**
  * *Search Filters:* Inputs to query donors by Age, Height, Weight, Blood Group, Eye Color, Hair Color, and Category (Fresh vs. Frozen).
  * *Donor Grid Cards:* Displays profile cards for matching donors showing their avatar, age, height, education, occupation, why they want to donate, and an inquiry button.
* **Match Inquiry Submission Drawer:** A custom form where parents can request a match for a specific Donor ID and enter special request notes.
* **Quick Support Links:** Shortcut links to view FAQs, Contact Us, and terms.

### 2. Egg Donor Dashboard
* **Header Status Banner:** Displays the logged-in Donor's profile completion percentage bar, their unique system status (e.g. *New Registration*, *Active*, etc.), name, email, and the `Edit Profile / Cancel Edit` toggles.
* **Personal Profile Fields:**
  * `Donor ID` (Assigned code)
  * `Availability Status` (Available, Reserved, Temporarily Unavailable, Not Available)
  * `Date of Birth` (Date selector)
  * `Nationality` (Text field)
  * `Height` and `Weight` (Text inputs)
  * `Blood Group` (A+, A-, B+, B-, AB+, AB-, O+, O-, Unknown)
  * `Eye Colour` (Brown, Blue, Black, Green, Gray, Hazel, Other)
  * `Hair Colour` (Black, Brown, Blonde, Red, Gray, Other)
* **Education & Occupation Fields:**
  * `Education Level` (High School, College, Bachelor's, Master's, Doctorate, Other)
  * `Field of Study` & `Occupation` (Text inputs)
  * `Languages Spoken` (Text area)
* **Donation Preferences & Category Fields:**
  * `Donation Type` (Anonymous, First Time, Repeat, Known)
  * `Willing to Travel` & `Valid Passport` (Yes / No dropdowns)
  * `Number of Previous Donations` (Number field)
  * `Egg Type Category` (Fresh Egg Donor, Frozen Egg Donor, Both)
  * *Frozen Stock Details (Conditional):* `Number of Eggs Available` and `Storage Country` (only visible when Frozen or Both is selected).
* **Profile Photo Dropzone:** Drag-and-drop file uploader area with instant client-side preview.
* **Open Text Areas (More About You):**
  * `About Me` (Description of personality, values, etc.)
  * `Hobbies & Interests` (Favorite activities, reading, sports)
  * `Why do you want to donate?` (Motivation details)

### 3. Admin Dashboard (Staff Portal)
* **High-Level Metric Cards:** Grid displaying the current real-time counts of:
  * *Total Parents*
  * *Total Donors*
  * *Total Match Requests*
  * *Total General Enquiries*
* **Intended Parents Tab Table:** List of all registered parents with:
  * First Name, Last Name, Email, Country.
  * Payment Status (Paid / Unpaid) & transaction reference codes.
  * Submitted Match Inquiry details.
  * Delete record button.
* **Egg Donors Tab Table:** List of registered donors showing:
  * First Name, Last Name, Email, Donor ID, Age.
  * Category (Fresh / Frozen), stock numbers, and storage country.
  * Sync status with Zoho CRM.
  * Profile completion percentage.
  * Delete record button.
* **Match Inquiries Tab Table:** Consolidated view of match requests showing Parent Name, requested Donor ID, special notes, date submitted, and deletion controls.
* **General Inquiries Tab Table:** Log of public website inquiries showing Name, Email, Subject, Message content, date sent, and deletion controls.

---

## 7. Deployment Checklist (Going Live)

When you are ready to launch, ensure the following keys and settings are completed:

### 1. Stripe Setup (Live Payments)
* Log in to your [Stripe Dashboard](https://dashboard.stripe.com/).
* Retrieve your **Live Publishable Key** (`pk_live_...`) and **Live Secret Key** (`sk_live_...`).
* Update these keys in the Ovarias Parent Dashboard plugin settings.

### 2. Zoho CRM Setup (Live Data)
* Log in to the Zoho Developer Console.
* Enter your Client ID and Client Secret.
* Generate a persistent **Refresh Token** (with `ZohoCRM.modules.ALL` scopes) and update the Zoho settings.

### 3. Ultimate Member Page Settings
* Verify that you have restricted the page content on `/intended-parent-dashboard/` and `/donor-dashboard/` to their respective roles using the Ultimate Member settings box at the bottom of the page editor.
