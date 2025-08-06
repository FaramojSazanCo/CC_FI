jQuery(function($) {
    'use strict';

    if (typeof ccifData === 'undefined' || !ccifData.cities) {
        console.error('CCIF Iran Checkout: City data is not available.');
        return;
    }
    var cities = ccifData.cities;
    var requiredStar = ' <abbr class="required" title="required">*</abbr>';

    function togglePersonFields() {
        var personType = $('#billing_person_type').val();
        var $realPersonWrapper = $('.ccif-real-person-fields-wrapper');
        var $legalPersonWrapper = $('.ccif-legal-person-fields-wrapper');

        if (personType === 'real') {
            $realPersonWrapper.show();
            $legalPersonWrapper.hide();
        } else if (personType === 'legal') {
            $legalPersonWrapper.show();
            $realPersonWrapper.hide();
        } else {
            $realPersonWrapper.hide();
            $legalPersonWrapper.hide();
        }
    }

    function updateRequiredStatus() {
        var isInvoiceRequested = $('#billing_invoice_request').is(':checked');

        $('.ccif-person-field').each(function() {
            var $wrapper = $(this).closest('.form-row');
            var $label = $wrapper.find('label');
            var $input = $wrapper.find('input, select');
            $input.prop('required', isInvoiceRequested);
            if (isInvoiceRequested) {
                if ($label.find('.required').length === 0) {
                    $label.append(requiredStar);
                }
            } else {
                $label.find('.required').remove();
            }
        });

        $('.ccif-address-field').each(function() {
             var $label = $(this).find('label');
             // The 'required' prop is set in PHP, so we just ensure the star is there.
             if ($label.find('.required').length === 0) {
                $label.append(requiredStar);
             }
        });
        $(document.body).trigger('update_checkout');
    }

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
        // This is the critical fix for the disabled city field issue
        $cityField.prop('disabled', false);
    }

    $('body').on('change', '#billing_person_type', togglePersonFields);
    $('body').on('change', '#billing_invoice_request', updateRequiredStatus);
    $('body').on('change', '#billing_state', populateCities);

    // Initial setup on page load
    togglePersonFields();
    updateRequiredStatus();
    populateCities(); // This will correctly set the initial state of the city field
});
