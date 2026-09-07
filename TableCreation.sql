CREATE TABLE students (
    campus_id INT NOT NULL,
    name VARCHAR(60) NOT NULL,
    city VARCHAR(40) NOT NULL,
    department VARCHAR(60) NOT NULL,
    cgpa DECIMAL(3, 2) NOT NULL DEFAULT 0.00,
    semester VARCHAR(20) NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    PRIMARY KEY (campus_id),
    CONSTRAINT chk_students_id CHECK (campus_id > 0),
    CONSTRAINT chk_students_cgpa CHECK (cgpa >= 0.00 AND cgpa <= 4.00)
) ENGINE = InnoDB;

CREATE TABLE courses (
    course_id INT NOT NULL AUTO_INCREMENT,
    course_name VARCHAR(80) NOT NULL,
    PRIMARY KEY (course_id),
    UNIQUE KEY uq_courses_name (course_name)
) ENGINE = InnoDB;

CREATE TABLE teachers (
    teacher_id INT NOT NULL AUTO_INCREMENT,
    campus_id INT NOT NULL,
    name VARCHAR(60) NOT NULL,
    teaching_skill VARCHAR(80) NOT NULL,
    semester VARCHAR(20) NOT NULL,
    contact_number VARCHAR(20) NOT NULL,
    qualification VARCHAR(80) NOT NULL,
    PRIMARY KEY (teacher_id),
    UNIQUE KEY uq_teachers_campus (campus_id),
    CONSTRAINT fk_teachers_student FOREIGN KEY (campus_id)
        REFERENCES students (campus_id)
        ON DELETE CASCADE
        ON UPDATE CASCADE
) ENGINE = InnoDB;

CREATE TABLE sections (
    section_id INT NOT NULL AUTO_INCREMENT,
    section_name VARCHAR(40) NOT NULL,
    course_id INT NOT NULL,
    teacher_campus_id INT NOT NULL,
    schedule_time VARCHAR(40) NOT NULL,
    estimated_learning_days INT NOT NULL DEFAULT 30,
    number_of_projects INT NOT NULL DEFAULT 1,
    PRIMARY KEY (section_id),
    UNIQUE KEY uq_section_per_course (course_id, section_name),
    KEY idx_sections_teacher (teacher_campus_id),
    CONSTRAINT fk_sections_course FOREIGN KEY (course_id)
        REFERENCES courses (course_id)
        ON DELETE CASCADE
        ON UPDATE CASCADE,
    CONSTRAINT fk_sections_teacher FOREIGN KEY (teacher_campus_id)
        REFERENCES teachers (campus_id)
        ON DELETE CASCADE
        ON UPDATE CASCADE,
    CONSTRAINT chk_sections_days CHECK (estimated_learning_days > 0),
    CONSTRAINT chk_sections_projects CHECK (number_of_projects >= 0)
) ENGINE = InnoDB;

CREATE TABLE registrations (
    registration_id INT NOT NULL AUTO_INCREMENT,
    student_campus_id INT NOT NULL,
    section_id INT NOT NULL,
    PRIMARY KEY (registration_id),
    UNIQUE KEY uq_registration_pair (student_campus_id, section_id),
    KEY idx_registrations_section (section_id),
    CONSTRAINT fk_registrations_student FOREIGN KEY (student_campus_id)
        REFERENCES students (campus_id)
        ON DELETE CASCADE
        ON UPDATE CASCADE,
    CONSTRAINT fk_registrations_section FOREIGN KEY (section_id)
        REFERENCES sections (section_id)
        ON DELETE CASCADE
        ON UPDATE CASCADE
) ENGINE = InnoDB;

CREATE TABLE completed_courses (
    completion_id INT NOT NULL AUTO_INCREMENT,
    student_campus_id INT NOT NULL,
    section_id INT NOT NULL,
    skill_learnt VARCHAR(80) NOT NULL,
    completion_date DATE NOT NULL,
    PRIMARY KEY (completion_id),
    UNIQUE KEY uq_completion_pair (student_campus_id, section_id),
    KEY idx_completed_section (section_id),
    CONSTRAINT fk_completed_student FOREIGN KEY (student_campus_id)
        REFERENCES students (campus_id)
        ON DELETE CASCADE
        ON UPDATE CASCADE,
    CONSTRAINT fk_completed_section FOREIGN KEY (section_id)
        REFERENCES sections (section_id)
        ON DELETE CASCADE
        ON UPDATE CASCADE
) ENGINE = InnoDB;

CREATE TABLE removal_logs (
    log_id INT NOT NULL AUTO_INCREMENT,
    student_campus_id INT NOT NULL,
    section_id INT NOT NULL,
    removed_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (log_id),
    KEY idx_logs_student (student_campus_id),
    KEY idx_logs_section (section_id)
) ENGINE = InnoDB;