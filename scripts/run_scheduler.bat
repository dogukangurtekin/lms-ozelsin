@echo off
cd /d C:\xampp\htdocs\lms-ozelsin
C:\tools\php85\php.exe artisan schedule:run >> C:\xampp\htdocs\lms-ozelsin\storage\logs\scheduler.log 2>&1
