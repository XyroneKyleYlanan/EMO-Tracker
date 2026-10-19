@echo off
REM EMO Tracker's server: PHP's built-in web server, serving the app on port 8000
REM to this laptop and to the other devices on the network. "EMO Tracker.vbs"
REM starts this file hidden, and it keeps running until the laptop shuts down.
REM Its messages go to backend\storage\logs\server.log.
setlocal
call "%~dp0find-php.bat"
if errorlevel 1 exit /b 1

REM Already running (for example, the icon was clicked twice): nothing to do.
netstat -an | findstr /r /c:":8000 .*LISTENING" >NUL
if not errorlevel 1 exit /b 0

cd /d "%~dp0..\backend\public"

REM Start a fresh log when the old one passes 5 MB.
set "LOG=..\storage\logs\server.log"
if exist "%LOG%" for %%F in ("%LOG%") do if %%~zF GTR 5000000 move /y "%LOG%" "..\storage\logs\server-old.log" >NUL

REM Room for the 10 MB documents the app accepts (PHP's own limit is 2 MB).
php -d upload_max_filesize=12M -d post_max_size=16M -S 0.0.0.0:8000 "..\vendor\laravel\framework\src\Illuminate\Foundation\resources\server.php" >> "%LOG%" 2>&1
