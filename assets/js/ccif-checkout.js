jQuery(function($) {
    'use strict';

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
        $('.ccif-real-person-field').closest('.form-row').hide();
        $('.ccif-legal-person-field').closest('.form-row').hide();

        if (personType === 'real') {
            $('.ccif-real-person-field').closest('.form-row').show();
        } else if (personType === 'legal') {
            $('.ccif-legal-person-field').closest('.form-row').show();
        }
    }

    /**
     * Updates the 'required' status of fields based on the invoice checkbox.
     */
    function updateRequiredStatus() {
        var isInvoiceRequested = $('#billing_invoice_request').is(':checked');

        $('.ccif-person-field').each(function() {
            var $wrapper = $(this);
            var $input = $wrapper.find('input, select');

            $input.prop('required', isInvoiceRequested);

            if (isInvoiceRequested) {
                $wrapper.addClass('validate-required');
            } else {
                $wrapper.removeClass('validate-required');
            }
        });

        $(document.body).trigger('update_checkout');
    }

    /**
     * Populates the city dropdown based on the selected state.
     */
    function populateCities() {
        var state = $('#billing_state').val();
        var $cityField = $('#billing_city');

        if (!state) {
            $cityField.empty().append('<option value="">ابتدا استان را انتخاب کنید</option>').prop('disabled', true);
            return;
        }

        var currentCity = $cityField.val();

        $cityField.empty().append('<option value="">' + 'انتخاب کنید' + '</option>');

        if (cities[state]) {
            $.each(cities[state], function(index, cityName) {
                $cityField.append($('<option>', {
                    value: cityName,
                    text: cityName,
                    selected: cityName === currentCity
                }));
            });
        }
        // CRITICAL FIX: Re-enable the city field after populating it.
        $cityField.prop('disabled', false);
    }

    // --- Event Handlers ---
    $('body').on('change', '#billing_person_type', togglePersonFields);
    $('body').on('change', '#billing_invoice_request', updateRequiredStatus);
    $('body').on('change', '#billing_state', populateCities);

    // --- Initial Execution on Page Load ---
    togglePersonFields();
    updateRequiredStatus();
    populateCities(); // Run on load to set initial state of city field
});
