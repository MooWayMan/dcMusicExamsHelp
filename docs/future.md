# MusicExams Help – Target Data Architecture

## Core Principle

> Separate raw imported data from confirmed relationships.

---

## Target Model

### exam_contacts

All people in the system.

#### Fields

- id
- name
- email (nullable)
- phone (nullable)
- source (nullable)
- notes (nullable)
- user_id (nullable)
- created_at
- updated_at

#### Notes

- Central people table
- Replaces dependency on a teacher-only model
- Contextual meaning belongs in relationship tables

### orders

#### Fields

- id
- trinity_order_number
- requested_start_date
- delivery_method
- subject_area
- candidates
- venue_name
- order_status
- applicant_name_raw
- applicant_email_raw
- created_by_contact_id
- notes
- created_at
- updated_at
- deleted_at

### order_contacts

Contextual roles per order.

#### Fields

- order_id
- exam_contact_id
- role_in_order
- is_primary
- notes
- created_at
- updated_at

### students

#### Fields

- id
- first_name
- last_name
- full_name_cached
- email
- instrument_id
- notes
- teacher_contact_id
- teacher_credit_status
- created_at
- updated_at
- deleted_at

### exam_entries

#### Fields

- id
- order_id
- student_id
- candidate_name_raw
- candidate_number
- instrument_id
- grade
- subject_area
- delivery_method
- fee
- score
- result
- exam_date
- teacher_contact_id
- teacher_name_raw
- school_id
- school_name_raw
- teacher_credit_status
- notes
- show_on_thank_you
- show_full_name
- source
- created_at
- updated_at
- deleted_at

### candidate_contacts

Candidate ↔ contact relationships.

#### Fields

- candidate_id
- exam_contact_id
- relationship_type
- is_primary
- notes
- created_at
- updated_at

### schools

#### Fields

- id
- name
- notes
- created_at
- updated_at
- deleted_at

### venues

#### Fields

- id
- name
- school_id
- notes
- created_at
- updated_at
- deleted_at

## Key Differences From Current

- No reliance on `users` for teacher relationships
- All people handled via `exam_contacts`
- Raw fields preserved alongside relational fields
- Relationships confirmed, not assumed

## System Logic

- Import raw data
- Store raw data safely
- Create contacts without over-claiming truth
- Link where certain
- Leave unknown where not certain
- Confirm via workflow later

## Future Capabilities

- Teacher dashboards
- Commission tracking
- Parent relationships
- Student history
- School analytics

## Target Architecture

exam_contacts  
↑  
order_contacts → orders → exam_entries ← students  
↓  
created_by_contact_id  

students ↔ candidate_contacts ↔ exam_contacts

schools  
venues