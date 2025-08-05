jQuery(function($){
    var cities = ccifData.cities || {};

    // تابع نمایش/مخفی‌سازی فیلدها بر اساس درخواست فاکتور و نوع شخص
    function refreshFields() {
        var invoice     = $('#billing_invoice_request').is(':checked');
        var personType  = $('#billing_person_type').val();

        // اول همه real-only/ legal-only را مخفی و غیرفعال کن
        $('.real-only, .legal-only')
            .hide()
            .find('input, select')
            .prop('disabled', true)
            .prop('required', false)
            .closest('.form-row')
            .removeClass('validate-required');

        if (! invoice) {
            return; // اگر فاکتور نخواسته، همه فیلدها آزاد
        }

        // اگر شخص حقیقی باشد
        if (personType === 'real') {
            $('.real-only')
                .show()
                .find('input, select')
                .prop('disabled', false)
                .prop('required', true)
                .closest('.form-row')
                .addClass('validate-required');
        }

        // اگر شخص حقوقی باشد
        if (personType === 'legal') {
            $('.legal-only')
                .show()
                .find('input, select')
                .prop('disabled', false)
                .prop('required', true)
                .closest('.form-row')
                .addClass('validate-required');
        }
    }

    // تغییر استان -> پر کردن شهرها
    $('#billing_state').on('change', function(){
        var state = $(this).val(),
            $city = $('#billing_city').empty();

        $city.append('<option value="">ابتدا استان را انتخاب کنید</option>');
        if (cities[state]) {
            $.each(cities[state], function(i, name){
                $city.append('<option value="'+name+'">'+name+'</option>');
            });
        }
    });

    // رویدادهای تغییر
    $('#billing_invoice_request, #billing_person_type').on('change', refreshFields);

    // مقداردهی اولیه
    refreshFields();
});