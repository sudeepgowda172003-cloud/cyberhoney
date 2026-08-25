import socket
import threading
import time
from src.core.alert_manager import send_alert
from src.utils.logger import log_event

class FakeHTTPTrap(threading.Thread):
    """
    A fake HTTP server that mimics an admin login portal.
    It captures passwords submitted by attackers and logs their IP.
    """
    def __init__(self, port=8080):
        super().__init__(daemon=True)
        self.port = port
        self.host = '0.0.0.0'
        self.sock = None
        self._running = True

    def stop(self):
        self._running = False
        if self.sock:
            try:
                self.sock.close()
            except:
                pass

    def run(self):
        try:
            self.sock = socket.socket(socket.AF_INET, socket.SOCK_STREAM)
            self.sock.setsockopt(socket.SOL_SOCKET, socket.SO_REUSEADDR, 1)
            self.sock.bind((self.host, self.port))
            self.sock.listen(5)
            log_event(f"Fake HTTP Trap started on port {self.port}", "INFO")
        except Exception as e:
            log_event(f"Failed to start Fake HTTP Trap on port {self.port}: {e}", "ERROR")
            return

        while self._running:
            try:
                client_sock, addr = self.sock.accept()
                client_ip = addr[0]
                threading.Thread(target=self.handle_client, args=(client_sock, client_ip), daemon=True).start()
            except Exception:
                if self._running:
                    time.sleep(1)

    def handle_client(self, client_sock, client_ip):
        try:
            client_sock.settimeout(5.0)
            request = client_sock.recv(2048).decode('utf-8', errors='ignore')
            
            if not request:
                client_sock.close()
                return

            # Check if this is a POST request with credentials
            if request.startswith("POST "):
                # Extract body
                parts = request.split("\r\n\r\n", 1)
                body = parts[1] if len(parts) > 1 else ""
                
                # Send alert!
                msg = f"🚨 CREDENTIAL THEFT ATTEMPT on port {self.port}"
                extra = {
                    "event": "credential_theft",
                    "target_port": self.port,
                    "attacker_ip": client_ip,
                    "captured_payload": body[:200]  # Cap payload size
                }
                
                send_alert(msg, {"ip": client_ip, "extra": extra})
                log_event(f"CREDENTIAL CAPTURED from {client_ip}: {body[:50]}...", "WARNING")
                
                # Send fake failure response
                response = "HTTP/1.1 200 OK\r\nContent-Type: text/html\r\n\r\n"
                response += "<html><body><h1>Login Failed</h1><p>Invalid credentials. Your attempt has been logged.</p></body></html>"
                client_sock.sendall(response.encode('utf-8'))
                
            else:
                # Send fake login page for GET requests
                response = "HTTP/1.1 200 OK\r\nContent-Type: text/html\r\n\r\n"
                response += """
                <html>
                <head><title>System Administration</title></head>
                <body>
                    <h2>Admin Gateway</h2>
                    <form method="POST" action="/login">
                        Username: <input type="text" name="username"><br>
                        Password: <input type="password" name="password"><br>
                        <input type="submit" value="Login">
                    </form>
                </body>
                </html>
                """
                client_sock.sendall(response.encode('utf-8'))
                
                # Log the port scan / visit
                msg = f"👀 Network scan detected on port {self.port}"
                extra = {"event": "port_scan", "target_port": self.port, "attacker_ip": client_ip}
                send_alert(msg, {"ip": client_ip, "extra": extra})

        except Exception:
            pass
        finally:
            try:
                client_sock.close()
            except:
                pass

def start_network_traps():
    """Start all configured network traps."""
    # Start HTTP Trap on port 8080
    http_trap = FakeHTTPTrap(port=8080)
    http_trap.start()
    return [http_trap]
