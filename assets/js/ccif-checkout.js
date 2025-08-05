jQuery(function($) {
    'use strict';

    var cities = ccifData.cities || {};

    // تابع برای نمایش/مخفی کردن فیلدهای شخص حقیقی/حقوقی
    function togglePersonFields() {
        var personType = $('#billing_person_type').val();
        var $realFields = $('.real-only').closest('.form-row');
        var $legalFields = $('.legal-only').closest('.form-row');

        if (personType === 'real') {
            $realFields.show();
            $legalFields.hide();
        } else if (personType === 'legal') {
            $legalFields.show();
            $realFields.hide();
        } else {
            $realFields.hide();
            $legalFields.hide();
        }
        // After toggling visibility, we need to update the required status
        updateRequiredStatus();
    }

    // تابع برای آپدیت وضعیت ضروری بودن فیلدها
    function updateRequiredStatus() {
        var isInvoiceRequested = $('#billing_invoice_request').is(':checked');

        // همه فیلدهای مرتبط با فاکتور را پیدا کن
        $('.invoice-related').each(function() {
            var $fieldWrapper = $(this).closest('.form-row');
            var $input = $(this).find('input, select').length ? $(this).find('input, select') : $(this);

            // فیلد باید هم قابل مشاهده باشد و هم فاکتور درخواست شده باشد تا ضروری شود
            var shouldBeRequired = isInvoiceRequested && $fieldWrapper.is(':visible');

            $input.prop('required', shouldBeRequired);

            if (shouldBeRequired) {
                $fieldWrapper.addClass('validate-required');
            } else {
                $fieldWrapper.removeClass('validate-required');
            }
        });
        // Trigger checkout update to refresh validation UI
        $(document.body).trigger('update_checkout');
    }

    // تغییر استان -> پر کردن شهرها
    $('body').on('change', '#billing_state', function() {
        var state = $(this).val();
        var $cityField = $('#billing_city');
        var currentCity = $cityField.val();

        $cityField.empty();
        $cityField.append('<option value="">' + 'ابتدا استان را انتخاب کنید' + '</option>');

        if (state && cities[state]) {
            $.each(cities[state], function(index, cityName) {
                $cityField.append($('<option>', {
                    value: cityName,
                    text: cityName,
                    selected: cityName === currentCity
                }));
            });
        }
        $cityField.trigger('change'); // For compatibility with other plugins
    });

    // رویدادهای تغییر برای فیلدهای سفارشی
    $('body').on('change', '#billing_person_type', togglePersonFields);
    $('body').on('change', '#billing_invoice_request', updateRequiredStatus);

    // اجرای توابع در اولین بارگذاری صفحه
    togglePersonFields();
});
