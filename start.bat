@echo off
title EMO Tracker - Launcher
echo.
echo  ============================================================
echo   EMO Tracker - Starting up...
echo  ============================================================
echo.
echo  Make sure Laragon is running (Start All button clicked).
echo.

REM Use Laragon's PHP: the version Laragon is set to, or else any PHP 8 it has,
REM so a Laragon update (which renames the folder) doesn't break this file.
REM Without Laragon, PHP must already be on the PATH.
set "PHP_DIR="
for /d %%D in ("C:\laragon\bin\php\php-8*") do set "PHP_DIR=%%~fD"
for /f "tokens=2 delims==" %%V in ('findstr /b /c:"Version=php" "C:\laragon\usr\laragon.ini" 2^>NUL') do if exist "C:\laragon\bin\php\%%V\php.exe" set "PHP_DIR=C:\laragon\bin\php\%%V"
if defined PHP_DIR set "PATH=%PHP_DIR%;%PATH%"

where php >NUL 2>NUL
if errorlevel 1 (
  echo  PHP was not found. Install Laragon, or add PHP 8.3 or newer to the PATH.
  echo.
  pause
  exit /b 1
)

php -r "exit(version_compare(PHP_VERSION, '8.3.0', '<') ? 1 : 0);"
if errorlevel 1 (
  echo  EMO Tracker needs PHP 8.3 or newer. In Laragon, choose it under Menu, PHP, Version.
  echo.
  pause
  exit /b 1
)

REM A new version of the code may need database changes; they're applied here,
REM after a backup. Usually this just says the database is up to date.
pushd "%~dp0backend"
php artisan app:update-database
if errorlevel 1 (
  popd
  echo.
  echo  The database could not be updated, so EMO Tracker was not started.
  echo  Check that Laragon is running, then try again. The message above says why.
  echo.
  pause
  exit /b 1
)
popd

REM Start the Laravel backend in a new window (%~dp0 = the folder this file is in,
REM so it works whatever the project folder is called). It uses the PHP found above.
start "EMO Tracker - Backend (Laravel)" /D "%~dp0backend" cmd /k "echo Starting Laravel backend on http://127.0.0.1:8000 && echo. && php artisan serve"

REM Wait a moment then start the Vite frontend in a new window
timeout /t 2 /nobreak >NUL
start "EMO Tracker - Frontend (React)" /D "%~dp0frontend" cmd /k "echo Starting React frontend on http://localhost:5173 && echo. && npm run dev"

REM Wait for servers to come up, then open the browser
echo.
echo  Both servers are starting in separate windows...
echo  Browser will open in 5 seconds.
echo.
timeout /t 5 /nobreak >NUL
start http://localhost:5173

echo.
echo  ============================================================
echo   EMO Tracker is running!
echo  ============================================================
echo.
echo   Frontend: http://localhost:5173
echo   Backend:  http://127.0.0.1:8000
echo.
echo   To stop the app:
echo   - Close the two CMD windows titled "Backend" and "Frontend"
echo   - Then stop Laragon (optional)
echo.
echo  You can close this window now.
echo.
pause
