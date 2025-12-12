/**
 * Widget de Machine Learning para Dashboard SmartShield
 * Sistema independiente de análisis ML
 */

class MLDashboardWidget {
    constructor(containerId) {
        this.container = document.getElementById(containerId);
        this.apiUrl = '../backend/api/ml_dashboard.php';
        this.isLoading = false;
        this.mlData = null;
        this.refreshInterval = null;
        
        this.init();
    }
    
    init() {
        if (!this.container) {
            console.error('Contenedor ML Dashboard no encontrado');
            return;
        }
        
        this.render();
        this.loadMLData();
        
        // Actualizar cada 3 minutos
        this.refreshInterval = setInterval(() => this.loadMLData(), 3 * 60 * 1000);
    }
    
    async loadMLData() {
        if (this.isLoading) return;
        
        this.isLoading = true;
        this.showMLLoading();
        
        try {
            const response = await fetch(this.apiUrl);
            const result = await response.json();
            
            if (result.success) {
                this.mlData = result.ml_analysis;
                this.renderMLData();
            } else {
                this.showMLError(result.error || 'Error en análisis ML');
            }
        } catch (error) {
            console.error('Error ML Dashboard:', error);
            this.showMLError('Sistema ML no disponible');
        } finally {
            this.isLoading = false;
        }
    }
    
    render() {
        this.container.innerHTML = `
            <div class="ml-dashboard-widget">
                <div class="ml-dashboard-header">
                    <div class="ml-dashboard-title">
                        <span class="ml-dashboard-icon">🤖</span>
                        Machine Learning
                    </div>
                    <div class="ml-dashboard-subtitle">Análisis Inteligente de Comportamiento</div>
                </div>
                <div class="ml-dashboard-content" id="mlDashboardContent">
                    <div class="ml-dashboard-loading">
                        <div class="ml-loading-spinner"></div>
                        <span>Analizando con IA...</span>
                    </div>
                </div>
            </div>
        `;
    }
    
    showMLLoading() {
        const content = document.getElementById('mlDashboardContent');
        if (content) {
            content.innerHTML = `
                <div class="ml-dashboard-loading">
                    <div class="ml-loading-spinner"></div>
                    <span>Procesando análisis ML...</span>
                </div>
            `;
        }
    }
    
    showMLError(message) {
        const content = document.getElementById('mlDashboardContent');
        if (content) {
            content.innerHTML = `
                <div class="ml-dashboard-main">
                    <!-- Círculo ML Inactivo pero Clickeable -->
                    <div class="ml-circle-container" onclick="mlDashboard.showMLOfflineInfo()">
                        <div class="ml-main-circle ml-circle-offline">
                            <div class="ml-circle-content">
                                <div class="ml-circle-icon">🤖</div>
                                <div class="ml-circle-score">--</div>
                                <div class="ml-circle-label">ML Score</div>
                                <div class="ml-circle-status">Inactivo</div>
                            </div>
                        </div>
                        <div class="ml-circle-info">
                            <div class="ml-circle-classification">Sistema ML No Disponible</div>
                            <div class="ml-circle-hint">👆 Click para más información</div>
                        </div>
                    </div>
                    
                    <div class="ml-error-section">
                        <div class="ml-error-message">
                            <span class="ml-error-icon">⚠️</span>
                            <span>${message}</span>
                        </div>
                        <button class="ml-retry-btn" onclick="mlDashboard.loadMLData()">
                            🔄 Reintentar Conexión ML
                        </button>
                    </div>
                    
                    <div class="ml-offline-info">
                        <div class="ml-offline-item">
                            <span class="ml-offline-icon">📋</span>
                            <span>El sistema ML está desactivado</span>
                        </div>
                        <div class="ml-offline-item">
                            <span class="ml-offline-icon">🔧</span>
                            <span>Inicia el servidor ML para activar</span>
                        </div>
                    </div>
                </div>
            `;
        }
    }
    
    renderMLData() {
        if (!this.mlData) return;
        
        const content = document.getElementById('mlDashboardContent');
        if (!content) return;
        
        const stats = this.mlData.ml_statistics;
        const alerts = this.mlData.ml_alerts;
        const metrics = this.mlData.ml_metrics;
        
        content.innerHTML = `
            <div class="ml-dashboard-main">
                <!-- Círculo ML Clickeable -->
                <div class="ml-circle-container" onclick="mlDashboard.showMLDetails()">
                    <div class="ml-main-circle" style="--ml-score: ${this.mlData.ml_score}; --ml-color: ${this.mlData.ml_color}">
                        <div class="ml-circle-content">
                            <div class="ml-circle-icon">🤖</div>
                            <div class="ml-circle-score">${this.mlData.ml_score}</div>
                            <div class="ml-circle-label">ML Score</div>
                            <div class="ml-circle-status">${this.mlData.ml_available ? 'Activo' : 'Inactivo'}</div>
                        </div>
                        <div class="ml-circle-pulse"></div>
                    </div>
                    <div class="ml-circle-info">
                        <div class="ml-circle-classification">${this.mlData.ml_classification}</div>
                        <div class="ml-circle-hint">👆 Click para ver análisis completo</div>
                    </div>
                </div>
                
                <!-- Estadísticas Rápidas ML -->
                <div class="ml-quick-stats">
                    <div class="ml-quick-stat">
                        <span class="ml-quick-number">${stats.transacciones_analizadas}</span>
                        <span class="ml-quick-text">Transacciones</span>
                    </div>
                    <div class="ml-quick-stat">
                        <span class="ml-quick-number">${alerts.total_ml}</span>
                        <span class="ml-quick-text">Alertas ML</span>
                    </div>
                    <div class="ml-quick-stat">
                        <span class="ml-quick-number">${metrics.ml_feature_count}</span>
                        <span class="ml-quick-text">Features</span>
                    </div>
                </div>
                
                <!-- Resumen ML -->
                <div class="ml-summary">
                    <div class="ml-summary-item">
                        <span class="ml-summary-icon">🎯</span>
                        <span class="ml-summary-text">
                            ${this.mlData.ml_patterns.length > 0 ? this.mlData.ml_patterns[0] : 'Analizando patrones...'}
                        </span>
                    </div>
                    <div class="ml-summary-item">
                        <span class="ml-summary-icon">💡</span>
                        <span class="ml-summary-text">
                            ${this.mlData.ml_recommendations.length > 0 ? this.mlData.ml_recommendations[0] : 'Generando recomendaciones...'}
                        </span>
                    </div>
                </div>
                
                <!-- Botón de Actualización -->
                <div class="ml-refresh-section">
                    <button class="ml-refresh-btn" onclick="mlDashboard.refreshMLAnalysis()">
                        🔄 Actualizar Análisis ML
                    </button>
                </div>
            </div>
        `;
    }
    
    showMLDetails() {
        if (!this.mlData) return;
        
        // Crear modal con análisis ML completo
        const modal = document.createElement('div');
        modal.className = 'ml-modal-overlay';
        modal.innerHTML = `
            <div class="ml-modal">
                <div class="ml-modal-header">
                    <h3>🤖 Análisis Completo de Machine Learning</h3>
                    <button class="ml-modal-close" onclick="this.closest('.ml-modal-overlay').remove()">✕</button>
                </div>
                <div class="ml-modal-body">
                    
                    <!-- Información del Sistema ML -->
                    <div class="ml-detail-section">
                        <h4>🔧 Estado del Sistema ML</h4>
                        <div class="ml-system-info">
                            <div class="ml-info-item">
                                <span class="ml-info-label">Estado:</span>
                                <span class="ml-info-value ${this.mlData.ml_available ? 'ml-success' : 'ml-error'}">
                                    ${this.mlData.ml_available ? '✅ Operativo' : '❌ No disponible'}
                                </span>
                            </div>
                            <div class="ml-info-item">
                                <span class="ml-info-label">Modelos entrenados:</span>
                                <span class="ml-info-value">
                                    ${this.mlData.ml_metrics.ml_models_trained ? '✅ Sí' : '❌ No'}
                                </span>
                            </div>
                            <div class="ml-info-item">
                                <span class="ml-info-label">Características analizadas:</span>
                                <span class="ml-info-value">${this.mlData.ml_metrics.ml_feature_count}</span>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Estadísticas Detalladas -->
                    <div class="ml-detail-section">
                        <h4>📊 Estadísticas ML Detalladas</h4>
                        <div class="ml-stats-detailed">
                            <div class="ml-stat-detailed">
                                <span class="ml-stat-label">Transacciones analizadas:</span>
                                <span class="ml-stat-value">${this.mlData.ml_statistics.transacciones_analizadas}</span>
                            </div>
                            <div class="ml-stat-detailed">
                                <span class="ml-stat-label">Monto promedio:</span>
                                <span class="ml-stat-value">S/ ${this.mlData.ml_statistics.monto_promedio_ml}</span>
                            </div>
                            <div class="ml-stat-detailed">
                                <span class="ml-stat-label">Monto máximo:</span>
                                <span class="ml-stat-value">S/ ${this.mlData.ml_statistics.monto_maximo_ml}</span>
                            </div>
                            <div class="ml-stat-detailed">
                                <span class="ml-stat-label">Días activos:</span>
                                <span class="ml-stat-value">${this.mlData.ml_statistics.dias_activos_ml}</span>
                            </div>
                            <div class="ml-stat-detailed">
                                <span class="ml-stat-label">Transacciones nocturnas:</span>
                                <span class="ml-stat-value">${this.mlData.ml_statistics.transacciones_nocturnas}</span>
                            </div>
                            <div class="ml-stat-detailed">
                                <span class="ml-stat-label">Frecuencia diaria:</span>
                                <span class="ml-stat-value">${this.mlData.ml_statistics.frecuencia_diaria}</span>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Alertas ML -->
                    <div class="ml-detail-section">
                        <h4>🚨 Análisis de Alertas ML</h4>
                        <div class="ml-alerts-analysis">
                            <div class="ml-alert-stat">
                                <span class="ml-alert-label">Total alertas ML:</span>
                                <span class="ml-alert-value">${this.mlData.ml_alerts.total_ml}</span>
                            </div>
                            <div class="ml-alert-stat">
                                <span class="ml-alert-label">Alertas críticas ML:</span>
                                <span class="ml-alert-value">${this.mlData.ml_alerts.criticas_ml}</span>
                            </div>
                            <div class="ml-alert-stat">
                                <span class="ml-alert-label">Riesgo promedio ML:</span>
                                <span class="ml-alert-value">${this.mlData.ml_alerts.riesgo_promedio_ml}%</span>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Patrones ML Completos -->
                    <div class="ml-detail-section">
                        <h4>🔍 Patrones ML Detectados</h4>
                        <div class="ml-patterns-complete">
                            ${this.mlData.ml_patterns.map(pattern => `
                                <div class="ml-pattern-complete">${pattern}</div>
                            `).join('')}
                        </div>
                    </div>
                    
                    <!-- Recomendaciones ML Completas -->
                    <div class="ml-detail-section">
                        <h4>💡 Recomendaciones ML Completas</h4>
                        <div class="ml-recommendations-complete">
                            ${this.mlData.ml_recommendations.map(rec => `
                                <div class="ml-rec-complete">${rec}</div>
                            `).join('')}
                        </div>
                    </div>
                    
                    ${this.mlData.ml_model_info ? `
                        <div class="ml-detail-section">
                            <h4>🧠 Información del Modelo ML</h4>
                            <div class="ml-model-details">
                                <pre>${JSON.stringify(this.mlData.ml_model_info, null, 2)}</pre>
                            </div>
                        </div>
                    ` : ''}
                    
                </div>
                <div class="ml-modal-footer">
                    <button class="ml-modal-btn" onclick="this.closest('.ml-modal-overlay').remove()">
                        Cerrar
                    </button>
                </div>
            </div>
        `;
        
        document.body.appendChild(modal);
        
        // Cerrar con ESC
        const closeHandler = (e) => {
            if (e.key === 'Escape') {
                modal.remove();
                document.removeEventListener('keydown', closeHandler);
            }
        };
        document.addEventListener('keydown', closeHandler);
        
        // Cerrar clickeando fuera
        modal.addEventListener('click', (e) => {
            if (e.target === modal) {
                modal.remove();
            }
        });
    }
    
    showMLOfflineInfo() {
        // Crear modal con información de ML offline
        const modal = document.createElement('div');
        modal.className = 'ml-modal-overlay';
        modal.innerHTML = `
            <div class="ml-modal">
                <div class="ml-modal-header">
                    <h3>🤖 Sistema Machine Learning</h3>
                    <button class="ml-modal-close" onclick="this.closest('.ml-modal-overlay').remove()">✕</button>
                </div>
                <div class="ml-modal-body">
                    <div class="ml-offline-status">
                        <div class="ml-status-icon">⚠️</div>
                        <h4>Sistema ML No Disponible</h4>
                        <p>El sistema de Machine Learning no está activo en este momento.</p>
                    </div>
                    
                    <div class="ml-setup-instructions">
                        <h4>🚀 Para Activar el Sistema ML:</h4>
                        <ol>
                            <li>Navega a la carpeta <code>ml-system</code></li>
                            <li>Ejecuta: <code>python setup_ml.py</code></li>
                            <li>Inicia el servidor: <code>start_ml_server.bat</code></li>
                            <li>Recarga esta página</li>
                        </ol>
                    </div>
                    
                    <div class="ml-features-info">
                        <h4>🎯 Características del ML:</h4>
                        <ul>
                            <li>🔍 Detección de anomalías con Isolation Forest</li>
                            <li>🌳 Clasificación de fraude con Random Forest</li>
                            <li>📊 Análisis de 25+ características</li>
                            <li>🚨 Alertas inteligentes automáticas</li>
                            <li>📈 Análisis de comportamiento del usuario</li>
                        </ul>
                    </div>
                    
                    <div class="ml-benefits">
                        <h4>✨ Beneficios del ML:</h4>
                        <ul>
                            <li>Detección avanzada de fraudes</li>
                            <li>Análisis predictivo de riesgo</li>
                            <li>Recomendaciones personalizadas</li>
                            <li>Aprendizaje continuo de patrones</li>
                        </ul>
                    </div>
                </div>
                <div class="ml-modal-footer">
                    <button class="ml-modal-btn" onclick="mlDashboard.loadMLData()">
                        🔄 Reintentar Conexión
                    </button>
                    <button class="ml-modal-btn" onclick="this.closest('.ml-modal-overlay').remove()">
                        Cerrar
                    </button>
                </div>
            </div>
        `;
        
        document.body.appendChild(modal);
        
        // Cerrar con ESC
        const closeHandler = (e) => {
            if (e.key === 'Escape') {
                modal.remove();
                document.removeEventListener('keydown', closeHandler);
            }
        };
        document.addEventListener('keydown', closeHandler);
        
        // Cerrar clickeando fuera
        modal.addEventListener('click', (e) => {
            if (e.target === modal) {
                modal.remove();
            }
        });
    }

    refreshMLAnalysis() {
        this.loadMLData();
    }
    
    destroy() {
        if (this.refreshInterval) {
            clearInterval(this.refreshInterval);
        }
    }
}

// Inicializar widget ML cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', () => {
    // Solo inicializar si existe el contenedor
    if (document.getElementById('mlDashboardWidget')) {
        window.mlDashboard = new MLDashboardWidget('mlDashboardWidget');
    }
});