CREATE TRIGGER after_student_removal
AFTER DELETE ON registrations
FOR EACH ROW
BEGIN
    INSERT INTO removal_logs (student_campus_id, section_id, removed_at)
    VALUES (OLD.student_campus_id, OLD.section_id, NOW());
END$$