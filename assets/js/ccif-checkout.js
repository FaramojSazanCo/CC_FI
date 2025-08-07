jQuery(function($) {
    'use strict';

    // Ensure ccifData and cities exist to prevent errors
    if (typeof ccifData === 'undefined' || !ccifData.cities) {
        console.error('CCIF Iran Checkout: City data is not available.');
        return;
    }
    var cities = ccifData.cities;
    /**
     * Toggles the visibility of fields for Real vs. Legal persons.
     */
    function togglePersonFields() {
        var personType = $('#billing_person_type').val();
        var $realPersonWrapper = $('.ccif-real-person-fields-wrapper');
        var $legalPersonWrapper = $('.ccif-legal-person-fields-wrapper');

        if (personType === 'legal') {
            $legalPersonWrapper.show();
            $realPersonWrapper.hide();
        } else {
            $realPersonWrapper.show();
            $legalPersonWrapper.hide();
        }
    }

    /**
     * Updates the 'required' status of fields based on the invoice checkbox.
     */
    function updateRequiredStatus() {
        var isInvoiceRequested = $('#billing_invoice_request').is(':checked');

        // Target all fields within the person/company box
        $('.person-info-box .form-row').each(function() {
            var $wrapper = $(this);
            var $label = $wrapper.find('label');
            var $input = $wrapper.find('input, select');

            // Set the required property on the input/select element
            $input.prop('required', isInvoiceRequested);
        });

        // Trigger the WooCommerce event to update its validation state
        $(document.body).trigger('update_checkout');
    }

    /**
     * Populates the city dropdown based on the selected state.
     */
    function populateCities() {
        var state = $('#billing_state').val();
        var $cityField = $('#billing_city');

        // Remember the current value if it exists
        var currentCity = $cityField.val();

        $cityField.empty().append('<option value="">' + ccifData.i18n.select_state_first + '</option>');

        if (state && cities[state]) {
            $.each(cities[state], function(index, cityName) {
                // Create new option, select it if it matches the remembered value
                $cityField.append($('<option>', {
                    value: cityName,
                    text: cityName,
                    selected: cityName === currentCity
                }));
            });
        }
    }

    // --- Event Handlers ---
    // We use event delegation on the body element because the checkout form can be updated via AJAX.
    $('body').on('change', '#billing_person_type', togglePersonFields);
    $('body').on('change', '#billing_invoice_request', updateRequiredStatus);
    $('body').on('change', '#billing_state', populateCities);

    // --- Initial Execution on Page Load ---
    togglePersonFields();
    updateRequiredStatus();

    // Populate cities on load if a state is already selected (e.g., on form validation error)
    if ($('#billing_state').val()) {
        populateCities();
    }
});
