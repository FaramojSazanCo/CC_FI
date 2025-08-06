jQuery(function($) {
    'use strict';

    if (typeof ccifData === 'undefined' || !ccifData.cities) {
        console.error('CCIF Iran Checkout: City data is not available.');
        return;
    }
    var cities = ccifData.cities;

    /**
     * Shows/hides fields based on the selected person type.
     */
    function togglePersonFields() {
        var personType = $('#billing_person_type').val();
        $('.real-person-field').closest('.form-row').hide();
        $('.legal-person-field').closest('.form-row').hide();

        if (personType === 'real') {
            $('.real-person-field').closest('.form-row').show();
        } else if (personType === 'legal') {
            $('.legal-person-field').closest('.form-row').show();
        }
    }

    /**
     * Updates the 'required' status of invoice fields based on the invoice checkbox.
     * Address fields are always required and are not affected.
     */
    function updateRequiredStatus() {
        var isInvoiceRequested = $('#billing_invoice_request').is(':checked');

        $('.invoice-field').each(function() {
            var $inputOrSelect = $(this).find('input, select');
            if (!$inputOrSelect.length) {
                $inputOrSelect = $(this);
            }

            var $wrapper = $inputOrSelect.closest('.form-row');

            // Set the required property on the actual input/select
            $inputOrSelect.prop('required', isInvoiceRequested);

            // Toggle the class on the wrapper to show/hide the asterisk
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
    togglePersonFields();
    updateRequiredStatus();

    if ($('#billing_state').val()) {
        populateCities();
    }
});
