-- Drop existing database if it exists
DROP DATABASE IF EXISTS dittobase;

-- Create a new database
CREATE DATABASE dittobase;
USE dittobase;

-- Create members table
CREATE TABLE members (
    member_id INT AUTO_INCREMENT PRIMARY KEY,
    name ENUM('Minji', 'Hanni', 'Danielle', 'Haerin', 'Hyein') NOT NULL
);

-- Insert members data
INSERT INTO members (name) VALUES 
('Minji'), 
('Hanni'), 
('Danielle'), 
('Haerin'), 
('Hyein');

-- Create albums table
CREATE TABLE albums (
    album_id INT AUTO_INCREMENT PRIMARY KEY,
    album_name VARCHAR(255) NOT NULL,
    release_date DATE NOT NULL
);

-- Create versions table
CREATE TABLE versions (
    version_id INT AUTO_INCREMENT PRIMARY KEY,
    version_name VARCHAR(255) NOT NULL
);

-- Create album_versions table
CREATE TABLE album_versions (
    album_id INT,
    version_id INT,
    PRIMARY KEY (album_id, version_id),
    FOREIGN KEY (album_id) REFERENCES albums(album_id),
    FOREIGN KEY (version_id) REFERENCES versions(version_id)
);

-- Create pobs table
CREATE TABLE pobs (
    pob_id INT AUTO_INCREMENT PRIMARY KEY,
    album_id INT,
    pob_version VARCHAR(255) NOT NULL,
    FOREIGN KEY (album_id) REFERENCES albums(album_id)
);

-- Create photocards table
CREATE TABLE photocards (
    photocard_id INT AUTO_INCREMENT PRIMARY KEY,
    album_id INT,
    member_id INT,
    version_id INT,
    pob_id INT DEFAULT NULL,
    image VARCHAR(255) NOT NULL,
    FOREIGN KEY (album_id) REFERENCES albums(album_id),
    FOREIGN KEY (member_id) REFERENCES members(member_id),
    FOREIGN KEY (version_id) REFERENCES versions(version_id),
    FOREIGN KEY (pob_id) REFERENCES pobs(pob_id)
);

-- Create users table
CREATE TABLE users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) NOT NULL UNIQUE,
    username VARCHAR(255) NOT NULL,
    password VARCHAR(255) NOT NULL
);

-- Create user_photocards table
CREATE TABLE user_photocards (
    user_photocard_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    photocard_id INT,
    status ENUM('on hand', 'not on hand', 'wishlist') NOT NULL,
    FOREIGN KEY (user_id) REFERENCES users(user_id),
    FOREIGN KEY (photocard_id) REFERENCES photocards(photocard_id)
);
