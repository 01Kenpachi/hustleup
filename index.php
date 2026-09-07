<?php

require 'db.php';

$teacher_secret_code = 'HUSTLE_TEACH_2026';

$campus_id = logged_in_campus_id();
$role = logged_in_role();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = isset($_POST['action']) ? $_POST['action'] : '';

    if ($action === 'login') {
        $login_id = (int) (isset($_POST['campus_id']) ? $_POST['campus_id'] : 0);
        $password = isset($_POST['password']) ? $_POST['password'] : '';
        $wanted_role = (isset($_POST['login_role']) && $_POST['login_role'] === 'teacher') ? 'teacher' : 'student';

        $stmt = $conn->prepare('SELECT campus_id, name, password_hash FROM students WHERE campus_id = ?');
        $stmt->bind_param('i', $login_id);
        $stmt->execute();
        $account = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$account || !password_verify($password, $account['password_hash'])) {
            set_flash('Wrong University ID or password. Try again.', 'bad');
            set_old($_POST);
            redirect('index.php?auth=login');
        }

        if ($wanted_role === 'teacher') {
            $stmt = $conn->prepare('SELECT teacher_id FROM teachers WHERE campus_id = ?');
            $stmt->bind_param('i', $login_id);
            $stmt->execute();
            $mentor = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            if (!$mentor) {
                set_flash('That University ID is not registered as a mentor. Log in as a student instead.', 'bad');
                set_old($_POST);
                redirect('index.php?auth=login');
            }
        }

        session_regenerate_id(true);
        $_SESSION['campus_id'] = (int) $account['campus_id'];
        $_SESSION['role'] = $wanted_role;
        take_old();
        set_flash('Welcome back, ' . $account['name'] . ' ⚡');
        redirect('index.php?tab=home');
    }

    if ($action === 'signup') {
        $new_id = (int) (isset($_POST['campus_id']) ? $_POST['campus_id'] : 0);
        $name = trim(isset($_POST['name']) ? $_POST['name'] : '');
        $city = trim(isset($_POST['city']) ? $_POST['city'] : '');
        $department = trim(isset($_POST['department']) ? $_POST['department'] : '');
        $cgpa = (float) (isset($_POST['cgpa']) ? $_POST['cgpa'] : 0);
        $semester = trim(isset($_POST['semester']) ? $_POST['semester'] : '');
        $password = isset($_POST['password']) ? $_POST['password'] : '';
        $signup_role = (isset($_POST['signup_role']) && $_POST['signup_role'] === 'teacher') ? 'teacher' : 'student';
        $secret_input = trim(isset($_POST['teacher_code']) ? $_POST['teacher_code'] : '');
        $teaching_skill = trim(isset($_POST['teaching_skill']) ? $_POST['teaching_skill'] : '');
        $contact_number = trim(isset($_POST['contact_number']) ? $_POST['contact_number'] : '');
        $qualification = trim(isset($_POST['qualification']) ? $_POST['qualification'] : '');

        $errors = array();

        if ($new_id < 1000) {
            $errors[] = 'Enter your real University ID, for example 2210205.';
        }

        if ($name === '' || strlen($name) > 60) {
            $errors[] = 'Enter your full name.';
        }

        if ($city === '' || strlen($city) > 40) {
            $errors[] = 'Enter your city.';
        }

        if ($department === '' || strlen($department) > 60) {
            $errors[] = 'Enter your department.';
        }

        if ($cgpa < 0 || $cgpa > 4) {
            $errors[] = 'CGPA must be between 0.00 and 4.00.';
        }

        if ($semester === '' || strlen($semester) > 20) {
            $errors[] = 'Enter your semester, for example Fall 2026.';
        }

        if (strlen($password) < 6) {
            $errors[] = 'Pick a password of at least 6 characters.';
        }

        if ($signup_role === 'teacher') {
            if ($secret_input !== $teacher_secret_code) {
                $errors[] = 'That one-time teacher code is not valid. Check the email you received when you were selected.';
            }

            if ($contact_number === '' || strlen($contact_number) > 20) {
                $errors[] = 'Mentors must provide a contact number.';
            }

            if ($qualification === '' || strlen($qualification) > 80) {
                $errors[] = 'Mentors must provide a qualification.';
            }

            $skill_stmt = $conn->prepare('SELECT course_id FROM courses WHERE course_name = ?');
            $skill_stmt->bind_param('s', $teaching_skill);
            $skill_stmt->execute();
            $skill_row = $skill_stmt->get_result()->fetch_assoc();
            $skill_stmt->close();

            if (!$skill_row) {
                $errors[] = 'Choose the skill you will be teaching.';
            }
        }

        if (count($errors) === 0) {
            $check = $conn->prepare('SELECT campus_id FROM students WHERE campus_id = ?');
            $check->bind_param('i', $new_id);
            $check->execute();
            $taken = $check->get_result()->fetch_assoc();
            $check->close();

            if ($taken) {
                $errors[] = 'That University ID already has a HustleUp account.';
            }
        }

        if (count($errors) > 0) {
            set_flash(implode(' ', $errors), 'bad');
            set_old($_POST);
            redirect('index.php?auth=signup');
        }

        $hash = password_hash($password, PASSWORD_DEFAULT);

        try {
            $conn->begin_transaction();

            $stmt = $conn->prepare('INSERT INTO students (campus_id, name, city, department, cgpa, semester, password_hash) VALUES (?, ?, ?, ?, ?, ?, ?)');
            $stmt->bind_param('isssdss', $new_id, $name, $city, $department, $cgpa, $semester, $hash);
            $stmt->execute();
            $stmt->close();

            if ($signup_role === 'teacher') {
                $stmt = $conn->prepare('INSERT INTO teachers (campus_id, name, teaching_skill, semester, contact_number, qualification) VALUES (?, ?, ?, ?, ?, ?)');
                $stmt->bind_param('isssss', $new_id, $name, $teaching_skill, $semester, $contact_number, $qualification);
                $stmt->execute();
                $stmt->close();
            }

            $conn->commit();
        } catch (mysqli_sql_exception $exception) {
            $conn->rollback();
            set_flash('The account could not be created. Check your University ID and try again.', 'bad');
            set_old($_POST);
            redirect('index.php?auth=signup');
        }

        take_old();

        if ($signup_role === 'teacher') {
            set_flash('Mentor account created 🎓 Log in as a teacher to open your first section.');
        } else {
            set_flash('Account created 🚀 Log in with your University ID to start hustling.');
        }

        redirect('index.php?auth=login');
    }

    if ($action === 'logout') {
        $_SESSION = array();
        session_destroy();
        session_start();
        set_flash('Signed out. See you soon ⚡');
        redirect('index.php');
    }

    if ($action === 'join' && $campus_id > 0) {
        $section_id = (int) $_POST['section_id'];

        $stmt = $conn->prepare('SELECT teacher_campus_id FROM sections WHERE section_id = ?');
        $stmt->bind_param('i', $section_id);
        $stmt->execute();
        $section = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$section) {
            set_flash('That section is no longer available.', 'bad');
        } elseif ((int) $section['teacher_campus_id'] === $campus_id) {
            set_flash('You cannot join the section you teach.', 'bad');
        } else {
            try {
                $stmt = $conn->prepare('INSERT INTO registrations (student_campus_id, section_id) VALUES (?, ?)');
                $stmt->bind_param('ii', $campus_id, $section_id);
                $stmt->execute();
                $stmt->close();
                set_flash('You are in 🚀 Check the Courses tab for your ongoing sections.');
            } catch (mysqli_sql_exception $exception) {
                set_flash('You have already joined that section.', 'bad');
            }
        }

        redirect('index.php?tab=advising');
    }

    if ($action === 'drop_student' && $role === 'teacher') {
        $registration_id = (int) $_POST['registration_id'];

        $stmt = $conn->prepare('DELETE r FROM registrations r INNER JOIN sections se ON se.section_id = r.section_id WHERE r.registration_id = ? AND se.teacher_campus_id = ?');
        $stmt->bind_param('ii', $registration_id, $campus_id);
        $stmt->execute();
        $dropped = $stmt->affected_rows;
        $stmt->close();

        if ($dropped > 0) {
            set_flash('Student dropped. The trigger recorded it in the exit log.');
        } else {
            set_flash('That student is not enrolled in one of your sections.', 'bad');
        }

        redirect('index.php?tab=courses');
    }

    if ($action === 'complete_section' && $role === 'teacher') {
        $section_id = (int) $_POST['section_id'];

        try {
            $conn->begin_transaction();

            $stmt = $conn->prepare('INSERT IGNORE INTO completed_courses (student_campus_id, section_id, skill_learnt, completion_date) SELECT r.student_campus_id, r.section_id, c.course_name, CURDATE() FROM registrations r INNER JOIN sections se ON se.section_id = r.section_id INNER JOIN courses c ON c.course_id = se.course_id WHERE r.section_id = ? AND se.teacher_campus_id = ?');
            $stmt->bind_param('ii', $section_id, $campus_id);
            $stmt->execute();
            $stmt->close();

            $stmt = $conn->prepare('DELETE r FROM registrations r INNER JOIN sections se ON se.section_id = r.section_id WHERE r.section_id = ? AND se.teacher_campus_id = ?');
            $stmt->bind_param('ii', $section_id, $campus_id);
            $stmt->execute();
            $graduated = $stmt->affected_rows;
            $stmt->close();

            $conn->commit();
        } catch (mysqli_sql_exception $exception) {
            $conn->rollback();
            $graduated = 0;
        }

        if ($graduated > 0) {
            set_flash($graduated . ' hustler(s) graduated 🎓 Certificates added to their completed list.');
        } else {
            set_flash('There is nobody active to graduate in that section.', 'bad');
        }

        redirect('index.php?tab=courses');
    }

    if ($action === 'create_section' && $role === 'teacher') {
        $course_id = (int) $_POST['course_id'];
        $section_name = trim(isset($_POST['section_name']) ? $_POST['section_name'] : '');
        $schedule_time = trim(isset($_POST['schedule_time']) ? $_POST['schedule_time'] : '');
        $days = (int) (isset($_POST['estimated_learning_days']) ? $_POST['estimated_learning_days'] : 0);
        $projects = (int) (isset($_POST['number_of_projects']) ? $_POST['number_of_projects'] : 0);

        if ($section_name === '' || strlen($section_name) > 40) {
            set_flash('Enter a section name, for example Sec-03.', 'bad');
        } elseif ($schedule_time === '' || strlen($schedule_time) > 40) {
            set_flash('Enter a schedule, for example Sun & Tue - 10:00 AM.', 'bad');
        } elseif ($days < 1 || $days > 365) {
            set_flash('Estimated learning days must be between 1 and 365.', 'bad');
        } elseif ($projects < 0 || $projects > 50) {
            set_flash('Number of projects must be between 0 and 50.', 'bad');
        } else {
            try {
                $stmt = $conn->prepare('INSERT INTO sections (section_name, course_id, teacher_campus_id, schedule_time, estimated_learning_days, number_of_projects) VALUES (?, ?, ?, ?, ?, ?)');
                $stmt->bind_param('siisii', $section_name, $course_id, $campus_id, $schedule_time, $days, $projects);
                $stmt->execute();
                $stmt->close();
                set_flash('Section created ⚡ Hustlers can find it in the Advising portal now.');
                redirect('index.php?tab=courses');
            } catch (mysqli_sql_exception $exception) {
                set_flash('That skill already has a section with this name. Pick another name.', 'bad');
            }
        }

        redirect('index.php?tab=create');
    }
}

$flash = get_flash();
$old = take_old();

if ($campus_id === 0) {
    $auth_mode = (isset($_GET['auth']) && $_GET['auth'] === 'signup') ? 'signup' : 'login';
    $signup_role_old = isset($old['signup_role']) ? $old['signup_role'] : 'student';
    $course_options = array();
    $course_option_rows = $conn->query('SELECT course_id, course_name FROM courses ORDER BY course_name ASC');

    while ($row = $course_option_rows->fetch_assoc()) {
        $course_options[] = $row;
    }
} else {
    $student = current_student();

    if (!$student) {
        $_SESSION = array();
        session_destroy();
        session_start();
        redirect('index.php');
    }

    $teacher = current_teacher();

    if ($role === 'teacher' && !$teacher) {
        $_SESSION['role'] = 'student';
        $role = 'student';
    }

    $allowed_tabs = ($role === 'teacher') ? array('home', 'courses', 'create') : array('home', 'profile', 'courses', 'advising');
    $tab = isset($_GET['tab']) ? $_GET['tab'] : 'home';

    if (!in_array($tab, $allowed_tabs, true)) {
        $tab = 'home';
    }

    if ($tab === 'home') {
        $metrics_sql = "SELECT
                COUNT(*) AS total_students,
                SUM(CASE WHEN t.campus_id IS NOT NULL THEN 1 ELSE 0 END) AS total_mentors,
                ROUND(AVG(s.cgpa), 2) AS avg_cgpa,
                MAX(s.cgpa) AS top_cgpa,
                MIN(s.cgpa) AS low_cgpa,
                (SELECT COUNT(*) FROM sections) AS total_sections,
                (SELECT COUNT(*) FROM completed_courses) AS total_completions
            FROM students s
            LEFT JOIN teachers t ON t.campus_id = s.campus_id";

        $metrics = $conn->query($metrics_sql)->fetch_assoc();

        $trending_sql = "SELECT
                c.course_name AS course_name,
                COUNT(r.registration_id) AS learner_count,
                COUNT(DISTINCT se.section_id) AS section_count
            FROM courses c
            INNER JOIN sections se ON se.course_id = c.course_id
            INNER JOIN registrations r ON r.section_id = se.section_id
            GROUP BY c.course_id, c.course_name
            HAVING COUNT(r.registration_id) > 0
            ORDER BY learner_count DESC, c.course_name ASC
            LIMIT 5";

        $trending_skills = array();
        $trending_rows = $conn->query($trending_sql);

        while ($row = $trending_rows->fetch_assoc()) {
            $trending_skills[] = $row;
        }
    }

    if ($tab === 'profile') {
        $profile_stmt = $conn->prepare('SELECT (SELECT COUNT(*) FROM registrations WHERE student_campus_id = ?) AS ongoing_count, (SELECT COUNT(*) FROM completed_courses WHERE student_campus_id = ?) AS completed_count');
        $profile_stmt->bind_param('ii', $campus_id, $campus_id);
        $profile_stmt->execute();
        $profile_counts = $profile_stmt->get_result()->fetch_assoc();
        $profile_stmt->close();
    }

    if ($tab === 'courses' && $role === 'student') {
        $ongoing_sql = "SELECT
                se.section_id AS section_id,
                se.section_name AS section_name,
                se.schedule_time AS schedule_time,
                se.estimated_learning_days AS estimated_learning_days,
                se.number_of_projects AS number_of_projects,
                c.course_name AS course_name,
                t.name AS teacher_name,
                t.contact_number AS contact_number
            FROM registrations r
            INNER JOIN sections se ON se.section_id = r.section_id
            INNER JOIN courses c ON c.course_id = se.course_id
            INNER JOIN teachers t ON t.campus_id = se.teacher_campus_id
            WHERE r.student_campus_id = ?
            ORDER BY c.course_name ASC, se.section_name ASC";

        $ongoing_stmt = $conn->prepare($ongoing_sql);
        $ongoing_stmt->bind_param('i', $campus_id);
        $ongoing_stmt->execute();
        $ongoing_courses = array();
        $ongoing_rows = $ongoing_stmt->get_result();

        while ($row = $ongoing_rows->fetch_assoc()) {
            $ongoing_courses[] = $row;
        }

        $ongoing_stmt->close();

        $done_sql = "SELECT
                cc.skill_learnt AS skill_learnt,
                cc.completion_date AS completion_date,
                se.section_name AS section_name,
                se.number_of_projects AS number_of_projects,
                t.name AS teacher_name
            FROM completed_courses cc
            INNER JOIN sections se ON se.section_id = cc.section_id
            INNER JOIN teachers t ON t.campus_id = se.teacher_campus_id
            WHERE cc.student_campus_id = ?
            ORDER BY cc.completion_date DESC";

        $done_stmt = $conn->prepare($done_sql);
        $done_stmt->bind_param('i', $campus_id);
        $done_stmt->execute();
        $done_courses = array();
        $done_rows = $done_stmt->get_result();

        while ($row = $done_rows->fetch_assoc()) {
            $done_courses[] = $row;
        }

        $done_stmt->close();
    }

    if ($tab === 'advising') {
        $keyword = trim(isset($_GET['q']) ? $_GET['q'] : '');
        $course_filter = isset($_GET['course']) ? (int) $_GET['course'] : 0;

        $advising_sql = "SELECT
                v.section_id AS section_id,
                v.section_name AS section_name,
                v.course_id AS course_id,
                v.course_name AS course_name,
                v.schedule_time AS schedule_time,
                v.estimated_learning_days AS estimated_learning_days,
                v.number_of_projects AS number_of_projects,
                v.teacher_name AS teacher_name,
                v.qualification AS qualification,
                v.enrolled_count AS enrolled_count,
                v.graduate_count AS graduate_count
            FROM v_section_overview v
            WHERE v.teacher_campus_id <> ?
              AND v.section_id NOT IN (SELECT section_id FROM registrations WHERE student_campus_id = ?)
              AND v.section_id NOT IN (SELECT section_id FROM completed_courses WHERE student_campus_id = ?)";

        $types = 'iii';
        $params = array($campus_id, $campus_id, $campus_id);

        if ($keyword !== '') {
            $advising_sql .= ' AND (v.course_name LIKE ? OR v.teacher_name LIKE ? OR v.section_name LIKE ?)';
            $like = '%' . $keyword . '%';
            $types .= 'sss';
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
        }

        if ($course_filter > 0) {
            $advising_sql .= ' AND v.course_id = ?';
            $types .= 'i';
            $params[] = $course_filter;
        }

        $advising_sql .= ' ORDER BY v.course_name ASC, v.section_name ASC LIMIT 12';

        $advising_stmt = $conn->prepare($advising_sql);
        $advising_stmt->bind_param($types, ...$params);
        $advising_stmt->execute();
        $available_sections = array();
        $advising_rows = $advising_stmt->get_result();

        while ($row = $advising_rows->fetch_assoc()) {
            $available_sections[] = $row;
        }

        $advising_stmt->close();

        $filter_courses = array();
        $filter_rows = $conn->query('SELECT DISTINCT c.course_id, c.course_name FROM courses c INNER JOIN sections se ON se.course_id = c.course_id ORDER BY c.course_name ASC');

        while ($row = $filter_rows->fetch_assoc()) {
            $filter_courses[] = $row;
        }
    }

    if ($tab === 'courses' && $role === 'teacher') {
        $mine_stmt = $conn->prepare('SELECT section_id, section_name, course_name, schedule_time, estimated_learning_days, number_of_projects, enrolled_count, graduate_count FROM v_section_overview WHERE teacher_campus_id = ? ORDER BY course_name ASC, section_name ASC');
        $mine_stmt->bind_param('i', $campus_id);
        $mine_stmt->execute();
        $my_sections = array();
        $mine_rows = $mine_stmt->get_result();

        while ($row = $mine_rows->fetch_assoc()) {
            $my_sections[] = $row;
        }

        $mine_stmt->close();

        $roster_sql = "SELECT
                r.registration_id AS registration_id,
                r.section_id AS section_id,
                s.campus_id AS campus_id,
                s.name AS student_name,
                s.department AS department,
                s.semester AS semester,
                s.cgpa AS cgpa
            FROM registrations r
            INNER JOIN students s ON s.campus_id = r.student_campus_id
            INNER JOIN sections se ON se.section_id = r.section_id
            WHERE se.teacher_campus_id = ?
            ORDER BY s.name ASC";

        $roster_stmt = $conn->prepare($roster_sql);
        $roster_stmt->bind_param('i', $campus_id);
        $roster_stmt->execute();
        $roster_rows = $roster_stmt->get_result();

        $students_by_section = array();

        while ($row = $roster_rows->fetch_assoc()) {
            $students_by_section[(int) $row['section_id']][] = $row;
        }

        $roster_stmt->close();

        $log_sql = "SELECT
                l.log_id AS log_id,
                l.removed_at AS removed_at,
                s.name AS student_name,
                s.campus_id AS campus_id,
                c.course_name AS course_name,
                se.section_name AS section_name
            FROM removal_logs l
            INNER JOIN sections se ON se.section_id = l.section_id
            INNER JOIN courses c ON c.course_id = se.course_id
            INNER JOIN students s ON s.campus_id = l.student_campus_id
            WHERE se.teacher_campus_id = ?
            ORDER BY l.removed_at DESC, l.log_id DESC
            LIMIT 8";

        $log_stmt = $conn->prepare($log_sql);
        $log_stmt->bind_param('i', $campus_id);
        $log_stmt->execute();
        $exit_logs = array();
        $log_rows = $log_stmt->get_result();

        while ($row = $log_rows->fetch_assoc()) {
            $exit_logs[] = $row;
        }

        $log_stmt->close();
    }

    if ($tab === 'create') {
        $skill_stmt = $conn->prepare('SELECT course_id, course_name FROM courses WHERE course_name = ?');
        $skill_stmt->bind_param('s', $teacher['teaching_skill']);
        $skill_stmt->execute();
        $create_courses = array();
        $skill_rows = $skill_stmt->get_result();

        while ($row = $skill_rows->fetch_assoc()) {
            $create_courses[] = $row;
        }

        $skill_stmt->close();

        if (count($create_courses) === 0) {
            $all_course_rows = $conn->query('SELECT course_id, course_name FROM courses ORDER BY course_name ASC');

            while ($row = $all_course_rows->fetch_assoc()) {
                $create_courses[] = $row;
            }
        }
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>HustleUp ⚡</title>
<style>
* {
    box-sizing: border-box;
}

body {
    margin: 0;
    background: #121212;
    color: #ececec;
    font-family: "Segoe UI", Roboto, Arial, sans-serif;
    font-size: 15px;
    line-height: 1.55;
}

a {
    color: #bb86fc;
    text-decoration: none;
}

.topbar {
    background: #1a1a1a;
    border-bottom: 1px solid #2c2c2c;
    padding: 14px 26px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 12px;
}

.logo {
    font-size: 20px;
    font-weight: 800;
    letter-spacing: 0.5px;
    background: linear-gradient(90deg, #bb86fc, #00e676);
    -webkit-background-clip: text;
    background-clip: text;
    color: transparent;
}

.whoami {
    font-size: 13px;
    color: #9b9b9b;
}

.whoami b {
    color: #ececec;
}

.pill {
    display: inline-block;
    padding: 2px 10px;
    border-radius: 20px;
    font-size: 12px;
    border: 1px solid #bb86fc;
    color: #bb86fc;
    margin-left: 6px;
}

.pill.green {
    border-color: #00e676;
    color: #00e676;
}

.tabbar {
    display: flex;
    width: 100%;
    background: #171717;
    border-bottom: 1px solid #2c2c2c;
}

.tabbar a, .tabbar button {
    flex: 1;
    text-align: center;
    padding: 15px 8px;
    font-size: 14px;
    font-weight: 600;
    color: #9b9b9b;
    background: transparent;
    border: none;
    border-bottom: 3px solid transparent;
    font-family: inherit;
    cursor: pointer;
}

.tabbar a:hover, .tabbar button:hover {
    color: #ececec;
    background: #1e1e1e;
}

.tabbar a.active {
    color: #00e676;
    border-bottom-color: #00e676;
    background: #1e1e1e;
}

.shell {
    max-width: 1040px;
    margin: 0 auto;
    padding: 26px 22px 70px 22px;
}

.card {
    background: #1e1e1e;
    border: 1px solid #2c2c2c;
    border-radius: 14px;
    padding: 22px 24px;
    margin-bottom: 20px;
}

h1 {
    font-size: 24px;
    margin: 0 0 16px 0;
}

h2 {
    font-size: 18px;
    margin: 0 0 14px 0;
}

h3 {
    font-size: 15px;
    margin: 0 0 8px 0;
}

.banner {
    background: linear-gradient(135deg, #1e1e1e 0%, #241b33 100%);
    border: 1px solid #3a2d52;
    border-radius: 16px;
    padding: 28px 30px;
    margin-bottom: 22px;
}

.banner h1 {
    font-size: 26px;
    margin-bottom: 4px;
}

.banner .tagline {
    font-size: 17px;
    font-weight: 700;
    color: #00e676;
    margin: 12px 0 14px 0;
}

.banner p {
    margin: 0 0 10px 0;
    color: #c9c9c9;
}

.banner p b {
    color: #bb86fc;
}

.metrics {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 16px;
    margin-bottom: 20px;
}

.metric {
    background: #1e1e1e;
    border: 1px solid #2c2c2c;
    border-left: 4px solid #bb86fc;
    border-radius: 12px;
    padding: 18px 20px;
}

.metric.green {
    border-left-color: #00e676;
}

.metric b {
    display: block;
    font-size: 32px;
    font-weight: 800;
    color: #ffffff;
    line-height: 1.1;
}

.metric span {
    font-size: 12px;
    letter-spacing: 0.7px;
    text-transform: uppercase;
    color: #9b9b9b;
}

table {
    width: 100%;
    border-collapse: collapse;
    font-size: 14px;
}

th, td {
    text-align: left;
    padding: 11px 12px;
    border-bottom: 1px solid #2c2c2c;
    vertical-align: middle;
}

th {
    font-size: 12px;
    letter-spacing: 0.5px;
    text-transform: uppercase;
    color: #9b9b9b;
}

tr:last-child td {
    border-bottom: none;
}

.empty {
    background: #1a1a1a;
    border: 1px dashed #3a3a3a;
    border-radius: 12px;
    padding: 26px;
    text-align: center;
    color: #9b9b9b;
}

.empty b {
    display: block;
    font-size: 17px;
    color: #ececec;
    margin-bottom: 4px;
}

label {
    display: block;
    font-size: 13px;
    color: #9b9b9b;
    margin-bottom: 6px;
}

input, select {
    width: 100%;
    padding: 11px 13px;
    background: #141414;
    border: 1px solid #333333;
    border-radius: 9px;
    color: #ececec;
    font-size: 14px;
    font-family: inherit;
}

input:focus, select:focus {
    outline: none;
    border-color: #bb86fc;
}

.field {
    margin-bottom: 14px;
}

.duo {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
    gap: 14px;
}

.btn {
    display: inline-block;
    padding: 11px 20px;
    border: none;
    border-radius: 9px;
    background: #bb86fc;
    color: #14101c;
    font-size: 14px;
    font-weight: 700;
    font-family: inherit;
    cursor: pointer;
}

.btn.green {
    background: #00e676;
    color: #06210f;
}

.btn.wide {
    width: 100%;
}

.btn.small {
    padding: 7px 14px;
    font-size: 13px;
}

.btn.danger {
    background: transparent;
    border: 1px solid #ff6b81;
    color: #ff6b81;
}

.flash {
    border-radius: 10px;
    padding: 13px 18px;
    margin-bottom: 20px;
    font-size: 14px;
}

.flash.good {
    background: rgba(0, 230, 118, 0.12);
    border: 1px solid #00e676;
    color: #00e676;
}

.flash.bad {
    background: rgba(255, 107, 129, 0.12);
    border: 1px solid #ff6b81;
    color: #ff6b81;
}

.muted {
    color: #9b9b9b;
}

.small {
    font-size: 13px;
}

.chip {
    display: inline-block;
    padding: 3px 11px;
    border-radius: 20px;
    background: #262626;
    color: #c9c9c9;
    font-size: 12px;
    margin-right: 6px;
}

.chip.purple {
    background: rgba(187, 134, 252, 0.16);
    color: #bb86fc;
}

.chip.green {
    background: rgba(0, 230, 118, 0.14);
    color: #00e676;
}

.sectionhead {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 14px;
    flex-wrap: wrap;
    margin-bottom: 12px;
}

.authwrap {
    max-width: 560px;
    margin: 46px auto;
    padding: 0 20px;
}

.authhead {
    text-align: center;
    margin-bottom: 22px;
}

.authhead .logo {
    font-size: 34px;
}

.authtabs {
    display: flex;
    background: #171717;
    border: 1px solid #2c2c2c;
    border-radius: 12px;
    overflow: hidden;
    margin-bottom: 20px;
}

.authtabs a {
    flex: 1;
    text-align: center;
    padding: 14px;
    font-weight: 700;
    color: #9b9b9b;
}

.authtabs a.active {
    background: #1e1e1e;
    color: #00e676;
}

.rolepick {
    position: absolute;
    opacity: 0;
    width: 0;
    height: 0;
    pointer-events: none;
}

.rolebtns {
    display: flex;
    gap: 12px;
    margin-bottom: 18px;
}

.rolebtns label {
    flex: 1;
    margin: 0;
    padding: 13px 10px;
    text-align: center;
    border: 1px solid #333333;
    border-radius: 10px;
    background: #141414;
    color: #9b9b9b;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
}

#role_student:checked ~ .rolebtns label[for="role_student"] {
    border-color: #00e676;
    color: #00e676;
    background: rgba(0, 230, 118, 0.1);
}

#role_teacher:checked ~ .rolebtns label[for="role_teacher"] {
    border-color: #bb86fc;
    color: #bb86fc;
    background: rgba(187, 134, 252, 0.12);
}

.teacheronly {
    display: none;
    border: 1px solid #3a2d52;
    background: rgba(187, 134, 252, 0.07);
    border-radius: 12px;
    padding: 18px 20px;
    margin-bottom: 16px;
}

#role_teacher:checked ~ .teacheronly {
    display: block;
}

.codehint {
    font-size: 13px;
    color: #bb86fc;
    margin-bottom: 6px;
}
</style>
</head>
<body>

<?php if ($campus_id === 0) { ?>

<div class="authwrap">
    <div class="authhead">
        <div class="logo">HustleUp ⚡</div>
        <p class="muted">Skill Up. Build Fast. Lead Next.</p>
    </div>

    <?php if ($flash) { ?>
        <div class="flash <?php echo e($flash['tone']); ?>"><?php echo e($flash['message']); ?></div>
    <?php } ?>

    <div class="authtabs">
        <a href="index.php?auth=login" class="<?php echo $auth_mode === 'login' ? 'active' : ''; ?>">🔑 Log In</a>
        <a href="index.php?auth=signup" class="<?php echo $auth_mode === 'signup' ? 'active' : ''; ?>">➕ Sign Up</a>
    </div>

    <?php if ($auth_mode === 'login') { ?>
        <div class="card">
            <h2>Welcome back 🚀</h2>
            <form method="post" action="index.php">
                <input type="hidden" name="action" value="login">
                <div class="field">
                    <label for="campus_id">University ID</label>
                    <input type="number" id="campus_id" name="campus_id" placeholder="2210203" value="<?php echo e(isset($old['campus_id']) ? $old['campus_id'] : ''); ?>" required>
                </div>
                <div class="field">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required>
                </div>
                <div class="field">
                    <label for="login_role">Log in as</label>
                    <select id="login_role" name="login_role">
                        <option value="student">🎓 Login as Student</option>
                        <option value="teacher" <?php echo (isset($old['login_role']) && $old['login_role'] === 'teacher') ? 'selected' : ''; ?>>💻 Login as Teacher</option>
                    </select>
                </div>
                <button type="submit" class="btn green wide">Enter HustleUp ⚡</button>
            </form>
        </div>
    <?php } else { ?>
        <div class="card">
            <h2>Join the hustle ➕</h2>
            <form method="post" action="index.php">
                <input type="hidden" name="action" value="signup">

                <input type="radio" class="rolepick" id="role_student" name="signup_role" value="student" <?php echo $signup_role_old !== 'teacher' ? 'checked' : ''; ?>>
                <input type="radio" class="rolepick" id="role_teacher" name="signup_role" value="teacher" <?php echo $signup_role_old === 'teacher' ? 'checked' : ''; ?>>

                <div class="rolebtns">
                    <label for="role_student">🎓 Sign up as Student</label>
                    <label for="role_teacher">💻 Sign up as Teacher</label>
                </div>

                <div class="duo">
                    <div class="field">
                        <label for="s_campus_id">University ID</label>
                        <input type="number" id="s_campus_id" name="campus_id" placeholder="2210205" value="<?php echo e(isset($old['campus_id']) ? $old['campus_id'] : ''); ?>" required>
                    </div>
                    <div class="field">
                        <label for="s_name">Full name</label>
                        <input type="text" id="s_name" name="name" maxlength="60" value="<?php echo e(isset($old['name']) ? $old['name'] : ''); ?>" required>
                    </div>
                </div>
                <div class="duo">
                    <div class="field">
                        <label for="s_city">City</label>
                        <input type="text" id="s_city" name="city" maxlength="40" value="<?php echo e(isset($old['city']) ? $old['city'] : ''); ?>" required>
                    </div>
                    <div class="field">
                        <label for="s_department">Department</label>
                        <input type="text" id="s_department" name="department" maxlength="60" value="<?php echo e(isset($old['department']) ? $old['department'] : ''); ?>" required>
                    </div>
                </div>
                <div class="duo">
                    <div class="field">
                        <label for="s_cgpa">CGPA</label>
                        <input type="number" id="s_cgpa" name="cgpa" step="0.01" min="0" max="4" value="<?php echo e(isset($old['cgpa']) ? $old['cgpa'] : ''); ?>" required>
                    </div>
                    <div class="field">
                        <label for="s_semester">Semester</label>
                        <input type="text" id="s_semester" name="semester" maxlength="20" placeholder="Fall 2026" value="<?php echo e(isset($old['semester']) ? $old['semester'] : ''); ?>" required>
                    </div>
                </div>
                <div class="field">
                    <label for="s_password">Password</label>
                    <input type="password" id="s_password" name="password" minlength="6" required>
                </div>

                <div class="teacheronly">
                    <h3>💻 Mentor authorization</h3>
                    <div class="field">
                        <label for="teacher_code" class="codehint">Provide the one-time special code you received after being selected as the teacher - Check your email!</label>
                        <input type="text" id="teacher_code" name="teacher_code" maxlength="40" placeholder="One-time code">
                    </div>
                    <div class="field">
                        <label for="teaching_skill">Teaching skill</label>
                        <select id="teaching_skill" name="teaching_skill">
                            <?php foreach ($course_options as $course) { ?>
                                <option value="<?php echo e($course['course_name']); ?>" <?php echo (isset($old['teaching_skill']) && $old['teaching_skill'] === $course['course_name']) ? 'selected' : ''; ?>>
                                    <?php echo e($course['course_name']); ?>
                                </option>
                            <?php } ?>
                        </select>
                    </div>
                    <div class="duo">
                        <div class="field">
                            <label for="contact_number">Contact number</label>
                            <input type="text" id="contact_number" name="contact_number" maxlength="20" placeholder="+8801700000000" value="<?php echo e(isset($old['contact_number']) ? $old['contact_number'] : ''); ?>">
                        </div>
                        <div class="field">
                            <label for="qualification">Qualification</label>
                            <input type="text" id="qualification" name="qualification" maxlength="80" placeholder="Meta Front-End Certified" value="<?php echo e(isset($old['qualification']) ? $old['qualification'] : ''); ?>">
                        </div>
                    </div>
                </div>

                <button type="submit" class="btn wide">Create my HustleUp account 🚀</button>
            </form>
        </div>
    <?php } ?>
</div>

<?php } else { ?>

<div class="topbar">
    <div class="logo">HustleUp ⚡</div>
    <div class="whoami">
        <b><?php echo e($student['name']); ?></b> &middot; ID <?php echo (int) $student['campus_id']; ?>
        <?php if ($role === 'teacher') { ?>
            <span class="pill">💻 Mentor &middot; <?php echo e($teacher['teaching_skill']); ?></span>
        <?php } else { ?>
            <span class="pill green">🎓 Student</span>
        <?php } ?>
    </div>
</div>

<div class="tabbar">
    <a href="index.php?tab=home" class="<?php echo $tab === 'home' ? 'active' : ''; ?>">🏠 Home</a>
    <?php if ($role === 'teacher') { ?>
        <a href="index.php?tab=courses" class="<?php echo $tab === 'courses' ? 'active' : ''; ?>">📚 Courses</a>
        <a href="index.php?tab=create" class="<?php echo $tab === 'create' ? 'active' : ''; ?>">➕ Section Creation</a>
    <?php } else { ?>
        <a href="index.php?tab=profile" class="<?php echo $tab === 'profile' ? 'active' : ''; ?>">👤 Profile</a>
        <a href="index.php?tab=courses" class="<?php echo $tab === 'courses' ? 'active' : ''; ?>">📚 Courses</a>
        <a href="index.php?tab=advising" class="<?php echo $tab === 'advising' ? 'active' : ''; ?>">📝 Advising</a>
    <?php } ?>
    <form method="post" action="index.php" style="flex:1;display:flex">
        <input type="hidden" name="action" value="logout">
        <button type="submit">🚪 Sign Out</button>
    </form>
</div>

<div class="shell">

<?php if ($flash) { ?>
    <div class="flash <?php echo e($flash['tone']); ?>"><?php echo e($flash['message']); ?></div>
<?php } ?>

<?php if ($tab === 'home') { ?>

    <div class="banner">
        <h1>Welcome to HustleUp ⚡ | Peer-to-Peer Skill Mastery</h1>
        <div class="tagline">🚀 Skill Up. Build Fast. Lead Next.</div>
        <p>HustleUp is a campus skill-sharing ecosystem built to bridge the gap between academic theory and practical industry execution.</p>
        <p>💡 <b>Learn by Doing:</b> Access student-led, section-based workshops focused on high-demand technical and creative skills.</p>
        <p>🛠️ <b>Project-Driven:</b> Work on real-world projects and build portfolio-ready proof of work within structured timelines.</p>
        <p>🎓 <b>Full-Circle Growth:</b> Every learner has a clear path forward, complete a skill, earn certified university ratings, and return as a teacher to lead your own section.</p>
        <p>🤝 <b>Campus Collaboration:</b> Learn directly from peers who have mastered the stack, fostering an agile, community-first learning environment.</p>
        <p>⚡ <b>Zero Tuition Barriers:</b> Skill sharing is fully accessible for all admitted campus students.</p>
    </div>

    <div class="metrics">
        <div class="metric">
            <b><?php echo (int) $metrics['total_students']; ?></b>
            <span>👤 Total Student Hustlers</span>
        </div>
        <div class="metric green">
            <b><?php echo (int) $metrics['total_mentors']; ?></b>
            <span>🎓 Certified Mentors</span>
        </div>
        <div class="metric">
            <b><?php echo (int) $metrics['total_sections']; ?></b>
            <span>💻 Active Skill Sections</span>
        </div>
        <div class="metric green">
            <b><?php echo (int) $metrics['total_completions']; ?></b>
            <span>📚 Completed Certifications</span>
        </div>
    </div>

    <div class="card">
        <h2>📊 Campus CGPA snapshot</h2>
        <p class="muted" style="margin:0">
            <span class="chip purple">Average <?php echo e($metrics['avg_cgpa']); ?></span>
            <span class="chip green">Highest <?php echo e($metrics['top_cgpa']); ?></span>
            <span class="chip">Lowest <?php echo e($metrics['low_cgpa']); ?></span>
        </p>
    </div>

    <?php if (count($trending_skills) > 0) { ?>
        <div class="card">
            <h2>🔥 Trending skills right now</h2>
            <table>
                <tr><th>Skill</th><th>Sections</th><th>Active hustlers</th></tr>
                <?php foreach ($trending_skills as $row) { ?>
                    <tr>
                        <td><?php echo e($row['course_name']); ?></td>
                        <td><?php echo (int) $row['section_count']; ?></td>
                        <td><?php echo (int) $row['learner_count']; ?></td>
                    </tr>
                <?php } ?>
            </table>
        </div>
    <?php } ?>

<?php } elseif ($tab === 'profile') { ?>

    <h1>👤 My Profile</h1>
    <div class="card">
        <table>
            <tr><th>University ID</th><td><?php echo (int) $student['campus_id']; ?></td></tr>
            <tr><th>Name</th><td><?php echo e($student['name']); ?></td></tr>
            <tr><th>City</th><td><?php echo e($student['city']); ?></td></tr>
            <tr><th>Department</th><td><?php echo e($student['department']); ?></td></tr>
            <tr><th>CGPA</th><td><?php echo e($student['cgpa']); ?></td></tr>
            <tr><th>Semester</th><td><?php echo e($student['semester']); ?></td></tr>
        </table>
    </div>

    <div class="metrics">
        <div class="metric">
            <b><?php echo (int) $profile_counts['ongoing_count']; ?></b>
            <span>📚 Ongoing sections</span>
        </div>
        <div class="metric green">
            <b><?php echo (int) $profile_counts['completed_count']; ?></b>
            <span>🎓 Skills mastered</span>
        </div>
    </div>

    <?php if ($teacher) { ?>
        <div class="card">
            <h2>💻 Mentor credentials</h2>
            <table>
                <tr><th>Teaching skill</th><td><?php echo e($teacher['teaching_skill']); ?></td></tr>
                <tr><th>Qualification</th><td><?php echo e($teacher['qualification']); ?></td></tr>
                <tr><th>Contact</th><td><?php echo e($teacher['contact_number']); ?></td></tr>
            </table>
            <p class="muted small" style="margin-bottom:0">Sign out and log back in as a teacher to open your mentor dashboard.</p>
        </div>
    <?php } ?>

<?php } elseif ($tab === 'courses' && $role === 'student') { ?>

    <h1>📚 My Courses</h1>

    <div class="card">
        <h2>⚡ Ongoing courses</h2>
        <?php if (count($ongoing_courses) === 0) { ?>
            <div class="empty">
                <b>All caught up! 🚀</b>
                Head to the Advising tab and grab a section.
            </div>
        <?php } else { ?>
            <table>
                <tr><th>Skill</th><th>Section</th><th>Schedule</th><th>Mentor</th><th>Duration</th><th>Projects</th></tr>
                <?php foreach ($ongoing_courses as $row) { ?>
                    <tr>
                        <td><?php echo e($row['course_name']); ?></td>
                        <td><?php echo e($row['section_name']); ?></td>
                        <td><?php echo e($row['schedule_time']); ?></td>
                        <td>
                            <?php echo e($row['teacher_name']); ?>
                            <div class="muted small"><?php echo e($row['contact_number']); ?></div>
                        </td>
                        <td><?php echo (int) $row['estimated_learning_days']; ?> days</td>
                        <td><?php echo (int) $row['number_of_projects']; ?></td>
                    </tr>
                <?php } ?>
            </table>
        <?php } ?>
    </div>

    <div class="card">
        <h2>🎓 Completed courses</h2>
        <?php if (count($done_courses) === 0) { ?>
            <div class="empty">
                <b>Your first certificate is loading 🚀</b>
                Finish a section and your mentor will graduate you.
            </div>
        <?php } else { ?>
            <table>
                <tr><th>Skill learnt</th><th>Section</th><th>Mentor</th><th>Projects built</th><th>Completed on</th></tr>
                <?php foreach ($done_courses as $row) { ?>
                    <tr>
                        <td><span class="chip green"><?php echo e($row['skill_learnt']); ?></span></td>
                        <td><?php echo e($row['section_name']); ?></td>
                        <td><?php echo e($row['teacher_name']); ?></td>
                        <td><?php echo (int) $row['number_of_projects']; ?></td>
                        <td><?php echo e($row['completion_date']); ?></td>
                    </tr>
                <?php } ?>
            </table>
        <?php } ?>
    </div>

<?php } elseif ($tab === 'advising') { ?>

    <h1>📝 Advising Portal</h1>

    <div class="card">
        <form method="get" action="index.php">
            <input type="hidden" name="tab" value="advising">
            <div class="duo">
                <div class="field">
                    <label for="q">Search skill, mentor or section</label>
                    <input type="text" id="q" name="q" value="<?php echo e($keyword); ?>" placeholder="python">
                </div>
                <div class="field">
                    <label for="course">Filter by skill</label>
                    <select id="course" name="course">
                        <option value="0">All skills</option>
                        <?php foreach ($filter_courses as $choice) { ?>
                            <option value="<?php echo (int) $choice['course_id']; ?>" <?php echo $course_filter === (int) $choice['course_id'] ? 'selected' : ''; ?>>
                                <?php echo e($choice['course_name']); ?>
                            </option>
                        <?php } ?>
                    </select>
                </div>
            </div>
            <button type="submit" class="btn small">🔍 Search</button>
            <a href="index.php?tab=advising" class="btn small green">Reset</a>
        </form>
    </div>

    <?php if (count($available_sections) === 0) { ?>
        <div class="empty">
            <b>All caught up! 🚀</b>
            You have joined everything on offer right now.
        </div>
    <?php } else { ?>
        <?php foreach ($available_sections as $row) { ?>
            <div class="card">
                <div class="sectionhead">
                    <div>
                        <h2 style="margin:0">💻 <?php echo e($row['course_name']); ?> &middot; <?php echo e($row['section_name']); ?></h2>
                        <p class="muted small" style="margin:4px 0 0 0">
                            Mentor <b><?php echo e($row['teacher_name']); ?></b> &middot; <?php echo e($row['qualification']); ?>
                        </p>
                    </div>
                    <form method="post" action="index.php">
                        <input type="hidden" name="action" value="join">
                        <input type="hidden" name="section_id" value="<?php echo (int) $row['section_id']; ?>">
                        <button type="submit" class="btn green">➕ Join Section</button>
                    </form>
                </div>
                <p style="margin:0">
                    <span class="chip purple">🕒 <?php echo e($row['schedule_time']); ?></span>
                    <span class="chip">📅 <?php echo (int) $row['estimated_learning_days']; ?> days</span>
                    <span class="chip">🛠️ <?php echo (int) $row['number_of_projects']; ?> projects</span>
                    <span class="chip">👥 <?php echo (int) $row['enrolled_count']; ?> learning</span>
                    <span class="chip green">🎓 <?php echo (int) $row['graduate_count']; ?> graduated</span>
                </p>
            </div>
        <?php } ?>
    <?php } ?>

<?php } elseif ($tab === 'courses' && $role === 'teacher') { ?>

    <h1>📚 My Sections</h1>

    <?php if (count($my_sections) === 0) { ?>
        <div class="empty">
            <b>Time to launch your first section ⚡</b>
            Open the Section Creation tab and publish one.
        </div>
    <?php } else { ?>
        <?php foreach ($my_sections as $section) { ?>
            <?php
                $section_id = (int) $section['section_id'];
                $enrolled = isset($students_by_section[$section_id]) ? $students_by_section[$section_id] : array();
            ?>
            <div class="card">
                <div class="sectionhead">
                    <div>
                        <h2 style="margin:0">💻 <?php echo e($section['course_name']); ?> &middot; <?php echo e($section['section_name']); ?></h2>
                        <p style="margin:6px 0 0 0">
                            <span class="chip purple">🕒 <?php echo e($section['schedule_time']); ?></span>
                            <span class="chip">📅 <?php echo (int) $section['estimated_learning_days']; ?> days</span>
                            <span class="chip">🛠️ <?php echo (int) $section['number_of_projects']; ?> projects</span>
                            <span class="chip green">🎓 <?php echo (int) $section['graduate_count']; ?> graduated</span>
                        </p>
                    </div>
                    <form method="post" action="index.php">
                        <input type="hidden" name="action" value="complete_section">
                        <input type="hidden" name="section_id" value="<?php echo $section_id; ?>">
                        <button type="submit" class="btn green">🎓 Complete Section &amp; Graduate Students</button>
                    </form>
                </div>
                <?php if (count($enrolled) === 0) { ?>
                    <div class="empty">
                        <b>All caught up! 🚀</b>
                        No active hustler in this section right now.
                    </div>
                <?php } else { ?>
                    <table>
                        <tr><th>University ID</th><th>Hustler</th><th>Department</th><th>Semester</th><th>CGPA</th><th></th></tr>
                        <?php foreach ($enrolled as $row) { ?>
                            <tr>
                                <td><?php echo (int) $row['campus_id']; ?></td>
                                <td><?php echo e($row['student_name']); ?></td>
                                <td><?php echo e($row['department']); ?></td>
                                <td><?php echo e($row['semester']); ?></td>
                                <td><?php echo e($row['cgpa']); ?></td>
                                <td>
                                    <form method="post" action="index.php">
                                        <input type="hidden" name="action" value="drop_student">
                                        <input type="hidden" name="registration_id" value="<?php echo (int) $row['registration_id']; ?>">
                                        <button type="submit" class="btn danger small">Drop Student</button>
                                    </form>
                                </td>
                            </tr>
                        <?php } ?>
                    </table>
                <?php } ?>
            </div>
        <?php } ?>
    <?php } ?>

    <?php if (count($exit_logs) > 0) { ?>
        <div class="card">
            <h2>📋 Section exit log</h2>
            <table>
                <tr><th>Hustler</th><th>Skill</th><th>Section</th><th>Left at</th></tr>
                <?php foreach ($exit_logs as $row) { ?>
                    <tr>
                        <td><?php echo e($row['student_name']); ?> <span class="muted small">(<?php echo (int) $row['campus_id']; ?>)</span></td>
                        <td><?php echo e($row['course_name']); ?></td>
                        <td><?php echo e($row['section_name']); ?></td>
                        <td><?php echo e($row['removed_at']); ?></td>
                    </tr>
                <?php } ?>
            </table>
        </div>
    <?php } ?>

<?php } else { ?>

    <h1>➕ Section Creation</h1>

    <div class="card">
        <h2>Launch a new section</h2>
        <p class="muted small" style="margin-top:0">Your certified teaching skill is <b><?php echo e($teacher['teaching_skill']); ?></b>.</p>
        <form method="post" action="index.php">
            <input type="hidden" name="action" value="create_section">
            <div class="duo">
                <div class="field">
                    <label for="course_id">Skill</label>
                    <select id="course_id" name="course_id">
                        <?php foreach ($create_courses as $course) { ?>
                            <option value="<?php echo (int) $course['course_id']; ?>"><?php echo e($course['course_name']); ?></option>
                        <?php } ?>
                    </select>
                </div>
                <div class="field">
                    <label for="section_name">Section name</label>
                    <input type="text" id="section_name" name="section_name" maxlength="40" placeholder="Sec-03" required>
                </div>
            </div>
            <div class="field">
                <label for="schedule_time">Schedule time</label>
                <input type="text" id="schedule_time" name="schedule_time" maxlength="40" placeholder="Sun &amp; Tue - 10:00 AM" required>
            </div>
            <div class="duo">
                <div class="field">
                    <label for="estimated_learning_days">Estimated learning days</label>
                    <input type="number" id="estimated_learning_days" name="estimated_learning_days" min="1" max="365" value="30" required>
                </div>
                <div class="field">
                    <label for="number_of_projects">Number of projects</label>
                    <input type="number" id="number_of_projects" name="number_of_projects" min="0" max="50" value="2" required>
                </div>
            </div>
            <button type="submit" class="btn wide">🚀 Publish Section</button>
        </form>
    </div>

<?php } ?>

</div>

<?php } ?>

</body>
</html>
