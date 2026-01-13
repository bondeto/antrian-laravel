@echo off
set TITLE=Sistem Antrian Pelayanan
title %TITLE%

echo ==================================================
echo   MEMULAI SISTEM ANTRIAN (3 LANTAI)
echo ==================================================
echo.

:: 1. Docker
echo [1/5] Memastikan Docker PG dan Redis Berjalan...
docker-compose up -d
if %ERRORLEVEL% NEQ 0 (
    echo [ERROR] Gagal menjalankan Docker.
    pause
    exit /b %ERRORLEVEL%
)

:: 2. Config Clear
echo [2/5] Membersihkan Cache...
php artisan config:clear
php artisan cache:clear

:: 3. Web Server
echo [3/5] Menjalankan Server Utama (Port 8000)...
start "Laravel Server (8000)" cmd /c "php artisan serve"

:: 4. WebSocket Reverb
echo [4/5] Menjalankan Server WebSocket (Port 8081)...
:: Ditambahkan debug untuk melihat aktivitas
start "Reverb WebSocket (8081)" cmd /c "php artisan reverb:start --port=8081 --debug"

:: 5. Frontend Assets dan Worker
echo [5/5] Menjalankan Vite dan Worker...
start "Vite Dev" cmd /c "npm run dev"
start "Queue Worker" cmd /c "php artisan queue:work"

echo.
echo ==================================================
echo   APLIKASI BERHASIL DIJALANKAN
echo ==================================================
echo.
echo  Akses Web App    : http://127.0.0.1:8000
echo  Akses Monitor L1 : http://127.0.0.1:8000/monitor/1
echo  Akses Operator   : http://127.0.0.1:8000/operator
echo.
echo  Note: JANGAN tutup terminal yang baru terbuka.
echo ==================================================
pause
