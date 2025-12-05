# Exam Management (mt-exam)

WordPress admin plugin to manage exams, terms, subjects, students, and results.

## What it adds
- Custom Post Types: `em_student`, `em_subject`, `em_exam`, `em_result`.
- Taxonomy: `em_term` (academic terms) with start/end date meta.
- Exam meta box: start/end datetime and linked subject.
- Result meta box: pick an exam, then enter per-student marks (0–100).
- Custom admin page: “All Exams (Custom)” showing status (ongoing/upcoming/past) with CSV/PDF export (DataTables + Bootstrap).
- Bulk import page (Results → Import Results): CSV creates/updates students, subjects, terms, exams, and results.
- Student Reports page (top-level menu): per-student marks by term with PDF export (jsPDF).
- Shortcode `[em_top_students]`: shows top 3 students per term by total marks.

## Typical flow
1) Create subjects, students, and terms (with start/end dates).  
2) Create exams: set subject, term, and start/end datetime.  
3) Create results: choose an exam, enter marks for each student — or import via CSV.  
4) View “All Exams (Custom)” under Exams to see sortable/exportable list.  
5) Use “Student Reports” to review per-term marks and export PDF.  
6) Optional: place `[em_top_students]` in a page/post to show top performers by term.

## CSV import (Results → Import Results)
Expected columns (header row skipped):  
`student_name, exam_name, start_datetime, end_datetime, subject_name, term_name, marks`

- Creates missing students/subjects/terms/exams as needed.  
- Updates exam meta (start/end datetime, subject, term).  
- Creates or updates `em_result` with marks keyed by student ID.
 