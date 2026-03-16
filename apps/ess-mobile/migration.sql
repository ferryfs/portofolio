
-- 1. Tabel notifikasi per user
CREATE TABLE IF NOT EXISTS ess_notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    employee_id VARCHAR(50) NOT NULL,
    type VARCHAR(50) NOT NULL COMMENT 'checkin, checkout, leave_approved, leave_rejected, overtime_approved, payslip',
    message TEXT NOT NULL,
    is_read TINYINT(1) DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_emp (employee_id),
    INDEX idx_read (is_read)
);

-- 2. Tabel pengajuan lembur
CREATE TABLE IF NOT EXISTS ess_overtime (
    id INT AUTO_INCREMENT PRIMARY KEY,
    employee_id VARCHAR(50) NOT NULL,
    fullname VARCHAR(100) NOT NULL,
    division VARCHAR(100),
    overtime_date DATE NOT NULL,
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    duration_hours DECIMAL(4,2),
    reason TEXT NOT NULL,
    status ENUM('Pending','Approved','Rejected') DEFAULT 'Pending',
    approved_by VARCHAR(100),
    approved_at DATETIME,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_emp (employee_id),
    INDEX idx_status (status)
);

-- 3. Tabel payslip
CREATE TABLE IF NOT EXISTS ess_payslips (
    id INT AUTO_INCREMENT PRIMARY KEY,
    employee_id VARCHAR(50) NOT NULL,
    period_month TINYINT NOT NULL COMMENT '1-12',
    period_year YEAR NOT NULL,
    basic_salary DECIMAL(15,2) DEFAULT 0,
    transport_allowance DECIMAL(15,2) DEFAULT 0,
    meal_allowance DECIMAL(15,2) DEFAULT 0,
    overtime_pay DECIMAL(15,2) DEFAULT 0,
    deduction_tax DECIMAL(15,2) DEFAULT 0,
    deduction_bpjs DECIMAL(15,2) DEFAULT 0,
    deduction_absence DECIMAL(15,2) DEFAULT 0,
    net_salary DECIMAL(15,2) DEFAULT 0,
    attendance_days INT DEFAULT 0,
    absent_days INT DEFAULT 0,
    overtime_hours DECIMAL(6,2) DEFAULT 0,
    generated_by VARCHAR(100),
    generated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    notes TEXT,
    UNIQUE KEY unique_period (employee_id, period_month, period_year),
    INDEX idx_emp (employee_id)
);

-- 4. Tambah kolom phone_number jika belum ada (safe)
ALTER TABLE ess_users MODIFY COLUMN phone_number VARCHAR(20) DEFAULT '-';
ALTER TABLE ess_users MODIFY COLUMN address TEXT;

-- 5. Tambah kolom leave_type di ess_leaves jika butuh 'Sakit'
-- (sudah ada, cuma perlu tambah opsi di aplikasi)
