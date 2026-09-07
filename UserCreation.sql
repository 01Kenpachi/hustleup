
CREATE USER 'hustleup_reader'@'localhost' IDENTIFIED BY 'passsword_read';
GRANT SELECT ON hustleup_db.* TO 'hustleup_reader'@'localhost';
FLUSH PRIVILEGES;
