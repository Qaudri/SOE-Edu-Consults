/**
 * SOE GCal Booking - Frontend JavaScript
 */
(function($) {
    'use strict';

    var modal = $('#soe-booking-modal');
    var form = $('#soe-booking-form');
    var messageEl = $('.soe-form-message');

    // Open modal when clicking book button
    $(document).on('click', '.soe-book-btn', function(e) {
        e.preventDefault();

        var classId = $(this).data('class-id');
        var className = $(this).data('class-name');
        var card = $(this).closest('.soe-class-card');
        var classDate = card.find('.soe-class-date').text().replace('📅', '').trim();

        $('#soe-booking-class-id').val(classId);
        $('.soe-modal-class-name').text(className + ' - ' + classDate);

        // Reset form and show fields
        form[0].reset();
        form.find('.soe-form-group, .soe-form-actions').show();
        messageEl.hide().removeClass('success error');

        modal.fadeIn(200);
    });

    // Close modal
    $(document).on('click', '.soe-modal-close', function() {
        modal.fadeOut(200);
    });

    // Close modal on outside click
    $(document).on('click', '.soe-modal', function(e) {
        if ($(e.target).hasClass('soe-modal')) {
            modal.fadeOut(200);
        }
    });

    // Close modal on escape key
    $(document).on('keydown', function(e) {
        if (e.key === 'Escape') {
            modal.fadeOut(200);
        }
    });

    // Handle form submission
    form.on('submit', function(e) {
        e.preventDefault();
        
        var submitBtn = form.find('.soe-submit-btn');
        var originalText = submitBtn.text();
        
        // Disable button
        submitBtn.prop('disabled', true).text('Booking...');
        messageEl.hide();
        
        $.ajax({
            url: soeGcalAjax.ajaxurl,
            type: 'POST',
            data: {
                action: 'soe_gcal_book_class',
                nonce: soeGcalAjax.nonce,
                class_id: $('#soe-booking-class-id').val(),
                customer_name: $('#soe-customer-name').val(),
                customer_email: $('#soe-customer-email').val(),
                customer_phone: $('#soe-customer-phone').val()
            },
            success: function(response) {
                if (response.success) {
                    messageEl
                        .removeClass('error')
                        .addClass('success')
                        .text(response.data.message)
                        .show();
                    
                    // Hide form fields on success
                    form.find('.soe-form-group, .soe-form-actions').hide();
                    
                    // Auto close after 3 seconds
                    setTimeout(function() {
                        modal.fadeOut(200);
                        // Reload page to update class list if needed
                        // location.reload();
                    }, 3000);
                } else {
                    messageEl
                        .removeClass('success')
                        .addClass('error')
                        .text(response.data.message)
                        .show();
                    submitBtn.prop('disabled', false).text(originalText);
                }
            },
            error: function() {
                messageEl
                    .removeClass('success')
                    .addClass('error')
                    .text('An error occurred. Please try again.')
                    .show();
                submitBtn.prop('disabled', false).text(originalText);
            }
        });
    });

})(jQuery);

