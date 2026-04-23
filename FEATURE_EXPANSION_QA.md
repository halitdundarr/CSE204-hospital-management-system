# Feature Expansion QA Checklist

## Requirement Mapping

- Track bill payment method (`Cash`, `CreditCard`, `Bitcoin`, `Other`):
  - `database_setup.sql` (`Bill.Payment_Method`, `Bill.Paid_At`, `Bill.Payment_Reference`)
  - `doctor/process_add_bill.php`
  - `doctor/manage_patient.php`
  - `patient/view_bills.php`
  - `admin/view_bills.php`
- Support additional staff types helping patients:
  - `database_setup.sql` (`Secretary`, `Translator`, `Appointment_Support_Staff`)
  - `admin/add_secretary.php`, `admin/process_add_secretary.php`
  - `admin/add_translator.php`, `admin/process_add_translator.php`
  - `admin/manage_support_staff.php`, `admin/process_assign_support_staff.php`
- UI requests:
  1. Doctors seen by patient: `admin/find_patient_doctors.php` (kept and integrated with new admin menu)
  2. Patients + appointment count in past year: `admin/view_all_patients_appointments.php`
  3. Change appointment date: `patient/edit_appointment.php`, `patient/process_edit_appointment.php`, `admin/edit_appointment.php`, `admin/process_edit_appointment.php`
  4. Add test for patient: `doctor/manage_patient.php`, `doctor/process_assign_test.php`
  5. Remove appointment: `admin/manage_appointments.php`, `admin/process_delete_appointment.php`

## Manual Smoke Test

- Admin:
  - Open `admin/view_bills.php`, verify new payment columns are visible.
  - Create secretary and translator records from new pages.
  - Assign support staff to an appointment from `admin/manage_support_staff.php`.
  - Open `admin/view_all_patients_appointments.php` and verify appointment count appears per patient.
  - Edit an appointment date/time and confirm success feedback.
  - Delete an appointment and verify clear error when constrained.
- Doctor:
  - Open `doctor/manage_patient.php`, create/update bill with `Status=Paid`, choose method.
  - Verify `Payment Method` becomes required only for paid bills.
  - Assign a new test to patient and confirm pending result appears.
- Patient:
  - Open `patient/view_bills.php` and confirm payment method/paid date/reference visibility.
  - Reschedule an eligible appointment and verify updated date/time in appointment list.

## Docker Detailed Test Flow

- Prerequisite:
  - Docker Desktop (or Docker Engine + Compose) is running.
- Execute full test flow:
  - `./scripts/docker_feature_tests.sh`
- What this script does:
  - Resets containers and DB volume (`docker compose down -v`).
  - Rebuilds and starts containers.
  - Waits for MariaDB readiness.
  - Runs `tests/sql/feature_expansion_checks.sql` inside DB container.
  - Confirms web app root endpoint returns HTTP `200`.
  - Prints `docker compose ps` summary.

## SQL Check Coverage

- `tests/sql/feature_expansion_checks.sql` validates:
  - `Bill` payment columns exist (`Payment_Method`, `Paid_At`, `Payment_Reference`).
  - `Bill.Patient_ID` is removed (patient comes from `Appointment`).
  - `Clinic.Admin_ID` exists and `Doctor/Nurse.Admin_ID` columns are removed.
  - Medication is represented in `Medical_Treatment` (`Treatment_Type='Medication'`).
  - Legacy `Appointment_Medicine` table is removed.
  - New minimum-scope staff tables exist (`Secretary`, `Translator`, `Appointment_Support_Staff`).
  - Seed data includes at least one paid bill with payment method.
  - Last-year appointment count query structure works across patient set.

## CI Integration (GitHub Actions)

- Workflow file:
  - `.github/workflows/docker-feature-tests.yml`
- Trigger:
  - `pull_request`
  - `push` to `main` / `master`
  - manual run (`workflow_dispatch`)
- Pipeline flow:
  - checkout code
  - run `./scripts/docker_feature_tests.sh`
  - collect docker logs on failure
  - always perform `docker compose down -v --remove-orphans` cleanup

## Notes

- `php -l` runtime syntax checks could not be executed in this environment because `php` binary is unavailable.
- IDE lint diagnostics returned no issues on edited files.
