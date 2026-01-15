@echo off
:: Cek apakah pythonw tersedia (untuk menjalankan tanpa konsol)
where pythonw >nul 2>nul
if %ERRORLEVEL% EQ 0 (
    start "" pythonw antrian_launcher.py
) else (
    :: Fallback jika pythonw tidak ada, gunakan python biasa
    echo Pythonw not found, using python...
    python antrian_launcher.py
)
