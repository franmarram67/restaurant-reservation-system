# Restaurant Reservation System

## 1. Overview

The system allows restaurant owners to configure their restaurants, seating layouts, opening hours, reservation rules, and staff accounts.

Customers can make reservations without registering for an account. They provide their contact details, select a reservation time and the available table(s), and receive email notifications related to their reservation.

Restaurant owners and staff can manage reservations through a restaurant dashboard, including changing reservation statuses and creating reservations for walk-in customers.

---

# 2. Core Entities

The system will contain the following main entities:

* Restaurant
* Restaurant Owner / Admin
* Staff Account
* Grid
* Table
* Schedule
* Holiday
* Reservation
* Customer / Reservation Contact

## 2.1 Restaurant

A Restaurant belongs to a Restaurant Owner.

A Restaurant can have:

* One Grid
* Multiple Tables
* One weekly Schedule
* Multiple Holidays
* Reservation configuration
* Multiple Staff Accounts
* Multiple Reservations

### Reservation configuration

Each Restaurant can configure:

* Reservation duration
* Booking interval
* Buffer time
* Optional maximum party size

### Example

A Restaurant could configure:

* Reservation duration: `60 minutes`
* Booking interval: `30 minutes`
* Buffer time: `15 minutes`

This would normally produce reservation slots such as:

* 18:00
* 18:30
* 19:00
* 19:30

The buffer time affects table availability but does not itself create additional reservation slots.

---

# 3. Restaurant Seating Layout

## 3.1 Grid

Each Restaurant has exactly one Grid.

The Grid represents the restaurant's seating area as a rectangular collection of points/cells.

The Grid is primarily a visual representation of the restaurant's physical layout.

It may contain:

* Tables
* Empty spaces
* Walkways
* Areas for customers and staff to move through

The entire Grid does not need to be filled with Tables.

## 3.2 Tables

Each Table belongs to a Restaurant and is positioned within its Grid.

A Table is represented by:

* A top-left point
* A bottom-right point
* A maximum capacity

The two points define the rectangular area occupied by the Table on the Grid.

For example:

```text
Top-left:     (2, 3)
Bottom-right: (4, 5)
Capacity:     6
```

The exact visual representation of the Table can be handled by JavaScript on the frontend.

### Restaurant owner table configuration

When configuring a restaurant, the owner/admin should be able to interact with the Grid using JavaScript to:

1. Select an area of the Grid.
2. Create a Table in that area.
3. Set the Table's maximum capacity.
4. View the resulting seating layout.

The Grid is a representation of the physical seating arrangement rather than a mechanism for calculating whether tables can physically be joined together.

---

# 4. Restaurant Schedule

Each Restaurant has a weekly schedule defining when it is open.

For each day of the week, the Restaurant can configure:

* Opening time
* Closing time
* Open/closed status

For example:

| Day       | Status | Opening | Closing |
| --------- | ------ | ------: | ------: |
| Monday    | Open   |   11:00 |   22:00 |
| Tuesday   | Open   |   11:00 |   22:00 |
| Wednesday | Closed |       — |       — |
| Thursday  | Open   |   11:00 |   22:00 |
| Friday    | Open   |   11:00 |   23:00 |
| Saturday  | Open   |   10:00 |   23:00 |
| Sunday    | Closed |       — |       — |

## 4.1 Holidays

Restaurants can configure specific dates on which they will be closed.

Holidays override the normal weekly schedule.

For example:

* Weekly schedule: Monday 11:00–22:00
* Holiday: December 25
* Result: Restaurant is closed on December 25 regardless of the weekday schedule.

---

# 5. Reservation Configuration

Each Restaurant can configure how reservations are generated.

## 5.1 Reservation Duration

The Restaurant defines how long a reservation occupies a Table.

Example:

```text
Reservation duration: 60 minutes
```

A reservation starting at 18:00 occupies its assigned Table until 19:00.

## 5.2 Booking Interval

The Restaurant defines the interval at which reservation start times are generated.

Possible examples:

* 5 minutes
* 15 minutes
* 30 minutes
* 60 minutes

For example, with a 30-minute interval:

```text
18:00
18:30
19:00
19:30
20:00
...
```

## 5.3 Buffer Time

The Restaurant can configure a buffer period that is added after a reservation.

Example:

```text
Reservation duration: 60 minutes
Buffer:              15 minutes
```

A reservation starting at 18:00 therefore blocks the Table until:

```text
19:15
```

However, the next available reservation **must still start on a configured booking interval**.

### Example: 30-minute booking interval

Available slots:

```text
18:00
18:30
19:00
19:30
20:00
```

Reservation:

```text
Start:     18:00
Duration:  60 minutes
Buffer:    15 minutes
Blocked:   until 19:15
```

The 19:00 slot is unavailable because it overlaps the blocked period.

The next available slot is therefore:

```text
19:30
```

### Example: 15-minute booking interval

Available slots:

```text
18:00
18:15
18:30
18:45
19:00
19:15
19:30
...
```

With the same 18:00 reservation and 15-minute buffer, the reservation blocks the Table until 19:15.

Therefore:

```text
19:15 → available
```

This approach keeps availability calculation aligned with the Restaurant's configured booking interval.

---

# 6. Customer Reservations

Customers do not need to create an account to make a reservation.

To create a reservation, the customer provides:

* Full name
* Phone number
* Email address
* Number of people

The customer then selects from the available reservation times and Tables.

## 6.1 Maximum Party Size

A Restaurant may optionally define a maximum party size.

For example:

```text
Maximum party size: 10
```

A customer cannot create a reservation exceeding this limit.

---

# 7. Reservation Availability

The backend is responsible for determining which reservation slots and Tables are available.

Availability depends on:

* Restaurant opening hours
* Holidays
* Reservation duration
* Booking interval
* Buffer time
* Existing reservations
* Table capacity
* Requested party size
* Restaurant maximum party size

The frontend should not be responsible for determining authoritative availability.

---

# 8. Table Selection Logic

The primary goal of the availability algorithm is to accommodate the requested number of people while minimizing unnecessary unused capacity.

The system should prefer using **one Table** whenever possible.

## 8.1 Exact Single-Table Match

If the customer requests a reservation for 6 people and the Restaurant has available 6-person Tables:

```text
Requested: 6

Available:
- Table A: 6
- Table B: 6
- Table C: 8
- Table D: 4
```

The system should return the suitable single-table options rather than presenting all available Tables.

The preferred result would be:

```text
Table A: 6
Table B: 6
```

## 8.2 Multiple Tables

If no single Table can accommodate the requested party, the system should allow multiple Tables to be selected.

Example:

```text
Requested: 6

Available:
- 4-person Table
- 2-person Table
```

The system should provide these Tables so that the customer can select:

```text
4 + 2 = 6
```

## 8.3 Avoiding Unused Capacity

If an exact combination is not available, the system should attempt to minimize unused seats.

Example:

```text
Requested: 6

Available:
- 4-person Tables
- 3-person Tables
- 2-person Tables
```

If no 2-person Table is available, the system could offer:

```text
3 + 3 = 6
```

and allow the customer to select two 3-person Tables.

## 8.4 No Perfect Combination

If no combination exactly matches the requested party size, the system should attempt to provide the smallest available total capacity that can accommodate the party.

Example:

```text
Requested: 5

Available:
- 4-person Table
- 2-person Table
```

The system should offer:

```text
4 + 2 = 6
```

This leaves one unused seat.

The system's goal is to avoid rejecting a reservation unnecessarily when the restaurant has sufficient total seating capacity.

## 8.5 Example: Only Larger Tables

Requested:

```text
6 people
```

Available:

```text
4-person Table
4-person Table
4-person Table
```

The system should return the available 4-person Tables and allow the customer to select two:

```text
4 + 4 = 8
```

The system does not need to guarantee that the selected Tables can physically be joined together.

---

# 9. Table Combination / Joining

The system does **not** guarantee that multiple Tables can physically be joined.

Whether Tables can be combined is ultimately a decision made by:

* The customer, when selecting Tables
* Restaurant staff, when seating the party

The availability system only determines capacity and reservation availability.

The system therefore treats multiple Tables as separate Tables that are simultaneously assigned to the same Reservation.

---

# 10. Reservation Creation

Once the customer selects a valid reservation time and Table(s), the backend creates the Reservation.

The Reservation contains:

* Restaurant
* Reservation date
* Reservation start time
* Number of people
* Assigned Table(s)
* Customer full name
* Customer phone number
* Customer email
* Reservation status

No payment is required initially.

Payment functionality may be added later.

---

# 11. Reservation Status

A Reservation can have one of the following statuses:

```text
pending
confirmed
seated
completed
cancelled
no_show
```

The exact status transitions should be defined separately as part of the reservation lifecycle.

For example:

```text
pending → confirmed → seated → completed
```

Alternative paths can include:

```text
confirmed → cancelled
confirmed → no_show
```

---

# 12. Concurrent Reservations / Double Booking Prevention

The backend must prevent two customers from successfully reserving the same Table for overlapping periods.

Availability shown to the customer is not sufficient on its own because two customers may attempt to reserve the same Table at approximately the same time.

Therefore, Table assignment must be validated again during Reservation creation.

### Example

Customer A sees Table 5 as available.

Customer B also sees Table 5 as available.

Customer A completes the reservation first.

When Customer B attempts to reserve Table 5, the backend must detect the conflict and reject the reservation.

The customer should receive an error indicating that the selected Table has already been booked and that they should select another available Table.

The frontend can then refresh availability and allow the customer to choose another Table.

This protection should be implemented at the backend/database level so that simultaneous requests cannot create conflicting reservations.

---

# 13. Customer Reservation Management

Customers do not need an account to manage their reservations.

The confirmation email will contain a secure link allowing the customer to:

* View their reservation
* Edit their reservation
* Cancel their reservation

The reservation-management link should be associated with a secure token rather than requiring customer authentication.

## 13.1 Cancellation

When a customer cancels their reservation:

1. The Reservation status changes to `cancelled`.
2. The associated Tables become available again according to the Restaurant's availability rules.
3. A cancellation email is sent to the customer.

---

# 14. No-Show Handling

If a customer does not arrive on time, Restaurant staff can manually mark the Reservation as:

```text
no_show
```

This is a manual process.

Once marked as a no-show, the Restaurant can make the affected Table(s) available for new customers.

The system does not automatically determine whether a customer has failed to arrive.

---

# 15. Restaurant Dashboard

Each Restaurant has a dashboard accessible to authorized accounts.

The dashboard is available to:

* Restaurant Owner / Admin
* Staff Accounts associated with the Restaurant

The dashboard provides a daily overview of reservations.

## 15.1 Reservation Timeline

The dashboard should display reservations according to:

* Date
* Time
* Table

A timeline-style interface should make it possible to see which Tables are occupied throughout the day.

For example:

```text
Table 1   | 12:00 █████ | 14:00
Table 2   | 12:30 █████████ | 15:00
Table 3   | 13:00 █████ | 14:00
```

The exact visual implementation can be handled separately.

## 15.2 Dashboard Actions

Authorized users should be able to:

* View reservations
* Change Reservation statuses
* Mark reservations as `no_show`
* Mark reservations as `seated`
* Mark reservations as `completed`
* Create reservations manually
* Create reservations for walk-in customers

Walk-in reservations should use the same underlying Reservation system as customer-created reservations.

---

# 16. Staff Accounts

A Restaurant can have multiple Staff Accounts.

Staff Accounts are associated with a specific Restaurant.

Staff members can access the Restaurant Dashboard and perform the actions permitted by their role.

The exact permission model can be defined separately.

At minimum, the system should distinguish between:

* Restaurant Owner / Admin
* Staff

---

# 17. Email Notifications

The system sends emails at several points in the Reservation lifecycle.

## 17.1 Reservation Created

After a Reservation is successfully created, the customer receives a confirmation email containing:

* Restaurant information
* Reservation date
* Reservation time
* Number of people
* Assigned Table(s), where appropriate
* Customer information
* Reservation management link

## 17.2 Reservation Cancelled

When a customer cancels a Reservation, the customer receives a cancellation email.

## 17.3 Reservation Reminder — Day Before

The customer receives a reminder email one day before the Reservation.

## 17.4 Reservation Reminder — One Hour Before

The customer receives another reminder email one hour before the Reservation.

## 17.5 Queued Emails

Emails should be dispatched through Laravel queues rather than being sent synchronously as part of the HTTP request.

This applies particularly to:

* Confirmation emails
* Cancellation emails
* Day-before reminders
* One-hour reminders

---

# 18. High-Level Reservation Flow

The overall customer flow is:

```text
1. Customer selects Restaurant
        ↓
2. Customer selects date
        ↓
3. System checks Restaurant schedule
        ↓
4. System checks holidays
        ↓
5. Customer selects reservation time
        ↓
6. System calculates available Tables
        ↓
7. Customer enters party size
        ↓
8. Backend determines suitable Table combinations
        ↓
9. Customer selects Table(s)
        ↓
10. Customer enters:
        - Full name
        - Phone number
        - Email
        ↓
11. Backend validates availability again
        ↓
12. Reservation is created
        ↓
13. Confirmation email is queued
```

---

# 19. High-Level Availability Flow

Availability calculation should follow approximately this process:

```text
Requested date/time
        ↓
Is Restaurant open?
        ↓
Is date a holiday?
        ↓
Is requested party size within Restaurant limit?
        ↓
Generate valid reservation slots
        ↓
Find Tables available for the requested slot
        ↓
Exclude Tables blocked by:
    - existing Reservation duration
    - buffer time
        ↓
Determine suitable Table combinations
        ↓
Prefer:
    1. One Table
    2. Exact capacity
    3. Smallest unused capacity
    4. Larger combinations when necessary
        ↓
Return available options
```

---

# 20. Backend Responsibilities

The backend is responsible for all authoritative business logic, including:

* Restaurant configuration
* Schedule validation
* Holiday validation
* Reservation slot generation
* Buffer-time calculations
* Table availability
* Party-size validation
* Table-combination logic
* Reservation creation
* Double-booking prevention
* Reservation status changes
* Cancellation
* No-show handling
* Staff authorization
* Customer reservation-management tokens
* Email dispatching

The frontend should present and interact with this information but should not be treated as the authoritative source for availability.

---

# 21. Frontend / JavaScript Responsibilities

JavaScript will primarily be used for interactive interfaces, including:

### Restaurant administration

* Interactive Grid
* Table creation and positioning
* Table selection
* Seating layout visualization

### Customer reservation flow

* Interactive Table selection
* Reservation selection UI
* Availability updates
* Appropriate feedback when a selected Table becomes unavailable

### Dashboard

* Reservation timeline
* Table/time visualization
* Interactive reservation management

---

# 22. Initial Scope / Explicit Non-Goals

The initial version does **not** require:

* Customer accounts
* Customer login
* Online payment
* Automatic no-show detection
* Guaranteed physical Table joining
* Automatic physical seating optimization beyond capacity matching

These can potentially be added in later versions.

---

# 23. Key Business Rules Summary

For implementation, the most important rules are:

1. **One Restaurant has exactly one Grid.**

2. **A Restaurant can have many Tables.**

3. **Each Table has a capacity and a rectangular position on the Grid.**

4. **Restaurants define opening and closing hours per weekday.**

5. **Restaurants can define specific holidays/closed dates.**

6. **Restaurants define a reservation duration.**

7. **Restaurants define a booking interval.**

8. **Restaurants can define a buffer period after reservations.**

9. **Buffer time blocks Tables, but does not change the configured booking interval.**

10. **Customers do not need an account to make reservations.**

11. **Customers must provide their name, phone number, and email.**

12. **Restaurants may optionally define a maximum party size.**

13. **The system should prefer one Table that can accommodate the entire party.**

14. **If one Table cannot accommodate the party, multiple Tables may be selected.**

15. **The system should minimize unused capacity when multiple Tables are required.**

16. **The system should attempt to accommodate a reservation rather than reject it simply because an exact capacity combination does not exist.**

17. **The system does not guarantee that multiple Tables can physically be joined.**

18. **The backend must perform a final availability check when creating a Reservation.**

19. **Concurrent requests must not result in the same Table being booked twice for overlapping periods.**

20. **Customers can edit or cancel reservations through a secure link received by email.**

21. **Staff can manually mark reservations as `no_show`.**

22. **Staff can create reservations for walk-in customers.**

23. **Reservation emails and reminders should be handled through Laravel queues.**

24. **The Reservation statuses are:**

* `pending`
* `confirmed`
* `seated`
* `completed`
* `cancelled`
* `no_show`
