#!/usr/bin/env python3
"""
Script de configuración e instalación del sistema ML SmartShield
Instala dependencias, entrena modelos y configura el sistema completo
"""

import subprocess
import sys
import os
from pathlib import Path
import time

def print_step(step, message):
    """Imprimir paso con formato"""
    print(f"\n{'='*60}")
    print(f"PASO {step}: {message}")
    print('='*60)

def install_python_packages():
    """Instalar paquetes de Python necesarios"""
    print_step(1, "INSTALANDO DEPENDENCIAS DE PYTHON")
    
    packages = [
        'flask',
        'flask-cors', 
        'pandas',
        'numpy',
        'scikit-learn',
        'mysql-connector-python',
        'joblib'
    ]
    
    for package in packages:
        try:
            print(f"📦 Instalando {package}...")
            subprocess.check_call([
                sys.executable, '-m', 'pip', 'install', package
            ], stdout=subprocess.DEVNULL, stderr=subprocess.DEVNULL)
            print(f"  ✅ {package} instalado")
        except subprocess.CalledProcessError as e:
            print(f"  ❌ Error instalando {package}: {e}")
            return False
    
    print("\n✅ Todas las dependencias instaladas correctamente")
    return True

def create_ml_directories():
    """Crear directorios necesarios para ML"""
    print_step(2, "CREANDO ESTRUCTURA DE DIRECTORIOS")
    
    directories = [
        'ml-models',
        'ml-logs',
        'ml-backups'
    ]
    
    for directory in directories:
        try:
            Path(directory).mkdir(exist_ok=True)
            print(f"📁 {directory}/ creado")
        except Exception as e:
            print(f"❌ Error creando {directory}: {e}")
            return False
    
    print("\n✅ Estructura de directorios creada")
    return True

def test_database_connection():
    """Probar conexión a MySQL"""
    print_step(3, "PROBANDO CONEXIÓN A BASE DE DATOS")
    
    try:
        import mysql.connector
        
        config = {
            'host': 'db',
            'user': 'root',
            'password': 'root',
            'database': 'interbank',
            'port': 3306
        }
        
        print("🔌 Conectando a MySQL...")
        conn = mysql.connector.connect(**config)
        cursor = conn.cursor()
        
        # Verificar tablas necesarias
        tables_needed = ['usuarios', 'tarjetas', 'transacciones', 'alertas', 'config_seguridad_tarjeta']
        
        for table in tables_needed:
            cursor.execute(f"SELECT COUNT(*) FROM {table}")
            count = cursor.fetchone()[0]
            print(f"  ✅ Tabla '{table}': {count} registros")
        
        conn.close()
        print("\n✅ Conexión a base de datos exitosa")
        return True
        
    except Exception as e:
        print(f"\n❌ Error de conexión: {e}")
        print("💡 Asegúrate de que:")
        print("   - XAMPP esté corriendo")
        print("   - MySQL esté activo")
        print("   - La base de datos 'interbank' exista")
        print("   - Las tablas estén creadas")
        return False

def train_ml_models():
    """Entrenar modelos de Machine Learning"""
    print_step(4, "ENTRENANDO MODELOS DE MACHINE LEARNING")
    
    try:
        print("🧠 Importando sistema ML...")
        from smartshield_ml import SmartShieldML
        
        db_config = {
            'host': 'db',
            'user': 'root',
            'password': 'root',
            'database': 'interbank',
            'port': 3306
        }
        
        print("🤖 Inicializando SmartShield ML...")
        ml_system = SmartShieldML(db_config)
        
        print("📊 Extrayendo datos para entrenamiento...")
        df = ml_system.extract_ml_features()
        
        if len(df) < 5:
            print(f"⚠️ Pocos datos para entrenar: {len(df)} registros")
            print("💡 Realiza algunas transferencias desde el sistema web")
            print("   para generar datos de entrenamiento")
            return False
        
        print(f"📈 Entrenando modelos con {len(df)} registros...")
        success = ml_system.train_ml_models(df)
        
        if success:
            print("💾 Guardando modelos entrenados...")
            ml_system.save_ml_models()
            print("✅ Modelos ML entrenados y guardados exitosamente")
            return True
        else:
            print("❌ Error durante el entrenamiento")
            return False
        
    except Exception as e:
        print(f"❌ Error entrenando modelos: {e}")
        return False

def create_startup_scripts():
    """Crear scripts de inicio para el sistema ML"""
    print_step(5, "CREANDO SCRIPTS DE INICIO")
    
    # Script para Windows
    bat_content = """@echo off
echo ========================================
echo   SmartShield ML System - Starting
echo ========================================
cd /d "%~dp0"
echo Iniciando servidor ML en puerto 5001...
python ml_api_server.py
pause
"""
    
    try:
        with open('start_ml_server.bat', 'w') as f:
            f.write(bat_content)
        print("📝 start_ml_server.bat creado")
    except Exception as e:
        print(f"❌ Error creando script Windows: {e}")
    
    # Script para Linux/Mac
    sh_content = """#!/bin/bash
echo "========================================"
echo "  SmartShield ML System - Starting"
echo "========================================"
cd "$(dirname "$0")"
echo "Iniciando servidor ML en puerto 5001..."
python3 ml_api_server.py
"""
    
    try:
        with open('start_ml_server.sh', 'w') as f:
            f.write(sh_content)
        os.chmod('start_ml_server.sh', 0o755)
        print("📝 start_ml_server.sh creado")
    except Exception as e:
        print(f"❌ Error creando script Linux/Mac: {e}")
    
    # Script de entrenamiento
    train_content = """@echo off
echo Reentrenando modelos ML...
python smartshield_ml.py
pause
"""
    
    try:
        with open('retrain_models.bat', 'w') as f:
            f.write(train_content)
        print("📝 retrain_models.bat creado")
    except Exception as e:
        print(f"❌ Error creando script de entrenamiento: {e}")
    
    print("\n✅ Scripts de inicio creados")
    return True

def test_ml_api():
    """Probar la API ML"""
    print_step(6, "PROBANDO API DE MACHINE LEARNING")
    
    try:
        print("🚀 Iniciando servidor ML de prueba...")
        
        # Importar y probar componentes
        from smartshield_ml import SmartShieldML
        
        db_config = {
            'host': 'db',
            'user': 'root',
            'password': 'root',
            'database': 'interbank',
            'port': 3306
        }
        
        ml_system = SmartShieldML(db_config)
        
        # Probar carga de modelos
        if ml_system.load_ml_models():
            print("✅ Modelos ML cargados correctamente")
        else:
            print("⚠️ Modelos no encontrados (normal en primera instalación)")
        
        # Probar predicción de ejemplo
        test_data = {
            'id_usuario': 1,
            'monto': 100.0,
            'tipo_transaccion': 'transferencia',
            'moneda': 'PEN'
        }
        
        result = ml_system.predict_fraud_ml(test_data)
        print(f"✅ Predicción ML de prueba: Score {result['ml_risk_score']}")
        
        print("\n✅ Sistema ML funcionando correctamente")
        return True
        
    except Exception as e:
        print(f"❌ Error probando ML: {e}")
        return False

def show_final_instructions():
    """Mostrar instrucciones finales"""
    print_step(7, "INSTALACIÓN COMPLETADA")
    
    print("🎉 ¡Sistema ML SmartShield instalado exitosamente!")
    print("\n📋 INSTRUCCIONES DE USO:")
    print("\n1️⃣ INICIAR SERVIDOR ML:")
    print("   Windows: Doble click en 'start_ml_server.bat'")
    print("   Linux/Mac: ./start_ml_server.sh")
    print("   Manual: python ml_api_server.py")
    
    print("\n2️⃣ VERIFICAR FUNCIONAMIENTO:")
    print("   - El servidor ML correrá en: http://localhost:5001")
    print("   - Abre tu sistema web: http://localhost/frontend/index.php")
    print("   - Verás el widget ML en el dashboard")
    
    print("\n3️⃣ GENERAR DATOS DE ENTRENAMIENTO:")
    print("   - Realiza varias transferencias desde el sistema web")
    print("   - Esto generará datos para mejorar el ML")
    
    print("\n4️⃣ REENTRENAR MODELOS:")
    print("   - Ejecuta: retrain_models.bat")
    print("   - O desde la API: POST http://localhost:5001/ml/train")
    
    print("\n🔧 ENDPOINTS ML DISPONIBLES:")
    print("   GET  /ml/health - Estado del sistema")
    print("   POST /ml/predict - Predicción de fraude")
    print("   POST /ml/analyze-user - Análisis de usuario")
    print("   POST /ml/train - Reentrenar modelos")
    print("   GET  /ml/model-info - Info de modelos")
    
    print("\n💡 TIPS:")
    print("   - El widget ML aparece automáticamente en el dashboard")
    print("   - Las alertas ML se generan automáticamente en transferencias")
    print("   - El sistema aprende continuamente de los datos")
    
    print("\n🆘 SOPORTE:")
    print("   - Logs en: ml-logs/")
    print("   - Modelos en: ml-models/")
    print("   - Backups en: ml-backups/")

def main():
    """Función principal de instalación"""
    print("🤖 INSTALADOR SMARTSHIELD MACHINE LEARNING")
    print("=" * 60)
    print("Este script instalará y configurará el sistema completo de ML")
    print("para detección de fraudes y análisis de comportamiento.")
    print("=" * 60)
    
    # Verificar Python
    if sys.version_info < (3, 8):
        print("❌ Se requiere Python 3.8 o superior")
        return False
    
    steps = [
        # ("Instalar dependencias Python", install_python_packages),
        ("Crear directorios", create_ml_directories),
        ("Probar conexión BD", test_database_connection),
        ("Entrenar modelos ML", train_ml_models),
        ("Crear scripts de inicio", create_startup_scripts),
        ("Probar sistema ML", test_ml_api)
    ]
    
    success_count = 0
    
    for i, (step_name, step_func) in enumerate(steps, 1):
        if step_func():
            success_count += 1
        else:
            print(f"\n❌ FALLÓ: {step_name}")
            print("💡 Revisa los errores anteriores y ejecuta nuevamente")
            break
    
    print(f"\n📊 RESULTADO: {success_count}/{len(steps)} pasos completados")
    
    if success_count == len(steps):
        show_final_instructions()
        return True
    else:
        print("\n❌ La instalación tuvo problemas.")
        print("💡 Revisa los errores y ejecuta: python setup_ml.py")
        return False

if __name__ == "__main__":
    try:
        success = main()
        input("\nPresiona Enter para salir...")
        sys.exit(0 if success else 1)
    except KeyboardInterrupt:
        print("\n\n⚠️ Instalación cancelada por el usuario")
        sys.exit(1)
    except Exception as e:
        print(f"\n❌ Error inesperado: {e}")
        sys.exit(1)