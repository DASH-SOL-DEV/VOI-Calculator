(function($) {
    'use strict';

    $(document).ready(function() {
        $('#voi-calculator-form').on('submit', function(e) {
            e.preventDefault();

            var form = $(this);
            var messageDiv = $('#voi-form-message');
            var submitButton = form.find('.voi-submit-button');
            var originalButtonText = submitButton.text();

            console.log('VOI Calculator: Form submitted. Sending AJAX request.');

            $.ajax({
                type: 'POST',
                url: voi_ajax.ajax_url,
                data: form.serialize() + '&action=voi_handle_form_submission&nonce=' + voi_ajax.nonce,
                beforeSend: function() {
                    submitButton.prop('disabled', true).text('Generating...');
                    messageDiv.hide().removeClass('success error').html('');
                },
                success: function(response) {
                    console.log('VOI AJAX Response:', response); // Log the full response

                    // Check if the response is a valid JSON object with the expected structure
                    if (typeof response === 'object' && response !== null && typeof response.success !== 'undefined') {
                        if (response.success) {
                            var successMessage = response.data.message + 
                                ' <a href="' + response.data.pdf_url + '" target="_blank" class="pdf-download-link">Download Your PDF</a>';
                            messageDiv.addClass('success').html(successMessage).show();
                            form[0].reset();
                        } else {
                            // Handle cases where success is false (a controlled error from wp_send_json_error)
                            messageDiv.addClass('error').text(response.data.message).show();
                        }
                    } else {
                        // Handle unexpected responses (like a direct PHP error string)
                        console.error('VOI AJAX Error: Response was not valid JSON.');
                        // Display the raw response as an error. Strip HTML tags for cleaner display.
                        var errorText = $('<textarea />').html(response).text();
                        messageDiv.addClass('error').text('An unexpected server error occurred: ' + errorText).show();
                    }
                },
                error: function(xhr, status, error) {
                    // This block will catch network errors or HTTP error statuses (like 404, 500)
                    console.error('VOI AJAX Error:', {
                        status: status,
                        error: error,
                        response: xhr.responseText,
                        xhr: xhr
                    });

                    var errorMsg = 'A network or server error occurred. Please check the browser console.';
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
    });

})(jQuery);
