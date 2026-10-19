@echo off
REM Finds PHP 8.3 or newer for the other files in this folder ("call" this file):
REM the version Laragon is set to, or else any PHP 8 that Laragon has, or else
REM PHP on the PATH. Adds it to the PATH of the file that called it.
set "PHP_DIR="
for /d %%D in ("C:\laragon\bin\php\php-8*") do set "PHP_DIR=%%~fD"
for /f "tokens=2 delims==" %%V in ('findstr /b /c:"Version=php" "C:\laragon\usr\laragon.ini" 2^>NUL') do if exist "C:\laragon\bin\php\%%V\php.exe" set "PHP_DIR=C:\laragon\bin\php\%%V"
if defined PHP_DIR set "PATH=%PHP_DIR%;%PATH%"

where php >NUL 2>NUL
if errorlevel 1 (
  echo  PHP was not found. Install Laragon first, then run this again.
  exit /b 1
)
php -r "exit(version_compare(PHP_VERSION, '8.3.0', '<') ? 1 : 0);"
if errorlevel 1 (
  echo  EMO Tracker needs PHP 8.3 or newer. In Laragon: Menu, PHP, Version, then choose 8.3 or 8.4.
  exit /b 1
)
exit /b 0
