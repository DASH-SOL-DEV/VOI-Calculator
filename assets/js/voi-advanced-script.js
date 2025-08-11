/**
 * VOI Advanced Calculator - Complete Frontend
 * Save as: assets/js/voi-advanced-script.js
 */

(function($) {
    'use strict';

    class VOIAdvancedCalculator {
        constructor() {
            this.currentStep = 1;
            this.maxSteps = 6;
            this.stepData = {};
            this.calculatorSteps = {};
            this.isSubmitting = false;
            this.previewTimeout = null;
            
            console.log('VOI Advanced Calculator initialized');
            this.init();
        }

        async init() {
            try {
                await this.loadStepConfiguration();
                this.renderProgressIndicator();
                this.renderCurrentStep();
                this.bindEvents();
            } catch (error) {
                console.error('Failed to initialize calculator:', error);
                this.showError('Failed to load calculator. Please refresh the page.');
            }
        }

        async loadStepConfiguration() {
            try {
                const response = await $.ajax({
                    url: voi_advanced_ajax.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'voi_get_step_config',
                        nonce: voi_advanced_ajax.nonce
                    }
                });

                if (response.success) {
                    this.calculatorSteps = response.data.steps;
                    this.maxSteps = Object.keys(this.calculatorSteps).length;
                } else {
                    throw new Error('Failed to load step configuration');
                }
            } catch (error) {
                console.error('Error loading step configuration:', error);
                // Fallback to hardcoded steps if AJAX fails
                this.loadFallbackSteps();
            }
        }

        loadFallbackSteps() {
            // Fallback step configuration in case AJAX fails
            this.calculatorSteps = {
                1: {
                    title: 'Environment Setup',
                    description: 'Tell us about your current storage environment',
                    fields: {
                        'total_tb': { label: 'Total Storage Space (TB)', type: 'number', required: true, placeholder: 'e.g., 10000', min: 1 },
                        'cost_per_tb': { label: 'Cost per TB ($)', type: 'number', required: true, default: 500, placeholder: 'e.g., 500', min: 1 },
                        'total_vms': { label: 'Total Number of VMs', type: 'number', required: true, placeholder: 'e.g., 1000', min: 1 }
                    }
                },
                2: {
                    title: 'Employee Cost Structure',
                    description: 'Configure your employee cost parameters',
                    fields: {
                        'employee_yearly_cost': { label: 'Fully Burdened Annual Employee Cost ($)', type: 'number', required: true, default: 150000, min: 1 },
                        'work_hours_yearly': { label: 'Work Hours per Year', type: 'number', required: true, default: 1880, min: 1 }
                    }
                },
                3: {
                    title: 'Storage Optimization',
                    description: 'Set your expected storage efficiency improvements',
                    fields: {
                        'reuse_orphaned_percent': { label: 'Reuse of Orphaned Space (%)', type: 'number', required: true, default: 2.0, step: 0.1, min: 0, max: 50 },
                        'improved_processes_percent': { label: 'Process Improvement Savings (%)', type: 'number', required: true, default: 2.0, step: 0.1, min: 0, max: 50 },
                        'buying_accuracy_percent': { label: 'Purchasing Accuracy Improvement (%)', type: 'number', required: true, default: 1.0, step: 0.1, min: 0, max: 25 }
                    }
                },
                4: {
                    title: 'Personnel Time Savings',
                    description: 'How much time will VOI save your team each week?',
                    fields: {
                        'time_building_reports': { label: 'Time Building Reports (hours/week)', type: 'number', required: true, default: 4, step: 0.5, min: 0, max: 40 },
                        'time_planning': { label: 'Time on Capacity Planning (hours/week)', type: 'number', required: true, default: 2, step: 0.5, min: 0, max: 40 },
                        'modeling_trends': { label: 'Trend Analysis (hours/week)', type: 'number', required: true, default: 2, step: 0.5, min: 0, max: 40 },
                        'problem_resolution': { label: 'Problem Resolution (hours/week)', type: 'number', required: true, default: 4, step: 0.5, min: 0, max: 40 },
                        'capacity_reporting': { label: 'Data Collection (hours/week)', type: 'number', required: true, default: 4, step: 0.5, min: 0, max: 40 },
                        'service_improvement': { label: 'Service Optimization (hours/week)', type: 'number', required: true, default: 6, step: 0.5, min: 0, max: 40 },
                        'automation_tasks': { label: 'Manual Tasks (hours/week)', type: 'number', required: true, default: 4, step: 0.5, min: 0, max: 40 }
                    }
                },
                5: {
                    title: 'Business Impact & Costs',
                    description: 'Configure operational costs and VOI investment',
                    fields: {
                        'outage_avoidance_savings': { label: 'Annual Outage Avoidance Value ($)', type: 'number', required: true, default: 250000, min: 0 },
                        'voi_annual_cost': { label: 'VOI Annual License Cost ($)', type: 'number', required: true, default: 150000, min: 1 }
                    }
                },
                6: {
                    title: 'Contact Information',
                    description: 'Your details for the analysis report',
                    fields: {
                        'full_name': { label: 'Full Name', type: 'text', required: true, placeholder: 'e.g., John Doe', maxlength: 100 },
                        'email': { label: 'Email Address', type: 'email', required: true, placeholder: 'e.g., john@company.com', maxlength: 100 },
                        'company_name': { label: 'Company Name', type: 'text', required: true, placeholder: 'e.g., ACME Corporation', maxlength: 100 },
                        'company_url': { label: 'Company Website', type: 'url', required: false, placeholder: 'e.g., https://company.com', maxlength: 200 }
                    }
                }
            };
        }

        bindEvents() {
            // Handle form input changes for live preview
            $(document).on('input change', '.voi-advanced-form input', () => {
                this.schedulePreviewUpdate();
            });

            // Navigation events
            $(document).on('click', '.nav-previous', () => this.previousStep());
            $(document).on('click', '.nav-next', () => this.nextStep());
            $(document).on('click', '.nav-submit', () => this.submitCalculator());

            // Prevent form submission
            $(document).on('submit', '.voi-advanced-form', (e) => {
                e.preventDefault();
                return false;
            });

            // Start over functionality
            $(document).on('click', '.start-over-btn', () => this.startOver());
        }

        schedulePreviewUpdate() {
            clearTimeout(this.previewTimeout);
            this.previewTimeout = setTimeout(() => {
                this.updatePreview();
            }, 500);
        }

        renderProgressIndicator() {
            const progressContainer = $('#voiAdvancedProgress');
            if (!progressContainer.length) return;

            let html = '';
            for (let i = 1; i <= this.maxSteps; i++) {
                const stepConfig = this.calculatorSteps[i];
                if (!stepConfig) continue;

                const isActive = i === this.currentStep;
                const isCompleted = i < this.currentStep;
                
                let classes = 'step-indicator';
                if (isActive) classes += ' active';
                if (isCompleted) classes += ' completed';
                
                html += `
                    <div class="${classes}">
                        <div class="step-number">${i}</div>
                        <div class="step-label">${stepConfig.title}</div>
                    </div>
                `;
            }

            progressContainer.html(html);
        }

        renderCurrentStep() {
            const stepsContainer = $('#voiAdvancedSteps');
            const stepConfig = this.calculatorSteps[this.currentStep];
            
            if (!stepConfig) {
                console.error('Step configuration not found for step:', this.currentStep);
                return;
            }

            // Generate fields HTML
            let fieldsHtml = '';
            Object.entries(stepConfig.fields).forEach(([fieldName, fieldConfig]) => {
                const value = this.stepData[fieldName] || fieldConfig.default || '';
                const attributes = this.buildFieldAttributes(fieldConfig);
                
                fieldsHtml += `
                    <div class="form-group">
                        <label for="${fieldName}">${fieldConfig.label}</label>
                        <input 
                            type="${fieldConfig.type}" 
                            id="${fieldName}" 
                            name="${fieldName}" 
                            value="${value}"
                            placeholder="${fieldConfig.placeholder || ''}"
                            ${attributes}
                        />
                        ${fieldConfig.help ? `<div class="field-help">${fieldConfig.help}</div>` : ''}
                    </div>
                `;
            });

            const html = `
                <div class="calculator-step active">
                    <div class="step-header">
                        <h2>${stepConfig.title}</h2>
                        <p>${stepConfig.description}</p>
                    </div>
                    
                    <form class="voi-advanced-form">
                        <div class="form-grid">
                            ${fieldsHtml}
                        </div>
                    </form>
                    
                    ${this.currentStep > 2 ? '<div id="previewPanel"></div>' : ''}
                    
                    <div class="step-navigation">
                        <button 
                            class="nav-button secondary nav-previous" 
                            ${this.currentStep === 1 ? 'style="visibility: hidden;"' : ''}
                        >
                            Previous
                        </button>
                        
                        <div class="step-counter">
                            Step ${this.currentStep} of ${this.maxSteps}
                        </div>
                        
                        <button 
                            class="nav-button primary ${this.currentStep === this.maxSteps ? 'nav-submit' : 'nav-next'}"
                        >
                            ${this.currentStep === this.maxSteps ? 'Generate Analysis' : 'Next Step'}
                        </button>
                    </div>
                </div>
            `;

            stepsContainer.html(html);

            // Trigger preview update if we're past step 2
            if (this.currentStep > 2) {
                setTimeout(() => this.updatePreview(), 100);
            }
        }

        buildFieldAttributes(fieldConfig) {
            let attributes = [];
            
            if (fieldConfig.required) attributes.push('required');
            if (fieldConfig.step) attributes.push(`step="${fieldConfig.step}"`);
            if (fieldConfig.min !== undefined) attributes.push(`min="${fieldConfig.min}"`);
            if (fieldConfig.max !== undefined) attributes.push(`max="${fieldConfig.max}"`);
            if (fieldConfig.maxlength) attributes.push(`maxlength="${fieldConfig.maxlength}"`);
            
            return attributes.join(' ');
        }

        collectCurrentStepData() {
            const data = {};
            $('.voi-advanced-form input').each(function() {
                const $input = $(this);
                const name = $input.attr('name');
                let value = $input.val();
                
                if ($input.attr('type') === 'number') {
                    value = parseFloat(value) || 0;
                }
                
                if (name && value !== '') {
                    data[name] = value;
                }
            });
            return data;
        }

        validateCurrentStep() {
            let isValid = true;
            const errors = [];

            $('.voi-advanced-form input[required]').each(function() {
                const $input = $(this);
                const value = $input.val().trim();
                
                if (!value) {
                    isValid = false;
                    errors.push(`${$input.prev('label').text()} is required`);
                    $input.addClass('error');
                } else {
                    $input.removeClass('error');
                }
            });

            if (!isValid) {
                this.showError('Please fill in all required fields:\n' + errors.join('\n'));
            }

            return isValid;
        }

        async previousStep() {
            if (this.currentStep > 1) {
                this.currentStep--;
                this.renderProgressIndicator();
                this.renderCurrentStep();
                this.hideError();
            }
        }

        async nextStep() {
            if (!this.validateCurrentStep()) {
                return;
            }

            // Save current step data
            const currentData = this.collectCurrentStepData();
            Object.assign(this.stepData, currentData);

            try {
                await this.saveStepData(this.currentStep, currentData);
                
                if (this.currentStep < this.maxSteps) {
                    this.currentStep++;
                    this.renderProgressIndicator();
                    this.renderCurrentStep();
                    this.hideError();
                }
            } catch (error) {
                console.error('Failed to save step data:', error);
                this.showError('Failed to save progress. Please try again.');
            }
        }

        async saveStepData(step, data) {
            return $.ajax({
                url: voi_advanced_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'voi_save_step',
                    nonce: voi_advanced_ajax.nonce,
                    step: step,
                    data: data
                }
            });
        }

        async updatePreview() {
            if (this.currentStep <= 2) return;

            const currentData = this.collectCurrentStepData();
            const allData = { ...this.stepData, ...currentData };

            try {
                const response = await $.ajax({
                    url: voi_advanced_ajax.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'voi_calculate_preview',
                        nonce: voi_advanced_ajax.nonce,
                        data: allData
                    }
                });

                if (response.success) {
                    this.renderPreview(response.data.calculations);
                }
            } catch (error) {
                console.error('Preview calculation failed:', error);
            }
        }

        renderPreview(calculations) {
            const previewContainer = $('#previewPanel');
            if (!previewContainer.length) return;

            const html = `
                <div class="preview-panel">
                    <h3>Live ROI Preview</h3>
                    <div class="preview-grid">
                        <div class="preview-item">
                            <div class="preview-value">$${this.formatNumber(calculations.summary.total_annual_savings)}</div>
                            <div class="preview-label">Total Annual Savings</div>
                        </div>
                        <div class="preview-item">
                            <div class="preview-value">${Math.round(calculations.summary.annual_roi)}%</div>
                            <div class="preview-label">Annual ROI</div>
                        </div>
                        <div class="preview-item">
                            <div class="preview-value">${Math.round(calculations.summary.payback_months)} mo</div>
                            <div class="preview-label">Payback Period</div>
                        </div>
                        <div class="preview-item">
                            <div class="preview-value">$${this.formatNumber(calculations.summary.net_benefit)}</div>
                            <div class="preview-label">Net Benefit</div>
                        </div>
                    </div>
                </div>
            `;

            previewContainer.html(html);
        }

        async submitCalculator() {
            if (this.isSubmitting) return;
            
            if (!this.validateCurrentStep()) {
                return;
            }

            this.isSubmitting = true;
            this.showLoading('Generating your custom ROI analysis...');

            try {
                // Save final step data
                const finalData = this.collectCurrentStepData();
                Object.assign(this.stepData, finalData);

                const response = await $.ajax({
                    url: voi_advanced_ajax.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'voi_submit_advanced',
                        nonce: voi_advanced_ajax.nonce,
                        data: finalData
                    }
                });

                this.hideLoading();

                if (response.success) {
                    this.showFinalResults(response.data.calculations);
                } else {
                    this.showError(response.data.message || 'Submission failed. Please try again.');
                }
            } catch (error) {
                this.hideLoading();
                console.error('Submission failed:', error);
                this.showError('Submission failed. Please check your connection and try again.');
            } finally {
                this.isSubmitting = false;
            }
        }

        showFinalResults(calculations) {
            const resultsContainer = $('#voiAdvancedSteps');
            
            const html = `
                <div class="final-results">
                    <h2>Your Custom ROI Analysis Complete!</h2>
                    <div class="results-summary">
                        <div class="result-highlight">
                            <h3>Key Results</h3>
                            <div class="results-grid">
                                <div class="result-card">
                                    <div class="result-value">$${this.formatNumber(calculations.summary.total_annual_savings)}</div>
                                    <div class="result-label">Total Annual Savings</div>
                                </div>
                                <div class="result-card">
                                    <div class="result-value">${Math.round(calculations.summary.annual_roi)}%</div>
                                    <div class="result-label">Return on Investment</div>
                                </div>
                                <div class="result-card">
                                    <div class="result-value">${Math.round(calculations.summary.payback_months)}</div>
                                    <div class="result-label">Payback (Months)</div>
                                </div>
                                <div class="result-card">
                                    <div class="result-value">$${this.formatNumber(calculations.summary.net_benefit)}</div>
                                    <div class="result-label">Net Annual Benefit</div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="breakdown-summary">
                            <h3>Savings Breakdown</h3>
                            <div class="breakdown-item">
                                <span>Storage Cost Avoidance:</span>
                                <span>$${this.formatNumber(calculations.cost_avoidance.total)}</span>
                            </div>
                            <div class="breakdown-item">
                                <span>Personnel Productivity:</span>
                                <span>$${this.formatNumber(calculations.personnel_savings.total)}</span>
                            </div>
                            <div class="breakdown-item">
                                <span>Risk Mitigation:</span>
                                <span>$${this.formatNumber(calculations.operational_savings.total)}</span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="final-actions">
                        <button class="nav-button primary start-over-btn">Start New Analysis</button>
                        <p style="margin-top: 15px; color: #6b7280;">
                            Your analysis has been saved and our team may contact you to discuss implementation details.
                        </p>
                    </div>
                </div>
            `;

            resultsContainer.html(html);
            
            // Hide progress indicator
            $('#voiAdvancedProgress').hide();
        }

        startOver() {
            if (confirm('Are you sure you want to start a new analysis? This will clear all current data.')) {
                this.currentStep = 1;
                this.stepData = {};
                $('#voiAdvancedProgress').show();
                this.renderProgressIndicator();
                this.renderCurrentStep();
                this.hideError();
            }
        }

        formatNumber(number) {
            return new Intl.NumberFormat('en-US').format(Math.round(number));
        }

        showLoading(message) {
            const loadingHtml = `
                <div class="loading-overlay">
                    <div class="loading-spinner">
                        <div class="spinner"></div>
                        <p>${message}</p>
                    </div>
                </div>
            `;
            $('body').append(loadingHtml);
        }

        hideLoading() {
            $('.loading-overlay').remove();
        }

        showError(message) {
            const errorContainer = $('#voiAdvancedError');
            if (errorContainer.length) {
                errorContainer.text(message).show();
            } else {
                alert(message);
            }
        }

        hideError() {
            $('#voiAdvancedError').hide();
        }

        // Utility function for debouncing
        debounce(func, wait) {
            let timeout;
            return function executedFunction(...args) {
                const later = () => {
                    clearTimeout(timeout);
                    func(...args);
                };
                clearTimeout(timeout);
                timeout = setTimeout(later, wait);
            };
        }
    }

    // Initialize calculator when DOM is ready
    $(document).ready(function() {
        if ($('#voiAdvancedCalculator').length) {
            window.voiAdvancedCalculator = new VOIAdvancedCalculator();
        }
    });

})(jQuery);