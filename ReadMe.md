<h1>HustleUp⚡</h1>

A peer-to-peer skill-sharing platform for university students. Students learn skills from certified mentors, enroll in sections, and earn certificates upon completion.

<h2>📖 Project Overview</h2>

A comprehensive data-driven web application was developed and implemented utilizing a seven table MySQL database. Six tables adhere to third normal form, while one table intentionally  deviates from this standard (completed_courses.skill_learnt). The schema enforces declarative  integrity through primary keys, foreign keys, composite UNIQUE keys to prevent duplicate  enrollments and section names, as well as CHECK constraints. The SQL implementation covers  the entire course syllabus, incorporating joins across three and four tables, an outer join for 
counting active sections, all five aggregate functions with conditional aggregation, GROUP BY  with HAVING, single-row and multiple-row subqueries, a view supporting two dashboards, and  a trigger that maintains an independent audit trail. The front end enables insertion, modification,  deletion, search, and filtering, with all parameterized queries executed as prepared statements to  enhance security. 
In addition to the database design, a fully functional website, ‘HustleUp,’ was developed and  deployed on a live remote server using Always Data hosting. The website is accessible online  and available for immediate use by students and teachers. Core features such as user registration,  login, role-based access, section creation, enrollment, graduation, and student removal are fully  operational. The user interface is designed to be clean and responsive, ensuring a proper platform  for both learners and mentors. This project demonstrates theoretical knowledge of databases and  practical web development skills, resulting in a deployable, real-world project.

<h2>✨ Core Features</h2>

 <h3>Authentication & Authorization</h3>
- Secure user registration with **password hashing** (bcrypt)
- Login with role selection (Student / Teacher)
- Session-based authentication with flash messages
- Role-based access control (Students cannot access teacher features)

<h3>Student Features</h3>

| Feature | Description |
| :--- | :--- |
| **Profile Dashboard** | View personal details, CGPA, department, semester |
| **Course Management** | View ongoing sections and completed courses |
| **Advising Portal** | Search and filter available sections |
| **Enrollment** | Join sections with one-click |

<h3>Teacher Features</h3>

| Feature | Description |
| :--- | :--- |
| **Section Creation** | Create new sections with schedule and project details |
| **Roster Management** | View enrolled students with drop functionality |
| **Graduation** | Complete sections and automatically graduate students |
| **Audit Log** | View section exit logs (automatically maintained by trigger) |

<h3>Dashboards</h3>

| Dashboard | Content |
| :--- | :--- |
| **Home Page** | Campus statistics (student count, mentors, sections, completions) |
| **Trending Skills** | Top 5 skills by enrollment |
| **CGPA Snapshot** | Average, highest, and lowest CGPA |

<h2>🛠️ Technical Architecture</h2>

| Layer | Technology |
| :--- | :--- |
| **Markup** | HTML5, generated inline by PHP |
| **Styling** | A single internal `<style>` block in `index.php`. No external CSS framework and no image assets are used. |
| **Scripting** | PHP (procedural style, using the object-oriented mysqli extension). No JavaScript is used anywhere in the project. The mentor sign-up form's conditional fields are shown and hidden purely with a CSS sibling selector on hidden radio inputs. |
| **Database** | MySQL / MariaDB |
| **Server** | Apache (e.g. via XAMPP) with PHP and the mysqli extension enabled |
| **Hosting (Live Deployment)** | [https://www.alwaysdata.com](https://www.alwaysdata.com) |


<h2>📁 Project Structure</h2>

hustleup/
│
├── src/
│ ├── db.php # Database connection & helper functions
│ └── index.php # Main application entry point (UI + Logic)
│
├── sql/
│ ├── 01_create_tables.sql # Table creation scripts
│ ├── 02_insert_data.sql # Sample data insertion
│ ├── 03_create_views.sql # View creation
│ ├── 04_user_privileges.sql # User & privilege creation

<h2>📋 Relational Schema</h2>
students ( campus_id, name, city, department, cgpa, semester, password_hash )  

courses ( course_id, course_name )  

teachers ( teacher_id, campus_id, name, teaching_skill, semester, contact_number, qualification )  

campus_id → students (campus_id)  

sections ( section_id, section_name, course_id, teacher_campus_id, schedule_time, estimated_learning_days, number_of_projects )  
course_id → courses (course_id)  
teacher_campus_id → teachers (campus_id)  

registrations ( registration_id, student_campus_id, section_id )  
student_campus_id → students (campus_id)  
section_id → sections (section_id)  

completed_courses ( completion_id, student_campus_id, section_id, skill_learnt, completion_date )  
student_campus_id → students (campus_id)  
section_id → sections (section_id)  

removal_logs ( log_id, student_campus_id, section_id, removed_at )  
student_campus_id → students (campus_id)  
section_id → sections (section_id)  


<h2>📊 Normalization Status</h2>

| Table | Normal Form | Justification |
| :--- | :--- | :--- |
| `students` | **3NF** | No transitive dependencies. All non-prime attributes depend directly on the primary key `campus_id`. |
| `courses` | **3NF** | No transitive dependencies. All non-prime attributes depend directly on `course_id`. |
| `teachers` | **3NF** | No transitive dependencies. All non-prime attributes depend directly on `teacher_id`. Foreign key `campus_id` references `students`. |
| `sections` | **3NF** | No transitive dependencies. All non-prime attributes depend directly on `section_id`. Foreign keys `course_id` and `teacher_campus_id` reference their parent tables. |
| `registrations` | **3NF** | No transitive dependencies. All non-prime attributes depend directly on the composite primary key `(student_campus_id, section_id)`. |
| `removal_logs` | **3NF** | No transitive dependencies. All non-prime attributes depend directly on `log_id`. |
| `completed_courses` | **Denormalized (3NF Violation)** | The attribute `skill_learnt` is deliberately denormalized to preserve a historical snapshot of the skill name at the time of graduation. If the course is later renamed in the `courses` table, the certificate should still show the original name. The fully normalized alternative would require joining `completed_courses` with `sections` and `courses` at query time, which would lose the historical accuracy of the certificate. |


<h2>🚀 Getting Started</h2>

<h3>Prerequisites</h3>

- PHP 7.4 or higher (with MySQLi extension enabled)
- MySQL 5.7 or higher
- Apache web server (XAMPP / WAMP / MAMP recommended)
- Git (for cloning)


<h3>Installation & Setup</h3>

<h4>Step 1: Clone the repository</h4>

```bash
git clone https://github.com/your-username/hustleup.git
cd hustleup
```
<h4>Step 2: Set up the database</h4>
Start XAMPP and enable Apache and MySQL.

Open your browser and go to http://localhost/phpmyadmin.

Create a new database named hustleup_db.

Go to the Import tab and import the SQL files in the following order:
01)_create_tables.sql # Table creation scripts
02)_insert_data.sql # Sample data insertion
03)_create_views.sql # View creation
04)_user_privileges.sql # User & privilege creation
05)_hustleup_db_dump.sql # Complete database dump

<h4>Step 3: Configure the application</h4>
Open db.php and ensure the credentials match your local setup:

define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'hustleup_db');

<h4>Step 4: Run the application</h4>
Move the project folder to C:\xampp\htdocs\ (Windows) or /Applications/XAMPP/htdocs/ (Mac).

Open your browser and visit: http://localhost/hustleup/index.php

<h4>Step 5: Login with demo credentials</h4>

<h2>💼 Commercialization Pathway</h2>
<h3>Phase 1: Core Enterprise Features</h3>
- MySQL InnoDB Cluster – Replace single-instance MySQL with a high-availability cluster using Group Replication and MySQL Router for automatic failover and read/write splitting.

- Automated Backups – Schedule full and incremental backups using Percona XtraBackup with S3 object storage retention.

- Point-in-Time Recovery – Enable binary logging with retention to support restoration to any specific point in time.

- Admin Dashboard – Develop a comprehensive admin panel for user management, platform analytics, and system monitoring.

- Email Notification System – Implement automated email alerts for enrollment, graduation, drops, and password reset using PHPMailer or SendGrid.

- PDF Certificate Generation – Generate downloadable PDF certificates with student name, skill learnt, completion date, and mentor signature.

- Profile Editing & User Management – Allow students and teachers to update passwords, contact numbers, and CGPA; enable admin-level user suspension and role changes.

- Capacity & Waitlist System – Add capacity to sections and enforce automatic enrollment closure; create a waitlist table for automatic enrollment when seats become available.

- Normalize schedule_time – Replace free-text schedule with a child table storing day and start time in separate typed columns to enable queries like "sections meeting on Tuesday".

- Replace estimated_learning_days with Actual Dates – Add section_start_date and section_end_date to enable automatic completion and queries like "sections starting next week".

- Add reason Column to removal_logs – Distinguish between mentor-initiated drops and graduation departures for clearer audit trails.

- Soft Deletes – Add is_active or deleted_at to key tables (students, sections, teachers) to preserve historical data and allow recovery of accidentally removed records.

<h3>hase 2: Advanced Features</h3>
- Ratings & Reviews System – Allow learners to rate completed sections and display average ratings in the advising portal to guide future students.

- Achievements & Gamification – Award badges like "Top Performer", "Skill Master", and "Community Leader" based on student activity and completions.

- Discussion Forums per Section – Create forum_posts and forum_replies tables for section-based student-teacher interaction.

- Peer-to-Peer Mentoring – Enable students to request 1-on-1 mentoring sessions with teachers outside of section hours.

- Student Recommendations Engine – Recommend related skills based on completed courses (e.g., "You learned Python, try Web Development next").

- Learning Analytics Dashboard – Provide teachers with visual analytics on enrollment trends, completion rates, and student performance.

- Student Progress Reports – Generate individual reports showing completed skills, ongoing sections, and CGPA trends over time.

- Export Functionality – Allow teachers and admins to export student rosters and completion records as CSV or PDF.

- Bulk Operations – Enable teachers to drop multiple students or graduate all eligible students with a single action.

<h3>Phase 3: Scalability & Enterprise Expansion</h3>
- Multi-Institution Support – Add an institutions table to support multiple universities or campuses with isolated data, separate administrators, and custom branding.

- RESTful API Development – Build a RESTful API using PHP for mobile app development, third-party integrations, and external analytics tools.

- Single Sign-On (SSO) Integration – Integrate with university authentication systems via OAuth 2.0, LDAP, or SAML for seamless login.

- Mobile Application Development – Develop Android and iOS applications using React Native or Flutter for student bookings and notifications.

- Payment Gateway Integration – Integrate with Stripe/PayPal for paid courses, subscription plans, and certificate fees.

- SaaS Subscription Model – Implement tiered pricing (Free, Pro, Enterprise) with premium features like advanced analytics, custom branding, and dedicated support.

- White-label Solution – Allow institutions to customize branding, logos, and color schemes for their platform instance.

- Internationalization – Add multi-language and multi-currency support for global market expansion.

- Disaster Recovery (DR) – Deploy InnoDB ClusterSet with primary and replica clusters in alternate geographic locations for automated failover and business continuity.

- Elasticsearch Integration – Replace basic SQL search with Elasticsearch for fuzzy matching, autocomplete, and advanced search capabilities.

<h2>📊 Monetization Strategy</h2>
- Commission per Enrollment – Percentage of revenue from paid course enrollments.

- SaaS Subscription Plans – Monthly/yearly fees for institutions (Free, Pro, Enterprise tiers).

- Premium Feature Add-ons – Advanced analytics, integrations, and custom branding.

- Certificate Verification Fees – One-time fees for official certificate verification and printing.

- White-label Licensing – One-time licensing fee for custom-branded deployments.

- API Access – Monthly fees for third-party API access and integrations.

<h2>📈 Market Expansion Roadmap</h2>

- Phase 1 – Pilot deployment on 1 campus with 500 users; core enterprise features implemented.

- Phase 2 – Campus rollout to 5 campuses with 2,500 users; mobile app and API launched.

- Phase 3 – National expansion to 25 campuses with 15,000 users; AI-driven analytics and gamification enabled.

- Phase 4 – Global market entry with 100+ campuses and 100,000+ users; multi-language and multi-currency support.




