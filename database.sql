CREATE DATABASE lms_database;
USE lms_database;

CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    role ENUM('etudiant', 'enseignant', 'promoteur') NOT NULL
);

CREATE TABLE courses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    type ENUM('pdf', 'video') NOT NULL,
    content_url VARCHAR(255) NOT NULL
);

CREATE TABLE progress (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL,
    course_id INT NOT NULL,
    score INT DEFAULT 0,
    FOREIGN KEY (course_id) REFERENCES courses(id),
    UNIQUE(username, course_id)
);
