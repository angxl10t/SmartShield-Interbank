/**
 * Modal de Machine Learning para SmartShield
 * Se activa desde el botón ML en el header
 */

class MLModal {
    constructor() {
        this.apiUrl = '../backend/api/ml_dashboard.php';
        this.isLoading = false;
        this.mlData = null;
        
        this.init();
    }
    
    init() {
        // Añadir event listener al botón ML del header
        const btnML = document.getElementById('btnML');
        if (btnML) {
            btnML.addEventListener('click', () => this.openMLModal());
        }
    }
    
    async openMLModal() {
        // Crear modal
        this.createModal();
        
        // Mostrar modal
        const modal = document.getElementById('mlModal');
        if (modal) {
            setTimeout(() => modal.classList.add('show'), 10);
        }
        
        // Cargar datos ML
        await this.loadMLData();
    }
    
    createModal() {
        // Remover modal existente si existe
        const existingModal = document.getElementById('mlModal');
        if (existingModal) {
            existingModal.remove();
        }
        
        // Crear nuevo modal
        const modal = document.createElement('div');
        modal.id = 'mlModal';
        modal.className = 'ml-modal-overlay';
        modal.innerHTML = `
            <div class="ml-modal">
                <div class="ml-modal-header">
                    <div class="ml-modal-title">
                        <span class="ml-modal-title-icon">🤖</span>
                        Machine Learning Analytics
                    </div>
                    <button class="ml-modal-close" onclick="mlModal.closeModal()">✕</button>
                </div>
                <div class="ml-modal-body" id="mlModalBody">
                    <div class="ml-loading-section">
                        <div class="ml-loading-spinner"></div>
                        <p>Cargando análisis de Machine Learning...</p>
                    </div>
                </div>
            </div>
        `;
        
        document.body.appendChild(modal);
        
        // Event listeners para cerrar
        modal.addEventListener('click', (e) => {
            if (e.target === modal) {
                this.closeModal();
            }
        });
        
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && modal.classList.contains('show')) {
                this.closeModal();
            }
        });
    }
    
    async loadMLData() {
        if (this.isLoading) return;
        
        this.isLoading = true;
        
        try {
            const response = await fetch(this.apiUrl);
            const result = await response.json();
            
            if (result.success) {
                this.mlData = result.ml_analysis;
                this.renderMLContent();
                this.updateHeaderButton(true);
            } else {
                this.renderMLError(result.error || 'Sistema ML no disponible');
                this.updateHeaderButton(false);
            }
        } catch (error) {
            console.error('Error cargando ML:', error);
            this.renderMLError('Error de conexión con el sistema ML');
            this.updateHeaderButton(false);
        } finally {
            this.isLoading = false;
        }
    }
    
    updateHeaderButton(isActive) {
        const btnML = document.getElementById('btnML');
        if (btnML) {
            if (isActive) {
                btnML.classList.remove('ml-inactive');
            } else {
                btnML.classList.add('ml-inactive');
            }
        }
    }
    
    renderMLContent() {
        const body = document.getElementById('mlModalBody');
        if (!body || !this.mlData) return;
        
        const stats = this.mlData.ml_statistics || {};
        const alerts = this.mlData.ml_alerts || {};
        
        body.innerHTML = `
            <div class="ml-content-grid">
                <!-- Score Principal ML -->
                <div class="ml-main-score">
                    <div class="ml-score-circle-large" style="--ml-score: ${this.mlData.ml_score || 0}; --ml-color: ${this.mlData.ml_color || '#4f46e5'}">
                        <div class="ml-score-content-large">
                            <div class="ml-score-value-large">${this.mlData.ml_score || 0}</div>
                            <div class="ml-score-label-large">ML Score</div>
                        </div>
                    </div>
                    <div class="ml-classification-large">${this.mlData.ml_classification || 'Sin clasificación'}</div>
                    <div class="ml-status-large ${this.mlData.ml_available ? 'ml-status-active' : 'ml-status-inactive'}">
                        ${this.mlData.ml_available ? '🟢 Sistema Activo' : '🔴 Sistema Inactivo'}
                    </div>
                </div>
                
                <!-- Estadísticas ML -->
                <div class="ml-stats-section">
                    <div class="ml-stats-title">
                        📊 Estadísticas de Análisis
                    </div>
                    <div class="ml-stats-grid">
                        <div class="ml-stat-item">
                            <span class="ml-stat-label">Transacciones Analizadas</span>
                            <span class="ml-stat-value">${stats.transacciones_analizadas || 0}</span>
                        </div>
                        <div class="ml-stat-item">
                            <span class="ml-stat-label">Alertas Generadas</span>
                            <span class="ml-stat-value">${alerts.total_ml || 0}</span>
                        </div>
                        <div class="ml-stat-item">
                            <span class="ml-stat-label">Días Activos</span>
                            <span class="ml-stat-value">${stats.dias_activos_ml || 0}</span>
                        </div>
                        <div class="ml-stat-item">
                            <span class="ml-stat-label">Frecuencia Diaria</span>
                            <span class="ml-stat-value">${stats.frecuencia_diaria || 0}</span>
                        </div>
                        <div class="ml-stat-item">
                            <span class="ml-stat-label">Monto Promedio</span>
                            <span class="ml-stat-value">S/ ${stats.monto_promedio_ml || 0}</span>
                        </div>
                        <div class="ml-stat-item">
                            <span class="ml-stat-label">Transacciones Nocturnas</span>
                            <span class="ml-stat-value">${stats.transacciones_nocturnas || 0}</span>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Patrones Detectados -->
            <div class="ml-info-section">
                <div class="ml-info-title">
                    🔍 Patrones de Comportamiento Detectados
                </div>
                <div class="ml-info-content">
                    <ul class="ml-info-list">
                        ${(this.mlData.ml_patterns || ['No se han detectado patrones inusuales']).map(pattern => `
                            <li class="ml-info-item">
                                <span class="ml-info-icon">🎯</span>
                                <span>${pattern}</span>
                            </li>
                        `).join('')}
                    </ul>
                </div>
            </div>
            
            <!-- Recomendaciones ML -->
            <div class="ml-info-section">
                <div class="ml-info-title">
                    💡 Recomendaciones Personalizadas
                </div>
                <div class="ml-info-content">
                    <ul class="ml-info-list">
                        ${(this.mlData.ml_recommendations || ['Tu comportamiento financiero es estable']).map(rec => `
                            <li class="ml-info-item">
                                <span class="ml-info-icon">💡</span>
                                <span>${rec}</span>
                            </li>
                        `).join('')}
                    </ul>
                </div>
            </div>
        `;
    }
    
    renderMLError(message) {
        const body = document.getElementById('mlModalBody');
        if (!body) return;
        
        body.innerHTML = `
            <div class="ml-offline-section">
                <div class="ml-offline-icon">⚠️</div>
                <div class="ml-offline-title">Sistema ML No Disponible</div>
                <div class="ml-offline-text">${message}</div>
                <button class="ml-retry-button" onclick="mlModal.loadMLData()">
                    🔄 Reintentar Conexión
                </button>
            </div>
            
            <!-- Estado del Sistema -->
            <div class="ml-info-section">
                <div class="ml-info-title">
                    🔧 Estado del Sistema
                </div>
                <div class="ml-info-content">
                    <div class="ml-status-message">
                        El sistema de Machine Learning está temporalmente no disponible. 
                        Por favor, inténtalo de nuevo más tarde o contacta al administrador del sistema.
                    </div>
                </div>
            </div>
        `;
    }
    
    closeModal() {
        const modal = document.getElementById('mlModal');
        if (modal) {
            modal.classList.remove('show');
            setTimeout(() => modal.remove(), 300);
        }
    }
}

// Inicializar cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', () => {
    window.mlModal = new MLModal();
});