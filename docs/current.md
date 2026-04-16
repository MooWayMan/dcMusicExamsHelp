# MusicExams Help – Current System State

## Core Principle

> Store what is known.  
> Do not guess relationships.  
> Unknown is valid.

---

## Current Data State

### Orders

- 26 total 2026 orders
  - 21 from legacy data
  - 5 from TOL CSV
- 24 have known applicants
- 2 remain unknown and are correctly left blank

### Exam Entries

- 73 exam entries imported
- Includes:
  - candidate names
  - candidate numbers
  - results
  - scores
  - `teacher_name` as raw text
  - `school_name` as raw text

### Students

- 73 students created
- 73 exam entries linked to students
- Students were created from `candidate_name`

### Teacher Linking

- 24 orders linked to teachers via `order_contacts`
- Based on exact name matching only
- No ambiguous matches accepted
- 14 entries have no teacher name and were left untouched

## Relationships

orders → exam_entries → students  
orders → order_contacts → exam_contacts

## Known Limitations

- `exam_entries` still use raw:
  - `teacher_name`
  - `school_name`
- `students` still use:
  - `user_id` as a temporary teacher link in the older/current model
- no candidate ↔ contact relationship layer yet
- teacher credit not yet confirmed

## Data Trust Rules

### Safe

- order number
- candidate name
- candidate number
- results
- scores
- grade
- subject
- delivery method

### Unsafe

- teacher relationships
- applicant = teacher assumptions
- school = venue assumptions
- legacy joins

## Key Reality

Trinity does **not** provide reliable teacher relationships.

This system must resolve that carefully.

## What Has Been Done

### Imports completed

- contacts imported
- orders imported from legacy/source
- additional 2026 orders imported from TOL CSV
- exam entries imported
- students created from exam entries

### Linking completed

- applicants linked where known
- `created_by_contact_id` set where justified
- teacher links created on orders through `order_contacts`
- exam entries linked to students

### UI completed

- Contacts page showing new contact data
- Orders page showing imported 2026 orders
- Exam Entries page added
- Students page populated
- Students → Exam Entries navigation working
- Teacher shown on Students page via derived relationship

## Current Counts

- Orders: 26
- Exam entries: 73
- Students: 73
- Orders with known applicants: 24
- Orders with unknown applicants: 2
- Orders with teacher links: 24

## Immediate Next Moves

1. Add `teacher_contact_id` to `exam_entries`
2. Move student teacher relationship away from `user_id` toward contact-based linking
3. Introduce `candidate_contacts`
4. Make school relationships relational where safe
5. Add tests for new linking commands
6. Keep improving admin navigation and click-throughs