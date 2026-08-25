import os, getpass, socket
import subprocess
from datetime import datetime

def collect_basic(event_path: str):
    # Best-effort local forensics
    info = {
        "path": event_path,
        "timestamp": datetime.utcnow().isoformat(),
        "user": getpass.getuser(),
        "hostname": socket.gethostname(),
        "pid": os.getpid(),
        "ip": _get_local_ip(),
        "active_connections": _get_active_connections()
    }
    return info

def _get_local_ip():
    try:
        s = socket.socket(socket.AF_INET, socket.SOCK_DGRAM)
        s.connect(("8.8.8.8", 80))
        ip = s.getsockname()[0]
        s.close()
        return ip
    except Exception:
        return "0.0.0.0"

def _get_active_connections():
    """Extract active remote IP addresses connected to this machine."""
    connections = []
    try:
        # Run 'ss -tn' to get active TCP connections
        result = subprocess.run(['ss', '-tn', 'state', 'established'], capture_output=True, text=True, timeout=2)
        lines = result.stdout.strip().split('\n')[1:] # Skip header
        for line in lines:
            parts = line.split()
            if len(parts) >= 5:
                local_addr = parts[3]
                remote_addr = parts[4]
                # Ignore loopback connections
                if not remote_addr.startswith('127.') and not remote_addr.startswith('[::1]'):
                    connections.append(remote_addr)
    except Exception:
        pass
    return connections[:5] # Return top 5 connections
