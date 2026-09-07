INSERT INTO courses (course_name) VALUES
('Full-Stack Web Dev'),
('UI/UX Design'),
('Python Automation'),
('Data Science'),
('Cyber Security'),
('Mobile App Dev'),
('Digital Marketing'),
('Cloud DevOps'),
('AI Engineering'),
('Game Development');

INSERT INTO students (campus_id, name, city, department, cgpa, semester, password_hash) VALUES
(2110101, 'Sakib Imam Khan', 'Dhaka', 'Computer Science', 3.85, 'Fall 2026', '$2y$10$Hs11qP1NRbKGUlHuSXVWr.8lrnxl9kev2xRotPnUdYivL8c1sDdzq'),
(2110102, 'Tahsina Mamun Niha', 'Chattogram', 'Computer Science', 3.92, 'Fall 2026', '$2y$10$Hs11qP1NRbKGUlHuSXVWr.8lrnxl9kev2xRotPnUdYivL8c1sDdzq'),
(2110103, 'Raisa Jarin Islam', 'Sylhet', 'Electrical Engineering', 3.70, 'Summer 2026', '$2y$10$Hs11qP1NRbKGUlHuSXVWr.8lrnxl9kev2xRotPnUdYivL8c1sDdzq'),
(2110104, 'Mekdad Hossain Abir', 'Dhaka', 'Business Administration', 3.55, 'Fall 2026', '$2y$10$Hs11qP1NRbKGUlHuSXVWr.8lrnxl9kev2xRotPnUdYivL8c1sDdzq'),
(2210201, 'Arif Hasan', 'Rajshahi', 'Computer Science', 3.40, 'Spring 2026', '$2y$10$Hs11qP1NRbKGUlHuSXVWr.8lrnxl9kev2xRotPnUdYivL8c1sDdzq'),
(2210202, 'Farhana Akter', 'Khulna', 'Computer Science', 3.66, 'Spring 2026', '$2y$10$Hs11qP1NRbKGUlHuSXVWr.8lrnxl9kev2xRotPnUdYivL8c1sDdzq'),
(2210203, 'Tanvir Ahmed', 'Dhaka', 'Computer Science', 3.21, 'Spring 2026', '$2y$10$Hs11qP1NRbKGUlHuSXVWr.8lrnxl9kev2xRotPnUdYivL8c1sDdzq'),
(2210204, 'Sumaiya Rahman', 'Barishal', 'Electronics', 3.78, 'Spring 2026', '$2y$10$Hs11qP1NRbKGUlHuSXVWr.8lrnxl9kev2xRotPnUdYivL8c1sDdzq'),
(2310301, 'Rifat Chowdhury', 'Dhaka', 'Computer Science', 3.10, 'Fall 2026', '$2y$10$Hs11qP1NRbKGUlHuSXVWr.8lrnxl9kev2xRotPnUdYivL8c1sDdzq'),
(2310302, 'Ayesha Siddiqua', 'Cumilla', 'Architecture', 3.88, 'Fall 2026', '$2y$10$Hs11qP1NRbKGUlHuSXVWr.8lrnxl9kev2xRotPnUdYivL8c1sDdzq');

INSERT INTO teachers (campus_id, name, teaching_skill, semester, contact_number, qualification) VALUES
(2110101, 'Sakib Imam Khan', 'Full-Stack Web Dev', 'Fall 2026', '+8801711000101', 'Meta Front-End Certified'),
(2110102, 'Tahsina Mamun Niha', 'Data Science', 'Fall 2026', '+8801711000102', 'IBM Data Science Professional'),
(2110103, 'Raisa Jarin Islam', 'UI/UX Design', 'Summer 2026', '+8801711000103', 'Adobe Certified Professional'),
(2110104, 'Mekdad Hossain Abir', 'Digital Marketing', 'Fall 2026', '+8801711000104', 'Meta Blueprint Certified');

INSERT INTO sections (section_name, course_id, teacher_campus_id, schedule_time, estimated_learning_days, number_of_projects) VALUES
('Sec-01', 1, 2110101, 'Sun & Tue - 08:00 AM', 45, 3),
('Sec-02', 1, 2110101, 'Mon & Wed - 06:00 PM', 45, 3),
('Sec-01', 4, 2110102, 'Sun & Thu - 11:30 AM', 60, 4),
('Sec-02', 4, 2110102, 'Fri - 09:00 AM', 60, 5),
('Section Alpha', 2, 2110103, 'Tue & Thu - 02:00 PM', 30, 2),
('Section Beta', 2, 2110103, 'Sat - 10:00 AM', 30, 2),
('Sec-01', 7, 2110104, 'Mon & Wed - 04:00 PM', 25, 1);

INSERT INTO registrations (student_campus_id, section_id) VALUES
(2210201, 1),
(2210202, 1),
(2210203, 1),
(2110102, 1),
(2310301, 2),
(2210202, 3),
(2210204, 3),
(2110103, 3),
(2210201, 5),
(2310301, 5),
(2110101, 5),
(2310302, 6),
(2210204, 7);

INSERT INTO completed_courses (student_campus_id, section_id, skill_learnt, completion_date) VALUES
(2210201, 7, 'Digital Marketing', '2026-05-20'),
(2210203, 7, 'Digital Marketing', '2026-05-20'),
(2310301, 7, 'Digital Marketing', '2026-05-20'),
(2210202, 6, 'UI/UX Design', '2026-04-15'),
(2210204, 5, 'UI/UX Design', '2026-03-10'),
(2110104, 1, 'Full-Stack Web Dev', '2026-02-28'),
(2110101, 3, 'Data Science', '2026-01-30');
