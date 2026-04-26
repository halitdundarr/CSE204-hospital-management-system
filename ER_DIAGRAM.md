# Hospital Management System - ER Diagram (Mermaid)

Sunumda kullanmak icin bu kodu Markdown destekleyen bir editore yapistirarak diyagrami dogrudan goruntuleyebilirsin.

```mermaid
erDiagram
    Admin {
        int Admin_ID PK
        varchar Admin_Name
        varchar Admin_Email UK
    }

    Clinic {
        int Clinic_ID PK
        int Admin_ID FK
        varchar Clinic_Name
    }

    Doctor {
        int Doctor_ID PK
        int Clinic_ID FK
        varchar Doctor_Email UK
    }

    Nurse {
        int Nurse_ID PK
        int Clinic_ID FK
        varchar Nurse_Email UK
    }

    Patient {
        int Patient_ID PK
        varchar Patient_Name
    }

    Appointment {
        int Appointment_ID PK
        int Patient_ID FK
        int Doctor_ID FK
        int Nurse_ID FK
        int Follow_Up_Appointment_ID FK
        varchar Status
        datetime Appointment_Date
    }

    Diagnosis {
        int Diagnosis_ID PK
        varchar Diagnosis_Name
    }

    Appointment_Diagnosis {
        int Appointment_ID PK, FK
        int Diagnosis_ID PK, FK
    }

    Test {
        int Test_ID PK
        varchar Test_Name
    }

    Appointment_Test {
        int Appointment_ID PK, FK
        int Test_ID PK, FK
        text Test_Result
    }

    Medical_Treatment {
        int Medical_Treatment_ID PK
        enum Treatment_Type
        varchar Treatment_Name
    }

    Appointment_Treatment {
        int Appointment_ID PK, FK
        int Medical_Treatment_ID PK, FK
        varchar Dosage
    }

    Bill {
        int Bill_ID PK
        int Appointment_ID FK, UK
        decimal Total_Amount
        enum Status
    }

    Secretary {
        int Secretary_ID PK
        int Clinic_ID FK
        int Admin_ID FK
        varchar Secretary_Email UK
    }

    Translator {
        int Translator_ID PK
        int Clinic_ID FK
        int Admin_ID FK
        varchar Translator_Email UK
    }

    Appointment_Support_Staff {
        int Assignment_ID PK
        int Appointment_ID FK
        enum Staff_Type
        int Staff_ID
    }

    Admin ||--o{ Clinic : manages
    Clinic ||--o{ Doctor : has
    Clinic ||--o{ Nurse : has
    Clinic ||--o{ Secretary : has
    Clinic ||--o{ Translator : has

    Admin ||--o{ Secretary : assigns
    Admin ||--o{ Translator : assigns

    Patient ||--o{ Appointment : books
    Doctor ||--o{ Appointment : handles
    Nurse o|--o{ Appointment : assists
    Appointment o|--o{ Appointment : follow_up

    Appointment ||--o{ Appointment_Diagnosis : includes
    Diagnosis ||--o{ Appointment_Diagnosis : referenced_by

    Appointment ||--o{ Appointment_Test : includes
    Test ||--o{ Appointment_Test : referenced_by

    Appointment ||--o{ Appointment_Treatment : includes
    Medical_Treatment ||--o{ Appointment_Treatment : referenced_by

    Appointment ||--o| Bill : billed_as

    Appointment ||--o{ Appointment_Support_Staff : has_assignment
```

## Notes

- `Appointment_Support_Staff` tablosu polimorfik bir baglanti kullanir (`Staff_Type` + `Staff_ID`), yani `Staff_ID` dogrudan `Secretary` veya `Translator` tablosuna FK ile bagli degildir.
- `Appointment -> Bill` iliskisi pratikte `Appointment_ID` uzerindeki unique kisiti nedeniyle 1:0..1 seklindedir.
- `Appointment` tablosunda self-reference (`Follow_Up_Appointment_ID`) ile takip randevusu zinciri modellenir.
- Recete kavrami uygulamada `Appointment_Treatment + Medical_Treatment(Treatment_Type='Medication')` uzerinden turetilir; ayri bir `Prescription` tablosu yoktur.
