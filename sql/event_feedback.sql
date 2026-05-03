-- Event Feedback table for the Events Module Overhaul
-- Run this SQL manually in phpMyAdmin or MySQL CLI

CREATE TABLE IF NOT EXISTS event_feedback (
    id INT AUTO_INCREMENT PRIMARY KEY,
    participation_id INT NOT NULL UNIQUE,
    rating INT NOT NULL DEFAULT 5,
    comment TEXT DEFAULT NULL,
    would_recommend TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_feedback_participation FOREIGN KEY (participation_id) 
        REFERENCES event_participation(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
