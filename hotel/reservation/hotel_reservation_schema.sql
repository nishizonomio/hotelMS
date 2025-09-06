-- Create the database
CREATE DATABASE IF NOT EXISTS `hotel`;

-- Use the database
USE `hotel`;

-- Guests table
CREATE TABLE IF NOT EXISTS `guests` (
  `guest_id` INT AUTO_INCREMENT PRIMARY KEY,
  `first_name` VARCHAR(255) NOT NULL,
  `last_name` VARCHAR(255) NOT NULL,
  `email` VARCHAR(255),
  `first_phone` VARCHAR(20),
  `second_phone` VARCHAR(20),
  `status` VARCHAR(50),
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Rooms table
CREATE TABLE IF NOT EXISTS `rooms` (
  `room_id` INT AUTO_INCREMENT PRIMARY KEY,
  `room_number` VARCHAR(10) NOT NULL UNIQUE,
  `room_type` ENUM('Single Room','Double Room','Twin Room','Deluxe Room','Suite','Family Room') NOT NULL,
  `max_occupancy` INT,
  `price_rate` DECIMAL(10,2) NOT NULL,
  `status` ENUM('available','occupied','reserved','under maintenance','dirty') DEFAULT 'available',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Reservations table
CREATE TABLE IF NOT EXISTS `reservations` (
  `reservation_id` INT AUTO_INCREMENT PRIMARY KEY,
  `guest_id` INT NOT NULL,
  `room_id` INT NOT NULL,
  `status` VARCHAR(50),
  `remarks` TEXT,
  `check_in` DATETIME NOT NULL,
  `check_out` DATETIME NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`guest_id`) REFERENCES `guests` (`guest_id`),
  FOREIGN KEY (`room_id`) REFERENCES `rooms` (`room_id`)
);

-- Bookings table
CREATE TABLE IF NOT EXISTS `bookings` (
  `booking_id` INT AUTO_INCREMENT PRIMARY KEY,
  `guest_id` INT NOT NULL,
  `room_id` INT NOT NULL,
  `booking_type` ENUM('reservation','walk-in'),
  `status` ENUM('pending','confirmed','checked_in','checked_out') DEFAULT 'pending',
  `remarks` TEXT,
  `check_in` TIMESTAMP NULL,
  `check_out` TIMESTAMP NULL,
  `booking_date` DATE NOT NULL,
  FOREIGN KEY (`guest_id`) REFERENCES `guests` (`guest_id`),
  FOREIGN KEY (`room_id`) REFERENCES `rooms` (`room_id`)
);

-- Reservation calendar table
CREATE TABLE IF NOT EXISTS `reservation_calendar` (
  `calendar_id` INT AUTO_INCREMENT PRIMARY KEY,
  `room_id` INT,
  `guest_id` INT,
  `event_date` DATE NOT NULL,
  `event_type` ENUM('reserved','occupied','maintenance','checked_in','checked_out','extend_stay','note') NOT NULL,
  `note` TEXT,
  FOREIGN KEY (`room_id`) REFERENCES `rooms` (`room_id`),
  FOREIGN KEY (`guest_id`) REFERENCES `guests` (`guest_id`)
);

-- Walk-ins table
CREATE TABLE IF NOT EXISTS `walk_ins` (
    `walk_in_id` INT AUTO_INCREMENT PRIMARY KEY,
    `guest_id` INT NOT NULL,
    `room_id` INT NOT NULL,
    `check_in_time` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `expected_check_out` DATETIME NOT NULL,
    `actual_check_out` DATETIME NULL,
    `status` ENUM('checked_in','checked_out') DEFAULT 'checked_in',
    `payment_status` ENUM('pending','partial','paid') DEFAULT 'pending',
    `total_amount` DECIMAL(10,2),
    `remarks` TEXT,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`guest_id`) REFERENCES `guests` (`guest_id`),
    FOREIGN KEY (`room_id`) REFERENCES `rooms` (`room_id`)
);

-- Triggers for updated_at fields
DELIMITER //
CREATE TRIGGER guests_before_update
BEFORE UPDATE ON guests
FOR EACH ROW
BEGIN
    SET NEW.updated_at = CURRENT_TIMESTAMP;
END//

CREATE TRIGGER rooms_before_update
BEFORE UPDATE ON rooms
FOR EACH ROW
BEGIN
    SET NEW.updated_at = CURRENT_TIMESTAMP;
END//

CREATE TRIGGER reservations_before_update
BEFORE UPDATE ON reservations
FOR EACH ROW
BEGIN
    SET NEW.updated_at = CURRENT_TIMESTAMP;
END//

CREATE TRIGGER walk_ins_before_update
BEFORE UPDATE ON walk_ins
FOR EACH ROW
BEGIN
    SET NEW.updated_at = CURRENT_TIMESTAMP;
END//
DELIMITER ;
