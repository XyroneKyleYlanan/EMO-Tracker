@echo off
title EMO Tracker - Launcher
echo.
echo  ============================================================
echo   EMO Tracker - Starting up...
echo  ============================================================
echo.
echo  Make sure Laragon is running (Start All button clicked).
echo.

REM Start the Laravel backend in a new window (%~dp0 = the folder this file is in,
REM so it works whatever the project folder is called)
start "EMO Tracker - Backend (Laravel)" /D "%~dp0backend" cmd /k "set PATH=C:\laragon\bin\php\php-8.3.26-Win32-vs16-x64;%%PATH%% && echo Starting Laravel backend on http://127.0.0.1:8000 && echo. && php artisan serve"

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
