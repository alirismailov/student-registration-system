@echo off
REM Start XAMPP MariaDB for BWP501 on 127.0.0.1:3306
REM Does not stop or change MySQL96 on port 3306.
REM Uses isolated config: C:\xampp\mysql\bin\my-bwp501-3306.ini
REM Original XAMPP my.ini is left unchanged.

netstat -an | findstr ":3306" | findstr LISTENING >nul
if %ERRORLEVEL%==0 (
  echo MariaDB already listening on port 3306.
  exit /b 0
)

start "" /B "C:\xampp\mysql\bin\mysqld.exe" --defaults-file=C:/xampp/mysql/bin/my-bwp501-3306.ini
echo Started mysqld with my-bwp501-3306.ini ^(port 3306, bind 127.0.0.1^).
echo Verify: C:\xampp\php\php.exe tools\check_database.php
