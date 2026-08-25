import argparse
import yaml
from src.core.file_generator import rotation_scheduler, generate_honeyfiles
from src.core.monitor import start_monitoring
from src.utils.logger import log_event, init_logging

def main():
    parser = argparse.ArgumentParser(description="HoneyGuard Monitoring Agent")
    parser.add_argument("--push-url", help="Remote dashboard URL for alert push")
    parser.add_argument("--api-key", help="API key for remote dashboard authentication")
    args = parser.parse_args()

    init_logging()
    log_event("🚀 Starting HoneyGuard Trap Tool...", "INFO")

    # Initialize remote push if configured
    push_url = args.push_url
    api_key = args.api_key

    if not push_url or not api_key:
        # Fallback to settings.yaml
        with open("config/settings.yaml", "r", encoding="utf-8") as f:
            cfg = yaml.safe_load(f) or {}
        remote = cfg.get("remote_dashboard", {})
        if remote.get("enabled"):
            push_url = push_url or remote.get("url", "")
            api_key = api_key or remote.get("api_key", "")

    if push_url and api_key:
        from src.core.api_push import init_pusher
        init_pusher(push_url, api_key)

    generate_honeyfiles()   # ensure initial set exists
    rotation_scheduler()    # schedule auto-rotation in background
    
    # Start network traps
    from src.core.network_trap import start_network_traps
    traps = start_network_traps()
    
    try:
        start_monitoring()      # blocking loop
    finally:
        for t in traps:
            t.stop()

if __name__ == "__main__":
    main()
