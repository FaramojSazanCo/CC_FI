jQuery(function($) {
    'use strict';

    if (typeof ccifData === 'undefined' || !ccifData.cities) {
        console.error('CCIF Iran Checkout: City data is not available.');
        return;
    }
    var cities = ccifData.cities;

    /**
     * Creates visual boxes by wrapping field groups in divs.
     * This function should run only once on page load.
     */
    function createVisualBoxes() {
        var wrapper = $('.woocommerce-billing-fields__field-wrapper');
        if (!wrapper.length || wrapper.data('ccif-boxes-created')) {
            return; // Run only once
        }

        // Group fields by their function
        var invoiceRequest = $('.ccif-invoice-request').closest('.form-row');
        var personFields = $('.ccif-invoice-field').closest('.form-row');
        var addressFields = $('.ccif-address-field').closest('.form-row');

        // Create boxes and append fields
        var invoiceBox = $('<div class="ccif-box invoice-request-box"></div>').append(invoiceRequest);
        var personBox = $('<div class="ccif-box person-info-box"><h2>اطلاعات شخص/شرکت</h2></div>').append(personFields);
        var addressBox = $('<div class="ccif-box address-info-box"><h2>اطلاعات آدرس</h2></div>').append(addressFields);

        // Prepend the boxes to the main wrapper in the correct order
        wrapper.prepend(addressBox).prepend(personBox).prepend(invoiceBox);

        wrapper.data('ccif-boxes-created', true);
    }

    /**
     * Shows/hides fields based on the selected person type.
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
     * Updates the 'required' status of invoice fields based on the invoice checkbox.
     */
    function updateRequiredStatus() {
        var isInvoiceRequested = $('#billing_invoice_request').is(':checked');

        $('.ccif-invoice-field').each(function() {
            var $inputOrSelect = $(this).find('input, select').addBack(this);
            var $wrapper = $inputOrSelect.closest('.form-row');

            $inputOrSelect.prop('required', isInvoiceRequested);

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
        var currentCity = $cityField.val();

        $cityField.empty().append('<option value="">' + 'ابتدا استان را انتخاب کنید' + '</option>');

        if (state && cities[state]) {
            $.each(cities[state], function(index, cityName) {
                $cityField.append($('<option>', { value: cityName, text: cityName, selected: cityName === currentCity }));
            });
        }
    }

    // --- Event Handlers ---
    $('body').on('change', '#billing_person_type', togglePersonFields);
    $('body').on('change', '#billing_invoice_request', updateRequiredStatus);
    $('body').on('change', '#billing_state', populateCities);

    // --- Initial Execution ---
    createVisualBoxes();
    togglePersonFields();
    updateRequiredStatus();

    if ($('#billing_state').val()) {
        populateCities();
    }
});
