# PoultryCore — Business Flows & User Stories

> **Audience:** Product owners, managers, investors, and non-technical stakeholders  
> **Purpose:** Explain how the application works in plain, business-friendly language  
> **Date:** June 2026

---

## Table of Contents

1. [What Is PoultryCore?](#1-what-is-poultrycore)
2. [Who Uses the System?](#2-who-uses-the-system)
3. [The Big Picture: How Everything Connects](#3-the-big-picture-how-everything-connects)
4. [User Journey: From First Login to Daily Operations](#4-user-journey-from-first-login-to-daily-operations)
5. [Feature Walkthroughs](#5-feature-walkthroughs)
   - [Managing Barns](#51-managing-barns)
   - [Managing Batches](#52-managing-batches)
   - [Managing Suppliers](#53-managing-suppliers)
   - [Managing Customers](#54-managing-customers)
   - [Recording Purchases](#55-recording-purchases)
   - [Recording Sales](#56-recording-sales)
   - [Recording Deaths](#57-recording-deaths)
   - [Recording Expenses](#58-recording-expenses)
   - [Managing Payments](#59-managing-payments)
   - [The Dashboard](#510-the-dashboard)
6. [Real-Life Business Scenarios](#6-real-life-business-scenarios)
   - [Scenario A: Starting a New Batch of Broilers](#scenario-a-starting-a-new-batch-of-broilers)
   - [Scenario B: Raising the Batch — Daily Operations](#scenario-b-raising-the-batch--daily-operations)
   - [Scenario C: Harvest & Sell](#scenario-c-harvest--sell)
   - [Scenario D: End of Batch & Reconciliation](#scenario-d-end-of-batch--reconciliation)
   - [Scenario E: Handling a Return or Correction](#scenario-e-handling-a-return-or-correction)
   - [Scenario F: Multiple Batches Across Multiple Barns](#scenario-f-multiple-batches-across-multiple-barns)
7. [How Features Affect Each Other](#7-how-features-affect-each-other)
8. [What Happens Behind the Scenes](#8-what-happens-behind-the-scenes)
   - [When You Record a Cash Purchase](#81-when-you-record-a-cash-purchase)
   - [When You Record a Credit Purchase](#82-when-you-record-a-credit-purchase)
   - [When You Record a Cash Sale](#83-when-you-record-a-cash-sale)
   - [When You Record a Credit Sale](#84-when-you-record-a-credit-sale)
   - [When You Record a Death](#85-when-you-record-a-death)
   - [When You Make a Payment to a Supplier](#86-when-you-make-a-payment-to-a-supplier)
   - [When You Collect a Payment from a Customer](#87-when-you-collect-a-payment-from-a-customer)
   - [When You Close a Batch](#88-when-you-close-a-batch)
   - [When You View the Dashboard](#89-when-you-view-the-dashboard)
9. [Common, Expected, and Edge-Case Examples](#9-common-expected-and-edge-case-examples)
10. [Module Dependency Map](#10-module-dependency-map)
11. [Summary: How Everything Connects](#11-summary-how-everything-connects)

---

## 1. What Is PoultryCore?

**PoultryCore** is a farm management system designed for poultry farmers. It helps you track every aspect of your farm's operations in one place:

- **What you own** — your barns and the poultry batches inside them
- **What you buy** — chicks, feed, medicine, equipment from suppliers
- **What you sell** — live birds, meat, eggs to customers
- **What you spend** — operational costs, utilities, labor, transport
- **What you're owed** — customer debts for credit sales
- **What you owe** — supplier dues for credit purchases
- **Your profits** — income minus costs across all batches

The system is designed as a **backend API** — a digital engine that powers a mobile app or website. It handles all the complex calculations, inventory tracking, and financial reconciliation automatically so the farmer can focus on running the farm.

### Who Is This For?

| Role | How They Use the System |
|------|------------------------|
| **Farm Owner / Manager** | Records daily operations, tracks finances, views dashboard |
| **Farm Worker** | Records deaths, expenses, and basic transactions |
| **Accountant / Finance Team** | Manages payments, reconciles debts and dues |
| **Investor / Stakeholder** | Views dashboard for farm performance and profitability |

---

## 2. Who Uses the System?

Every user account is completely isolated — each farm owner sees only their own barns, batches, suppliers, customers, transactions, and financial data. There is no sharing between accounts.

### Account Types

| Type | Permissions |
|------|-------------|
| **User** | Full access to all farm features (standard farm owner) |
| **Admin** | Same as user (reserved for future platform-level administration) |

When someone first creates an account, the system knows they haven't finished setting up yet. After they create their first batch of poultry, the system marks their setup as "complete" and they can start full operations.

---

## 3. The Big Picture: How Everything Connects

Here is a visual overview of how the different parts of the system relate to each other:

```
                          ┌─────────────────┐
                          │    FARM OWNER    │
                          │     (User)      │
                          └────────┬────────┘
                                   │
            ┌──────────────────────┼──────────────────────┐
            │                      │                      │
     ┌──────▼──────┐       ┌──────▼──────┐       ┌──────▼──────┐
     │   BARNS     │       │  SUPPLIERS  │       │  CUSTOMERS  │
     │ (Physical   │       │  (People I  │       │  (People I  │
     │  houses)    │       │   buy from) │       │   sell to)  │
     └──────┬──────┘       └──────┬──────┘       └──────┬──────┘
            │                     │                      │
            │              ┌──────▼──────┐       ┌──────▼──────┐
            │              │ PURCHASES   │       │    SALES    │
            │              │ (What I     │       │ (What I     │
            │              │  buy for    │       │  sell from  │
            │              │  the farm)  │       │  the farm)  │
            │              └──────┬──────┘       └──────┬──────┘
            │                     │                      │
     ┌──────▼──────┐              │                      │
     │   BATCHES   │◄─────────────┘                      │
     │  (Groups of │◄────────────────────────────────────┘
     │   poultry)  │
     └──────┬──────┘
            │
     ┌──────▼──────┐       ┌────────────────────────────┐
     │   DEATHS    │       │         PAYMENTS           │
     │ (Mortality) │       │  ┌──────────────────────┐  │
     └─────────────┘       │  │  to_supplier = paying │  │
                            │  │  what I owe them     │  │
     ┌─────────────┐       │  ├──────────────────────┤  │
     │  EXPENSES   │       │  │ from_customer =      │  │
     │ (Operational│       │  │ collecting what they  │  │
     │  costs)     │       │  │ owe me               │  │
     └─────────────┘       │  └──────────────────────┘  │
                            └────────────────────────────┘
```

### At a Glance: What Each Module Tracks

| Module | Tracks | Key Numbers |
|--------|--------|-------------|
| **Barns** | Physical poultry houses | Name, location, maximum capacity |
| **Batches** | Groups of poultry in a barn | Type of bird, current count, start date, status |
| **Suppliers** | People you buy from | Name, contact info, **total owed to them** |
| **Customers** | People you sell to | Name, contact info, **total they owe you** |
| **Purchases** | Everything you buy (chicks, feed, medicine, other) | Item, type, quantity, price, **payment status** |
| **Sales** | Everything you sell | Item, quantity, price, **payment status** |
| **Deaths** | Bird mortality | How many died, when, why |
| **Expenses** | Operational costs | Type of expense, amount, date |
| **Payments** | Money movement | Who paid whom, how much, when, method |

---

## 4. User Journey: From First Login to Daily Operations

### Phase 1: Onboarding (First Time)

```
  Register ──► Create Barn(s) ──► Add Suppliers ──► Add Customers ──► Create First Batch
     │                                                                         │
     │                                                                         ▼
     │                                                    Setup marked "complete"
     │                                                    System is ready for use
     ▼
  User account created
  (not yet set up)
```

**Step-by-step:**

1. **Register** — The farmer creates an account with their name, phone number, and password. They receive a secure token that acts as their digital key.
2. **Create Barns** — The farmer adds their poultry houses (barns). Each barn has a name, location (optional), and maximum bird capacity (optional).
3. **Add Suppliers** — The farmer adds the people and companies they buy from (chick suppliers, feed sellers, medicine vendors).
4. **Add Customers** — The farmer adds the buyers who purchase their poultry products.
5. **Create First Batch** — The farmer starts their first group of poultry in a barn. This action tells the system "I'm ready to start farming" and marks the setup process as complete.

### Phase 2: Daily Operations

```
                           ┌─────────────────────────────────────┐
                           │         FARMER'S DAY                │
                           │                                     │
                           │  Morning: Check Dashboard           │
                           │    - How many birds are alive?      │
                           │    - Any alerts?                    │
                           │    - Any outstanding payments?      │
                           │                                     │
                           │  During the day:                    │
                           │    - Record any deaths              │
                           │    - Record purchases (feed, etc.)  │
                           │    - Record expenses (utilities)    │
                           │    - Record sales                   │
                           │                                     │
                           │  End of day:                        │
                           │    - Make/receive payments          │
                           │    - Check dashboard again          │
                           └─────────────────────────────────────┘
```

### Phase 3: Batch Lifecycle

```
  ┌──────────┐    ┌──────────┐    ┌──────────┐    ┌──────────┐    ┌──────────┐
  │  START   │    │  GROWTH  │    │  SELL    │    │  CLOSE   │    │  REVIEW  │
  │  Batch   │──► │  Phase   │──► │  Phase   │──► │  Batch   │──► │  Profits │
  │          │    │          │    │          │    │          │    │          │
  │ Buy      │    │ Record   │    │ Sell     │    │ Mark as  │    │ Compare  │
  │ chicks   │    │ deaths,  │    │ birds    │    │ finished │    │ income   │
  │ & inputs │    │ expenses │    │ & eggs   │    │          │    │ vs costs │
  └──────────┘    └──────────┘    └──────────┘    └──────────┘    └──────────┘
```

---

## 5. Feature Walkthroughs

### 5.1 Managing Barns

**What it does:** Barns are the physical houses or pens where you keep your poultry. Think of them as locations on your farm.

**User flow:**

```
  1. Open "Barns" section
  2. See a list of all your barns (each shows how many batches it has)
  3. Tap "Add Barn"
  4. Enter:
     - Barn name (must be unique for your farm)
     - Location (optional)
     - Maximum capacity (optional)
  5. Save → new barn is ready to use
```

**What you can do:**
- View all barns with their batch counts
- View a single barn and see all batches that were in it
- Edit barn details anytime
- Delete a barn (will remove it from the system)

**Business rule:** Each barn name must be unique within your farm. You cannot have two barns with the same name.

---

### 5.2 Managing Batches

**What it does:** A batch is a group of poultry that start and end together. For example, "500 broiler chickens in Barn A, started on March 1st."

**User flow:**

```
  1. Open "Batches" section
  2. See a list of all your batches (active and closed)
  3. Tap "Add Batch"
  4. Enter:
     - Which barn
     - Type of poultry (e.g., "Broiler", "Layer")
     - Start date
     - Optional notes
  5. Save → batch is created with 0 birds, status "Active"

  To close a batch:
  1. Open the batch details
  2. Tap "Close Batch"
  3. Confirm → batch status changes to "Closed", end date recorded
```

**Important rules:**
- **Only one active batch per barn at a time.** You cannot start a new batch in a barn that already has active poultry.
- A batch with purchases or sales **cannot be deleted** (to protect financial records).
- Closed batches **cannot accept** new purchases, sales, or death records.

**What affects a batch's bird count:**

```
  ┌─────────────────────────────────────────────────────────┐
  │                    BATCH BIRD COUNT                      │
  │                                                          │
  │  Starting count: 0                                       │
  │                                                          │
  │  Increases when:              Decreases when:            │
  │  ┌────────────────────┐       ┌────────────────────┐     │
  │  │ Buy chicks only   │       │ You sell birds     │     │
  │  │ (Purchase with    │       │ (Sale)             │     │
  │  │  type = chicks)   │       └────────────────────┘     │
  │  └────────────────────┘       ┌────────────────────┐     │
  │  No change for feed, │       │ Birds die          │     │
  │  medicine, or other   │       │ (Death record)     │     │
  │  purchases            │       └────────────────────┘     │
  └─────────────────────────────────────────────────────────┘
```

**Key rule:** Only purchases with type `chicks` increase the bird count.
Purchases of type `feed`, `medicine`, or `other` are recorded as costs
against the batch but do **not** modify the bird count.

---

### 5.3 Managing Suppliers

**What it does:** Suppliers are the people and businesses you buy from — chick hatcheries, feed mills, medicine vendors, equipment sellers.

**User flow:**

```
  1. Open "Suppliers" section
  2. See a list of all suppliers with their names and how much you owe them
  3. Tap "Add Supplier"
  4. Enter:
     - Name (must be unique for your farm)
     - Phone number (optional)
     - Address (optional)
  5. Save → supplier is ready
```

**What the system tracks for each supplier:**

| Field | Meaning |
|-------|---------|
| Total dues | How much money you currently owe this supplier for credit purchases |
| Purchase history | Every purchase you made from this supplier |
| Payment history | Every payment you made to this supplier |

**Important rules:**
- A supplier **cannot be deleted** if they have purchase records.
- Each supplier name must be unique within your farm.

---

### 5.4 Managing Customers

**What it does:** Customers are the people and businesses who buy your poultry products — restaurants, market vendors, neighbors, wholesalers.

**User flow:**

```
  1. Open "Customers" section
  2. See a list of all customers with their names and how much they owe you
  3. Tap "Add Customer"
  4. Enter:
     - Name (must be unique for your farm)
     - Phone number (optional)
     - Address (optional)
  5. Save → customer is ready
```

**What the system tracks for each customer:**

| Field | Meaning |
|-------|---------|
| Total debts | How much money this customer currently owes you for credit purchases |
| Purchase history | Every sale you made to this customer |
| Payment history | Every payment you received from this customer |

**Important rules:**
- A customer **cannot be deleted** if they have sale records.
- Each customer name must be unique within your farm.

---

### 5.5 Recording Purchases

**What it does:** You record a purchase whenever you buy something for the farm — chicks, feed, medicine, equipment, etc.

**User flow:**

```
  1. Open "Purchases" section
  2. Tap "Record Purchase"
  3. Enter:
     - Supplier (who you bought from)
     - Batch (which batch this is for — required for all types)
     - Type: Chicks / Feed / Medicine / Other
     - Item name (e.g., "500 Broiler Chicks", "Starter Feed")
     - Unit (e.g., "piece", "kilogram", "bag")
     - Quantity
     - Unit price
     - Purchase date
     - Payment type: Cash or Credit
  4. Save
```

**What happens based on payment type:**

```
  ┌──────────────────────────────────────────────────────────────────┐
  │                    RECORDING A PURCHASE                          │
  │                                                                  │
  │  ┌─── CASH ─────────────────────────────────────────────────┐    │
  │  │ • You pay the full amount immediately                    │    │
  │  │ • The purchase is marked as "paid"                       │    │
  │  │ • Your supplier's dues are NOT affected                  │    │
  │  │ • If type is "chicks": batch bird count ▲ by quantity    │    │
  │  │ • If type is feed/medicine/other: bird count unchanged   │    │
  │  └──────────────────────────────────────────────────────────┘    │
  │                                                                  │
  │  ┌─── CREDIT ───────────────────────────────────────────────┐    │
  │  │ • You don't pay today — you'll pay later                  │    │
  │  │ • The purchase is marked as "unpaid"                      │    │
  │  │ • Your supplier's total dues INCREASE by the total price  │    │
  │  │ • If type is "chicks": batch bird count ▲ by quantity     │    │
  │  │ • If type is feed/medicine/other: bird count unchanged    │    │
  │  └──────────────────────────────────────────────────────────┘    │
  └──────────────────────────────────────────────────────────────────┘
```

**Editing a purchase:**
- You can change quantity (bird count adjusts automatically **only for chick purchases**)
- You can change total price (supplier dues adjust if credit)
- You can switch between cash and credit (the system handles all financial adjustments)
- You **cannot** change the supplier or batch after saving
- A fully paid purchase **cannot be edited** (except changing payment type)

**Deleting a purchase:**
- Not allowed if payments have been made against this purchase
- If credit: the unpaid amount is removed from supplier's dues
- **If type is "chicks":** the bird count decreases by the purchase quantity
- **If type is feed/medicine/other:** no change to bird count

---

### 5.6 Recording Sales

**What it does:** You record a sale whenever you sell poultry products — live birds, meat, eggs, etc.

**User flow:**

```
  1. Open "Sales" section
  2. Tap "Record Sale"
  3. Enter:
     - Customer (who bought)
     - Batch (which batch this comes from)
     - Item name (e.g., "50 Live Broilers")
     - Unit (e.g., "piece", "kilogram")
     - Quantity
     - Unit price
     - Sale date
     - Payment type: Cash or Credit
  4. Save
```

**What happens based on payment type:**

```
  ┌──────────────────────────────────────────────────────────────────┐
  │                     RECORDING A SALE                             │
  │                                                                  │
  │  ┌─── CASH ─────────────────────────────────────────────────┐    │
  │  │ • The customer pays the full amount immediately          │    │
  │  │ • The sale is marked as "paid"                           │    │
  │  │ • Your customer's debts are NOT affected                 │    │
  │  │ • Your batch bird count DECREASES by the quantity sold   │    │
  │  └──────────────────────────────────────────────────────────┘    │
  │                                                                  │
  │  ┌─── CREDIT ───────────────────────────────────────────────┐    │
  │  │ • The customer doesn't pay today — they'll pay later      │    │
  │  │ • The sale is marked as "unpaid"                         │    │
  │  │ • Your customer's total debts INCREASE by total price    │    │
  │  │ • Your batch bird count DECREASES by the quantity sold   │    │
  │  └──────────────────────────────────────────────────────────┘    │
  └──────────────────────────────────────────────────────────────────┘
```

**Important rules:**
- You **cannot sell more birds than you have** in the batch.
- You **cannot sell from a closed batch**.
- Same editing/deletion rules as purchases (mirrored for customers instead of suppliers).

---

### 5.7 Recording Deaths

**What it does:** When birds die, you record it. This keeps your live bird count accurate and helps track mortality rates.

**User flow:**

```
  1. Open "Deaths" section
  2. Tap "Record Death"
  3. Enter:
     - Batch (which batch)
     - Quantity (how many died)
     - Date
     - Reason (optional)
  4. Save → batch bird count decreases
```

**Important rules:**
- You **cannot record more deaths** than the current bird count in the batch.
- You **cannot record deaths** for a closed batch.
- If you delete a death record by mistake, the bird count is restored.

---

### 5.8 Recording Expenses

**What it does:** You record any operational cost related to a batch — electricity, water, labor, transport, maintenance, etc.

**User flow:**

```
  1. Open "Expenses" section
  2. Tap "Record Expense"
  3. Enter:
     - Batch (which batch this expense is for)
     - Type: (Feed / Treatment / Utilities / Labor / Maintenance / Transport / Other)
     - Amount
     - Date
     - Notes (optional)
  4. Save
```

**Important:** Expenses are purely financial records. They **do not affect** the bird count in a batch. They do affect your profit calculations on the dashboard.

---

### 5.9 Managing Payments

**What it does:** Payments track actual money movement between you and your suppliers/customers.

**Two types of payments:**

```
  ┌─── PAY TO SUPPLIER ──────────────────────────────────────────┐
  │  You pay money TO someone you buy from.                      │
  │  Example: Paying the feed supplier for last month's credit.  │
  │                                                              │
  │  Effect:                                                     │
  │  • Your supplier's total dues DECREASE                       │
  │  • The linked purchase's paid amount INCREASES               │
  │  • The purchase's payment status updates:                    │
  │    - Fully paid → status becomes "paid"                      │
  │    - Partially paid → status becomes "partial"               │
  └──────────────────────────────────────────────────────────────┘

  ┌─── COLLECT FROM CUSTOMER ────────────────────────────────────┐
  │  You receive money FROM someone who buys from you.           │
  │  Example: A customer paying for last week's credit purchase. │
  │                                                              │
  │  Effect:                                                     │
  │  • Your customer's total debts DECREASE                      │
  │  • The linked sale's paid amount INCREASES                   │
  │  • The sale's payment status updates                         │
  └──────────────────────────────────────────────────────────────┘
```

**User flow:**

```
  1. Open "Payments" section
  2. Tap "Record Payment"
  3. Enter:
     - Type: "Pay to supplier" or "Collect from customer"
     - Link to the specific purchase (for supplier payments)
       or sale (for customer payments)
     - Amount (cannot exceed what's still unpaid)
     - Payment date
     - Payment method (cash, bank transfer, etc.)
     - Notes (optional)
  4. Save
```

**Important rules:**
- The payment amount **cannot exceed** what's still unpaid on the linked purchase or sale.
- You **cannot make payments** on a fully paid transaction.
- When you edit a payment, the system carefully unwinds the old effect and applies the new one — this is the most complex operation in the system.

---

### 5.10 The Dashboard

**What it does:** The dashboard is your farm's command center — a single screen that shows everything you need to know at a glance.

**What you see:**

```
  ┌─────────────────────────────────────────────────────────────────┐
  │                       FARM DASHBOARD                            │
  ├─────────────────────────────────────────────────────────────────┤
  │                                                                  │
  │  QUICK COUNTS:                                                   │
  │  • Barns: 3  |  Active Batches: 2  |  Total Batches: 5          │
  │  • Suppliers: 8  |  Customers: 12                                │
  │  • Purchases: 45  |  Sales: 38  |  Payments: 62                 │
  │  • Deaths Recorded: 23  |  Expenses: 31                         │
  │                                                                  │
  │  FINANCIAL SUMMARY:                                              │
  │  • Total spent on purchases:  EGP 125,000                        │
  │  • Total earned from sales:   EGP 198,000                        │
  │  • Total expenses:           EGP 15,000                          │
  │  • Net revenue:              EGP 58,000                          │
  │  • Outstanding supplier dues: EGP 12,000  ⚠                     │
  │  • Outstanding customer debts: EGP 8,000  ⚠                    │
  │                                                                  │
  │  PRODUCTION:                                                     │
  │  • Total birds across all batches: 2,450                         │
  │  • Total deaths recorded: 150                                    │
  │                                                                  │
  │  ⚠ ALERTS:                                                      │
  │  • 2 batches have low stock (≤100 birds)                         │
  │  • 3 suppliers have outstanding dues                             │
  │  • 5 customers have outstanding debts                            │
  │  • 7 deaths recorded in the last week                            │
  │  • 1 batch ending soon                                           │
  │  • 4 unpaid purchases                                            │
  │  • 3 unpaid sales                                                │
  │                                                                  │
  │  RECENT ACTIVITY:                                                │
  │  • Last 5 purchases (with supplier names)                        │
  │  • Last 5 payments (with who was paid/received from)             │
  │  • Last 5 batches (with barn names)                              │
  │  • Last 5 sales (with customer names)                            │
  └─────────────────────────────────────────────────────────────────┘
```

---

## 6. Real-Life Business Scenarios

### Scenario A: Starting a New Batch of Broilers

**Farmer Ahmed** has a poultry farm with two barns. He wants to start a new batch.

**His steps:**

1. **Checks the dashboard** — sees Barn A is empty (previous batch was closed). ✓
2. **Creates a batch** in Barn A: "Broiler Batch #4", poultry type "Broiler", start date today.
3. **Calls his chick supplier** — orders 1,000 broiler chicks at EGP 5 each = EGP 5,000 total.
4. **Records the purchase:** Supplier "Mohamed's Hatchery", batch "Broiler Batch #4", type **chicks**, 1,000 chicks, EGP 5 each, **cash** payment.
   - System deducts EGP 5,000 from his bank (in his mind — the system just records it)
   - System adds 1,000 birds to the batch count
5. **Records a second purchase:** Supplier "Feed Co.", batch "Broiler Batch #4", type **feed**, 500 kg of starter feed, EGP 10/kg = EGP 5,000, **credit**.
   - System adds EGP 5,000 to the supplier's total dues
   - System adds 0 birds (feed doesn't add birds — only chick purchases affect bird count)
6. Checks dashboard: Batch now has 1,000 birds. Supplier owes: EGP 5,000.

---

### Scenario B: Raising the Batch — Daily Operations

**Over the next 4 weeks, Farmer Ahmed records daily activities:**

**Week 1:**
- 15 chicks die (records death) → batch now has 985 birds
- Buys more feed (cash) → no change to birds
- Records electricity expense → adds to costs

**Week 2:**
- 8 more deaths → 977 birds remaining
- Buys medicine (credit) → supplier dues increase
- Records labor cost → adds to costs

**Week 3:**
- 5 deaths → 972 birds
- Buys more feed (cash)
- Customer Khaled wants to buy 50 live birds, but will pay later
- Records sale: 50 birds, EGP 60 each = EGP 3,000, **credit**
  - System reduces batch to 922 birds
  - System adds EGP 3,000 to customer Khaled's debts

**Week 4:**
- 3 deaths → 919 birds
- Record expenses (transport, utilities)
- Customer Khaled pays EGP 2,000 of his EGP 3,000 debt
  - Records payment: type "Collect from customer", EGP 2,000
  - System reduces Khaled's debt to EGP 1,000
  - System updates the sale status to "partial"

---

### Scenario C: Harvest & Sell

**The birds are ready for market. Farmer Ahmed sells in bulk:**

**Day 1:** Sells 300 birds to a wholesaler (cash, EGP 55 each = EGP 16,500)
- Batch decreases to 619 birds
- All paid, no debt created

**Day 2:** Sells 200 birds to a restaurant (credit, EGP 60 each = EGP 12,000)
- Batch decreases to 419 birds
- Restaurant now owes EGP 12,000

**Day 3:** Sells 400 birds to multiple small customers (all cash)
- Batch decreases to 19 birds
- Dashboard starts showing "Low stock" alert

**Day 4:** Sells remaining 19 birds (cash)
- Batch reaches 0 birds
- Farmer closes the batch

---

### Scenario D: End of Batch & Reconciliation

**After closing the batch, Farmer Ahmed wants to see how it went:**

1. **Checks dashboard:**
   - Total spent on purchases for this batch: EGP 30,000
   - Total earned from sales: EGP 48,000
   - Total expenses: EGP 4,000
   - Net profit: EGP 14,000

2. **Checks remaining debts:**
   - He still owes EGP 3,000 to the feed supplier
   - He's owed EGP 1,000 from customer Khaled

3. **Settles the feed supplier** — records a "Pay to supplier" payment of EGP 3,000
   - Supplier dues become 0

4. **Follows up with Khaled** about the remaining EGP 1,000

5. **Prepares Barn A** for the next batch

---

### Scenario E: Handling a Return or Correction

**Farmer Ahmed realizes he made a mistake:**

**Mistake 1:** Recorded 100 bird deaths but it was actually 50.
- He edits the death record from 100 to 50
- System adds 50 birds back to the batch (undoing 50 of the original decrease)

**Mistake 2:** Recorded a cash purchase of feed, but actually paid later.
- He edits the purchase, changing payment type from "cash" to "credit"
- System removes the auto-payment that was created for the cash version
- System adds the amount to supplier's dues
- Purchase status changes to "unpaid"

**Mistake 3:** Recorded a sale to the wrong customer.
- This one cannot be changed — customer is locked after creation
- He must delete the sale (only possible if no payments were made) and re-record it correctly

---

### Scenario F: Multiple Batches Across Multiple Barns

**Farmer Ahmed has 3 barns. His farm can look like this:**

```
  Barn A: "Broiler Batch #5" — Active, 800 birds, started June 1
  Barn B: "Layer Batch #2"  — Active, 200 birds, started May 15
  Barn C: (empty) — Ready for new batch
```

- He can record purchases and sales for each batch independently
- Each batch has its own bird count, expenses, deaths, and financials
- The dashboard shows combined totals across ALL batches
- When Barn C is empty, he can start a new batch there without affecting A or B

---

## 7. How Features Affect Each Other

This section shows the chain reaction when you perform common actions.

### Recording a Cash Purchase

```
  ┌─────────────────┐
  │  CASH PURCHASE  │
  └────────┬────────┘
           │
           ├── If type = chicks ──► Batch bird count  ▲ (increases)
           │
           ├── If type = feed/medicine/other ──► Bird count unchanged
           │
           ├────► Auto-payment created (cash to supplier)
           │
           └────► No change to supplier dues
```

### Recording a Credit Purchase

```
  ┌───────────────────┐
  │  CREDIT PURCHASE  │
  └────────┬──────────┘
           │
           ├── If type = chicks ──► Batch bird count  ▲ (increases)
           │
           ├── If type = feed/medicine/other ──► Bird count unchanged
           │
           ├────► Supplier total dues  ▲ (increases by total price)
           │
           └────► Purchase marked "unpaid"
```

### Recording a Cash Sale

```
  ┌──────────────┐
  │  CASH SALE   │
  └──────┬───────┘
         │
         ├────► Batch bird count  ▼ (decreases)
         │
         ├────► Auto-payment created (cash from customer)
         │
         └────► No change to customer debts
```

### Recording a Credit Sale

```
  ┌────────────────┐
  │  CREDIT SALE   │
  └───────┬────────┘
          │
          ├────► Batch bird count  ▼ (decreases)
          │
          ├────► Customer total debts  ▲ (increases by total price)
          │
          └────► Sale marked "unpaid"
```

### Making a Payment to Supplier

```
  ┌───────────────────┐
  │  PAY TO SUPPLIER  │
  └────────┬──────────┘
           │
           ├────► Supplier total dues  ▼ (decreases)
           │
           ├────► Purchase paid amount  ▲ (increases)
           │
           └────► Purchase status updates (unpaid → partial → paid)
```

### Collecting Payment from Customer

```
  ┌────────────────────────┐
  │  COLLECT FROM CUSTOMER │
  └───────────┬────────────┘
              │
              ├────► Customer total debts  ▼ (decreases)
              │
              ├────► Sale paid amount  ▲ (increases)
              │
              └────► Sale status updates (unpaid → partial → paid)
```

### Recording a Death

```
  ┌────────────┐
  │   DEATH    │
  └─────┬──────┘
        │
        └────► Batch bird count  ▼ (decreases)
```

### Editing a Purchase Quantity

```
  ┌────────────────────┐
  │  EDIT PURCHASE QTY │
  └─────────┬──────────┘
            │
            ├─ If type = chicks ──► Batch bird count adjusts (▲/▼)
            │
            └─ If type = feed/medicine/other ──► Bird count unchanged
```

### Deleting a Purchase

```
  ┌────────────────┐
  │  DELETE CREDIT │
  │   PURCHASE     │
  └───────┬────────┘
          │
          ├── Blocked if payments exist on this purchase
          │
          ├────► Supplier dues  ▼ (unpaid amount removed)
          │
          └── If type = chicks ──► Batch bird count  ▼ (quantity removed)
              If type = feed/medicine/other ──► No change to bird count
```

---

## 8. What Happens Behind the Scenes

This section explains the system's automatic actions in plain business language.

### 8.1 When You Record a Cash Purchase

**You see:** "Purchase recorded successfully."

**Behind the scenes:**
1. The system checks that the supplier and batch belong to your farm.
2. If you didn't enter a total price, the system calculates it: `quantity × unit price`.
3. Because you paid cash, the system marks the purchase as fully paid.
4. The system automatically creates a **payment record** showing you paid the supplier in cash.
5. **If the purchase type is "chicks":** the system adds the purchased quantity to the batch's bird count.
6. **If the purchase type is feed, medicine, or other:** the bird count is **not changed**.
7. The supplier's "total dues" are **not affected** (because you paid immediately).

**Example:** Buying 500 chicks at EGP 5 each with cash:
- Purchase recorded: type "chicks", EGP 2,500, status "paid"
- Auto-payment created: EGP 2,500 to supplier, method "cash"
- Batch bird count: +500

**Example:** Buying 500 kg of feed at EGP 10/kg with cash:
- Purchase recorded: type "feed", EGP 5,000, status "paid"
- Auto-payment created: EGP 5,000 to supplier, method "cash"
- Batch bird count: unchanged

### 8.2 When You Record a Credit Purchase

**You see:** "Purchase recorded successfully."

**Behind the scenes:**
1. Same validation and price calculation.
2. Because you bought on credit, the system marks the purchase as **unpaid**.
3. The system **adds the total price** to the supplier's "total dues" (what you owe them).
4. **If the purchase type is "chicks":** the system adds the purchased quantity to the batch's bird count.
5. **If the purchase type is feed, medicine, or other:** the bird count is **not changed**.
6. No payment record is created (you'll pay later).

**Example:** Buying 500 chicks at EGP 5 each on credit:
- Purchase recorded: type "chicks", EGP 2,500, status "unpaid"
- Supplier's total dues: +EGP 2,500
- Batch bird count: +500

**Example:** Buying feed worth EGP 5,000 on credit:
- Purchase recorded: type "feed", EGP 5,000, status "unpaid"
- Supplier's total dues: +EGP 5,000
- Batch bird count: +0 (feed doesn't add birds — only chick purchases affect bird count)

### 8.3 When You Record a Cash Sale

**You see:** "Sale recorded successfully."

**Behind the scenes:**
1. The system checks that the customer and batch belong to your farm.
2. The system checks the batch has enough birds to sell.
3. Total price is calculated if not provided.
4. The sale is marked as **fully paid**.
5. An **auto-payment** is created showing the customer paid in cash.
6. The batch bird count **decreases** by the quantity sold.
7. The customer's "total debts" are **not affected**.

**Example:** Selling 50 birds at EGP 60 each for cash:
- Sale recorded: EGP 3,000, status "paid"
- Auto-payment created: EGP 3,000 from customer, method "cash"
- Batch bird count: -50

### 8.4 When You Record a Credit Sale

**You see:** "Sale recorded successfully."

**Behind the scenes:**
1. Same validation and quantity check.
2. The sale is marked as **unpaid**.
3. The **total price is added** to the customer's "total debts".
4. The batch bird count **decreases** by the quantity sold.

**Example:** Selling 50 birds at EGP 60 each on credit:
- Sale recorded: EGP 3,000, status "unpaid"
- Customer's total debts: +EGP 3,000
- Batch bird count: -50

### 8.5 When You Record a Death

**You see:** "Death recorded successfully."

**Behind the scenes:**
1. The system checks the batch belongs to your farm and isn't closed.
2. The system verifies you're not recording more deaths than birds in the batch.
3. The batch bird count **decreases** by the death quantity.

**Example:** 15 chicks die:
- Batch bird count: -15
- (No financial impact)

### 8.6 When You Make a Payment to a Supplier

**You see:** "Payment recorded successfully."

**Behind the scenes:**
1. The system verifies the linked purchase exists, belongs to you, and isn't fully paid.
2. It checks that the payment amount doesn't exceed what's still unpaid.
3. It verifies the supplier matches the one on the purchase.
4. The payment is saved.
5. The purchase's "paid amount" **increases** by the payment amount.
6. The purchase's status **recalculates**:
   - If now fully paid → status changes to "paid"
   - If still partially paid → status changes to "partial"
7. The supplier's "total dues" **decrease** by the payment amount.

**Example:** Paying EGP 2,000 toward a EGP 5,000 feed bill:
- Purchase paid amount: EGP 0 → EGP 2,000
- Purchase status: "unpaid" → "partial"
- Supplier dues: -EGP 2,000

### 8.7 When You Collect a Payment from a Customer

**You see:** "Payment recorded successfully."

**Behind the scenes:**
1. Same verification as supplier payment, mirrored for customer side.
2. The sale's "paid amount" **increases**.
3. The sale's status **recalculates**.
4. The customer's "total debts" **decrease**.

**Example:** Collecting EGP 2,000 from a customer who owes EGP 3,000:
- Sale paid amount: EGP 0 → EGP 2,000
- Sale status: "unpaid" → "partial"
- Customer debts: -EGP 2,000

### 8.8 When You Close a Batch

**You see:** "Batch closed successfully."

**Behind the scenes:**
1. The system changes the batch status from "active" to "closed".
2. The system records today's date as the end date.
3. From now on, no new purchases, sales, or deaths can be recorded for this batch.
4. The barn becomes available for a new batch.

### 8.9 When You View the Dashboard

**You see:** A comprehensive farm overview.

**Behind the scenes:**
The system runs about 20 different calculations to build your dashboard:

1. **Counts everything** — barns, batches, active batches, purchases, sales, payments, deaths, expenses, suppliers, customers.
2. **Sums up your finances:**
   - Adds up every purchase total → "Total spent"
   - Adds up every sale total → "Total earned"
   - Adds up every expense → "Total expenses"
   - Calculates profit: earnings − spending − expenses
3. **Calculates your debts:**
   - Sum of all supplier "total dues" → "What you owe"
   - Sum of all customer "total debts" → "What you're owed"
4. **Checks production:**
   - Sums current bird count across all active batches
   - Sums all deaths ever recorded
5. **Finds alerts:**
   - Batches with 100 birds or fewer → "Low stock"
   - Suppliers with money owed → "Outstanding dues"
   - Customers with money owing → "Outstanding debts"
   - Deaths in the last 7 days
   - Batches ending in the next 7 days
   - Count of unpaid purchases and sales
6. **Gets recent activity:**
   - Last 5 purchases with supplier names
   - Last 5 payments with who was involved
   - Last 5 batches with barn names
   - Last 5 sales with customer names

---

## 9. Common, Expected, and Edge-Case Examples

### Common Scenarios

| # | Scenario | What the User Does | What the System Does |
|---|----------|-------------------|---------------------|
| 1 | Starting a new batch | Create barn → Create batch → Record purchase of chicks (cash) | Batch created with 0 birds → Batch gets +500 birds, auto-payment created |
| 2 | Daily feeding purchase | Record purchase of feed, cash | Bird count unchanged, auto-payment created |
| 3 | Selling birds for cash | Record sale, cash | -50 birds, auto-payment received |
| 4 | Bird dies | Record death | -3 birds, no financial impact |
| 5 | Paying a supplier bill | Create payment "to supplier" | Supplier dues decrease, purchase status updates |

### Expected Variations

| # | Scenario | What Happens |
|---|----------|-------------|
| 6 | Buying on credit | Supplier dues increase, purchase stays "unpaid" |
| 7 | Selling on credit | Customer debts increase, sale stays "unpaid" |
| 8 | Partial payment from customer | Customer debts decrease partially, sale status becomes "partial" |
| 9 | Recording a utility expense | Expense logged, no bird count impact, affects profit calculation |
| 10 | Checking farm performance mid-cycle | Dashboard shows current numbers and alerts |

### Edge Cases (Handled Automatically)

| # | Edge Case | How the System Handles It |
|---|-----------|--------------------------|
| 11 | User tries to record more deaths than birds in the batch | **Blocked** — error message: "Cannot record more deaths than current quantity" |
| 12 | User tries to sell more birds than available | **Blocked** — error message: "Insufficient batch quantity" |
| 13 | User tries to start a second batch in an occupied barn | **Blocked** — error message: "Barn already has an active batch" |
| 14 | User tries to make a payment larger than the unpaid amount | **Blocked** — error message: "Amount exceeds remaining balance" |
| 15 | User tries to delete a purchase that has payments | **Blocked** — error message: "Cannot delete purchase with payments" |
| 16 | User tries to edit a fully paid purchase | **Blocked** — only payment type can be changed |
| 17 | User changes a cash purchase to credit | System removes auto-payment, adds amount to supplier dues, marks as "unpaid" |
| 18 | User changes a credit purchase to cash | System creates auto-payment, removes amount from supplier dues, marks as "paid" |
| 19 | User records a sale that exactly empties the batch | Batch reaches 0 birds, dashboard shows alert (but batch remains "active" until manually closed) |
| 20 | Supplier dues would go negative from an edit | **Prevented** — system caps at 0 using `max(0, amount)` |
| 21 | Customer debts would go negative from an edit | **Prevented** — system caps at 0 using `max(0, amount)` |
| 22 | User changes the amount on a payment | System unwinds old amount effect, applies new amount effect, recalculates everything |
| 23 | User changes which purchase a payment is linked to | System unwinds from old purchase, applies to new purchase, adjusts both suppliers' dues |
| 24 | User tries to record a sale for a closed batch | **Blocked** — error message: "Cannot record sale for closed batch" |
| 25 | User tries to record a death for a closed batch | **Blocked** — error message: "Cannot record death for closed batch" |

---

## 10. Module Dependency Map

This shows which modules depend on others to function:

```
  ┌──────────┐
  │   USER   │  (Foundation — every record is linked to a user)
  └────┬─────┘
       │
       ▼
  ┌──────────┐     ┌──────────┐     ┌──────────┐
  │  BARNS   │     │SUPPLIERS │     │CUSTOMERS │
  └────┬─────┘     └────┬─────┘     └────┬─────┘
       │                │                │
       ▼                ▼                ▼
  ┌──────────┐     ┌──────────┐     ┌──────────┐
  │ BATCHES  │◄────│PURCHASES │     │  SALES   │
  │          │     │          │     │          │
  │ Needs:   │     │ Needs:   │     │ Needs:   │
  │ • Barn   │     │ • Batch  │     │ • Batch  │
  │          │     │ • Supplr │     │ • Customr│
  └────┬─────┘     └────┬─────┘     └────┬─────┘
       │                │                │
       ▼                ▼                ▼
  ┌──────────┐     ┌──────────────────────────┐
  │  DEATHS  │     │        PAYMENTS           │
  │          │     │                           │
  │ Needs:   │     │ Needs (choose one):       │
  │ • Batch  │     │ • Purchase (for supplier) │
  │          │     │ • Sale (for customer)     │
  └──────────┘     └──────────────────────────┘

  ┌──────────┐
  │ EXPENSES │
  │          │
  │ Needs:   │
  │ • Batch  │
  └──────────┘
```

### Dependency Rules

| Module | Depends On | Because |
|--------|-----------|---------|
| Barns | User | Every barn belongs to a farm owner |
| Batches | User, Barn | A batch lives in a barn |
| Suppliers | User | Every supplier belongs to a farm owner |
| Customers | User | Every customer belongs to a farm owner |
| Purchases | User, Supplier, Batch | Links what you bought, from whom, and for which batch |
| Sales | User, Customer, Batch | Links what you sold, to whom, and from which batch |
| Deaths | User, Batch | Mortality is tracked per batch |
| Expenses | User, Batch | Operational costs are tracked per batch |
| Payments (to supplier) | User, Supplier, Purchase | Paying against a specific purchase |
| Payments (from customer) | User, Customer, Sale | Collecting against a specific sale |

---

## 11. Summary: How Everything Connects

### The Four Key Numbers

Four numbers are automatically maintained by the system. They update in real-time as you record transactions.

| Number | What It Tracks | Goes Up When | Goes Down When |
|--------|---------------|--------------|----------------|
| **Batch Bird Count** | Live birds in a batch | You buy chicks (purchase with type = chicks) | You sell birds (sale), birds die (death) |
| **Supplier Total Dues** | What you owe your suppliers | You buy on credit | You pay them (payment) |
| **Customer Total Debts** | What customers owe you | You sell on credit | They pay you (payment) |
| **Purchase/Sale Paid Amount** | How much of a transaction is settled | You make/receive a payment | You edit/delete a payment |

### The Golden Rules

1. **Only chick purchases** add to bird count. Feed, medicine, and other purchases are operational costs recorded against the batch. Every **sale** and **death** subtracts from inventory.
2. **Cash transactions** don't create debts. Money moves immediately.
3. **Credit transactions** create debts. Money moves later when a payment is recorded.
4. **Payments** are the bridge — they convert credit into cash by reducing debts.
5. **A fully paid transaction is frozen** — no more edits or payments allowed.
6. **Everything belongs to a batch.** Purchases, sales, deaths, and expenses are all linked to a specific batch, which is linked to a barn, which belongs to you.

### The Big Picture in One Paragraph

PoultryCore is a **digital farm assistant** that tracks every bird from the moment it arrives as a chick until it's sold, every expense from feed to electricity, every payment to suppliers and from customers, and automatically calculates your profits, debts, and inventory. It ensures you never sell birds you don't have, never double-pay a bill, always know who owes you money and who you owe, and can see your farm's financial health at a glance — all without needing a spreadsheet or calculator.
