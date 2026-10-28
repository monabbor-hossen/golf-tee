# Golf Tee Admin — Security Architecture & Guidelines

This document provides a comprehensive overview of the security architecture, threat mitigations, and implementation guidelines for the **Golf Tee Admin** (`admin-php`) management system.

All developers, contributors, and maintainers should review this document to understand the defenses in place and adhere to these standards when adding new features.

---

## Table of Contents

1. [Security Principles](#1-security-principles)
2. [SQL Injection (SQLi) Protections](#2-sql-injection-sqli-protections)
3. [Cross-Site Request Forgery (CSRF) Defense](#3-cross-site-request-forgery-csrf-defense)
4. [Authentication & Session Security](#4-authentication--session-security)
5. [Cross-Site Scripting (XSS) Mitigation](#5-cross-site-scripting-xss-mitigation)
6. [HTTP Security Headers & Content Security Policy (CSP)](#6-http-security-headers--content-security-policy-csp)
7. [CSV Formula Injection Defense](#7-csv-formula-injection-defense)
8. [Information Disclosure & Error Handling](#8-information-disclosure--error-handling)
9. [Developer Guidelines for Contributors](#9-developer-guidelines-for-contributors)

---

## 1. Security Principles

The Golf Tee Admin backend follows the principle of **Defense in Depth**:
- **Never trust client input**: All data from `$_GET`, `$_POST`, and headers is treated as untrusted.
- **Fail securely**: If authentication, CSRF tokens, or database connections fail, requests terminate safely with non-leaking errors.
- **Explicit isolation**: Database queries are strictly isolated in Repository classes with server-side prepared statements.
- **Consistent output escaping**: All dynamic values rendered in HTML or passed to JavaScript are sanitized.

---

## 2. SQL Injection (SQLi) Protections

### Server-Side Prepared Statements
- Configured in [`App\Core\Database`](app/Core/Database.php) with:
  ```php
  PDO::ATTR_EMULATE_PREPARES => false
  ```
- **Why this matters**: Disabling emulated prepares forces the MySQL engine to compile the SQL statement structure separately from user-supplied parameters. Even if malicious SQL syntax is passed, it cannot alter query structure.

### Strict Parameter Type Binding
In `Database::query()`, every bound value is checked and mapped to its exact PDO parameter type:
```php
$type = match (true) {
    is_int($param)  => PDO::PARAM_INT,
    is_bool($param) => PDO::PARAM_BOOL,
    is_null($param) => PDO::PARAM_NULL,
    default         => PDO::PARAM_STR,
};
$stmt->bindValue($i++, $param, $type);
```

### LIKE Wildcard Sanitization
User search input in `LIKE` queries (`%term%`) can be abused via wildcard characters (`%`, `_`, `\`) to cause Denial-of-Service or wildcard pattern matching attacks.
- The `Database::escapeLike()` method neutralizes wildcards:
  ```php
  public static function escapeLike(string $value): string
  {
      return addcslashes($value, '%_\\');
  }
  ```
- All repositories use this helper:
  ```php
  $search = '%' . Database::escapeLike($query) . '%';
  $params[] = $search;
  ```

### Order Clause & Identifier Whitelisting
SQL parameter binding does not apply to column names or sort directions in `ORDER BY`. These are protected via strict in-code whitelists:
```php
$allowedSorts = ['name', 'tier', 'hcp', 'rounds', 'spend', 'status'];
$sortCol = in_array($sort, $allowedSorts, true) ? $sort : 'name';
$direction = strtoupper($dir) === 'DESC' ? 'DESC' : 'ASC';
```

---

## 3. Cross-Site Request Forgery (CSRF) Defense

CSRF attacks trick authenticated users into submitting unauthorized requests. Golf Tee Admin implements a token-based verification system.

### Cryptographic Token Generation
- Managed by [`App\Core\Session`](app/Core/Session.php):
  - 256-bit entropy generated using `random_bytes(32)`.
  - Stored in `$_SESSION['_csrf_token']`.
  - Refreshed automatically whenever a user logs in to mitigate session hijacking.

### Timing-Attack Safe Verification
- Tokens are compared using `hash_equals()` to avoid side-channel timing leaks:
  ```php
  public static function verifyCsrfToken(?string $token): bool
  {
      self::start();
      if (empty($_SESSION['_csrf_token']) || empty($token)) {
          return false;
      }
      return hash_equals($_SESSION['_csrf_token'], $token);
  }
  ```

### Form & AJAX Integration
1. **Forms**: All 30 POST forms across all views include the hidden token input via `<?= csrfField() ?>`.
2. **AJAX**: The HTML layout exposes the token in a `<meta>` tag:
   ```html
   <meta name="csrf-token" content="<?= csrfToken() ?>">
   ```
   The JavaScript client (`app.js`) attaches it to the `X-CSRF-TOKEN` header on every `POST` `fetch` request.
3. **Controller Validation**: Controllers verify requests with:
   ```php
   Session::validateRequestCsrf($request);
   ```
   If a token is invalid or missing, a 403 Forbidden status is returned immediately.

---

## 4. Authentication & Session Security

### Brute-Force Rate Limiting
Implemented in [`App\Services\AuthService`](app/Services/Auth/AuthService.php):
- **Threshold**: Maximum 5 failed login attempts.
- **Lockout**: 15 minutes temporary lockout after reaching the limit.
- **Feedback**: Displays remaining attempt warnings without revealing whether the email or password was the incorrect component.

### Password Hashing
- Stored passwords use PHP's `PASSWORD_BCRYPT` with automatic cryptographic salting.
- Authenticated using `password_verify($password, $hash)` to prevent timing analysis.

### Session Fixation Protection
- When an account authenticates successfully, `session_regenerate_id(true)` is executed immediately. This invalidates the pre-login session ID and issues a new one.

### Cookie Hardening
Session cookies are configured with secure flags before the session starts:
```php
session_set_cookie_params([
    'lifetime' => 0,
    'path'     => '/',
    'domain'   => '',
    'secure'   => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
    'httponly' => true,
    'samesite' => 'Lax',
]);
```
- **`HttpOnly`**: JavaScript cannot access the session cookie (`document.cookie`), preventing cookie theft via XSS.
- **`SameSite=Lax`**: Restricts the cookie to first-party contexts, blocking cross-site request attacks.
- **`Secure`**: Enforces HTTPS transmission when running under SSL/TLS.

### Inactivity Expiration
Sessions track `$_SESSION['_last_activity']`. If the user is idle for more than 2 hours (7,200 seconds), the session is destroyed and the user is redirected to `login.php?timeout=1`.

---

## 5. Cross-Site Scripting (XSS) Mitigation

### HTML Entity Escaping
- Global helper `e($string)` wraps:
  ```php
  htmlspecialchars((string)$string, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
  ```
- All dynamic user data in templates is escaped before rendering.

### Safe JavaScript Data Embedding
When passing server data to inline JavaScript functions (e.g. edit modals), data is encoded using hex entity constants:
```php
json_encode($selectedMember, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP)
```
This guarantees that `</script>`, single quotes, double quotes, and ampersands cannot break out of HTML attributes.

---

## 6. HTTP Security Headers & Content Security Policy (CSP)

Set in [`bootstrap.php`](bootstrap.php) before output generation:

| Header | Value | Purpose |
| :--- | :--- | :--- |
| `X-Frame-Options` | `SAMEORIGIN` | Blocks clickjacking by preventing other domains from embedding the admin panel inside `<iframe>`. |
| `X-Content-Type-Options` | `nosniff` | Prevents browsers from MIME-sniffing responses away from the declared content-type. |
| `X-XSS-Protection` | `1; mode=block` | Enables browser-level reflective XSS filters. |
| `Referrer-Policy` | `strict-origin-when-cross-origin` | Limits referer leakage when navigating to external resources. |
| `Permissions-Policy` | `geolocation=(), camera=(), microphone=()` | Blocks access to device sensors and hardware. |
| `Content-Security-Policy` | Restrictive whitelist | Restricts asset loading to `'self'`, Google Fonts, and secure data URIs. |

---

## 7. CSV Formula Injection Defense

Exporting user-provided data (e.g. member names, descriptions) to CSV files introduces a risk of **CSV / Formula Injection (CWE-1236)**: spreadsheet applications like Microsoft Excel, LibreOffice, or Google Sheets may automatically execute commands if a cell starts with `=`, `+`, `-`, `@`, `\t`, or `\r`.

In [`App\Core\Response::csv()`](app/Core/Response.php), all exported cells are processed:
```php
public static function sanitizeCsvCell(mixed $value): mixed
{
    if (!is_string($value)) {
        return $value;
    }
    $val = trim($value);
    if ($val !== '' && in_array($val[0], ['=', '+', '-', '@', "\t", "\r"], true)) {
        return "'" . $val; // Neutralize formula execution
    }
    return $value;
}
```
Additionally:
- Export filenames are sanitized with `preg_replace('/[^a-zA-Z0-9_\-\.]/', '_', $filename)` to block directory traversal or HTTP header splitting.
- A UTF-8 Byte Order Mark (`\xEF\xBB\xBF`) is prepended to ensure correct character encoding across all operating systems.

---

## 8. Information Disclosure & Error Handling

- **Database Errors**: Caught with `try / catch (PDOException $e)`. Raw database connection errors and stack traces are logged to `error_log()` rather than displayed to end users.
- **Production Displays**: Generic error boxes with friendly messages are displayed instead of PHP fatal traces.
- **Credential Storage**: Credentials reside in environment configuration (`.env`), not in application code.

---

## 9. Developer Guidelines for Contributors

When developing or modifying features in `admin-php`, adhere to these mandatory practices:

### 1. Database Queries
- **NEVER** concatenate variables directly into SQL queries:
  ```php
  // ❌ VULNERABLE TO SQLi:
  Database::query("SELECT * FROM members WHERE name = '" . $_POST['name'] . "'");

  // ✅ SAFE:
  Database::query("SELECT * FROM members WHERE name = ?", [$_POST['name']]);
  ```
- Always escape search strings used in `LIKE` queries using `Database::escapeLike($term)`.
- If sorting dynamically, check the column against a strict whitelist array with `in_array(..., true)`.

### 2. Forms & Mutations
- Every `<form method="post">` **MUST** include:
  ```php
  <?= csrfField() ?>
  ```
- Every controller handling a POST action **MUST** call:
  ```php
  Session::validateRequestCsrf($request);
  ```

### 3. Output in Templates
- Never echo raw user input directly into HTML:
  ```php
  // ❌ VULNERABLE TO XSS:
  <p><?= $member['name'] ?></p>

  // ✅ SAFE:
  <p><?= htmlspecialchars($member['name']) ?></p>
  // or:
  <p><?= e($member['name']) ?></p>
  ```

### 4. Sessions & Permissions
- Verify authentication before rendering or handling protected actions using:
  ```php
  Session::requireAuth();
  ```
- Use `Session::getStaffId()` and `Session::getStaffRole()` for authorization checks.
