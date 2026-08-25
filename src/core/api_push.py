"""
HoneyGuard — Remote API Push Module
Pushes alerts from the local monitoring agent to the hosted PHP dashboard.
"""

import json
import time
import threading
import queue
from urllib.request import Request, urlopen
from urllib.error import URLError, HTTPError
from src.utils.logger import log_event


class RemotePusher:
    """Asynchronously push alerts to the hosted HoneyGuard dashboard."""

    def __init__(self, url: str, api_key: str, max_retries: int = 3):
        self.url = url.rstrip('/')
        self.api_key = api_key
        self.max_retries = max_retries
        self._cookie = None
        self._queue: queue.Queue = queue.Queue(maxsize=500)
        self._running = True
        self._thread = threading.Thread(target=self._worker, daemon=True)
        self._thread.start()

    def push(self, alert_data: dict) -> None:
        """Queue an alert for async delivery."""
        try:
            self._queue.put_nowait(alert_data)
        except queue.Full:
            log_event("Remote push queue full — dropping oldest alert", "WARN")
            try:
                self._queue.get_nowait()
                self._queue.put_nowait(alert_data)
            except Exception:
                pass

    def _worker(self) -> None:
        """Background worker that sends queued alerts."""
        while self._running:
            try:
                alert = self._queue.get(timeout=2)
            except queue.Empty:
                continue

            for attempt in range(self.max_retries):
                try:
                    self._send(alert)
                    break
                except Exception as e:
                    wait = 2 ** attempt
                    log_event(f"Push failed (attempt {attempt+1}): {e} — retry in {wait}s", "WARN")
                    # Clear cookie on failure in case it expired
                    self._cookie = None
                    time.sleep(wait)

    def _solve_infinityfree_challenge(self, html: str) -> str:
        """Decrypt the InfinityFree __test cookie from the aes.js challenge."""
        import re
        from cryptography.hazmat.primitives.ciphers import Cipher, algorithms, modes
        from cryptography.hazmat.backends import default_backend

        a = re.search(r'var a=toNumbers\("([a-f0-9]+)"\)', html)
        b = re.search(r'b=toNumbers\("([a-f0-9]+)"\)', html)
        c = re.search(r'c=toNumbers\("([a-f0-9]+)"\)', html)
        if not (a and b and c):
            return ""

        cipher = Cipher(
            algorithms.AES(bytes.fromhex(a.group(1))),
            modes.CBC(bytes.fromhex(b.group(1))),
            backend=default_backend()
        )
        decryptor = cipher.decryptor()
        plaintext = decryptor.update(bytes.fromhex(c.group(1))) + decryptor.finalize()
        return plaintext.hex()

    def _send(self, alert_data: dict) -> None:
        """HTTP POST to the remote ingest endpoint."""
        payload = json.dumps(alert_data).encode('utf-8')
        headers = {
            'Content-Type': 'application/json',
            'X-API-Key': self.api_key,
            'User-Agent': 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) HoneyGuard/2.0',
        }
        if self._cookie:
            headers['Cookie'] = f'__test={self._cookie}'

        req = Request(self.url, data=payload, headers=headers, method='POST')
        try:
            with urlopen(req, timeout=10) as resp:
                # InfinityFree returns a 200 OK for the challenge page
                body = resp.read().decode('utf-8')
                if 'aes.js' in body and 'slowAES.decrypt' in body:
                    log_event("InfinityFree challenge detected, solving...", "INFO")
                    new_cookie = self._solve_infinityfree_challenge(body)
                    if new_cookie:
                        self._cookie = new_cookie
                        headers['Cookie'] = f'__test={self._cookie}'
                        req = Request(self.url, data=payload, headers=headers, method='POST')
                        with urlopen(req, timeout=10) as resp2:
                            if resp2.status not in (200, 201):
                                raise Exception(f"HTTP {resp2.status} after bypass")
                    else:
                        raise Exception("Failed to solve InfinityFree challenge")
                elif resp.status not in (200, 201):
                    raise Exception(f"HTTP {resp.status}")
        except HTTPError as e:
            raise Exception(f"HTTP {e.code}: {e.read().decode()[:200]}")
        except URLError as e:
            raise Exception(f"Connection error: {e.reason}")

    def stop(self) -> None:
        self._running = False
        self._thread.join(timeout=5)


# Singleton instance
_pusher = None


def init_pusher(url: str, api_key: str) -> None:
    global _pusher
    if url and api_key:
        _pusher = RemotePusher(url, api_key)
        log_event(f"Remote push enabled → {url}", "INFO")


def push_alert_remote(alert_data: dict) -> None:
    if _pusher:
        _pusher.push(alert_data)
