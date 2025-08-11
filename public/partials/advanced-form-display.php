<!-- Save as: public/partials/advanced-form-display.php -->
<div id="voiAdvancedCalculator" class="voi-advanced-calculator">
    <!-- Header -->
    <div class="calculator-header">
        <h1>Advanced ROI Calculator</h1>
        <p>Create a custom analysis using your own parameters and assumptions</p>
        <div class="calculator-subtitle">
            Build a detailed ROI analysis tailored to your organization's specific costs, processes, and operational characteristics.
        </div>
    </div>

    <!-- Progress Indicator -->
    <div class="step-progress" id="voiAdvancedProgress">
        <!-- Progress indicators will be populated by JavaScript -->
    </div>

    <!-- Error Message Container -->
    <div class="error-message" id="voiAdvancedError" style="display: none;">
        <!-- Error messages will be displayed here -->
    </div>

    <!-- Calculator Steps Container -->
    <div id="voiAdvancedSteps" class="calculator-steps">
        <!-- Step content will be populated by JavaScript -->
        <div class="loading-initial">
            <div class="loading-spinner">
                <div class="spinner"></div>
                <p>Loading Advanced Calculator...</p>
            </div>
        </div>
    </div>
</div>

<style>
.voi-advanced-calculator {
    font-family: "Neue Montreal", Sans-serif, Arial, sans-serif;
    max-width: 1000px;
    margin: 40px auto;
    padding: 30px;
    background-color: #ffffff;
    border-radius: 12px;
    box-shadow: 0 10px 25px rgba(0,0,0,0.1);
}

.calculator-header {
    text-align: center;
    margin-bottom: 40px;
    background: linear-gradient(135deg, #1e3a8a 0%, #3b82f6 100%);
    color: white;
    padding: 30px;
    border-radius: 8px;
}

.calculator-header h1 {
    font-size: 28px;
    margin: 0 0 10px 0;
    font-weight: bold;
}

.calculator-header p {
    font-size: 16px;
    margin: 0 0 10px 0;
    opacity: 0.9;
}

.calculator-subtitle {
    font-size: 14px;
    opacity: 0.8;
    margin: 0;
}

.step-progress {
    display: flex;
    justify-content: space-between;
    margin-bottom: 40px;
    padding: 0 20px;
}

.step-indicator {
    flex: 1;
    text-align: center;
    position: relative;
}

.step-indicator:not(:last-child)::after {
    content: '';
    position: absolute;
    top: 15px;
    right: -50%;
    width: 100%;
    height: 2px;
    background: #e5e7eb;
    z-index: 1;
}

.step-indicator.completed::after {
    background: #10b981;
}

.step-number {
    width: 30px;
    height: 30px;
    border-radius: 50%;
    background: #e5e7eb;
    color: #6b7280;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
    margin-bottom: 8px;
    position: relative;
    z-index: 2;
    font-size: 14px;
}

.step-indicator.active .step-number {
    background: #3b82f6;
    color: white;
}

.step-indicator.completed .step-number {
    background: #10b981;
    color: white;
}

.step-label {
    font-size: 12px;
    color: #6b7280;
    font-weight: 500;
}

.step-indicator.active .step-label {
    color: #3b82f6;
    font-weight: 600;
}

.calculator-step {
    background: #f8fafc;
    border-radius: 8px;
    padding: 30px;
}

.step-header {
    text-align: center;
    margin-bottom: 30px;
}

.step-header h2 {
    font-size: 24px;
    color: #1e3a8a;
    margin: 0 0 10px 0;
    font-weight: bold;
}

.step-header p {
    color: #6b7280;
    margin: 0;
    font-size: 16px;
}

.form-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 25px;
    margin-bottom: 30px;
}

.form-group {
    display: flex;
    flex-direction: column;
}

.form-group label {
    font-weight: 600;
    margin-bottom: 8px;
    color: #374151;
    font-size: 14px;
}

.form-group input {
    padding: 12px 16px;
    border: 2px solid #e5e7eb;
    border-radius: 6px;
    font-size: 16px;
    transition: all 0.2s ease;
    background: white;
}

.form-group input:focus {
    outline: none;
    border-color: #3b82f6;
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

.form-group input.error {
    border-color: #ef4444;
    box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.1);
}

.field-help {
    font-size: 13px;
    color: #6b7280;
    margin-top: 5px;
    line-height: 1.4;
}

.step-navigation {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-top: 40px;
    padding-top: 20px;
    border-top: 1px solid #e5e7eb;
}

.step-counter {
    font-size: 14px;
    color: #6b7280;
    font-weight: 500;
}

.nav-button {
    padding: 12px 24px;
    border-radius: 6px;
    border: none;
    font-size: 16px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s ease;
    min-width: 120px;
}

.nav-button.primary {
    background: #3b82f6;
    color: white;
}

.nav-button.primary:hover:not(:disabled) {
    background: #2563eb;
    transform: translateY(-1px);
}

.nav-button.secondary {
    background: #f3f4f6;
    color: #374151;
}

.nav-button.secondary:hover:not(:disabled) {
    background: #e5e7eb;
}

.nav-button:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

.preview-panel {
    background: #ecfdf5;
    border: 2px solid #a7f3d0;
    border-radius: 8px;
    padding: 20px;
    margin-top: 20px;
}

.preview-panel h3 {
    color: #065f46;
    margin: 0 0 15px 0;
    font-size: 18px;
    font-weight: bold;
}

.preview-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 15px;
}

.preview-item {
    background: white;
    padding: 15px;
    border-radius: 6px;
    text-align: center;
    border: 1px solid #d1fae5;
}

.preview-value {
    font-size: 24px;
    font-weight: bold;
    color: #065f46;
    margin-bottom: 5px;
}

.preview-label {
    font-size: 12px;
    color: #6b7280;
    font-weight: 500;
}

.final-results {
    background: linear-gradient(135deg, #1e3a8a 0%, #3b82f6 100%);
    color: white;
    padding: 40px;
    border-radius: 12px;
    text-align: center;
}

.final-results h2 {
    font-size: 28px;
    margin: 0 0 20px 0;
    font-weight: bold;
}

.results-summary {
    margin: 30px 0;
}

.result-highlight {
    margin-bottom: 30px;
}

.result-highlight h3 {
    font-size: 20px;
    margin: 0 0 20px 0;
}

.results-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    margin: 20px 0;
}

.result-card {
    background: rgba(255, 255, 255, 0.1);
    padding: 20px;
    border-radius: 8px;
    border: 1px solid rgba(255, 255, 255, 0.2);
}

.result-value {
    font-size: 28px;
    font-weight: bold;
    margin-bottom: 8px;
}

.result-label {
    font-size: 14px;
    opacity: 0.9;
    font-weight: 500;
}

.breakdown-summary {
    background: rgba(255, 255, 255, 0.1);
    padding: 20px;
    border-radius: 8px;
    margin-top: 20px;
}

.breakdown-summary h3 {
    margin: 0 0 15px 0;
    font-size: 18px;
}

.breakdown-item {
    display: flex;
    justify-content: space-between;
    padding: 8px 0;
    border-bottom: 1px solid rgba(255, 255, 255, 0.2);
}

.breakdown-item:last-child {
    border-bottom: none;
}

.final-actions {
    margin-top: 30px;
}

.loading-initial, .loading-spinner {
    text-align: center;
    padding: 40px 20px;
}

.spinner {
    border: 3px solid rgba(255, 255, 255, 0.3);
    border-top: 3px solid #3b82f6;
    border-radius: 50%;
    width: 40px;
    height: 40px;
    animation: spin 1s linear infinite;
    margin: 0 auto 15px auto;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

.loading-overlay {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.7);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 9999;
}

.loading-overlay .loading-spinner {
    background: white;
    padding: 40px;
    border-radius: 8px;
    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.3);
}

.loading-overlay .spinner {
    border-top-color: #3b82f6;
    border-left-color: #3b82f6;
}

.error-message {
    background: #fef2f2;
    border: 1px solid #fecaca;
    color: #dc2626;
    padding: 15px;
    border-radius: 6px;
    margin: 15px 0;
    font-weight: 500;
}

@media (max-width: 768px) {
    .voi-advanced-calculator {
        padding: 20px;
        margin: 20px;
    }
    
    .form-grid {
        grid-template-columns: 1fr;
    }
    
    .step-progress {
        flex-wrap: wrap;
        gap: 15px 10px;
    }
    
    .step-indicator {
        flex: none;
        width: calc(50% - 5px);
    }
    
    .step-indicator::after {
        display: none;
    }
    
    .calculator-header {
        padding: 20px;
    }
    
    .calculator-header h1 {
        font-size: 24px;
    }
    
    .results-grid {
        grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
        gap: 15px;
    }
    
    .result-value {
        font-size: 22px;
    }
}

@media (max-width: 480px) {
    .step-indicator {
        width: 100%;
    }
    
    .step-navigation {
        flex-direction: column;
        gap: 15px;
    }
    
    .nav-button {
        width: 100%;
    }
}</style>