/**
 * USP Form Submission Processing Indicator
 * Shows "Processing..." with spinner when form is submitted
 * Standalone script - won't interfere with file upload functionality
 */

jQuery(document).ready(function ($) {

    // Find all USP Pro forms (they have a usp-form class or usp-submit button)
    var $uspForms = $('form').has('.usp-submit, input[name="usp-form-id"]');

    // Attach submit handler to each USP form
    $uspForms.on('submit', function (e) {
        var $form = $(this);
        var $submitButton = $form.find('.usp-submit');

        // Only proceed if submit button exists and form is actually submitting
        if ($submitButton.length && !$submitButton.hasClass('usp-processing')) {

            // Mark as processing
            $submitButton.addClass('usp-processing');

            // Disable button to prevent double-clicks
            $submitButton.prop('disabled', true);

            // Store original text/value
            var isInput = $submitButton.is('input');
            var originalContent = isInput ? $submitButton.val() : $submitButton.html();
            $submitButton.data('original-content', originalContent);

            // Update button with spinner and "Processing..." text
            if (isInput) {
                $submitButton.val('Processing...');
            } else {
                $submitButton.html('<span class="usp-spinner"></span> Processing...');
            }

            // Debug log
            console.log('USP form submitting - button updated to Processing state');
        }

        // Don't prevent default - let form submit normally
        // If form validation fails, we'll restore the button
    });

    // Add CSS for spinner and processing state
    if (!$('#usp-processing-styles').length) {
        $('head').append(
            '<style id="usp-processing-styles">\
                .usp-submit.usp-processing {\
                    opacity: 0.7;\
                    cursor: wait !important;\
                    pointer-events: none;\
                }\
                .usp-spinner {\
                    display: inline-block;\
                    width: 14px;\
                    height: 14px;\
                    border: 2px solid rgba(255, 255, 255, 0.3);\
                    border-top-color: currentColor;\
                    border-radius: 50%;\
                    animation: usp-spin 0.8s linear infinite;\
                    vertical-align: middle;\
                    margin-right: 6px;\
                }\
                @keyframes usp-spin {\
                    0% { transform: rotate(0deg); }\
                    100% { transform: rotate(360deg); }\
                }\
            </style>'
        );
    }

    // Restore button if form validation fails (form doesn't actually submit)
    // This catches cases where client-side validation stops submission
    setTimeout(function () {
        $('.usp-submit.usp-processing').each(function () {
            var $btn = $(this);
            // If we're still on the same page after 200ms, validation likely failed
            var originalContent = $btn.data('original-content');
            if (originalContent) {
                if ($btn.is('input')) {
                    $btn.val(originalContent);
                } else {
                    $btn.html(originalContent);
                }
                $btn.prop('disabled', false);
                $btn.removeClass('usp-processing');
                console.log('Form validation failed - button restored');
            }
        });
    }, 200);

});