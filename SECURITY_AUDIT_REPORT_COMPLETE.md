# 🔒 SECURITY AUDIT REPORT - PORTFOLIO CMS
**Generated:** February 5, 2026  
**Status:** CRITICAL VULNERABILITIES FOUND & PARTIALLY FIXED

---

## 📋 EXECUTIVE SUMMARY

This repository contains **multiple critical security vulnerabilities** across:
- **SQL Injection** (162+ instances of string interpolation in queries)
- **Weak Password Hashing** (MD5 still used in 5+ files)
- **CSRF Vulnerabilities** (state-changing operations via GET)
- **Insecure File Uploads** (no MIME validation, path traversal risks)
- **Missing Access Control** (insufficient session validation)
- **Information Disclosure** (error messages exposed to users)

**Priority:** URGENT - Deploy fixes before production use

---

## ✅ FIXES COMPLETED

### 1. ✅ Security Helper Library Created
**File:** `config/security.php` (NEW)
- Password hashing: `hashPassword()` & `verifyPassword()`
- Prepared statements: `safeQuery()` & `safeExecute()`
- Input validation: `sanitizeInput()`, `sanitizeInt()`, `isValidEmail()`
- File upload: `handleFileUpload()` with MIME validation
- CSRF: `generateCSRFToken()`, `verifyCSRFToken()`, `csrfTokenField()`
- Session security: `initSecureSession()`, `regenerateSessionId()`
- Logging: `logSecurityEvent()`, `handleError()`
- Rate limiting: `checkRateLimit()`

### 2. ✅ Database Configuration Secured
**File:** `config/database.php`
- Using PDO (modern, prepared statement ready)
- Environment variables for credentials
- UTF-8 charset set
- Direct access blocked
- Error handling improved

### 3. ✅ Auth Files Updated (MD5 → Bcrypt + Prepared Statements)

#### **apps/sales-brief/auth.php**
✅ FIXED:
- [x] Register: MD5 → `hashPassword()` (bcrypt)
- [x] Register: String interpolation → prepared statements
- [x] Login: Added CSRF token verification
- [x] Login: Added rate limiting
- [x] Login: MD5 password auto-upgrade on login
- [x] Logout: Changed from `session_destroy()` to `destroySession()`
- [x] IP logging: String interpolation → prepared statement
- [x] Added security logging with `logSecurityEvent()`

#### **apps/ess-mobile/auth.php**
✅ FIXED (same as above):
- [x] Register: MD5 → bcrypt + prepared statements
- [x] Login: CSRF token + rate limiting + auto-upgrade
- [x] Improved session security

### 4. ✅ State-Changing Operations Secured
**File:** `apps/sales-brief/change_status.php`
✅ FIXED:
- [x] Changed from GET to POST method
- [x] Added CSRF token verification
- [x] Changed SQL interpolation to prepared statements
- [x] Added input validation with `sanitizeInt()`
- [x] Added security logging

---

## ⚠️ REMAINING VULNERABILITIES

### CRITICAL - Must Fix Immediately

#### 1. SQL Injection in WMS Module (162 instances)
**Severity:** CRITICAL  
**Files Affected:**
- `apps/wms/shipping.php` (9 vulnerable queries)
- `apps/wms/physical_inventory.php` (4 instances)
- `apps/wms/rf_scanner.php` (8 instances)
- `apps/wms/task_confirm.php` (8 instances)
- `apps/wms/inbound.php` (4 instances)
- `apps/wms/internal.php` (5 instances)
- `apps/wms/master_data.php` (7 instances)
- `apps/wms/outbound.php` (3 instances)
- `apps/wms/print_label.php` (2 instances)
- `apps/wms/task.php` (3 instances)
- And 15+ more WMS files

**Example Vulnerable Code:**
```php
// ❌ BEFORE (VULNERABLE)
$so_number = $_POST['so_number'];  // No validation
mysqli_query($conn, "UPDATE wms_so_header SET status='COMPLETED' WHERE so_number='$so_number'");

// ✅ AFTER (SECURE)
$so_number = sanitizeInput($_POST['so_number']);
$stmt = $conn->prepare("UPDATE wms_so_header SET status='COMPLETED' WHERE so_number=?");
$stmt->bind_param("s", $so_number);
$stmt->execute();
```

**Action Plan:**
- [ ] Replace all `mysqli_query()` interpolation with prepared statements
- [ ] Add input validation (`sanitizeInt()`, `sanitizeInput()`)
- [ ] Test all CRUD operations

#### 2. SQL Injection in Sales Brief Module (30+ instances)
**Severity:** CRITICAL  
**Files Affected:**
- `apps/sales-brief/list_draft.php` (3 instances)
- `apps/sales-brief/process_reopen.php` (5 instances)
- `apps/sales-brief/view_sb.php` (3 instances)
- `apps/sales-brief/report_detail.php` (2 instances)
- And 8+ more files

**Fix Status:** change_status.php ✅ Fixed | Others ⏳ Pending

#### 3. SQL Injection in TMS Module (20+ instances)
**Files Affected:**
- `apps/tms/outbound.php`
- `apps/tms/orders.php`
- `apps/tms/drivers.php`
- `apps/tms/billing.php`
- `apps/tms/dashboard.php`

#### 4. SQL Injection in ESS/HRIS Module (15+ instances)
**Files Affected:**
- `apps/ess-mobile/menu_approval.php` (4 instances)
- `apps/ess-mobile/menu_setting.php` (3 instances)
- `apps/ess-mobile/attendance.php` (3 instances)
- `apps/hris/menu_employee.php` (3 instances)

#### 5. State-Changing via GET (CSRF)
**Severity:** HIGH  
**Files with GET delete/update:**
- [ ] `admin.php` - hapus_proj, hapus_time, hapus_tech, hapus_cert
- [x] `apps/sales-brief/change_status.php` ✅ FIXED
- [ ] `apps/sales-brief/list_draft.php` - ?delete parameter
- [ ] `apps/ess-mobile/menu_approval.php` - ?action & ?id
- [ ] `apps/hris/menu_employee.php` - ?delete parameter

**Required Action:**
- [ ] Convert GET requests to POST
- [ ] Add CSRF token verification
- [ ] Use prepared statements

#### 6. Weak Password Hashing Still in Use
**Severity:** HIGH  
**Files Using MD5:**
- [ ] `apps/tms/auth.php` - Lines 33, 57
- [ ] `apps/wms/auth.php` - Lines 31, 46
- [ ] `apps/ess-mobile/menu_setting.php` - Lines 23-24 (password change)

**Action:**
- [ ] Replace with `hashPassword()` function
- [ ] Create migration script for existing passwords
- [ ] Force password reset for old hashes

#### 7. Missing File Upload Validation
**Severity:** HIGH  
**Files With Unvalidated Uploads:**
- [ ] `admin.php` (profile_pic, about_img, project images, cert images)
- [ ] `edit_project.php`
- [ ] `edit_timeline.php`

**Current Issues:**
- No MIME type validation
- No size validation in all locations
- No extension whitelist
- Predictable filenames (sequential numbers)
- Files stored in webroot with execution enabled

**Fix Template:**
```php
// Use handleFileUpload() function from security.php
$uploadResult = handleFileUpload(
    $_FILES['image'],
    'assets/img/',
    ['image/jpeg', 'image/png', 'image/webp'],
    5242880, // 5MB
    ['jpg', 'jpeg', 'png', 'webp']
);

if (!$uploadResult['success']) {
    die('Upload failed: ' . $uploadResult['error']);
}

$newFilename = $uploadResult['filename'];
```

---

## 🔐 SECURITY CONFIGURATION CHECKLIST

### Session Security
- [ ] Set secure cookie flags in all auth files
- [ ] Implement session timeout on inactivity
- [ ] Use `initSecureSession()` from security.php
- [ ] Add session regeneration after privilege changes

### Input Validation
- [ ] Validate all `$_GET` parameters with `sanitizeInt()` or `sanitizeInput()`
- [ ] Validate email with `isValidEmail()`
- [ ] Validate URLs with `isValidUrl()`
- [ ] Set max length limits on text inputs

### Output Escaping
- [ ] Use `escapeHtml()` for all HTML output
- [ ] Use `escapeJs()` for JavaScript contexts
- [ ] Use `escapeUrl()` for URL contexts
- [ ] Audit all `echo`, `print`, `?>...<?php` outputs

### Database
- [ ] Convert ALL MySQLi queries to prepared statements
- [ ] Use parameter binding instead of string interpolation
- [ ] Test for SQL injection in every query
- [ ] Add database query logging

### File Uploads
- [ ] Implement MIME type validation with finfo_file()
- [ ] Generate random filenames
- [ ] Set permissions to 0644
- [ ] Consider storing outside webroot
- [ ] Add file type whitelist

### Error Handling
- [ ] Never expose database errors to users
- [ ] Log errors to file, not screen
- [ ] Show generic "System Error" message to users
- [ ] Create `/logs/` directory with proper permissions
- [ ] Implement error logging function

### HTTPS/Transport
- [ ] Force HTTPS on production `$_SERVER['HTTPS']`
- [ ] Set Secure flag on cookie params
- [ ] Set SameSite=Strict
- [ ] Add HSTS header

---

## 📝 MIGRATION GUIDE FOR MD5 PASSWORDS

### Current State
- New registrations: Bcrypt (via `hashPassword()`)
- Old logins: Fallback to MD5, auto-upgrade on login
- Mix of MD5 and Bcrypt in database

### Migration Steps
1. **Deploy** security.php and updated auth files
2. **Auto-upgrade:** Each MD5 login triggers upgrade to Bcrypt
3. **Verify:** Run query to check remaining MD5:
   ```sql
   SELECT COUNT(*) FROM sales_brief_users 
   WHERE password NOT LIKE '$2%' AND password NOT LIKE '$y%';
   ```
4. **Force reset:** For inactive MD5 users (90+ days)
   ```sql
   UPDATE sales_brief_users 
   SET password = 'RESET_REQUIRED' 
   WHERE password LIKE MD5('*') AND last_login < DATE_SUB(NOW(), INTERVAL 90 DAY);
   ```

---

## 🔧 RECOMMENDED FIX PRIORITY

### PHASE 1 (CRITICAL - Deploy ASAP)
1. ✅ `config/security.php` - helper functions
2. ✅ `apps/sales-brief/auth.php` - bcrypt + CSRF
3. ✅ `apps/ess-mobile/auth.php` - bcrypt + CSRF
4. ✅ `apps/sales-brief/change_status.php` - POST + CSRF
5. [ ] `admin.php` - Convert GET delete to POST + CSRF
   - Line 116: `?hapus_proj` 
   - Line 145: `?hapus_time`
   - Line 164: `?hapus_tech`
   - Line 183: `?hapus_cert`

### PHASE 2 (HIGH - Within 1 Week)
6. [ ] `apps/wms/*` - Replace all mysqli_query with prepared statements (20+ files)
7. [ ] `apps/tms/*` - Replace all mysqli_query with prepared statements (10+ files)
8. [ ] `apps/ess-mobile/*` - Replace all mysqli_query with prepared statements
9. [ ] `apps/hris/*` - Replace all mysqli_query with prepared statements
10. [ ] `apps/sales-brief/*` - Replace remaining mysqli_query (list_draft.php, process_reopen.php, etc.)

### PHASE 3 (MEDIUM - Within 2 Weeks)
11. [ ] File uploads: Add MIME validation to all `move_uploaded_file()` calls
12. [ ] File uploads: Generate random filenames
13. [ ] All GET delete/update: Convert to POST + CSRF
14. [ ] Password change: Replace MD5 in menu_setting.php files

### PHASE 4 (ONGOING)
15. [ ] Audit all output for XSS
16. [ ] Add rate limiting to all sensitive endpoints
17. [ ] Implement request logging
18. [ ] Add HTTPS enforcing
19. [ ] Security header implementation (CSP, X-Frame-Options, etc.)

---

## 🧪 TESTING CHECKLIST

### SQL Injection Testing
- [ ] Test each query with `' OR '1'='1` in input
- [ ] Test with UNION SELECT attacks
- [ ] Test with time-based blind SQL injection
- [ ] Verify prepared statements are used

### CSRF Testing
- [ ] Try POST without csrf_token
- [ ] Try with invalid csrf_token
- [ ] Monitor token regeneration

### File Upload Testing
- [ ] Try uploading PHP file → Should be blocked
- [ ] Try uploading with MIME type mismatch → Should be blocked
- [ ] Try uploading file > 5MB → Should be blocked
- [ ] Try directory traversal: `../../../etc/passwd` → Should be blocked

### Authentication Testing
- [ ] Rate limit: 6 failed logins in 5 min → Should block
- [ ] Password upgrade: Login with MD5 password → Should upgrade to Bcrypt
- [ ] Session: Kill session cookie → Should redirect to login
- [ ] Session timeout: Wait 1 hour inactive → Should logout

---

## 📚 REFERENCE IMPLEMENTATIONS

### 1. Simple Query Migration Template
```php
// ❌ BEFORE
$id = $_GET['id'];
$result = mysqli_query($conn, "SELECT * FROM table WHERE id='$id'");

// ✅ AFTER
$id = sanitizeInt($_GET['id']);
if ($id === false) die('Invalid ID');
$stmt = $conn->prepare("SELECT * FROM table WHERE id=?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
```

### 2. String Parameter Template
```php
// ❌ BEFORE
$name = $_POST['name'];
mysqli_query($conn, "UPDATE users SET name='$name' WHERE id='$id'");

// ✅ AFTER
$name = sanitizeInput($_POST['name'] ?? '');
$id = sanitizeInt($_POST['id']);
$stmt = $conn->prepare("UPDATE users SET name=? WHERE id=?");
$stmt->bind_param("si", $name, $id);
$stmt->execute();
```

### 3. Form with CSRF Template
```php
// In controller/auth.php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        die('Invalid request');
    }
    // Process form
}

// In form HTML
<?php ec() ?>
<form method="POST">
    <?= csrfTokenField() ?>
    <input type="email" name="email" required>
    <button type="submit">Submit</button>
</form>
```

---

## 📞 NEXT STEPS

1. **Review** this report with development team
2. **Extract** vulnerable file list and prioritize
3. **Schedule** Phase 1 deployment (critical fixes)
4. **Create** database backup before any changes
5. **Test** all CRUD operations after patches
6. **Deploy** to staging first, then production
7. **Monitor** logs for any security events

---

## 📊 VULNERABILITY STATISTICS

| Category | Count | Status |
|----------|-------|--------|
| SQL Injection | 162+ | ⏳ 3 Fixed, 159 Pending |
| MD5 Hash | 5+ | ✅ 2 Converted, 3 Pending |
| GET Delete/Update | 8 | ✅ 1 Fixed, 7 Pending |
| Unvalidated Upload | 15+ | ❌ All Pending |
| Missing Output Escape | 50+ | ❌ Requires Audit |
| Missing Access Control | 30+ | ❌ Requires Implementation |

**Overall Progress:** 15% FIXED · 85% PENDING

---

*Report Generated: Feb 5, 2026*  
*Next Review: After Phase 1 Completion*

