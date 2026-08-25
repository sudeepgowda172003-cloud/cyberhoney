import os, yaml, time, random, string, threading, schedule
from datetime import datetime
from src.utils.logger import log_event
from src.utils.logger import init_logging

DUMMY_CONTENTS = [
    "admin:123456",
    "root:toor",
    "api_key=AKIA" + "X"*16,
    "password=Passw0rd!",
]

def _read_settings():
    with open("config/settings.yaml", "r", encoding="utf-8") as f:
        return yaml.safe_load(f)

def _random_suffix(n=6):
    return ''.join(random.choices(string.ascii_lowercase + string.digits, k=n))

def write_honeyfile(path, name, content=None):
    os.makedirs(path, exist_ok=True)
    fpath = os.path.join(path, name)
    
    if name.endswith('.html') or name.endswith('.htm'):
        # Generate an HTML Canary Token
        import uuid
        token_id = str(uuid.uuid4())[:8]
        html_content = f"""<!DOCTYPE html>
<html>
<head><title>Admin Portal Access</title></head>
<body>
    <h1>Restricted Admin Area</h1>
    <p>Please log in using the secure gateway.</p>
    <!-- Tracking Pixel -->
    <img src="https://soctestone.free.je/api/canary.php?id={token_id}" width="1" height="1" style="display:none;" />
</body>
</html>"""
        with open(fpath, "w", encoding="utf-8") as f:
            f.write(html_content)
    else:
        # Standard Dummy Content
        if content is None:
            content = random.choice(DUMMY_CONTENTS)
        with open(fpath, "w", encoding="utf-8") as f:
            f.write(f"Dummy sensitive data: {content}\n")
            
    log_event(f"Honeyfile ensured → {fpath}", "INFO", {"file": fpath})
    return fpath

def generate_honeyfiles():
    cfg = _read_settings()
    for p in cfg.get("paths", []):
        for fname in cfg.get("honeyfiles", []):
            write_honeyfile(p, fname)

def _rotate_once():
    cfg = _read_settings()
    keep = int(cfg.get("rotation", {}).get("keep_versions", 3))
    for p in cfg.get("paths", []):
        for fname in cfg.get("honeyfiles", []):
            base, ext = os.path.splitext(fname)
            rotated = f"{base}_{datetime.now().strftime('%Y%m%d_%H%M%S')}_{_random_suffix()}{ext}"
            write_honeyfile(p, rotated)
            # prune old versions
            family = sorted([f for f in os.listdir(p) if f.startswith(base + "_")], reverse=True)
            for old in family[keep:]:
                try:
                    os.remove(os.path.join(p, old))
                except Exception:
                    pass
    log_event("Honeyfile rotation complete", "INFO")

def _schedule_thread():
    cfg = _read_settings()
    if not cfg.get("rotation", {}).get("enabled", True):
        return
    interval = cfg.get("rotation", {}).get("interval", "daily")
    if interval == "hourly":
        schedule.every().hour.do(_rotate_once)
    elif interval == "weekly":
        schedule.every().week.do(_rotate_once)
    else:
        schedule.every().day.do(_rotate_once)

    while True:
        schedule.run_pending()
        time.sleep(1)

def rotation_scheduler():
    # run in background
    t = threading.Thread(target=_schedule_thread, daemon=True)
    t.start()
