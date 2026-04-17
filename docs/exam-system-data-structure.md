# MusicExams Help – Data Structure (Refactor)

## Core Principle

> Store what is known.  
> Allow unknown relationships.  
> Confirm teacher credit later.

---

## 1. exam_contacts

All people involved in the system.

### Fields

- id
- name
- email (nullable)
- phone (nullable)
- role (nullable)
- source (nullable)
- notes (nullable)
- user_id (nullable)
- created_at
- updated_at

### Roles

- teacher
- parent
- self
- school_admin
- applicant
- submitter
- unknown

### Notes

- This replaces `teachers` as the main people table
- Role is a soft label / import hint, not the full truth
- Real contextual truth belongs in relationship tables like `order_contacts`

---

## 2. candidates (students)

Represents the person taking the exam.

### Fields

- id
- first_name
- last_name
- full_name_cached (nullable)
- email (nullable)
- instrument_id (nullable)
- notes (nullable)
- teacher_contact_id (nullable)
- teacher_credit_status (default: unknown)
- created_at
- updated_at
- deleted_at (nullable)

### Teacher Credit Status

- unknown
- requested
- confirmed
- not_applicable

### Notes

- `teacher_contact_id` is allowed to be null
- Teacher credit may be unknown at import time and confirmed later

---

## 3. orders (enrolments)

Represents a Trinity order/enrolment.

### Current implemented fields

- id
- trinity_order_number (unique)
- requested_start_date (nullable)
- delivery_method (nullable)
- subject_area (nullable)
- candidates (nullable)
- venue (nullable)
- order_status (nullable)
- applicant_name (nullable)
- applicant_email (nullable)
- created_by_contact_id (nullable)
- notes (nullable)
- created_at
- updated_at
- deleted_at (nullable)

### Notes

- `created_by_contact_id` links to the primary known applicant/contact
- `venue` currently remains raw text
- applicant fields are currently stored directly on the order as imported values
- a future refinement may rename some fields to raw-style names such as `venue_name`, `applicant_name_raw`, `applicant_email_raw`

---

## 4. exam_entries

One row per candidate exam entry.

### Target fields

- id
- order_id
- candidate_id (nullable)
- candidate_number (nullable)
- candidate_name_raw (nullable)
- instrument_id (nullable)
- grade (nullable)
- subject_area (nullable)
- delivery_method (nullable)
- fee (nullable)
- score (nullable)
- result (nullable)
- exam_date (nullable)
- teacher_contact_id (nullable)
- teacher_name_raw (nullable)
- teacher_credit_status (default: unknown)
- school_name_raw (nullable)
- notes (nullable)
- show_on_thank_you (boolean)
- show_full_name (boolean)
- source (nullable)
- created_at
- updated_at
- deleted_at (nullable)

### Notes

- Raw imported clues must be preserved
- Relationships should be added later, not guessed too early

---

## 5. order_contacts

Links contacts to orders.

### Fields

- id
- order_id
- exam_contact_id
- role_in_order
- is_primary (boolean)
- notes (nullable)
- created_at
- updated_at

### role_in_order

- applicant
- submitter
- teacher
- school_admin
- parent
- self
- other

### Notes

- This is where contextual truth lives
- The same contact can have different roles on different orders
- The same contact can potentially have more than one role across the system

---

## 6. candidate_contacts (optional)

Links candidates to contacts.

### Fields

- id
- candidate_id
- exam_contact_id
- relationship_type
- is_primary (boolean)
- notes (nullable)
- created_at
- updated_at

### relationship_type

- parent
- teacher
- applicant
- self
- guardian
- other

### Notes

- This is planned for later
- It will allow correct candidate-specific relationships without guessing from order data alone

---

## 7. schools

### Fields

- id
- name
- notes (nullable)
- created_at
- updated_at
- deleted_at (nullable)

---

## 8. venues

### Fields

- id
- name
- school_id (nullable)
- notes (nullable)
- created_at
- updated_at
- deleted_at (nullable)

### Notes

- Venue ≠ School
- Same name does not mean same entity

---

## Data Trust Rules

### Safe to trust

- order number
- requested date
- delivery method
- candidate count
- candidate number
- candidate name
- result
- score
- grade
- subject area

### Not safe to trust

- teacher relationships
- applicant = teacher assumptions
- school = venue assumptions
- old database relationships

---

## Data Sources

### TOL Orders CSV

Used for:

- full 2026 order skeleton
- requested start date
- delivery method
- subject area
- candidate count
- venue
- order status

### TOL Candidate Exports

Used for:

- exam_entries raw fields
- candidate-level details when available

### Old Database / Legacy Source

Used for:

- legacy Q1 orders
- results
- scores
- candidate numbers
- already captured applicant details
- teacher-name clues from exam entries

### Manual Trinity Review

Used for:

- filling missing applicant details where exports do not expose them
- correcting known gaps without guessing

---

## Import Strategy

### Phase 1

- Import contacts from legacy/source data
- Import legacy Q1 orders
- Import 2026 orders from TOL CSV
- Create applicant and teacher-name contacts provisionally

### Phase 2

- Link known applicant contacts to orders through `order_contacts`
- Set `created_by_contact_id` where the applicant is known
- Leave applicants blank where Trinity data is incomplete or the order is only a setup placeholder

### Phase 3

- Import exam entries
- Link entries to candidates
- Link teacher contacts to orders where justified
- Confirm teacher credit via app workflow

---

## Key Insight

> Trinity does NOT provide a clean teacher relationship.  
> This system is responsible for resolving that.

---

## Current Known Reality

### 2026 orders

- 26 known 2026 orders are currently imported into the refactor system
- 21 came from the legacy/source data
- 5 additional 2026 orders came from the newer TOL CSV export

### Applicant coverage

- 24 orders currently have known applicants
- 2 orders are honestly still unknown
- those unknown orders are likely setup / placeholder face-to-face July orders not yet filled in by applicants

### Important rule

- Unknown is a valid state
- Unknown should be preserved instead of guessed

---

## Architecture Summary

exam_contacts
↑
order_contacts → orders → exam_entries ← candidates
↓
created_by_contact_id

candidates → teacher_contact_id (nullable)

schools
venues

---

## Final Notes

- The `users` table is only for authentication
- `exam_contacts` is the real people system
- Teacher assignment must remain flexible
- Raw imported data must always be preserved
- Contextual roles belong in linking tables, not a single role field