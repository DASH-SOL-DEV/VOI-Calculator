(function($) {
    'use strict';

    $(document).ready(function() {
        $('#voi-calculator-form').on('submit', function(e) {
            e.preventDefault();

            var form = $(this);
            var messageDiv = $('#voi-form-message');
            var submitButton = form.find('.voi-submit-button');
            var originalButtonText = submitButton.text();

            $.ajax({
                type: 'POST',
                url: voi_ajax.ajax_url,
                data: {
                    action: 'voi_handle_form_submission',
                    nonce: voi_ajax.nonce,
                    total_tb: $('#total_tb').val(),
                    total_vms: $('#total_vms').val(),
                    company_name: $('#company_name').val(),
                    company_url: $('#company_url').val(),
                    full_name: $('#full_name').val(),
                    email: $('#email').val(),
                },
                beforeSend: function() {
                    submitButton.prop('disabled', true).text('Processing...');
                    messageDiv.hide().removeClass('success error');
                },
                success: function(response) {
                    if (response.success) {
                        messageDiv.addClass('success').text(response.data.message).show();
                        form[0].reset();
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
    });

})(jQuery);
