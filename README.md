# Hospital Management System Demo

This repository contains a role-based hospital management demo built with PHP and MySQL. The project was prepared as a practical database-focused coursework app and includes patient, doctor, and admin workflows.

## What This Project Covers

- Multi-role login (patient, doctor, admin)
- Patient registration
- Appointment booking, edit, and cancellation flows
- Doctor-side appointment follow-up and medical record entries
- Admin-side staff and appointment management
- Relational schema with normalized core entities and junction tables

## Stack

- PHP (server-side rendering)
- MySQL / MariaDB
- HTML, CSS, vanilla JavaScript
- AMPPS (Apache + MySQL) for local runtime

## Main Modules

### Patient

- View dashboard and appointment history
- Book standard or follow-up appointments
- Edit or cancel upcoming appointments
- View diagnoses, tests, and prescriptions

### Doctor

- Review upcoming appointments
- Open appointment details
- Add diagnosis, treatment, test assignments, and medicine records

### Admin

- Add doctor, nurse, and patient records
- Review all appointments
- Edit or remove scheduled appointments
- Query doctors linked to a patient

## Quick Start (AMPPS on macOS)

1. Start Apache and MySQL from AMPPS.
2. Place this project under AMPPS web root or create a symlink:

```bash
ln -sfn /absolute/path/to/hospital-management-system-demo /Applications/AMPPS/www/hospital-management-system-demo
```

3. Create database `Hospital_3NF` in phpMyAdmin.
4. Import `database_setup.sql` into `Hospital_3NF`.
5. Verify DB config in `includes/db_connect.php`.
6. Open:

```text
http://localhost/hospital-management-system-demo/
```

## Database Notes

- The application expects database name: `Hospital_3NF`
- The SQL file creates all required tables and constraints
- Seed data includes sample users for each role

## Sample Login Credentials

- Admin
  - Email: `admin@email.com`
  - Password: `admin123`
- Doctor
  - Email: `halit@email.com`
  - Password: `halit123`
- Patient
  - ID: `43543543565`
  - Password: `murat123`

## Project Structure (High Level)

- `admin/` admin panel pages and actions
- `doctor/` doctor panel pages and actions
- `patient/` patient panel pages and actions
- `includes/` shared DB and helper code
- `css/` shared styles
- `database_setup.sql` schema and seed script

## Important Security Notice

This is an educational/demo codebase.

- Passwords are stored as plain text in seed/app flow.
- There is no production-grade auth hardening.

Do not deploy this project as-is to a public server. For production use, implement password hashing, stricter validation, CSRF protection, role-safe authorization checks, and secure session handling.
