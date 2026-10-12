# DriveX — Vehicle Rental Management System

A database-backed web application for managing vehicle rentals through separate **administrator** and **customer** interfaces. The project combines a PHP application with relational database design documentation.

Developed as an academic project for **CSE370**, Group 11.

## Features

### Customer interface

- Account registration, sign-in, and sign-out.
- Vehicle search and booking for selected dates.
- Checks for overlapping bookings.
- Optional selection of an available driver.
- Booking history and payment-status views.
- Profile, contact details, and password updates.
- Dashboard summaries for bookings, payments, and penalties.

### Administrator interface

- Dashboard summaries for vehicles, customers, bookings, revenue, and payments.
- Vehicle and driver management.
- Booking-status and payment-status management.
- Maintenance records and service dates.
- Penalty records associated with bookings.
- Customer records and rental information.

Payments are recorded and tracked in the application; a live payment gateway is not included.

## Technologies

- **Application:** PHP
- **Database:** MySQL, accessed through PDO
- **Interface:** HTML and CSS
- **Authentication:** PHP sessions with role checks
- **Database design:** EER diagram and normalized schema documentation

## Included files

| Path or file | Purpose |
| --- | --- |
| `vehicle_rental/index.php` | Landing page, login, and registration |
| `vehicle_rental/admin/` | Administrator pages |
| `vehicle_rental/customer/` | Customer pages |
| `vehicle_rental/includes/` | Database connection, authentication helpers, and navigation |
| `vehicle_rental/css/style.css` | Shared styles |
| `vehicle_rental/logout.php` | Session logout |
| `CSE370 Group 11 (1).pdf` | Project report |
| `Normalized Schema.pdf` | Database schema documentation |
| `EER diagram.excalidraw` | Editable database diagram |

The archive's outer folder is only a packaging folder. The inner `vehicle_rental/` folder contains the runnable PHP application. Keep its internal structure intact.

## Local setup

### 1. Prepare a PHP and MySQL environment

Use a local development stack such as XAMPP, or another environment with a PHP web server, MySQL, and the PDO MySQL extension enabled.

### 2. Place the application in the web root

With XAMPP on Windows, copy the inner application folder to:

```text
C:\xampp\htdocs\vehicle_rental\
```

The application includes absolute links beginning with `/vehicle_rental/`. Keep this folder name and URL path unless you also update those links and redirects.

### 3. Set up the database

Create a database named `vehicle_rental`.

**An SQL import/setup script is not included in the supplied archive.** Obtain the original database export from the project team, or recreate the tables from the normalized schema and EER diagram before running the application.

The code references tables including `User`, `Admin`, `Customer`, `User_phone`, `Vehicle`, `Vehicle_category`, `Driver`, `Driver_phone`, `Booking`, `Payment`, `Maintenance`, and `Penalty`. These names are a guide to the application's dependencies, not a replacement for the complete schema and constraints.

An administrator must be represented by a user record associated with the `Admin` table. No verified default administrator credentials are supplied. Booking creation also expects an administrator record and appropriate vehicle/category data.

### 4. Configure the connection

Edit `vehicle_rental/includes/db.php` to match your local environment:

```php
$host = 'localhost';
$dbname = 'vehicle_rental';
$username = 'YOUR_DATABASE_USERNAME';
$password = 'YOUR_DATABASE_PASSWORD';
```

Keep real database credentials out of a public repository.

### 5. Start the application

Start Apache and MySQL, then open:

```text
http://localhost/vehicle_rental/
```

After the schema and initial records are ready, register a customer account or sign in with an existing project account.

## Database design

The project materials include an editable EER diagram and a normalized schema PDF. They document the relationships between users, rental roles, vehicles, categories, drivers, bookings, payments, maintenance, and penalties.

Preserve the original design documents alongside the source code so readers can understand how the application maps its rental workflow to relational tables.

## Scope and limitations

This is an academic local-development prototype. The supplied login and registration code uses plaintext password storage/comparison. Password hashing and a broader security review are required before handling real accounts or deploying publicly.

The uploaded materials do not include a database export, a verified installation environment, or live payment processing. End-to-end execution requires the original database or a faithful schema reconstruction.

## Academic context

Created for CSE370, Group 11. The accompanying report contains the original project documentation and team attribution.

