jQuery(document).ready(function($) {
    'use strict';
    
    var VOICalculator = {
        form: $('#voi-calculator-form'),
        loadingDiv: $('#voi-loading'),
        resultsDiv: $('#voi-results'),
        
        init: function() {
            this.bindEvents();
            this.setupValidation();
        },
        
        bindEvents: function() {
            var self = this;
            
            // Form submission
            this.form.on('submit', function(e) {
                e.preventDefault();
                self.submitForm();
            });
            
            // Email results button
            $(document).on('click', '#voi-email-results', function(e) {
                e.preventDefault();
                self.emailResults();
            });
            
            // Download buttons
            $(document).on('click', '#voi-download-pdf', function(e) {
                e.preventDefault();
                self.downloadFile($(this).data('url'));
            });
            
            $(document).on('click', '#voi-download-excel', function(e) {
                e.preventDefault();
                self.downloadFile($(this).data('url'));
            });
            
            // Real-time input formatting
            $('#total_storage_tb, #total_vms').on('input', function() {
                self.formatNumberInput($(this));
            });
            
            // Company URL formatting
            $('#company_url').on('blur', function() {
                self.formatUrlInput($(this));
            });
        },
        
        setupValidation: function() {
            var self = this;
            
            // Real-time validation
            this.form.find('input[required]').on('blur', function() {
                self.validateField($(this));
            });
            
            // Remove error states on input
            this.form.find('input').on('input', function() {
                self.clearFieldError($(this));
            });
        },
        
        validateField: function(field) {
            var isValid = true;
            var value = field.val().trim();
            var fieldType = field.attr('type');
            var fieldName = field.attr('name');
            
            // Clear previous errors
            this.clearFieldError(field);
            
            // Required field validation
            if (field.prop('required') && !value) {
                this.showFieldError(field, 'This field is required.');
                return false;
            }
            
            // Specific field validations
            switch (fieldType) {
                case 'email':
                    if (value && !this.isValidEmail(value)) {
                        this.showFieldError(field, 'Please enter a valid email address.');
                        isValid = false;
                    }
                    break;
                    
                case 'url':
                    if (value && !this.isValidUrl(value)) {
                        this.showFieldError(field, 'Please enter a valid URL.');
                        isValid = false;
                    }
                    break;
                    
                case 'number':
                    if (value && isNaN(value)) {
                        this.showFieldError(field, 'Please enter a valid number.');
                        isValid = false;
                    } else if (fieldName === 'total_storage_tb' && value && (value < 0.1 || value > 10000)) {
                        this.showFieldError(field, 'Storage should be between 0.1 TB and 10,000 TB.');
                        isValid = false;
                    } else if (fieldName === 'total_vms' && value && (value < 1 || value > 100000)) {
                        this.showFieldError(field, 'VMs should be between 1 and 100,000.');
                        isValid = false;
                    }
                    break;
            }
            
            return isValid;
        },
        
        validateForm: function() {
            var self = this;
            var isValid = true;
            
            this.form.find('input[required]').each(function() {
                if (!self.validateField($(this))) {
                    isValid = false;
                }
            });
            
            return isValid;
        },
        
        showFieldError: function(field, message) {
            field.addClass('error');
            
            var errorDiv = field.siblings('.error-message');
            if (errorDiv.length === 0) {
                errorDiv = $('<span class="error-message"></span>');
                field.after(errorDiv);
            }
            errorDiv.text(message);
        },
        
        clearFieldError: function(field) {
            field.removeClass('error');
            field.siblings('.error-message').remove();
        },
        
        formatNumberInput: function(field) {
            var value = field.val();
            var numericValue = value.replace(/[^0-9.]/g, '');
            
            // Prevent multiple decimal points
            var parts = numericValue.split('.');
            if (parts.length > 2) {
                numericValue = parts[0] + '.' + parts.slice(1).join('');
            }
            
            field.val(numericValue);
        },
        
        formatUrlInput: function(field) {
            var url = field.val().trim();
            if (url && !url.match(/^https?:\/\//)) {
                field.val('https://' + url);
            }
        },
        
        isValidEmail: function(email) {
            var emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            return emailRegex.test(email);
        },
        
        isValidUrl: function(url) {
            try {
                new URL(url);
                return true;
            } catch (e) {
                return false;
            }
        },
        
        submitForm: function() {
            var self = this;
            
            // Validate form
            if (!this.validateForm()) {
                this.showMessage('Please correct the errors above.', 'error');
                return;
            }
            
            // Show loading state
            this.showLoading();
            
            // Prepare form data
            var formData = {
                action: 'voi_submit_calculator',
                voi_nonce: $('#voi_nonce').val(),
                total_storage_tb: $('#total_storage_tb').val(),
                total_vms: $('#total_vms').val(),
                company_name: $('#company_name').val(),
                company_url: $('#company_url').val(),
                first_name: $('#first_name').val(),
                last_name: $('#last_name').val(),
                email: $('#email').val()
            };
            
            // Submit via AJAX
            $.ajax({
                url: voi_ajax.ajax_url,
                type: 'POST',
                data: formData,
                timeout: 30000,
                success: function(response) {
                    self.hideLoading();
                    
                    if (response.success) {
                        self.showResults(response);
                    } else {
                        self.showMessage('An error occurred while processing your request. Please try again.', 'error');
                    }
                },
                error: function(xhr, status, error) {
                    self.hideLoading();
                    
                    if (status === 'timeout') {
                        self.showMessage('Request timed out. Please try again.', 'error');
                    } else {
                        self.showMessage('Network error. Please check your connection and try again.', 'error');
                    }
                }
            });
        },
        
        showLoading: function() {
            this.form.find('.voi-submit-btn').addClass('loading').prop('disabled', true);
            this.loadingDiv.fadeIn(300);
            this.resultsDiv.hide();
            
            // Scroll to loading indicator
            $('html, body').animate({
                scrollTop: this.loadingDiv.offset().top - 100
            }, 500);
        },
        
        hideLoading: function() {
            this.form.find('.voi-submit-btn').removeClass('loading').prop('disabled', false);
            this.loadingDiv.fadeOut(300);
        },
        
        showResults: function(data) {
            var self = this;
            var calculations = data.calculations;
            
            // Build results HTML
            var resultsHtml = '';
            
            // Safe range indicator
            if (data.is_safe_range) {
                resultsHtml += '<div class="safe-range-indicator safe">✓ Results are within safe range</div>';
            } else {
                resultsHtml += '<div class="safe-range-indicator unsafe">⚠ Results require manual review</div>';
            }
            
            // Results summary cards
            resultsHtml += '<div class="results-summary">';
            resultsHtml += '<div class="result-card">';
            resultsHtml += '<h4>Annual Savings</h4>';
            resultsHtml += '<div class="value">$' + this.formatNumber(calculations.total_annual_savings) + '</div>';
            resultsHtml += '</div>';
            
            resultsHtml += '<div class="result-card">';
            resultsHtml += '<h4>ROI</h4>';
            resultsHtml += '<div class="value percentage">' + this.formatNumber(calculations.roi_percentage, 1) + '%</div>';
            resultsHtml += '</div>';
            
            resultsHtml += '<div class="result-card">';
            resultsHtml += '<h4>Payback Period</h4>';
            resultsHtml += '<div class="value">' + this.formatNumber(calculations.payback_months, 1) + ' months</div>';
            resultsHtml += '</div>';
            
            resultsHtml += '<div class="result-card">';
            resultsHtml += '<h4>Net Annual Savings</h4>';
            resultsHtml += '<div class="value">$' + this.formatNumber(calculations.net_annual_savings) + '</div>';
            resultsHtml += '</div>';
            resultsHtml += '</div>';
            
            // Detailed breakdown
            resultsHtml += '<div class="detailed-breakdown">';
            resultsHtml += '<h4>Detailed Breakdown</h4>';
            resultsHtml += '<p><strong>Current Environment:</strong> ' + calculations.total_storage_tb + ' TB storage, ' + calculations.total_vms + ' VMs</p>';
            resultsHtml += '<p><strong>Current Annual Costs:</strong> $' + this.formatNumber(calculations.total_current_cost) + '</p>';
            resultsHtml += '<p><strong>Storage Savings:</strong> $' + this.formatNumber(calculations.storage_savings) + '</p>';
            resultsHtml += '<p><strong>VM Management Savings:</strong> $' + this.formatNumber(calculations.vm_savings) + '</p>';
            resultsHtml += '<p><strong>Time Savings:</strong> $' + this.formatNumber(calculations.time_savings_annual) + '</p>';
            resultsHtml += '<p><strong>Ticket Reduction Savings:</strong> $' + this.formatNumber(calculations.ticket_reduction_savings) + '</p>';
            resultsHtml += '<p><strong>VOI Annual Investment:</strong> $' + this.formatNumber(calculations.voi_annual_cost) + '</p>';
            resultsHtml += '</div>';
            
            // Update results content
            $('#voi-results-content').html(resultsHtml);
            
            // Update download button URLs
            $('#voi-download-pdf').data('url', data.pdf_url);
            $('#voi-download-excel').data('url', data.excel_url);
            
            // Store data for email functionality
            this.resultsDiv.data('submission-id', data.submission_id);
            this.resultsDiv.data('email', $('#email').val());
            
            // Show results with animation
            this.resultsDiv.fadeIn(500);
            
            // Scroll to results
            $('html, body').animate({
                scrollTop: this.resultsDiv.offset().top - 100
            }, 500);
            
            // Hide form
            this.form.fadeOut(300);
        },
        
        emailResults: function() {
            var self = this;
            var submissionId = this.resultsDiv.data('submission-id');
            var email = this.resultsDiv.data('email');
            
            if (!submissionId) {
                this.showMessage('Unable to email results. Please try generating the report again.', 'error');
                return;
            }
            
            var button = $('#voi-email-results');
            var originalText = button.text();
            button.text('Sending...').prop('disabled', true);
            
            $.ajax({
                url: voi_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'voi_email_results',
                    submission_id: submissionId,
                    email: email,
                    nonce: voi_ajax.nonce
                },
                success: function(response) {
                    if (response.success) {
                        self.showMessage('Results have been emailed successfully!', 'success');
                        button.text('✓ Sent');
                    } else {
                        self.showMessage('Failed to send email. Please try again.', 'error');
                        button.text(originalText).prop('disabled', false);
                    }
                },
                error: function() {
                    self.showMessage('Network error while sending email.', 'error');
                    button.text(originalText).prop('disabled', false);
                }
            });
        },
        
        downloadFile: function(url) {
            if (!url) {
                this.showMessage('Download URL not available.', 'error');
                return;
            }
            
            // Create temporary link and trigger download
            var link = document.createElement('a');
            link.href = url;
            link.download = '';
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        },
        
        formatNumber: function(number, decimals) {
            decimals = decimals || 0;
            return parseFloat(number).toLocaleString('en-US', {
                minimumFractionDigits: decimals,
                maximumFractionDigits: decimals
            });
        },
        
        showMessage: function(message, type) {
            // Remove existing messages
            $('.voi-error, .voi-success').remove();
            
            var messageClass = type === 'error' ? 'voi-error' : 'voi-success';
            var messageDiv = $('<div class="' + messageClass + '">' + message + '</div>');
            
            this.form.before(messageDiv);
            
            // Auto-hide success messages
            if (type === 'success') {
                setTimeout(function() {
                    messageDiv.fadeOut(300);
                }, 5000);
            }
            
            // Scroll to message
            $('html, body').animate({
                scrollTop: messageDiv.offset().top - 100
            }, 300);
        }
    };
    
    // Initialize calculator
    VOICalculator.init();
    
    // Handle detailed calculator link
    $('.detailed-calculator-link').on('click', function(e) {
        e.preventDefault();
        alert('Detailed calculator will be available in Phase 2.');
    });
});