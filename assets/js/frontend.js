/**
 * NMTO LearnPress Course Instances Frontend JavaScript
 */

(function ($) {
    'use strict';

    var NMTO_LearnPress_CourseInstances = {

        init: function () {
            this.bindEvents();
            this.initCountdowns();
        },

        bindEvents: function () {
            $(document).on('click', '.btn-enroll', this.handleEnrollment);
            $(document).on('click', '.toggle-description', this.toggleDescription);
        },

        handleEnrollment: function (e) {
            e.preventDefault();

            var button = $(this);
            var instanceId = button.data('instance-id');

            if (!instanceId) {
                console.error('No instance ID found');
                return;
            }

            // Disable button and show loading state
            button.prop('disabled', true);
            var originalText = button.text();
            button.text('Enrolling...');

            $.ajax({
                url: nmto_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'enroll_course_instance',
                    instance_id: instanceId,
                    nonce: nmto_ajax.nonce
                },
                success: function (response) {
                    if (response.success) {
                        button.removeClass('btn-enroll')
                            .addClass('btn-success')
                            .text('Enrolled!');

                        // Show success message
                        NMTO_LearnPress_CourseInstances.showMessage(response.data.message, 'success');

                        // Optionally reload the page to update enrollment counts
                        setTimeout(function () {
                            location.reload();
                        }, 2000);

                    } else {
                        button.prop('disabled', false).text(originalText);
                        NMTO_LearnPress_CourseInstances.showMessage(response.data.message, 'error');
                    }
                },
                error: function (xhr, status, error) {
                    button.prop('disabled', false).text(originalText);
                    NMTO_LearnPress_CourseInstances.showMessage('An error occurred. Please try again.', 'error');
                    console.error('Enrollment error:', error);
                }
            });
        },

        toggleDescription: function (e) {
            e.preventDefault();
            var description = $(this).siblings('.instance-description');
            description.slideToggle();

            var text = $(this).text();
            $(this).text(text === 'Show Details' ? 'Hide Details' : 'Show Details');
        },

        showMessage: function (message, type) {
            // Remove existing messages
            $('.nmto-message').remove();

            var messageClass = 'nmto-message ' + (type === 'success' ? 'nmto-success' : 'nmto-error');
            var messageHtml = '<div class="' + messageClass + '">' + message + '</div>';

            $('.nmto-course-instances').prepend(messageHtml);

            // Add styles if not already added
            if (!$('#nmto-message-styles').length) {
                var styles = `
                    <style id="nmto-message-styles">
                        .nmto-message {
                            padding: 12px 16px;
                            margin-bottom: 16px;
                            border-radius: 4px;
                            font-weight: 500;
                        }
                        .nmto-success {
                            background: #d1f2eb;
                            color: #0c5460;
                            border: 1px solid #a7f3d0;
                        }
                        .nmto-error {
                            background: #fed7d7;
                            color: #9b2c2c;
                            border: 1px solid #feb2b2;
                        }
                    </style>
                `;
                $('head').append(styles);
            }

            // Auto-hide after 5 seconds
            setTimeout(function () {
                $('.nmto-message').fadeOut();
            }, 5000);
        },

        initCountdowns: function () {
            $('.enrollment-countdown').each(function () {
                var endTime = $(this).data('end-time');
                if (endTime) {
                    NMTO_LearnPress_CourseInstances.startCountdown($(this), endTime);
                }
            });
        },

        startCountdown: function ($element, endTime) {
            var countdownInterval = setInterval(function () {
                var now = new Date().getTime();
                var distance = new Date(endTime).getTime() - now;

                if (distance < 0) {
                    clearInterval(countdownInterval);
                    $element.html('Enrollment Closed');
                    return;
                }

                var days = Math.floor(distance / (1000 * 60 * 60 * 24));
                var hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
                var minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));

                var countdownText = '';
                if (days > 0) {
                    countdownText += days + 'd ';
                }
                if (hours > 0) {
                    countdownText += hours + 'h ';
                }
                countdownText += minutes + 'm remaining';

                $element.html(countdownText);
            }, 60000); // Update every minute
        },

        formatDate: function (dateString) {
            var date = new Date(dateString);
            return date.toLocaleDateString('en-US', {
                year: 'numeric',
                month: 'short',
                day: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            });
        }
    };

    // Initialize when document is ready
    $(document).ready(function () {
        NMTO_LearnPress_CourseInstances.init();
    });

    // Make it globally available for debugging
    window.NMTO_LearnPress_CourseInstances = NMTO_LearnPress_CourseInstances;

})(jQuery);
