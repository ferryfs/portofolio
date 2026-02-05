# 🚀 QUICK FIX GUIDE - Immediate Actions

## ✅ What's Been Done (Fixes Deployed)

### 1. Security Helper Library
**File:** [`config/security.php`](config/security.php)
- ✅ Password hashing functions (bcrypt)
- ✅ Prepared statement wrappers
- ✅ Input validation helpers
- ✅ CSRF token support
- ✅ Session security functions
- ✅ File upload validation
- ✅ Logging and error handling
- ✅ Rate limiting

**How to Use:**
```php
<?php
require_once __DIR__ . '/config/security.php';

// Hash password
$hash = hashPassword($_POST['password']);

// Verify password
if (verifyPassword($rawPassword, $hash)) { /* ... */ }

// Sanitize input
$email = sanitizeInput($_POST['email']);

// CSRF token in form
<?= csrfTokenField() ?>

// Verify CSRF
if (!verifyCSRFToken($_POST['csrf_token'])) die('Invalid');
?>
```

---

### 2. Fixed Auth Files (Bcrypt + Prepared Statements + CSRF)

#### ✅ `apps/sales-brief/auth.php`
- Register: Now uses bcrypt + prepared statements + input validation
- Login: Added CSRF token + rate limiting + MD5→Bcrypt auto-upgrade
- Logout: Secure session destroy

#### ✅ `apps/ess-mobile/auth.php`
- Same improvements as sales-brief
- Added employee ID validation
- Added security logging

#### ✅ `apps/sales-brief/change_status.php`
- Changed from GET to POST method
- Added CSRF token verification
- Changed to prepared statements
- No longer vulnerable to direct URL manipulation

---

## ⏳ Next Immediate Actions (Priority Order)

### ACTION 1: Fix `admin.php` GET Deletes (1 hour)
```php
// Current vulnerable code (lines 116, 145, 164, 183):
if (isset($_GET['hapus_proj'])) { ... }

// Should be converted to:
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    if (!verifyCSRFToken($_POST['csrf_token'])) die('Invalid');
    $id = sanitizeInt($_POST['delete_id']);
    // ... delete logic with prepared statement
}
```

### ACTION 2: Fix TMS/WMS/ESS-Mobile Auth Files (2-3 hours)
Files using MD5:
- `apps/tms/auth.php` - Lines 33, 57
- `apps/wms/auth.php` - Lines 31, 46
- `apps/ess-mobile/menu_setting.php` - Lines 23-24

**Solution:** Copy pattern from fixed `apps/sales-brief/auth.php`

### ACTION 3: SQL Injection Fixes - Pick One App (4-6 hours)
Start with ONE app module (WMS, TMS, or Sales-Brief):

**Template for each file:**
1. Find all `mysqli_query()` calls
2. Replace with `$stmt->prepare()` + `bind_param()`
3. Add input validation (`sanitizeInput()`, `sanitizeInt()`)
4. Test CRUD operations

**High-value files (fix these first):**
- `apps/sales-brief/list_draft.php` - lines 16, 17, 19
- `apps/wms/shipping.php` - multiple instances
- `apps/tms/outbound.php` - multiple instances

---

## 🔄 Migration Path for Old MD5 Passwords

### Current Auto-Migration (Already Set Up)
When user logs in with old MD5 password:
1. System detects MD5 hash format
2. Auto-upgrades to Bcrypt
3. Next login will use new Bcrypt hash
4. Old MD5 passwords gradually removed

### Check Progress
```sql
-- Check how many MD5 passwords remain
SELECT COUNT(*) as md5_users FROM sales_brief_users 
WHERE password NOT LIKE '$2%';
```

### Force Reset for Old Accounts
```sql
-- Reset inactive users with old passwords
UPDATE sales_brief_users 
SET password = 'FORCE_RESET' 
WHERE password LIKE '%a' OR password LIKE '%32'  -- MD5 ends in letter or 32 chars
AND last_login IS NULL OR last_login < DATE_SUB(NOW(), INTERVAL 90 DAY);
```

---

## 📋 Phase-by-Phase Execution Plan

### Phase 1 Complete ✅ (Today)
- [x] Create security.php helper
- [x] Fix sales-brief/auth.php
- [x] Fix ess-mobile/auth.php
- [x] Fix sales-brief/change_status.php
- [x] Document vulnerabilities

### Phase 1 Remaining (Next 1 Day)
- [ ] Test all 3 fixed auth files with new credentials
- [ ] Fix admin.php GET→POST deletes
- [ ] Deploy to staging environment

### Phase 2 (Next 1 Week)
- [ ] Fix remaining auth files (TMS, WMS, HRIS)
- [ ] Convert 20% of WMS queries to prepared statements
- [ ] Convert 20% of TMS queries to prepared statements
- [ ] Add file upload validation to admin.php

### Phase 3 (Next 2 Weeks)
- [ ] Convert remaining SQL queries
- [ ] Add CSRF tokens to all state-changing operations
- [ ] Implement output escaping audit
- [ ] Add security headers

### Phase 4 (Ongoing)
- [ ] Further hardening
- [ ] Security testing
- [ ] Monitoring

---

## 🧪 Quick Test Procedures

After deploying fixes, run these tests:

### Login Test
```
1. Clear cookies/session
2. Go to login page
3. Try wrong password → Should show "Username atau Password Salah"
4. Try same user 5+ times → Should show rate limit message
5. Login successfully → Should redirect to dashboard
6. Check database: Old MD5 password should be upgraded to $2y$ format
```

### CSRF Test
```
1. View page source
2. Copy CSRF token value
3. Open new tab, modify session cookie to invalid
4. Try to submit form with old CSRF token
5. Should show: "Invalid request" or "CSRF token mismatch"
```

### SQL Injection Test (After Query Fixes)
```
1. In any input field, try: ' OR '1'='1
2. In any numeric field, try: 1; DROP TABLE users;
3. Should show error or no change in data
4. Should NOT show database error details
```

---

## 📞 Support & Resources

### If Stuck on Implementation
1. Check the SECURITY_AUDIT_REPORT_COMPLETE.md for detailed examples
2. Copy implementation from already-fixed files:
   - `apps/sales-brief/auth.php` ← Use as template
   - `config/security.php` ← Import and use functions
3. Test thoroughly in staging before production

### PHP Version Check
```bash
php --version
# Make sure PHP 7.3+ for secure session_set_cookie_params()
```

### Database Columns Check
Before auto-upgrade, ensure these columns exist:
```sql
ALTER TABLE sales_brief_users ADD COLUMN last_ip VARCHAR(45) DEFAULT NULL;
ALTER TABLE ess_users ADD COLUMN last_ip VARCHAR(45) DEFAULT NULL;
ALTER TABLE sales_brief_users MODIFY password VARCHAR(255);  -- Bcrypt is 60+ chars
```

---

## ⚠️ CRITICAL: Before Going to Production

1. **Backup database** - `mysqldump portofolio_db > backup_$(date +%s).sql`
2. **Test in staging** - Deploy all Phase 1 fixes first
3. **Monitor logs** - Check for errors in `/logs/security.log`
4. **User communication** - Inform users about password/auth changes if needed
5. **Gradual rollout** - Don't deploy everything at once

---

**Status:** Phase 1 ✅ Complete | Phase 2-4 ⏳ Pending  
**Last Updated:** Feb 5, 2026
