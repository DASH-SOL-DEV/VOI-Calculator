(function($) {
    'use strict';

    $(document).ready(function() {
        // This function displays the results. It's used by both AJAX success and page load.
        function displayResults(html, pdfUrl) {
            var resultsWrapper = $('#voi-calculator-results');
            var formWrapper = $('.voi-form-wrapper');

            var actionButtons = `
                <div class="results-actions">
                    <a href="${pdfUrl}" target="_blank" class="voi-download-button">Download PDF</a>
                    <button type="button" class="voi-calculate-again-button">Calculate Again</button>
                </div>`;

            resultsWrapper.html(html + actionButtons);
            formWrapper.hide();
            resultsWrapper.show();
        }

        $('#voi-calculator-form').on('submit', function(e) {
            e.preventDefault();

            var form = $(this);
            var messageDiv = $('#voi-form-message');
            var submitButton = form.find('.voi-submit-button');
            var originalButtonText = submitButton.text();

            $.ajax({
                type: 'POST',
                url: voi_ajax.ajax_url,
                data: form.serialize() + '&action=voi_handle_form_submission&nonce=' + voi_ajax.nonce,
                beforeSend: function() {
                    submitButton.prop('disabled', true).text('Generating...');
                    messageDiv.hide().removeClass('error');
                },
                success: function(response) {
                    if (response.success) {
                        displayResults(response.data.html_output, response.data.pdf_url);

                        // Update URL to allow for refresh
                        var newUrl = window.location.pathname + '?submission_id=' + response.data.submission_id;
                        window.history.pushState({path: newUrl}, '', newUrl);

                    } else {
                        messageDiv.addClass('error').text(response.data.message).show();
                    }
                },
                error: function(xhr) {
                    var errorMsg = 'An unexpected error occurred. Please try again.';
                    if (xhr.responseJSON && xhr.responseJSON.data && xhr.responseJSON.data.message) {
                        errorMsg = xhr.responseJSON.data.message;
                    }
                    messageDiv.addClass('error').text(errorMsg).show();
                },
                complete: function() {
                    submitButton.prop('disabled', false).text(originalButtonText);
                }
            });
        });

        // Delegate event for the "Calculate Again" button
        $('#voi-calculator-container').on('click', '.voi-calculate-again-button', function() {
            $('#voi-calculator-results').hide().html('');
            $('#voi-calculator-form')[0].reset();
            $('.voi-form-wrapper').show();
            // Remove the parameter from the URL
            window.history.pushState({path: window.location.pathname}, '', window.location.pathname);
        });
    });

})(jQuery);
