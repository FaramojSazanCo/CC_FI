jQuery(function($) {
    'use strict';

    // اطمینان از وجود داده‌ها
    if (typeof ccifData === 'undefined' || !ccifData.cities) {
        console.error('CCIF Iran Checkout: City data is not available.');
        return;
    }
    var cities = ccifData.cities;

    /**
     * تابع برای نمایش/مخفی کردن فیلدهای وابسته به نوع شخص (حقیقی/حقوقی)
     */
    function togglePersonFields() {
        var personType = $('#billing_person_type').val();
        var $realFields = $('.real-only').closest('.form-row');
        var $legalFields = $('.legal-only').closest('.form-row');

        // مخفی کردن هر دو گروه در ابتدا
        $realFields.hide();
        $legalFields.hide();

        if (personType === 'real') {
            $realFields.show();
        } else if (personType === 'legal') {
            $legalFields.show();
        }

        // پس از تغییر نمایش، وضعیت ضروری بودن را به‌روزرسانی کن
        updateRequiredStatus();
    }

    /**
     * تابع برای به‌روزرسانی وضعیت ضروری (required) بودن فیلدها
     */
    function updateRequiredStatus() {
        var isInvoiceRequested = $('#billing_invoice_request').is(':checked');

        // همه فیلدهای مرتبط با فاکتور را پیدا کن
        $('.invoice-related').each(function() {
            var $input = $(this);
            var $fieldWrapper = $input.closest('.form-row');

            // یک فیلد زمانی ضروری است که چک‌باکس فاکتور تیک خورده باشد و فیلد قابل مشاهده باشد
            var shouldBeRequired = isInvoiceRequested && $fieldWrapper.is(':visible');

            $input.prop('required', shouldBeRequired);

            // افزودن/حذف کلاس اعتبارسنجی ووکامرس
            if (shouldBeRequired) {
                $fieldWrapper.addClass('validate-required');
            } else {
                $fieldWrapper.removeClass('validate-required');
            }
        });

        // به‌روزرسانی رابط کاربری اعتبارسنجی ووکامرس
        $(document.body).trigger('update_checkout');
    }

    /**
     * تابع برای پر کردن لیست شهرها بر اساس استان انتخاب‌شده
     */
    function populateCities() {
        var state = $('#billing_state').val();
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
    }

    // --- اتصال رویدادها ---

    // رویداد برای تغییر استان
    $('body').on('change', '#billing_state', populateCities);

    // رویدادها برای فیلدهای فاکتور
    $('body').on('change', '#billing_person_type', togglePersonFields);
    $('body').on('change', '#billing_invoice_request', updateRequiredStatus);

    // --- اجرای اولیه در زمان بارگذاری صفحه ---

    // اجرای اولیه برای تنظیم صحیح نمایش فیلدها
    togglePersonFields();

    // اجرای اولیه برای بارگذاری شهرها در صورتی که استانی از قبل انتخاب شده باشد (مثلاً در صورت خطا در فرم)
    if ($('#billing_state').val()) {
        populateCities();
    }
});
