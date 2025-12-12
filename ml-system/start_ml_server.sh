#!/bin/bash
echo "========================================"
echo "  SmartShield ML System - Starting"
echo "========================================"
cd "$(dirname "$0")"
echo "Iniciando servidor ML en puerto 5001..."
python3 ml_api_server.py
