@echo off
title Sober Inventory - Dev Server
echo Starting PHP server at http://localhost:8000
echo Press Ctrl+C to stop.
echo.
php -S localhost:8000 -t public public/index.php
pause
