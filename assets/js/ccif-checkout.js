jQuery(function($) {
    'use strict';

    if (typeof ccifData === 'undefined' || !ccifData.cities) {
        console.error('CCIF Iran Checkout: City data is not available.');
        return;
    }
    var cities = ccifData.cities;

    /**
     * Shows/hides fields based on the selected person type.
     * This function ONLY handles visibility.
     */
    function togglePersonFields() {
        var personType = $('#billing_person_type').val();

        // Hide all person-specific fields first for a clean state
        $('.real-person-field').closest('.form-row').hide();
        $('.legal-person-field').closest('.form-row').hide();

        // Show the fields for the selected person type
        if (personType === 'real') {
            $('.real-person-field').closest('.form-row').show();
        } else if (personType === 'legal') {
            $('.legal-person-field').closest('.form-row').show();
        }
    }

    /**
     * Updates the 'required' status of invoice fields based on the invoice checkbox.
     * This function ONLY handles the required attribute and the asterisk.
     */
    function updateRequiredStatus() {
        var isInvoiceRequested = $('#billing_invoice_request').is(':checked');

        $('.invoice-required').each(function() {
            var $input = $(this);
            var $wrapper = $input.closest('.form-row');

            // Set the required property
            $input.prop('required', isInvoiceRequested);

            // Add/remove WooCommerce's validation class to show/hide the asterisk
            if (isInvoiceRequested) {
                $wrapper.addClass('validate-required');
            } else {
                $wrapper.removeClass('validate-required');
            }
        });

        // Trigger checkout update to refresh validation UI
        $(document.body).trigger('update_checkout');
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
    $('body').on('change', '#billing_person_type', togglePersonFields);
    $('body').on('change', '#billing_invoice_request', updateRequiredStatus);
    $('body').on('change', '#billing_state', populateCities);

    // --- Initial Execution on Page Load ---
    togglePersonFields();   // Set initial visibility for person fields
    updateRequiredStatus(); // Set initial required status for invoice fields

    if ($('#billing_state').val()) {
        populateCities();
    }
});
