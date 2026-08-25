import yaml
from src.utils.logger import log_event
from src.plugins.telegram_alert import telegram_send
from src.plugins.discord_alert import discord_send
from src.plugins.slack_alert import slack_send
from src.plugins.siem_webhook import send_webhook

# Import remote push (lazy — only used if configured)
try:
    from src.core.api_push import push_alert_remote
except ImportError:
    push_alert_remote = None

def _alerts_cfg():
    with open("config/alerts.yaml", "r", encoding="utf-8") as f:
        return yaml.safe_load(f)

def _settings_cfg():
    with open("config/settings.yaml", "r", encoding="utf-8") as f:
        return yaml.safe_load(f) or {}

def send_alert(message: str, extra: dict | None = None):
    cfg = _alerts_cfg()

    # Always log locally
    log_event(message, "ALERT", extra or {})

    # Telegram
    if cfg.get("telegram", {}).get("enabled"):
        telegram_send(message, extra)

    # Discord
    if cfg.get("discord", {}).get("enabled"):
        discord_send(message, extra)

    # Slack
    if cfg.get("slack", {}).get("enabled"):
        slack_send(message, extra)

    # Generic webhook (e.g., SIEM)
    if cfg.get("webhook", {}).get("enabled"):
        send_webhook(message, extra, cfg.get("webhook", {}).get("url", ""))

    # Remote dashboard push
    if push_alert_remote:
        settings = _settings_cfg()
        remote = settings.get("remote_dashboard", {})
        if remote.get("enabled"):
            alert_data = {"message": message, "level": "ALERT"}
            if extra:
                alert_data.update(extra)
            push_alert_remote(alert_data)
