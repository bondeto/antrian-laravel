import tkinter as tk
from tkinter import ttk, messagebox, scrolledtext, Menu
import webbrowser
import os
import subprocess
import threading
import queue
import re
import socket
import sys
import psutil 
import time
from PIL import Image, ImageDraw

# --- CONFIGURATION (SAFE MODE) ---
LOG_UPDATE_INTERVAL_MS = 500   # Slower updates (2 FPS)
MONITOR_INTERVAL_MS = 3000     # Slower monitoring
MAX_LOG_CHARS_PER_TICK = 1000  # Strict limit on text insertion

try:
    import pystray
    from pystray import MenuItem as item
    HAS_TRAY = True
except ImportError:
    HAS_TRAY = False

ANSI_ESCAPE = re.compile(r'\x1B(?:[@-Z\\-_]|\[[0-?]*[ -/]*[@-~])')

def resource_path(relative_path):
    try:
        base_path = sys._MEIPASS
    except Exception:
        base_path = os.path.abspath(".")
    return os.path.join(base_path, relative_path)

class LogBuffer:
    def __init__(self):
        self.lock = threading.Lock()
        self.buffers = {} 

    def write(self, name, text):
        with self.lock:
            if name not in self.buffers:
                self.buffers[name] = ""
            # Don't let buffer grow infinitely if UI hangs
            if len(self.buffers[name]) > 50000:
                self.buffers[name] = self.buffers[name][-20000:]
            self.buffers[name] += text

    def flush(self):
        with self.lock:
            if not self.buffers: return {}
            data = self.buffers.copy()
            self.buffers.clear()
            return data

class ServiceThread:
    def __init__(self, name, command, log_buffer, status_callback):
        self.name = name
        self.command = command
        self.log_buffer = log_buffer
        self.status_callback = status_callback
        self.process = None
        self.stop_event = threading.Event()
        self.is_running = False

    def start(self):
        if self.is_running: return
        self.stop_event.clear()
        threading.Thread(target=self._run_job, daemon=True).start()

    def stop(self):
        threading.Thread(target=self._stop_internal, daemon=True).start()

    def _stop_internal(self):
        self.stop_event.set()
        if self.process:
            self.log_buffer.write(self.name, f"[{self.name}] Stopping...\n")
            try:
                subprocess.call(['taskkill', '/F', '/T', '/PID', str(self.process.pid)], 
                              creationflags=subprocess.CREATE_NO_WINDOW)
            except Exception: pass
            self.process = None

    def _run_job(self):
        self.is_running = True
        self.status_callback(self.name, True)
        self.log_buffer.write(self.name, f"[{self.name}] Starting...\n")
        
        try:
            startupinfo = subprocess.STARTUPINFO()
            startupinfo.dwFlags |= subprocess.STARTF_USESHOWWINDOW
            
            cmd = self.command
            if isinstance(cmd, str): cmd = cmd.split()
            if cmd[0] == 'npm' and os.name == 'nt': cmd[0] = 'npm.cmd'

            self.process = subprocess.Popen(
                cmd,
                stdout=subprocess.PIPE,
                stderr=subprocess.STDOUT,
                stdin=subprocess.DEVNULL,
                startupinfo=startupinfo,
                creationflags=subprocess.CREATE_NO_WINDOW,
                text=True,
                bufsize=1,
                encoding='utf-8', 
                errors='replace'
            )

            while not self.stop_event.is_set():
                if not self.process: break
                if self.process.poll() is not None: break
                
                try: 
                    # Blocking read with shortlines is usually fine
                    # But if no output, it sits here. That's fine for a thread.
                    line = self.process.stdout.readline() 
                except: break
                
                if not line: break
                
                clean = ANSI_ESCAPE.sub('', line)
                if clean.strip():
                    self.log_buffer.write(self.name, clean)

        except Exception as e:
            self.log_buffer.write(self.name, f"Error: {e}\n")
        finally:
            self.is_running = False
            self.status_callback(self.name, False)
            self.log_buffer.write(self.name, f"[{self.name}] Stopped.\n")

class AntrianLauncher:
    def __init__(self, root):
        self.root = root
        self.root.title("Antrian Control Panel - Safe Mode")
        self.root.geometry("1000x750")
        
        try:
            icon_path = resource_path("app_icon.ico")
            if os.path.exists(icon_path): self.root.iconbitmap(icon_path)
        except: pass

        self.log_buffer = LogBuffer()
        self.services = {} 
        self.queue = queue.Queue() 
        self.local_ip = "127.0.0.1" # Defer heavy init
        self.tray_icon = None
        self.is_quitting = False
        self.cwd = os.getcwd()

        self.service_definitions = [
            ("Docker Services", "docker-compose up"),
            ("Laravel Server", "php artisan serve --host 0.0.0.0"),
            ("Reverb WS", "php artisan reverb:start --debug --host 0.0.0.0"),
            ("Vite Frontend", "npm run dev -- --host"),
            ("Queue Worker", "php artisan queue:work")
        ]
        
        self.create_menu()
        self.init_ui()
        
        # Defer Everything Heavy
        self.root.after(100, self.lazy_init)

    def lazy_init(self):
        """Perform initialization after window is drawn"""
        self.local_ip = self.get_safe_ip()
        self.lbl_ip.configure(text=f"Local IP: {self.local_ip}")
        self.update_link_buttons()
        
        # Start loops
        self.root.after(100, self.process_status_queue)
        self.root.after(1000, self.update_logs_ui)
        self.root.after(3000, self.start_monitoring_threads) # Wait 3s before monitoring
        threading.Thread(target=self.run_dependency_check, daemon=True).start()

    def get_safe_ip(self):
        try:
            s = socket.socket(socket.AF_INET, socket.SOCK_DGRAM)
            s.connect(("8.8.8.8", 80))
            ip = s.getsockname()[0]
            s.close()
            return ip
        except: return "127.0.0.1"

    def create_menu(self):
        menubar = Menu(self.root)
        self.root.config(menu=menubar)
        file_menu = Menu(menubar, tearoff=0)
        menubar.add_cascade(label="File", menu=file_menu)
        file_menu.add_command(label="Open Project Folder", command=lambda: os.startfile(self.cwd))
        file_menu.add_command(label="Open .env", command=lambda: os.startfile(os.path.join(self.cwd, ".env")) if os.path.exists(os.path.join(self.cwd, ".env")) else None)
        file_menu.add_separator()
        file_menu.add_command(label="Hide to Tray", command=self.minimize_to_tray)
        file_menu.add_command(label="Exit", command=self.quit_app)
        
        self.root.protocol("WM_DELETE_WINDOW", self.minimize_to_tray)

    def init_ui(self):
        info_frame = ttk.Frame(self.root, padding=5, relief="groove")
        info_frame.pack(fill=tk.X)
        self.lbl_ip = ttk.Label(info_frame, text="Local IP: ...", font=("Segoe UI", 9, "bold"))
        self.lbl_ip.pack(side=tk.LEFT, padx=10)
        self.lbl_docker = ttk.Label(info_frame, text="Docker: Wait...", font=("Segoe UI", 9))
        self.lbl_docker.pack(side=tk.LEFT, padx=10)

        action_frame = ttk.Frame(self.root, padding=10)
        action_frame.pack(fill=tk.X)
        self.btn_start_all = ttk.Button(action_frame, text="▶ START ALL", command=self.start_all)
        self.btn_start_all.pack(side=tk.LEFT, padx=5)
        ttk.Button(action_frame, text="⏹ STOP ALL", command=self.stop_all).pack(side=tk.LEFT, padx=5)
        
        ttk.Separator(action_frame, orient=tk.VERTICAL).pack(side=tk.LEFT, padx=15, fill=tk.Y)
        
        self.qa_buttons = {}
        for label, path in [("Web App", ""), ("Monitor", "/monitor/1"), ("Operator", "/operator")]:
            btn = ttk.Button(action_frame, text=label, state="disabled")
            btn.pack(side=tk.LEFT, padx=2)
            self.qa_buttons[label] = (btn, path)

        paned = ttk.PanedWindow(self.root, orient=tk.HORIZONTAL)
        paned.pack(fill=tk.BOTH, expand=True, padx=5, pady=5)
        
        self.service_widgets = {}
        
        left_frame = ttk.LabelFrame(paned, text="Services", width=300)
        paned.add(left_frame, weight=0)
        
        self.log_pages = ttk.Notebook(paned)
        paned.add(self.log_pages, weight=1)
        
        # All Logs
        all_f = ttk.Frame(self.log_pages)
        self.log_pages.add(all_f, text="All Logs")
        self.all_log_txt = scrolledtext.ScrolledText(all_f, state='disabled', font=("Consolas", 9))
        self.all_log_txt.pack(fill=tk.BOTH, expand=True)

        for name, cmd in self.service_definitions:
            self.build_service_row(left_frame, name, cmd)

        # Footer
        footer = ttk.Frame(self.root, relief="sunken")
        footer.pack(fill=tk.X, side=tk.BOTTOM)
        self.lbl_res = ttk.Label(footer, text="System: Monitoring starting in 3s...", font=("Consolas", 9))
        self.lbl_res.pack(side=tk.LEFT, padx=5)

    def build_service_row(self, parent, name, cmd):
        row = ttk.Frame(parent, padding=2)
        row.pack(fill=tk.X, pady=1)
        
        top = ttk.Frame(row)
        top.pack(fill=tk.X)
        ttk.Label(top, text=name, font=("Segoe UI", 9, "bold")).pack(side=tk.LEFT)
        status_var = tk.StringVar(value="OFF")
        lbl = ttk.Label(top, textvariable=status_var, font=("Consolas", 9), foreground="gray")
        lbl.pack(side=tk.RIGHT)
        
        bot = ttk.Frame(row)
        bot.pack(fill=tk.X)
        b_start = ttk.Button(bot, text="Start", width=6, command=lambda: self.services[name].start())
        b_start.pack(side=tk.LEFT)
        b_stop = ttk.Button(bot, text="Stop", width=6, state="disabled", command=lambda: self.services[name].stop())
        b_stop.pack(side=tk.LEFT)

        # Tab
        tab = ttk.Frame(self.log_pages)
        self.log_pages.add(tab, text=name.split()[0])
        txt = scrolledtext.ScrolledText(tab, state='disabled', font=("Consolas", 9), bg="#222", fg="#ddd")
        txt.pack(fill=tk.BOTH, expand=True)

        self.service_widgets[name] = {
            "var": status_var, "lbl": lbl, "start": b_start, "stop": b_stop, "txt": txt
        }
        self.services[name] = ServiceThread(name, cmd, self.log_buffer, 
            lambda n, s: self.queue.put(("STATUS", n, s)))

    def update_link_buttons(self):
        base = f"http://{self.local_ip}:8000"
        for label, (btn, path) in self.qa_buttons.items():
            btn.configure(command=lambda u=f"{base}{path}": webbrowser.open(u))

    def process_status_queue(self):
        while not self.queue.empty():
            try:
                kind, n, d = self.queue.get_nowait()
                if kind == "STATUS":
                    w = self.service_widgets[n]
                    w["var"].set("RUNNING" if d else "OFF")
                    w["lbl"].configure(foreground="green" if d else "gray")
                    w["start"].configure(state="disabled" if d else "normal")
                    w["stop"].configure(state="normal" if d else "disabled")
                    if n == "Laravel Server":
                        for k in self.qa_buttons: self.qa_buttons[k][0].configure(state="normal" if d else "disabled")
                elif kind == "DOCKER":
                    self.lbl_docker.configure(text=n, foreground=d)
                elif kind == "RES":
                    self.lbl_res.configure(text=n)
                elif kind == "MSG":
                    messagebox.showinfo("Info", n)
            except: break
        if not self.is_quitting: self.root.after(100, self.process_status_queue)

    def update_logs_ui(self):
        # 2 FPS update
        data = self.log_buffer.flush()
        if data:
            for name, text in data.items():
                short_text = text[-MAX_LOG_CHARS_PER_TICK:] # Strict limit
                
                # Update specific tab
                if name != "System":
                    t = self.service_widgets[name]["txt"]
                    t.config(state='normal')
                    t.insert(tk.END, short_text)
                    t.see(tk.END)
                    t.config(state='disabled')
                
                # Update all
                at = self.all_log_txt
                at.config(state='normal')
                at.insert(tk.END, f"[{name}] {short_text}")
                at.see(tk.END)
                at.config(state='disabled')
        
        if not self.is_quitting: self.root.after(LOG_UPDATE_INTERVAL_MS, self.update_logs_ui)

    def start_all(self):
        self.btn_start_all.configure(state="disabled")
        def step(names):
            if not names: return
            self.services[names[0]].start()
            if len(names)>1: self.root.after(800, lambda: step(names[1:]))
        step(list(self.services.keys()))

    def stop_all(self):
        for s in self.services.values(): s.stop()
        self.btn_start_all.configure(state="normal")

    def run_dependency_check(self):
        # Simple non-blocking check
        pass # Disabled to prevent any startup hangs

    def start_monitoring_threads(self):
        threading.Thread(target=self._mon_dock, daemon=True).start()
        threading.Thread(target=self._mon_res, daemon=True).start()

    def _mon_dock(self):
        while not self.is_quitting:
            status = "Docker: Inactive"; col = "red"
            try:
                subprocess.check_output("docker info", shell=True, stderr=subprocess.STDOUT, creationflags=subprocess.CREATE_NO_WINDOW)
                status = "Docker: Active"; col = "green"
            except: pass
            if not self.is_quitting: self.queue.put(("DOCKER", status, col))
            time.sleep(5)

    def _mon_res(self):
        while not self.is_quitting:
            try:
                cpu = psutil.cpu_percent(interval=1)
                mem = psutil.virtual_memory()
                msg = f"CPU: {cpu}% | RAM: {round(mem.used/1024**3,1)} GB | Disk: {psutil.disk_usage('/').percent}%"
                if not self.is_quitting: self.queue.put(("RES", msg, None))
            except: pass
            # Sleep already happened in cpu_percent(interval=1)

    def minimize_to_tray(self):
        if not HAS_TRAY: self.quit_app(); return
        self.root.withdraw()
        if not self.tray_icon:
            img = Image.new('RGB', (64, 64), "#007acc")
            if os.path.exists(resource_path("app_icon.png")):
                 img = Image.open(resource_path("app_icon.png"))
            self.tray_icon = pystray.Icon("A", img, "Antrian", (item('Show', self.show_window), item('Exit', self.quit_app)))
            threading.Thread(target=self.tray_icon.run, daemon=True).start()

    def show_window(self, i=None, it=None):
        if self.tray_icon: self.tray_icon.stop(); self.tray_icon = None
        self.root.after(0, self.root.deiconify)

    def quit_app(self, i=None, it=None):
        self.is_quitting = True
        try: 
            if self.tray_icon: self.tray_icon.stop()
        except: pass
        self.stop_all()
        self.root.quit()

if __name__ == "__main__":
    root = tk.Tk()
    app = AntrianLauncher(root)
    root.mainloop()
