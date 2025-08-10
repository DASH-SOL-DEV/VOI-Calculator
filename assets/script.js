jQuery(document).ready(function($) {
    // Handle form submission
    $('#voi-calculator-form').on('submit', function(e) {
        e.preventDefault();
        
        const form = $(this);
        const submitBtn = $('#voi-submit-btn');
        const messagesDiv = $('#voi-form-messages');
        
        // Clear previous messages
        messagesDiv.empty();
        
        // Show loading state
        form.addClass('voi-loading');
        submitBtn.prop('disabled', true).text('Processing...');
        
        // Collect form data
        const formData = {
            action: 'voi_calculator_submit',
            storage_tb: $('#storage_tb').val(),
            vm_count: $('#vm_count').val(),
            first_name: $('#first_name').val(),
            last_name: $('#last_name').val(),
            email: $('#email').val(),
            company_name: $('#company_name').val(),
            company_url: $('#company_url').val(),
            voi_calc_nonce_field: $('#voi_calc_nonce_field').val()
        };
        
        // Submit via AJAX
        $.ajax({
            url: voi_calc_ajax.ajax_url,
            type: 'POST',
            data: formData,
            success: function(response) {
                if (response.success) {
                    showMessage('success', response.data.message);
                    showResults(response.data);
                    form[0].reset(); // Reset the form
                } else {
                    showMessage('error', response.data.message);
                }
            },
            error: function(xhr, status, error) {
                showMessage('error', 'An error occurred. Please try again.');
            },
            complete: function() {
                // Remove loading state
                form.removeClass('voi-loading');
                submitBtn.prop('disabled', false).text('Generate Value Document and See results');
            }
        });
    });
    
    // Function to show results
    function showResults(data) {
        const resultsHtml = `
            <div class="voi-results-container">
                <h3>Your ROI Calculation Results</h3>
                <div class="voi-results-grid">
                    <div class="voi-result-item">
                        <div class="voi-result-label">Annual ROI</div>
                        <div class="voi-result-value">${data.roi}%</div>
                    </div>
                    <div class="voi-result-item">
                        <div class="voi-result-label">Annual Savings</div>
                        <div class="voi-result-value">${data.annual_savings}</div>
                    </div>
                    <div class="voi-result-item">
                        <div class="voi-result-label">Payback Period</div>
                        <div class="voi-result-value">${data.payback_months} months</div>
                    </div>
                </div>
                <div class="voi-results-status">
                    <p><strong>Status:</strong> ${data.safe_range ? 'Within normal range' : 'Requires manual review'}</p>
                    <p><strong>HubSpot:</strong> ${data.hubspot_sent ? '✓ Contact created' : '✗ Failed to sync'}</p>
                    <p><strong>PDF Report:</strong> ${data.pdf_generated ? '✓ Generated' : '✗ Generation failed'}</p>
                </div>
                <div class="voi-results-actions">
                    <p>A detailed ROI report will be emailed to you shortly. Our sales team will also be in touch to discuss your results.</p>
                </div>
            </div>
        `;
        
        $('#voi-form-messages').html(resultsHtml);
    }
    
    // Function to show messages
    function showMessage(type, message) {
        const messagesDiv = $('#voi-form-messages');
        const messageHtml = '<div class="voi-message ' + type + '">' + message + '</div>';
        messagesDiv.html(messageHtml);
        
        // Auto-hide success messages after 5 seconds
        if (type === 'success') {
            setTimeout(function() {
                messagesDiv.fadeOut(300, function() {
                    $(this).empty().show();
                });
            }, 5000);
        }
    }
    
    // Form validation enhancements
    $('#storage_tb, #vm_count').on('input', function() {
        const value = parseFloat($(this).val());
        if (value < 0) {
            $(this).val('');
        }
    });
    
    // Company URL validation
    $('#company_url').on('blur', function() {
        let url = $(this).val();
        if (url && !url.match(/^https?:\/\//)) {
            $(this).val('https://' + url);
        }
    });
    
    // Real-time form validation feedback
    $('input[required]').on('blur', function() {
        const input = $(this);
        const value = input.val().trim();
        
        if (!value) {
            input.addClass('error');
        } else {
            input.removeClass('error');
        }
    });
    
    // Email validation
    $('#email').on('blur', function() {
        const email = $(this).val();
        const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        
        if (email && !emailPattern.test(email)) {
            $(this).addClass('error');
            showMessage('error', 'Please enter a valid email address.');
        } else {
            $(this).removeClass('error');
        }
    });
});