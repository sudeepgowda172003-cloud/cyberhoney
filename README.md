<div align="center">
  <img src="https://raw.githubusercontent.com/sudeepgowda172003-cloud/cyberhoney/main/web/assets/img/logo.png" alt="HoneyGuard Logo" width="120" onerror="this.src='https://upload.wikimedia.org/wikipedia/commons/thumb/6/60/Security_shield.svg/200px-Security_shield.svg.png'"/>
  
  # 🍯 CyberHoney (HoneyGuard)
  **An Advanced, Production-Ready Honeypot & Threat Intelligence Platform**
  
  ![Python](https://img.shields.io/badge/Python-3.8%2B-blue?style=for-the-badge&logo=python)
  ![PHP](https://img.shields.io/badge/PHP-8.0%2B-indigo?style=for-the-badge&logo=php)
  ![MySQL](https://img.shields.io/badge/MySQL-Database-orange?style=for-the-badge&logo=mysql)
  ![Status](https://img.shields.io/badge/Status-Active-success?style=for-the-badge)
  ![License](https://img.shields.io/badge/License-MIT-green?style=for-the-badge)

</div>

---

## 📖 Overview

**CyberHoney (HoneyGuard)** is a highly deceptive, hybrid cybersecurity honeypot designed to detect, track, and analyze malicious actors traversing your systems. By deploying incredibly enticing traps—ranging from fake administrative portals to tracking tokens—CyberHoney silently monitors for unauthorized access and pushes real-time telemetry to a beautifully crafted, centralized dashboard.

![Dashboard Preview](screenshot/dashboard1.png)

---

## ✨ Core Features & Traps

### 🕵️ Advanced Traps & Sensors
- **Canary Tokens (Phone-Home):** Generates highly enticing HTML files (e.g., `admin_portal.html`). If a hacker steals the file and opens it on their own machine, a hidden 1x1 tracking pixel silently connects back to the dashboard, logging their true Public IP address, location, and User-Agent.
- **Credential Trapping (Fake Services):** Deploys a deceptive HTTP login portal on configurable ports (e.g., `8080`). Captures aggressive port scans and actively steals usernames and passwords submitted by attackers attempting to brute-force or log in.
- **Dynamic File Watching:** Monitors high-value directories (like the Desktop or `/var/www/`) for unauthorized viewing, modifying, or deleting of sensitive "honeyfiles" (fake credentials, secret keys, bank accounts, etc.).

![Recent Alerts](screenshot/dashboard2.png)

### 🧠 Deep Forensics & Evasion
- **Active Connection Tracing:** Upon detecting an intrusion, the agent automatically executes system-level network scans (via `ss`) to instantly identify the IP addresses of any attackers currently connected to the machine via SSH, FTP, or remote protocols.
- **Automated Firewall Bypass:** Features a custom-built cryptographic payload engine that successfully decrypts and bypasses aggressive Javascript bot-protection firewalls (like InfinityFree's `aes.js`). This allows our headless Python agents to seamlessly communicate with highly restrictive remote dashboards without getting blocked.

### 📊 Modern UI Dashboard
- **Glassmorphism Interface:** A stunning, responsive UI built with pure CSS and modern design principles.
- **Interactive Analytics:** Powered by `Chart.js`, visualizing attack vectors, top targeted files, and threat levels over time.
- **Synchronized Date Filtering:** Select a specific date from the calendar box to instantly synchronize and filter all top metrics, recent alert tables, and bottom-half charts to analyze specific incidents.
- **Real-Time Security Scoring:** An algorithmic gauge that updates dynamically based on the severity and frequency of recent attacks.

![Login Portal](screenshot/loginpage.png)

---

## ⚙️ Architecture & Installation

The project is split into two distinct components for ultimate security: the **Local Python Agent** (the traps) and the **Remote PHP Dashboard** (the brain).

### 1. The PHP Dashboard (Backend/UI)
Designed to be hosted on strict free-tier hosting providers (like InfinityFree) or any standard LAMP/LEMP stack.
1. Upload the contents of the `web/` directory to your web server (`htdocs` or `public_html`).
2. Import `database.sql` into your MySQL database.
3. Update `web/config.php` with your database credentials.
4. Log in using the default credentials:
   - **Email:** `admin@honeyguard.com`
   - **Password:** `admin123` *(Change immediately upon login!)*

### 2. The Python Agent (Target Machine)
Runs silently on the machine you want to protect.
1. Clone this repository to the target machine.
2. Set up the virtual environment:
   ```bash
   python3 -m venv soc
   source soc/bin/activate
   pip install -r requirements.txt
   ```
3. Update `config/settings.yaml` with your remote dashboard URL and API key (which can be generated from the dashboard settings page).
4. Run the agent:
   ```bash
   python3 main.py
   ```
   *(The agent will automatically generate honeyfiles and start the network traps!)*

---

## 📸 Screenshots

| Security Score & Analytics | Live Alerts Table |
|:---:|:---:|
| ![Analytics](screenshot/dashboard1.png) | ![Alerts Table](screenshot/dashboard2.png) |

---

## 🛡️ Disclaimer
This tool is built for educational and defensive security purposes only. Do not deploy this in a production environment without proper network isolation, as hosting fake vulnerable services can attract real-world malicious traffic. The developers assume no liability for misuse.

---

<div align="center">
  <i>"Catch the flies before they find the hive."</i>
</div>
