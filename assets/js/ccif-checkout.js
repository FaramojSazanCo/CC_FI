jQuery(function($) {
    'use strict';

    if (typeof ccifData === 'undefined' || !ccifData.cities) {
        console.error('CCIF Iran Checkout: City data is not available.');
        return;
    }
    var cities = ccifData.cities;
    var requiredStar = ' <abbr class="required" title="required">*</abbr>';

    /**
     * Creates visual boxes by moving fields into new wrapper divs.
     * This is the most reliable method to regroup fields rendered by WooCommerce.
     */
    function createVisualBoxes() {
        var wrapper = $('.woocommerce-billing-fields__field-wrapper');
        if (!wrapper.length || wrapper.data('ccif-boxes-created')) {
            return; // Ensure this runs only once
        }

        // Create the boxes
        var invoiceBox = $('<div class="ccif-box invoice-request-box"></div>');
        var personBox = $('<div class="ccif-box person-info-box"><h2>اطلاعات شخص/شرکت</h2></div>');
        var addressBox = $('<div class="ccif-box address-info-box"><h2>اطلاعات آدرس</h2></div>');

        // Move fields into their respective boxes using .appendTo()
        $('.ccif-invoice-request-field').appendTo(invoiceBox);
        $('.ccif-person-field').closest('.form-row').appendTo(personBox);
        $('.ccif-address-field').closest('.form-row').appendTo(addressBox);

        // Prepend the boxes to the main wrapper in the correct order
        wrapper.prepend(addressBox).prepend(personBox).prepend(invoiceBox);

        wrapper.data('ccif-boxes-created', true);
    }

    /**
     * Shows/hides fields based on the selected person type.
     */
    function togglePersonFields() {
        var personType = $('#billing_person_type').val();
        $('.ccif-real-person-field').hide();
        $('.ccif-legal-person-field').hide();

        if (personType === 'real') {
            $('.ccif-real-person-field').show();
        } else if (personType === 'legal') {
            $('.ccif-legal-person-field').show();
        }
    }

    /**
     * Updates the 'required' status of fields based on the invoice checkbox.
     */
    function updateRequiredStatus() {
        var isInvoiceRequested = $('#billing_invoice_request').is(':checked');

        // --- Person/Company Fields ---
        $('.ccif-person-field').each(function() {
            var $wrapper = $(this);
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

        // --- Address Fields are ALWAYS required ---
        // This is handled in PHP, but we ensure the star is always there.
        $('.ccif-address-field').each(function() {
             var $label = $(this).find('label');
             if ($label.find('.required').length === 0) {
                $label.append(requiredStar);
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
