# HoneyGuard — Deployment Guide (InfinityFree)

## Your Setup Details

| Item | Value |
|---|---|
| **Domain** | `soctestone.free.je` |
| **MySQL Host** | `sql305.infinityfree.com` |
| **MySQL User** | `if0_42739341` |
| **MySQL Database** | `if0_42739341_soc` |
| **MySQL Port** | `3306` |

---

## Step-by-Step Deployment

### Step 1: Import Database Schema

1. Go to your InfinityFree dashboard → click **phpMyAdmin** button next to your database
2. In phpMyAdmin, click the **"SQL"** tab at the top
3. Copy the entire contents of `database.sql` from your project
4. Paste into the SQL query box and click **"Go"**
5. You should see 6 tables created: `users`, `alerts`, `user_sessions`, `api_keys`, `audit_log`, `honeyfiles`

> **Default admin login:** `admin@honeyguard.com` / `admin123` — Change this immediately after first login!

### Step 2: Upload Web Files via FTP

1. Go to InfinityFree dashboard → **Files** → **File Manager**
2. Navigate to `htdocs/` folder (this is your web root)
3. Upload ALL files from the `web/` folder in your project:
   ```
   web/
   ├── index.php          → htdocs/index.php
   ├── login.php          → htdocs/login.php
   ├── register.php       → htdocs/register.php
   ├── dashboard.php      → htdocs/dashboard.php
   ├── alerts.php         → htdocs/alerts.php
   ├── settings.php       → htdocs/settings.php
   ├── logout.php         → htdocs/logout.php
   ├── config.php         → htdocs/config.php
   ├── db.php             → htdocs/db.php
   ├── auth.php           → htdocs/auth.php
   ├── .htaccess          → htdocs/.htaccess
   ├── api/               → htdocs/api/
   │   ├── ingest.php
   │   ├── alerts.php
   │   ├── stats.php
   │   └── honeyfiles.php
   └── assets/            → htdocs/assets/
       ├── css/style.css
       └── js/app.js, charts.js
   ```

4. **Alternative: Use FTP client** (FileZilla)
   - Host: `ftpupload.net`
   - Username: Your InfinityFree FTP username
   - Password: Your InfinityFree password
   - Port: `21`
   - Upload `web/*` contents into the `htdocs/` directory

### Step 3: Verify Website

1. Visit: `https://soctestone.free.je`
2. You should see the login page
3. Login with: `admin@honeyguard.com` / `admin123`
4. You'll see the dashboard (empty initially)

### Step 4: Change Default Password

1. After logging in, go to **Settings**
2. Change the admin password immediately

### Step 5: Generate API Key

1. In **Settings**, scroll to "API Keys"
2. Enter a name like "My Agent" and click **Generate Key**
3. **Copy the key immediately** — it won't be shown again!

### Step 6: Configure Python Agent

On your local machine:

```bash
# Edit config/settings.yaml
# Set remote_dashboard.enabled: true
# Set remote_dashboard.api_key: YOUR_API_KEY

# Or use CLI flags:
python main.py --push-url https://soctestone.free.je/api/ingest.php --api-key YOUR_API_KEY
```

### Step 7: Test Alert Push

```bash
# Quick test with curl:
curl -X POST https://soctestone.free.je/api/ingest.php \
  -H "Content-Type: application/json" \
  -H "X-API-Key: YOUR_API_KEY" \
  -d '{"level":"ALERT","action":"test","file_name":"test.txt","message":"Test alert from agent"}'
```

---

## Troubleshooting

| Issue | Solution |
|---|---|
| Blank page | Check `config.php` DB credentials are correct |
| 500 error | Enable `display_errors` in `config.php` temporarily |
| Login fails | Verify `users` table has the admin row via phpMyAdmin |
| API 403 | Check API key is correct and active |
| CSS not loading | Ensure `assets/` folder was uploaded correctly |
| Domain not working | New domains take up to 72 hours to propagate |

---

## Security Checklist

- [x] Passwords hashed with bcrypt (cost 12)
- [x] CSRF protection on all forms
- [x] Prepared statements (no SQL injection)
- [x] XSS protection via htmlspecialchars
- [x] Session security headers
- [x] API key authentication for agent
- [x] .htaccess security headers
- [ ] Change default admin password!
- [ ] Enable HTTPS redirect in .htaccess (after SSL is active)
