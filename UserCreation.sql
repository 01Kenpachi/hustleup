1. /*This is the logical SQL equivalent for creating and granting privileges to the read-only user 'hustleup_reader' for local host. 
The user was created via the Always Data admin panel due to hosting restrictions.*/

CREATE USER 'hustleup_reader'@'localhost' IDENTIFIED BY 'passsword_read';
GRANT SELECT ON hustleup_db.* TO 'hustleup_reader'@'localhost';
FLUSH PRIVILEGES;