@echo off
echo ========================================
echo   SmartShield ML System - Starting
echo ========================================
cd /d "%~dp0"
echo Iniciando servidor ML en puerto 5001...
python ml_api_server.py
pause
