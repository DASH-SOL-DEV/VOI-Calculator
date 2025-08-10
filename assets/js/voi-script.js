(function($) {
    'use strict';

    $(document).ready(function() {
        $('#voi-calculator-form').on('submit', function(e) {
            e.preventDefault();

            var form = $(this);
            var formWrapper = $('.voi-form-wrapper');
            var resultsWrapper = $('#voi-calculator-results');
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
                        var resultsHtml = response.data.html_output;
                        var pdfUrl = response.data.pdf_url;

                        var actionButtons = `
                            <div class="results-actions">
                                <a href="${pdfUrl}" target="_blank" class="voi-download-button">Download PDF</a>
                                <button type="button" class="voi-calculate-again-button">Calculate Again</button>
                            </div>`;

                        resultsWrapper.html(resultsHtml + actionButtons);
                        formWrapper.hide();
                        resultsWrapper.show();

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
        });
    });

})(jQuery);
