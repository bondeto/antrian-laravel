@echo off
echo ==========================================
echo  MEMBUAT LAUNCHER EXE (AntrianLauncher)
echo ==========================================
echo.

echo [0/3] Stopping existing instances...
taskkill /IM "AntrianLauncher.exe" /F >nul 2>&1

echo [1/3] Building EXE with PyInstaller...
python -m PyInstaller --noconsole --onefile --icon="app_icon.ico" --name "AntrianLauncher" antrian_launcher.py

if %ERRORLEVEL% NEQ 0 (
    echo.
    echo [ERROR] Gagal membuat EXE.
    pause
    exit /b
)

echo.
echo [2/3] Moving EXE to current folder...
if exist "dist\AntrianLauncher.exe" (
    move /Y "dist\AntrianLauncher.exe" "AntrianLauncher.exe" >nul
    echo OK.
) else (
    echo [ERROR] File EXE tidak ditemukan di folder dist.
    pause
    exit /b
)

echo.
echo [3/3] Cleaning up temporary files...
if exist "build" rmdir /s /q "build"
if exist "dist" rmdir /s /q "dist"
if exist "AntrianLauncher.spec" del "AntrianLauncher.spec"
echo OK.

echo.
echo ==========================================
echo  SELESAI! File 'AntrianLauncher.exe' siap.
echo ==========================================
pause
