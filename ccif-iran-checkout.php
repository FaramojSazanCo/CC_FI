<?php
/**
 * Plugin Name: CCIF Iran Checkout
 * Description: افزودن فیلدهای صورتحساب شامل درخواست فاکتور، نوع شخص و لیست استان/شهر ایران به صورت دینامیک در ووکامرس
 * Version: 1.1
 * Author: Your Name
 * Text Domain: ccif-iran-checkout
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class CCIF_Iran_Checkout {

    public function __construct() {
        // اولویت 1001 برای اطمینان از اجرای این فیلتر بعد از سایر افزونه‌ها
        add_filter( 'woocommerce_checkout_fields', [ $this, 'modify_checkout_fields' ], 1001 );
        add_action( 'wp_enqueue_scripts',       [ $this, 'enqueue_assets' ] );
    }

    /**
     * خواندن JSON و تولید آرایه‌های استان‌ها و شهرها
     */
    private function load_iran_data() {
        $json_file = plugin_dir_path( __FILE__ ) . 'assets/data/iran-cities.json';
        if ( ! file_exists( $json_file ) ) {
            return [ 'states' => [], 'cities' => [] ];
        }

        $data   = json_decode( file_get_contents( $json_file ), true );
        $states = [];
        $cities = [];

        foreach ( $data as $province ) {
            $slug           = sanitize_title( $province['name'] );
            $states[ $slug ] = $province['name'];
            // This needs to be keyed by province slug to work correctly in JS
            $cities[ $slug ] = array_column( $province['cities'], 'name' );
        }

        return [ 'states' => $states, 'cities' => $cities ];
    }

    /**
     * اصلاح فیلدهای صورتحساب ووکامرس
     */
    public function modify_checkout_fields( $fields ) {

        // --- قدم اول: حذف فیلدهای پیش‌فرض ---
        unset(
            $fields['billing']['billing_first_name'],
            $fields['billing']['billing_last_name'],
            $fields['billing']['billing_company'],
            $fields['billing']['billing_country'],
            $fields['billing']['billing_address_1'],
            $fields['billing']['billing_address_2'],
            $fields['billing']['billing_postcode'],
            $fields['billing']['billing_phone']
        );

        // --- قدم دوم: اصلاح فیلدهای موجود و افزودن فیلدهای جدید ---

        $iran = $this->load_iran_data();

        // اصلاح فیلد استان
        $fields['billing']['billing_state'] = [
            'type'      => 'select',
            'label'     => 'استان',
            'required'  => true,
            'class'     => ['form-row-first'],
            'priority'  => 10,
            'options'   => [ '' => 'انتخاب کنید' ] + $iran['states'],
        ];

        // اصلاح فیلد شهر
        $fields['billing']['billing_city'] = [
            'type'      => 'select',
            'label'     => 'شهر',
            'required'  => true,
            'class'     => ['form-row-last'],
            'priority'  => 20,
            'options'   => [ '' => 'ابتدا استان را انتخاب کنید' ],
        ];

        // افزودن فیلد درخواست فاکتور
        $fields['billing']['billing_invoice_request'] = [
            'type'     => 'checkbox',
            'label'    => 'درخواست صدور فاکتور رسمی',
            'class'    => [ 'form-row-wide' ],
            'priority' => 30,
        ];

        // افزودن فیلد نوع شخص
        $fields['billing']['billing_person_type'] = [
            'type'     => 'select',
            'label'    => 'نوع شخص',
            'class'    => [ 'form-row-wide', 'invoice-related' ],
            'options'  => [
                ''      => 'انتخاب کنید',
                'real'  => 'حقیقی',
                'legal' => 'حقوقی',
            ],
            'priority' => 40,
        ];

        // افزودن فیلد کد ملی
        $fields['billing']['billing_national_code'] = [
            'type'        => 'text',
            'label'       => 'کد ملی',
            'class'       => [ 'form-row-first', 'real-only', 'invoice-related' ],
            'placeholder' => '۱۰ رقم بدون خط تیره',
            'priority'    => 50,
        ];

        // افزودن فیلد نام شرکت
        $fields['billing']['billing_company_name'] = [
            'type'        => 'text',
            'label'       => 'نام شرکت',
            'class'       => [ 'form-row-first', 'legal-only', 'invoice-related' ],
            'priority'    => 50,
        ];

        // افزودن فیلد شناسه ملی/اقتصادی
        $fields['billing']['billing_economic_code'] = [
            'type'        => 'text',
            'label'       => 'شناسه ملی/اقتصادی',
            'class'       => [ 'form-row-last', 'legal-only', 'invoice-related' ],
            'priority'    => 60,
        ];

        return $fields;
    }

    /**
     * بارگذاری اسکریپت و استایل
     */
    public function enqueue_assets() {
        if ( ! is_checkout() ) {
            return;
        }

        // اسکریپت اصلی
        wp_enqueue_script(
            'ccif-checkout-js',
            plugin_dir_url( __FILE__ ) . 'assets/js/ccif-checkout.js',
            [ 'jquery' ],
            '1.1', // Version bump
            true
        );

        // ارسال داده‌های شهرها به JS
        $iran = $this->load_iran_data();
        wp_localize_script(
            'ccif-checkout-js',
            'ccifData',
            [ 'cities' => $iran['cities'] ]
        );

        // استایل پایه (اختیاری)
        wp_enqueue_style(
            'ccif-checkout-css',
            plugin_dir_url( __FILE__ ) . 'assets/css/ccif-checkout.css',
            [],
            '1.1' // Version bump
        );
    }
}

new CCIF_Iran_Checkout();
