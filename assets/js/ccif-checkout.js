jQuery(function($) {
    'use strict';

    if (typeof ccifData === 'undefined' || !ccifData.cities) {
        console.error('CCIF Iran Checkout: City data is not available.');
        return;
    }
    var cities = ccifData.cities;

    /**
     * Updates the required status of invoice-specific fields based on their visibility.
     */
    function updateRequiredStatus() {
        // Find all inputs that are conditionally required for the invoice
        $('.invoice-required').each(function() {
            var $input = $(this);
            var $wrapper = $input.closest('.form-row');
            var isVisible = $wrapper.is(':visible');

            // Set the required property based on visibility
            $input.prop('required', isVisible);

            // Add/remove WooCommerce's validation class
            if (isVisible) {
                $wrapper.addClass('validate-required');
            } else {
                $wrapper.removeClass('validate-required');
            }
        });
    }

    /**
     * Shows/hides fields based on the selected person type ('real' or 'legal').
     */
    function togglePersonFields() {
        var personType = $('#billing_person_type').val();

        // Hide all person-specific fields first
        $('.real-person-field').closest('.form-row').hide();
        $('.legal-person-field').closest('.form-row').hide();

        // Show the fields for the selected person type
        if (personType === 'real') {
            $('.real-person-field').closest('.form-row').show();
        } else if (personType === 'legal') {
            $('.legal-person-field').closest('.form-row').show();
        }

        // After changing visibility, always update the required status.
        updateRequiredStatus();
    }

    /**
     * Shows/hides the entire invoice details section based on the invoice request checkbox.
     */
    function toggleInvoiceSection() {
        var isInvoiceRequested = $('#billing_invoice_request').is(':checked');
        var $invoiceSection = $('.invoice-section').closest('.form-row');

        if (isInvoiceRequested) {
            $invoiceSection.show();
        } else {
            $invoiceSection.hide();
        }

        // After toggling the main section, update sub-fields and required status.
        togglePersonFields();
    }

    /**
     * Populates the city dropdown based on the selected state.
     */
    function populateCities() {
        var state = $('#billing_state').val();
        var $cityField = $('#billing_city');
        var currentCity = $cityField.val();

        $cityField.empty().append('<option value="">' + 'ابتدا استان را انتخاب کنید' + '</option>');

        if (state && cities[state]) {
            $.each(cities[state], function(index, cityName) {
                $cityField.append($('<option>', {
                    value: cityName,
                    text: cityName,
                    selected: cityName === currentCity
                }));
            });
        }
    }

    // --- Event Handlers ---
    $('body').on('change', '#billing_state', populateCities);
    $('body').on('change', '#billing_invoice_request', toggleInvoiceSection);
    $('body').on('change', '#billing_person_type', togglePersonFields);

    // --- Initial Execution on Page Load ---
    toggleInvoiceSection(); // This is the main function to call on load.

    // Populate cities if a state is already selected (e.g., on form validation error)
    if ($('#billing_state').val()) {
        populateCities();
    }

    // Trigger update_checkout to ensure validation UI is correct on load
    $(document.body).trigger('update_checkout');
});
