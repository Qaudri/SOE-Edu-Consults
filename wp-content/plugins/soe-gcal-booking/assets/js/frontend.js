/**
 * SOE Class Booking - Frontend JavaScript
 * Multi-step booking flow
 */
(function($) {
    'use strict';

    var container = $('.soe-booking-container');
    var nonce = container.data('nonce');
    var currentStep = 1;
    var selectedClass = { id: null, name: null };
    var selectedSession = { id: null, date: null, time: null };

    // Go to a specific step
    function goToStep(step) {
        currentStep = step;

        // Update step indicators
        container.find('.soe-step').removeClass('active completed');
        container.find('.soe-step').each(function() {
            var stepNum = $(this).data('step');
            if (stepNum < step) {
                $(this).addClass('completed');
            } else if (stepNum === step) {
                $(this).addClass('active');
            }
        });

        // Show/hide step content
        container.find('.soe-step-content').removeClass('active');
        container.find('.soe-step-content[data-step="' + step + '"]').addClass('active');

        // Scroll to top of container
        $('html, body').animate({
            scrollTop: container.offset().top - 50
        }, 300);
    }

    // Step 1: Select a class
    $(document).on('click', '.soe-select-class-btn', function(e) {
        e.preventDefault();

        var card = $(this).closest('.soe-class-card');
        selectedClass.id = card.data('class-id');
        selectedClass.name = card.data('class-name');

        // Update UI
        container.find('.soe-selected-class-name').text(selectedClass.name);
        container.find('.soe-summary-class').text(selectedClass.name);

        // Load sessions
        loadSessions(selectedClass.id);

        goToStep(2);
    });

    // Load sessions for a class
    function loadSessions(classId) {
        var sessionsContainer = container.find('.soe-sessions-list');
        sessionsContainer.html('<div class="soe-loading">Loading available times...</div>');

        $.ajax({
            url: soeGcalAjax.ajaxurl,
            type: 'POST',
            data: {
                action: 'soe_gcal_get_sessions',
                nonce: nonce,
                class_id: classId
            },
            success: function(response) {
                if (response.success && response.data.sessions.length > 0) {
                    var html = '<div class="soe-sessions-grid">';

                    response.data.sessions.forEach(function(session) {
                        html += '<div class="soe-session-card" data-session-id="' + session.id + '"' +
                                ' data-date="' + session.date + '"' +
                                ' data-time="' + session.start_time + ' - ' + session.end_time + '">' +
                                '<div class="soe-session-date">' + session.date + '</div>' +
                                '<div class="soe-session-time">' + session.start_time + ' - ' + session.end_time + '</div>';

                        if (session.location) {
                            html += '<div class="soe-session-location">📍 ' + session.location + '</div>';
                        }

                        html += '<div class="soe-session-spots">' + session.spots_left + ' spot' + (session.spots_left !== 1 ? 's' : '') + ' left</div>' +
                                '<button type="button" class="soe-select-session-btn">Select</button>' +
                                '</div>';
                    });

                    html += '</div>';
                    sessionsContainer.html(html);
                } else {
                    sessionsContainer.html('<p class="soe-no-sessions">No available sessions for this class. Please check back later.</p>');
                }
            },
            error: function() {
                sessionsContainer.html('<p class="soe-error">Failed to load sessions. Please try again.</p>');
            }
        });
    }

    // Step 2: Select a session
    $(document).on('click', '.soe-select-session-btn', function(e) {
        e.preventDefault();

        var card = $(this).closest('.soe-session-card');
        selectedSession.id = card.data('session-id');
        selectedSession.date = card.data('date');
        selectedSession.time = card.data('time');

        // Update summary
        container.find('.soe-summary-date').text(selectedSession.date);
        container.find('.soe-summary-time').text(selectedSession.time);
        container.find('#soe-booking-session-id').val(selectedSession.id);

        // Reset form
        container.find('#soe-booking-form')[0].reset();
        container.find('#soe-booking-session-id').val(selectedSession.id);
        container.find('.soe-form-message').hide();

        goToStep(3);
    });

    // Back buttons
    $(document).on('click', '.soe-back-btn', function(e) {
        e.preventDefault();
        var gotoStep = $(this).data('goto');
        goToStep(gotoStep);
    });

    // Step 3: Submit booking form
    $(document).on('submit', '#soe-booking-form', function(e) {
        e.preventDefault();

        var form = $(this);
        var submitBtn = form.find('.soe-submit-btn');
        var messageEl = form.find('.soe-form-message');
        var originalText = submitBtn.text();

        submitBtn.prop('disabled', true).text('Booking...');
        messageEl.hide();

        $.ajax({
            url: soeGcalAjax.ajaxurl,
            type: 'POST',
            data: {
                action: 'soe_gcal_book_session',
                nonce: nonce,
                session_id: $('#soe-booking-session-id').val(),
                customer_name: $('#soe-customer-name').val(),
                customer_email: $('#soe-customer-email').val(),
                customer_phone: $('#soe-customer-phone').val()
            },
            success: function(response) {
                if (response.success) {
                    // Show confirmation
                    var details = response.data.details;
                    var confirmHtml = '<p><strong>Class:</strong> ' + details.class_name + '</p>' +
                                     '<p><strong>Date:</strong> ' + details.date + '</p>' +
                                     '<p><strong>Time:</strong> ' + details.time + '</p>';
                    if (details.location) {
                        confirmHtml += '<p><strong>Location:</strong> ' + details.location + '</p>';
                    }
                    container.find('.soe-confirmation-details').html(confirmHtml);

                    goToStep(4);
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

    // Book another class
    $(document).on('click', '.soe-new-booking-btn', function(e) {
        e.preventDefault();

        // Reset state
        selectedClass = { id: null, name: null };
        selectedSession = { id: null, date: null, time: null };
        container.find('#soe-booking-form')[0].reset();

        goToStep(1);
    });

})(jQuery);

