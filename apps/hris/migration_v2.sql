-- ============================================================
-- HRIS Migration V2
-- Jalankan file ini SEBELUM deploy file PHP baru
-- ============================================================

-- ── 1. ALTER ess_users: tambah kolom HR ──────────────────────
ALTER TABLE ess_users
    ADD COLUMN IF NOT EXISTS tipe_kontrak ENUM('PKWTT','PKWT','Kontrak','Magang','Freelance') DEFAULT 'PKWTT' AFTER role,
    ADD COLUMN IF NOT EXISTS employee_status ENUM('Active','Probation','Inactive','Resigned','Terminated') DEFAULT 'Active' AFTER tipe_kontrak,
    ADD COLUMN IF NOT EXISTS department VARCHAR(100) DEFAULT NULL AFTER division,
    ADD COLUMN IF NOT EXISTS position VARCHAR(100) DEFAULT NULL AFTER department,
    ADD COLUMN IF NOT EXISTS kontrak_start DATE DEFAULT NULL AFTER join_date,
    ADD COLUMN IF NOT EXISTS kontrak_end DATE DEFAULT NULL AFTER kontrak_start,
    ADD COLUMN IF NOT EXISTS probation_end DATE DEFAULT NULL AFTER kontrak_end,
    ADD COLUMN IF NOT EXISTS manager_id INT DEFAULT NULL AFTER probation_end,
    ADD COLUMN IF NOT EXISTS npwp VARCHAR(20) DEFAULT NULL AFTER address,
    ADD COLUMN IF NOT EXISTS no_bpjs_kes VARCHAR(20) DEFAULT NULL AFTER npwp,
    ADD COLUMN IF NOT EXISTS no_bpjs_tk VARCHAR(20) DEFAULT NULL AFTER no_bpjs_kes,
    ADD COLUMN IF NOT EXISTS gender ENUM('Laki-laki','Perempuan') DEFAULT NULL AFTER fullname,
    ADD COLUMN IF NOT EXISTS birth_date DATE DEFAULT NULL AFTER gender,
    ADD COLUMN IF NOT EXISTS education VARCHAR(50) DEFAULT NULL AFTER birth_date;

-- ── 2. Tabel departemen & posisi ─────────────────────────────
CREATE TABLE IF NOT EXISTS ess_departments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    code VARCHAR(20),
    parent_id INT DEFAULT NULL COMMENT 'NULL = Division level, filled = Department under division',
    head_employee_id VARCHAR(50) DEFAULT NULL COMMENT 'NIK kepala departemen',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_parent (parent_id)
);

CREATE TABLE IF NOT EXISTS ess_positions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(100) NOT NULL,
    grade VARCHAR(20) DEFAULT NULL COMMENT 'Misal: G1, G2, Manager Level, dll',
    department_id INT DEFAULT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- ── 3. Tabel shift ───────────────────────────────────────────
CREATE TABLE IF NOT EXISTS ess_shifts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    shift_name VARCHAR(50) NOT NULL,
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    late_tolerance_minutes INT DEFAULT 15 COMMENT 'Toleransi keterlambatan dalam menit',
    is_active TINYINT(1) DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- Seed data shift default
INSERT IGNORE INTO ess_shifts (id, shift_name, start_time, end_time, late_tolerance_minutes) VALUES
(1, 'Regular (08:00-17:00)', '08:00:00', '17:00:00', 15),
(2, 'Pagi (06:00-14:00)',    '06:00:00', '14:00:00', 15),
(3, 'Siang (14:00-22:00)',   '14:00:00', '22:00:00', 15),
(4, 'Malam (22:00-06:00)',   '22:00:00', '06:00:00', 15);

CREATE TABLE IF NOT EXISTS ess_employee_shifts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    employee_id VARCHAR(50) NOT NULL,
    shift_id INT NOT NULL,
    effective_date DATE NOT NULL,
    assigned_by VARCHAR(100),
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_emp (employee_id),
    INDEX idx_shift (shift_id)
);

-- ── 4. Tabel lifecycle karyawan ──────────────────────────────
CREATE TABLE IF NOT EXISTS ess_lifecycle (
    id INT AUTO_INCREMENT PRIMARY KEY,
    employee_id VARCHAR(50) NOT NULL,
    event_type ENUM(
        'Hired',
        'Probation Start',
        'Probation End',
        'Confirmed',
        'Promoted',
        'Transferred',
        'Contract Renewed',
        'Contract Expired',
        'Resigned',
        'Terminated',
        'Reactivated'
    ) NOT NULL,
    event_date DATE NOT NULL,
    old_position VARCHAR(100) DEFAULT NULL,
    new_position VARCHAR(100) DEFAULT NULL,
    old_division VARCHAR(100) DEFAULT NULL,
    new_division VARCHAR(100) DEFAULT NULL,
    old_salary DECIMAL(15,2) DEFAULT NULL,
    new_salary DECIMAL(15,2) DEFAULT NULL,
    notes TEXT DEFAULT NULL,
    created_by VARCHAR(100),
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_emp (employee_id),
    INDEX idx_event (event_type)
);

-- ── 5. Tabel leave policy ────────────────────────────────────
CREATE TABLE IF NOT EXISTS ess_leave_policy (
    id INT AUTO_INCREMENT PRIMARY KEY,
    leave_type VARCHAR(50) NOT NULL UNIQUE,
    annual_quota INT DEFAULT 12 COMMENT 'Hari per tahun',
    min_service_months INT DEFAULT 0 COMMENT 'Minimal masa kerja (bulan) untuk bisa pakai cuti ini',
    carry_forward TINYINT(1) DEFAULT 0 COMMENT '1 = sisa kuota bisa dibawa ke tahun berikut',
    max_carry_forward INT DEFAULT 0 COMMENT 'Maksimal hari yang bisa dibawa',
    gender_specific ENUM('All','Laki-laki','Perempuan') DEFAULT 'All',
    is_paid TINYINT(1) DEFAULT 1,
    requires_document TINYINT(1) DEFAULT 0,
    notes TEXT DEFAULT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- Seed data leave policy default
INSERT IGNORE INTO ess_leave_policy (leave_type, annual_quota, min_service_months, carry_forward, max_carry_forward, is_paid, requires_document) VALUES
('Cuti Tahunan',  12, 12, 1, 5,  1, 0),
('Sakit',          0,  0, 0, 0,  1, 1),
('Izin Khusus',    0,  0, 0, 0,  1, 0),
('Cuti Melahirkan',90, 0, 0, 0,  1, 1),
('Cuti Ayah',      2,  0, 0, 0,  1, 1),
('Cuti Besar',    30, 72, 0, 0,  1, 0);

-- ── 6. Log reset kuota tahunan ──────────────────────────────
CREATE TABLE IF NOT EXISTS ess_leave_reset_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    employee_id VARCHAR(50) NOT NULL,
    reset_year YEAR NOT NULL,
    old_quota INT,
    new_quota INT,
    carry_forward_days INT DEFAULT 0,
    reset_by VARCHAR(100),
    reset_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_reset (employee_id, reset_year)
);
