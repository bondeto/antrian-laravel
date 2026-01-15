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
from PIL import Image, ImageDraw

try:
    import pystray
    from pystray import MenuItem as item
    HAS_TRAY = True
except ImportError:
    HAS_TRAY = False

# Regex to strip ANSI escape codes
ANSI_ESCAPE = re.compile(r'\x1B(?:[@-Z\\-_]|\[[0-?]*[ -/]*[@-~])')

class ServiceThread:
    """Manages a single subprocess service."""
    def __init__(self, name, command, output_callback, status_callback):
        self.name = name
        self.command = command
        self.output_callback = output_callback
        self.status_callback = status_callback
        self.process = None
        self.stop_event = threading.Event()
        self.thread = None
        self.is_running = False

    def start(self):
        if self.is_running:
            return

        self.stop_event.clear()
        self.thread = threading.Thread(target=self._run_job, daemon=True)
        self.thread.start()

    def stop(self):
        self.stop_event.set()
        if self.process:
            self.output_callback(f"[{self.name}] Stopping...\n")
            try:
                subprocess.call(['taskkill', '/F', '/T', '/PID', str(self.process.pid)], creationflags=subprocess.CREATE_NO_WINDOW)
            except:
                pass
            self.process = None
        
    def _run_job(self):
        self.is_running = True
        self.status_callback(self.name, True)
        self.output_callback(f"[{self.name}] Starting: {self.command}\n")
        
        startupinfo = subprocess.STARTUPINFO()
        startupinfo.dwFlags |= subprocess.STARTF_USESHOWWINDOW
        
        try:
            cmd = self.command
            if isinstance(cmd, str):
                cmd = cmd.split()
                
            if cmd[0] == 'npm' and os.name == 'nt':
                cmd[0] = 'npm.cmd'
            
            self.process = subprocess.Popen(
                cmd,
                stdout=subprocess.PIPE,
                stderr=subprocess.STDOUT,
                stdin=subprocess.PIPE,
                startupinfo=startupinfo,
                creationflags=subprocess.CREATE_NO_WINDOW,
                text=True,
                bufsize=1,
                encoding='utf-8', 
                errors='replace'
            )

            while not self.stop_event.is_set():
                line = self.process.stdout.readline()
                if not line:
                    break
                
                clean_line = ANSI_ESCAPE.sub('', line)
                if clean_line.strip():
                    self.output_callback(clean_line)
            
            if self.process:
                self.process.wait()
        except Exception as e:
            self.output_callback(f"[{self.name}] Error: {str(e)}\n")
        finally:
            self.is_running = False
            self.status_callback(self.name, False)
            self.output_callback(f"[{self.name}] Stopped.\n")

class AntrianLauncher:
    def __init__(self, root):
        self.root = root
        self.root.title("Antrian Control Panel - Administrator Mode")
        self.root.geometry("1000x750")
        
        # USE ICON IF EXISTS
        if os.path.exists("app_icon.ico"):
            try:
                self.root.iconbitmap("app_icon.ico")
            except:
                pass

        # --- Data & State ---
        self.services = {} 
        self.queue = queue.Queue() 
        self.local_ip = self.get_local_ip()
        self.tray_icon = None
        self.is_quitting = False

        self.cwd = os.getcwd()

        # Define Services
        self.service_definitions = [
            ("Docker Services", "docker-compose up"),
            ("Laravel Server", "php artisan serve --host 0.0.0.0"),
            ("Reverb WS", "php artisan reverb:start --debug --host 0.0.0.0"),
            ("Vite Frontend", "npm run dev -- --host"),
            ("Queue Worker", "php artisan queue:work")
        ]

        # --- Base UI ---
        self.style = ttk.Style()
        self.style.configure("Status.TLabel", font=("Consolas", 10, "bold"))
        self.style.configure("Header.TLabel", font=("Segoe UI", 12, "bold"))
        self.style.configure("Footer.TLabel", font=("Consolas", 9))
        
        self.create_menu()
        self.init_ui()
        
        # --- Loops ---
        self.root.after(100, self.process_queue)
        self.root.after(1000, self.monitor_docker)
        self.root.after(2000, self.monitor_resources)

        self.root.protocol("WM_DELETE_WINDOW", self.minimize_to_tray)
        self.check_dependencies()

    def get_local_ip(self):
        try:
            s = socket.socket(socket.AF_INET, socket.SOCK_DGRAM)
            s.connect(("8.8.8.8", 80))
            ip = s.getsockname()[0]
            s.close()
            return ip
        except:
            return "127.0.0.1"

    def create_menu(self):
        menubar = Menu(self.root)
        self.root.config(menu=menubar)

        # File Menu
        file_menu = Menu(menubar, tearoff=0)
        menubar.add_cascade(label="File", menu=file_menu)
        file_menu.add_command(label="Open Project Folder", command=self.open_project_folder)
        file_menu.add_command(label="Open .env File", command=self.open_env_file)
        file_menu.add_separator()
        file_menu.add_command(label="Hide to Tray", command=self.minimize_to_tray)
        file_menu.add_command(label="Exit", command=self.quit_app)

        # Tools Menu
        tools_menu = Menu(menubar, tearoff=0)
        menubar.add_cascade(label="Tools", menu=tools_menu)
        tools_menu.add_command(label="Clear Cache (optimize:clear)", command=self.tool_clear_cache)
        tools_menu.add_separator()
        tools_menu.add_command(label="View Laravel.log", command=self.open_laravel_log)
        
        # Help Menu
        help_menu = Menu(menubar, tearoff=0)
        menubar.add_cascade(label="Help", menu=help_menu)
        help_menu.add_command(label="About", command=lambda: messagebox.showinfo("About", "Antrian Control Panel v4.1\nPowered by Google Deepmind Agent"))

    def check_dependencies(self):
        # Quick check for commands
        missing = []
        for cmd in ['php --version', 'npm --version', 'docker --version']:
            try:
                subprocess.check_output(cmd, shell=True, stderr=subprocess.STDOUT, creationflags=subprocess.CREATE_NO_WINDOW)
            except:
                missing.append(cmd.split()[0])
        
        if missing:
            msg = f"Warning: The following tools seem missing or not in PATH:\n{', '.join(missing)}\n\nSome services may not start."
            messagebox.showwarning("Dependencies Check", msg)

    # --- Tool Actions ---
    def open_project_folder(self):
        os.startfile(self.cwd)

    def open_env_file(self):
        env_path = os.path.join(self.cwd, ".env")
        if os.path.exists(env_path):
            os.startfile(env_path)
        else:
            messagebox.showerror("Error", ".env file not found!")

    def open_laravel_log(self):
        log_path = os.path.join(self.cwd, "storage", "logs", "laravel.log")
        if os.path.exists(log_path):
            os.startfile(log_path)
        else:
            messagebox.showinfo("Info", "Log file not found (might be empty).")

    def run_artisan_command(self, cmd_args, description):
        if messagebox.askyesno("Confirm", f"Run command: php artisan {cmd_args}?\n({description})"):
            threading.Thread(target=self._run_oneshot, args=(f"php artisan {cmd_args}", description)).start()

    def tool_clear_cache(self):
        self.run_artisan_command("optimize:clear", "Clearing App Cache")

    def _run_oneshot(self, cmd, label):
        self.queue_log("System", f"Executing: {label}...\n")
        try:
            startupinfo = subprocess.STARTUPINFO()
            startupinfo.dwFlags |= subprocess.STARTF_USESHOWWINDOW
            result = subprocess.check_output(cmd, shell=True, stderr=subprocess.STDOUT, creationflags=subprocess.CREATE_NO_WINDOW, encoding='utf-8', errors='replace')
            self.queue_log("System", f"--- Output ({label}) ---\n{result}\n----------------------\n")
            self.queue_log("System", f"{label}: SUCCESS\n")
            messagebox.showinfo("Success", f"{label} completed successfully.")
        except subprocess.CalledProcessError as e:
            self.queue_log("System", f"ERROR ({label}):\n{e.output}\n")
            messagebox.showerror("Error", f"{label} Failed. Check logs.")

    # --- UI & Core (Same as V3 but with Menu) ---
    def init_ui(self):
        # 1. Info Bar
        info_frame = ttk.Frame(self.root, padding=5, relief="groove")
        info_frame.pack(fill=tk.X)
        ttk.Label(info_frame, text=f"Local IP: {self.local_ip}", font=("Segoe UI", 9, "bold")).pack(side=tk.LEFT, padx=10)
        self.lbl_docker = ttk.Label(info_frame, text="Docker: Checking...", font=("Segoe UI", 9))
        self.lbl_docker.pack(side=tk.LEFT, padx=10)

        # 2. Main Controls
        action_frame = ttk.Frame(self.root, padding=10)
        action_frame.pack(fill=tk.X)
        self.btn_start_all = ttk.Button(action_frame, text="▶ START ALL SERVICES", command=self.start_all)
        self.btn_start_all.pack(side=tk.LEFT, padx=5)
        ttk.Button(action_frame, text="⏹ STOP ALL", command=self.stop_all).pack(side=tk.LEFT, padx=5)
        
        ttk.Separator(action_frame, orient=tk.VERTICAL).pack(side=tk.LEFT, padx=15, fill=tk.Y)
        
        ttk.Label(action_frame, text="Quick Open:", foreground="#666").pack(side=tk.LEFT, padx=5)
        ttk.Button(action_frame, text="Web App", command=lambda: webbrowser.open(f"http://{self.local_ip}:8000")).pack(side=tk.LEFT, padx=2)
        ttk.Button(action_frame, text="Monitor", command=lambda: webbrowser.open(f"http://{self.local_ip}:8000/monitor/1")).pack(side=tk.LEFT, padx=2)
        ttk.Button(action_frame, text="Operator", command=lambda: webbrowser.open(f"http://{self.local_ip}:8000/operator")).pack(side=tk.LEFT, padx=2)

        # 3. Main Split
        paned = ttk.PanedWindow(self.root, orient=tk.HORIZONTAL)
        paned.pack(fill=tk.BOTH, expand=True, padx=5, pady=5)
        self.status_frame = ttk.LabelFrame(paned, text=" Service Control ", padding=5, width=320)
        paned.add(self.status_frame, weight=0)
        self.log_notebook = ttk.Notebook(paned)
        paned.add(self.log_notebook, weight=5)

        # All Logs Tab
        all_logs_frame = ttk.Frame(self.log_notebook)
        self.log_notebook.add(all_logs_frame, text="All Logs")
        self.all_log_widget = scrolledtext.ScrolledText(all_logs_frame, state='disabled', font=("Consolas", 9))
        self.all_log_widget.pack(fill=tk.BOTH, expand=True)

        self.service_widgets = {}
        for name, cmd in self.service_definitions:
            self.create_service_ui(name, cmd)

        # 4. Footer
        footer_frame = ttk.Frame(self.root, padding=2, relief="sunken")
        footer_frame.pack(fill=tk.X, side=tk.BOTTOM)
        self.lbl_cpu = ttk.Label(footer_frame, text="CPU: 0%", style="Footer.TLabel", width=12)
        self.lbl_cpu.pack(side=tk.LEFT, padx=5)
        self.lbl_ram = ttk.Label(footer_frame, text="RAM: 0/0 GB", style="Footer.TLabel", width=20)
        self.lbl_ram.pack(side=tk.LEFT, padx=5)
        self.lbl_disk = ttk.Label(footer_frame, text="Disk: 0%", style="Footer.TLabel", width=15)
        self.lbl_disk.pack(side=tk.LEFT, padx=5)

    def create_service_ui(self, name, cmd):
        frame = ttk.Frame(self.status_frame, relief="flat", borderwidth=1)
        frame.pack(fill=tk.X, pady=2)
        
        r1 = ttk.Frame(frame)
        r1.pack(fill=tk.X)
        ttk.Label(r1, text=name, font=("Segoe UI", 10, "bold")).pack(side=tk.LEFT)
        status_var = tk.StringVar(value="⚫ OFF")
        lbl_status = ttk.Label(r1, textvariable=status_var, style="Status.TLabel", foreground="#666")
        lbl_status.pack(side=tk.RIGHT)

        r2 = ttk.Frame(frame)
        r2.pack(fill=tk.X, pady=(2, 6))
        btn_start = ttk.Button(r2, text="Start", width=8, command=lambda n=name: self.start_service(n))
        btn_start.pack(side=tk.LEFT, padx=(0, 2))
        btn_stop = ttk.Button(r2, text="Stop", width=8, state="disabled", command=lambda n=name: self.stop_service(n))
        btn_stop.pack(side=tk.LEFT, padx=(0, 2))
        ttk.Separator(frame, orient=tk.HORIZONTAL).pack(fill=tk.X, pady=4)

        tab_frame = ttk.Frame(self.log_notebook)
        short_name = name.split()[0]
        if "Laravel" in name: short_name = "Laravel"
        if "Reverb" in name: short_name = "Reverb"
        if "Queue" in name: short_name = "Queue"
        if "Docker" in name: short_name = "Docker"
        self.log_notebook.add(tab_frame, text=short_name)
        log_widget = scrolledtext.ScrolledText(tab_frame, state='disabled', font=("Consolas", 9), background="#1e1e1e", foreground="#d4d4d4")
        log_widget.pack(fill=tk.BOTH, expand=True)

        service = ServiceThread(
            name, cmd,
            output_callback=lambda msg, n=name: self.queue_log(n, msg),
            status_callback=lambda n, s: self.queue.put(("STATUS", n, s))
        )
        self.services[name] = service
        self.service_widgets[name] = {
            "status_var": status_var, "status_lbl": lbl_status,
            "btn_start": btn_start, "btn_stop": btn_stop, "log_widget": log_widget
        }

    # --- Tray & Lifecycle (Same as before) ---
    def create_image(self):
        # USE ICON IF POSSIBLE
        if os.path.exists("app_icon.png"):
             return Image.open("app_icon.png")
        # Fallback
        width, height = 64, 64
        image = Image.new('RGB', (width, height), "#007acc")
        dc = ImageDraw.Draw(image)
        dc.rectangle((16, 16, 48, 48), fill="#ffffff")
        return image

    def minimize_to_tray(self):
        if not HAS_TRAY: self.quit_app(); return
        self.root.withdraw()
        if not self.tray_icon:
            image = self.create_image()
            menu = (item('Show', self.show_window), item('Exit', self.quit_app))
            self.tray_icon = pystray.Icon("Antrian", image, "Antrian Launcher", menu)
            threading.Thread(target=self.tray_icon.run, daemon=True).start()

    def show_window(self, icon=None, item=None):
        if self.tray_icon: self.tray_icon.stop(); self.tray_icon = None
        self.root.after(0, self.root.deiconify)

    def quit_app(self, icon=None, item=None):
        self.is_quitting = True
        if self.tray_icon: self.tray_icon.stop()
        self.stop_all()
        self.root.quit()

    def start_service(self, name): self.services[name].start()
    def stop_service(self, name): self.services[name].stop()
    def start_all(self):
        self.btn_start_all.configure(state="disabled")
        for name in self.services: self.services[name].start()
    def stop_all(self):
        for name in reversed(self.services.keys()): self.services[name].stop()
        self.btn_start_all.configure(state="normal")
    
    def monitor_docker(self):
        try:
            subprocess.check_output("docker info", shell=True, stderr=subprocess.STDOUT, creationflags=subprocess.CREATE_NO_WINDOW)
            self.lbl_docker.configure(text="Docker: 🟢 Active", foreground="green")
        except:
            self.lbl_docker.configure(text="Docker: 🔴 Inactive", foreground="red")
        if not self.is_quitting: self.root.after(5000, self.monitor_docker)

    def monitor_resources(self):
        try:
            cpu_pct = psutil.cpu_percent(interval=None)
            self.lbl_cpu.configure(text=f"CPU: {cpu_pct}%", foreground="red" if cpu_pct > 80 else "black")
            mem = psutil.virtual_memory()
            self.lbl_ram.configure(text=f"RAM: {round(mem.used/1024**3,1)}/{round(mem.total/1024**3,1)} GB", foreground="red" if mem.percent > 85 else "black")
            self.lbl_disk.configure(text=f"Disk: {psutil.disk_usage('/').percent}%")
        except: pass
        if not self.is_quitting: self.root.after(2000, self.monitor_resources)

    def queue_log(self, name, msg): self.queue.put(("LOG", name, msg))
    def process_queue(self):
        while not self.queue.empty():
            try:
                kind, name, data = self.queue.get_nowait()
                if kind == "LOG": self.append_log(name, data)
                elif kind == "STATUS": self.update_status(name, data)
            except queue.Empty: break
        if not self.is_quitting: self.root.after(100, self.process_queue)

    def append_log(self, name, text):
        widgets = self.service_widgets.get(name)
        if widgets:
            w = widgets["log_widget"]; w.config(state='normal'); w.insert(tk.END, text); w.see(tk.END); w.config(state='disabled')
        self.all_log_widget.config(state='normal'); self.all_log_widget.insert(tk.END, f"[{name}] {text}"); self.all_log_widget.see(tk.END); self.all_log_widget.config(state='disabled')

    def update_status(self, name, is_running):
        w = self.service_widgets.get(name)
        if w:
            w["status_var"].set("🟢 RUNNING" if is_running else "⚫ STOPPED")
            w["status_lbl"].configure(foreground="green" if is_running else "#666")
            w["btn_start"].configure(state="disabled" if is_running else "normal")
            w["btn_stop"].configure(state="normal" if is_running else "disabled")

if __name__ == "__main__":
    root = tk.Tk()
    app = AntrianLauncher(root)
    root.mainloop()
