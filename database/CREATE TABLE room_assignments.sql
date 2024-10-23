CREATE TABLE room_assignments (
    assignment_id INT AUTO_INCREMENT PRIMARY KEY,
    room_id INT NOT NULL,
    schedule_id INT NOT NULL,
    assigned_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (room_id) REFERENCES rooms_tbl(room_id) ON DELETE CASCADE,
    FOREIGN KEY (schedule_id) REFERENCES schedules(schedule_id) ON DELETE CASCADE
);
