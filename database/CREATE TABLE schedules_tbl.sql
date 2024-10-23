CREATE TABLE schedules (
    schedule_id INT AUTO_INCREMENT PRIMARY KEY, -- Unique identifier for each schedule
    user_id INT NOT NULL, -- References the user who uploaded the schedule
    subject_code VARCHAR(50) NOT NULL, -- Subject code (e.g., "CS101")
    subject VARCHAR(255) NOT NULL, -- Subject name or title
    section VARCHAR(50) NOT NULL, -- Section information (e.g., "A", "B", "1st Year")
    instructor VARCHAR(255) NOT NULL, -- Instructor's full name
    start_time TIME NOT NULL, -- Class start time (e.g., "14:00:00")
    end_time TIME NOT NULL, -- Class end time (e.g., "15:30:00")
    days VARCHAR(50) NOT NULL, -- Days of the week (e.g., "Monday, Wednesday")
    class_type ENUM('lecture', 'lab') NOT NULL, -- Type of class (lecture or lab)
    ay_semester INT NOT NULL, -- Foreign key for academic year and semester (references term_id in terms_tbl)
    department_id INT NOT NULL, -- Foreign key for department (references dept_id in dept_tbl)
    sched_status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending', -- Schedule status
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, -- Automatically record when the schedule is created
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP, -- Automatically update when the schedule is modified
    
    -- Indexes for faster querying
    INDEX (subject_code),
    INDEX (instructor),
    INDEX (ay_semester),
    INDEX (department_id),

    -- Foreign key constraints
    CONSTRAINT fk_user_id FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    CONSTRAINT fk_ay_semester FOREIGN KEY (ay_semester) REFERENCES terms_tbl(term_id) ON DELETE CASCADE,
    CONSTRAINT fk_department_id FOREIGN KEY (department_id) REFERENCES dept_tbl(dept_id) ON DELETE CASCADE
);
