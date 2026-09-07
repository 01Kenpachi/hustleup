1. CREATE OR REPLACE SQL SECURITY INVOKER VIEW v_section_overview AS
SELECT
    se.section_id AS section_id,
    se.section_name AS section_name,
    se.schedule_time AS schedule_time,
    se.estimated_learning_days AS estimated_learning_days,
    se.number_of_projects AS number_of_projects,
    c.course_id AS course_id,
    c.course_name AS course_name,
    t.campus_id AS teacher_campus_id,
    t.name AS teacher_name,
    t.qualification AS qualification,
    t.contact_number AS contact_number,
    COUNT(r.registration_id) AS enrolled_count,
    (SELECT COUNT(*) FROM completed_courses cc WHERE cc.section_id = se.section_id) AS graduate_count
FROM sections se
INNER JOIN courses c ON c.course_id = se.course_id
INNER JOIN teachers t ON t.campus_id = se.teacher_campus_id
LEFT JOIN registrations r ON r.section_id = se.section_id
GROUP BY
    se.section_id,
    se.section_name,
    se.schedule_time,
    se.estimated_learning_days,
    se.number_of_projects,
    c.course_id,
    c.course_name,
    t.campus_id,
    t.name,
    t.qualification,
    t.contact_number;

2. CREATE VIEW v_struggling_students AS
SELECT 
    s.name AS student_name,
    s.cgpa,
    c.course_name AS course_learning,
    t.name AS teacher_name,
    se.section_name AS section_name
FROM students s
INNER JOIN registrations r ON s.campus_id = r.student_campus_id
INNER JOIN sections se ON r.section_id = se.section_id
INNER JOIN courses c ON se.course_id = c.course_id
INNER JOIN teachers t ON se.teacher_campus_id = t.campus_id
WHERE s.cgpa < 3.0
ORDER BY s.cgpa ASC;