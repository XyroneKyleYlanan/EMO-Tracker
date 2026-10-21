@echo off
title EMO Tracker - Setup
REM Sets up EMO Tracker on the office laptop. Run it once after copying the
REM EMOTracker folder (right-click, Run as administrator), and again after
REM copying in a new version. Laragon must be installed, with MySQL running.
setlocal EnableDelayedExpansion
set "OFFICE=%~dp0"
set "BACKEND=%~dp0..\backend"
echo.
echo  ============================================================
echo   EMO Tracker - Setup
echo  ============================================================
echo.

echo  [1/7] Checking PHP...
call "%OFFICE%find-php.bat"
if errorlevel 1 goto failed
set "MISSING="
for %%M in (pdo_mysql mbstring openssl fileinfo zip gd) do (
  REM Asked of PHP directly: "php -m | findstr /x" misses every module, because
  REM PHP ends its lines in a way findstr's exact-line match doesn't accept.
  php -r "exit(extension_loaded('%%M') ? 0 : 1);"
  if errorlevel 1 set "MISSING=!MISSING! %%M"
)
if defined MISSING (
  echo  PHP is missing these modules:!MISSING!
  echo  In Laragon: Menu, PHP, Extensions, then click each one. Then run this again.
  goto failed
)
echo        OK

echo  [2/7] Checking the EMO Tracker files...
if not exist "%BACKEND%\vendor\autoload.php" (
  echo  The backend\vendor folder is missing. Copy the whole EMOTracker folder again.
  goto failed
)
if not exist "%BACKEND%\public\app\index.html" (
  echo  The app's screens, backend\public\app, are missing. Copy the whole EMOTracker folder again.
  goto failed
)
echo        OK

cd /d "%BACKEND%"
echo  [3/7] Settings...
if exist ".env" (
  echo        Keeping the existing settings, backend\.env
) else (
  copy /y "%OFFICE%office.env" ".env" >NUL
  php artisan key:generate --force --no-interaction
  if errorlevel 1 goto failed
  echo        Created backend\.env
)

echo  [4/7] Preparing the database...
php artisan app:update-database
if errorlevel 1 (
  echo.
  echo  The database could not be prepared. Open Laragon and click Start All,
  echo  then run this again. The message above says what went wrong.
  goto failed
)

echo  [5/7] EMO Tracker's data...
set "DATA="
for %%F in ("%OFFICE%data\*.zip") do set "DATA=%%~fF"
if defined DATA (
  echo        Found a data backup: !DATA!
  choice /c YN /m "       Load it? This replaces any data already on this laptop"
  if !errorlevel! equ 1 (
    php artisan backup:restore "!DATA!" --force
    if errorlevel 1 goto failed
    REM Loaded once; renamed so running Setup again doesn't offer it again.
    move /y "!DATA!" "!DATA!.loaded" >NUL
  )
) else (
  echo        No data backup in office\data, nothing to load.
)

echo  [6/7] Desktop icon and starting with Windows...
cscript //nologo "%OFFICE%make-shortcuts.vbs"
if errorlevel 1 goto failed
net session >NUL 2>&1
if errorlevel 1 (
  echo        Not run as administrator: when Windows asks whether to allow "php", click Allow.
) else (
  netsh advfirewall firewall delete rule name="EMO Tracker" >NUL 2>&1
  netsh advfirewall firewall add rule name="EMO Tracker" dir=in action=allow protocol=TCP localport=8000 profile=any >NUL
  echo        Firewall: other devices on the network may connect to EMO Tracker.
)

echo  [7/7] Starting EMO Tracker...
REM Started in the background only: the browser opens from the desktop icon, which
REM also tests the icon (and the browser doesn't run as administrator).
wscript "%OFFICE%EMO Tracker.vbs" /startup

echo.
echo  ============================================================
echo   Done. Now double-click "EMO Tracker" on the desktop.
echo  ============================================================
echo.
echo   On this laptop:  the "EMO Tracker" icon, or http://127.0.0.1:8000
echo   Other devices on the same Wi-Fi, one of these addresses:
for /f "usebackq delims=" %%A in (`powershell -NoProfile -Command "Get-NetIPAddress -AddressFamily IPv4 | Where-Object { $_.IPAddress -notlike '127.*' -and $_.IPAddress -notlike '169.254.*' } | ForEach-Object { $_.IPAddress }"`) do echo                    http://%%A:8000
echo.
pause
exit /b 0

:failed
echo.
echo  Setup stopped. Fix the problem above, then run this again.
echo.
pause
exit /b 1
