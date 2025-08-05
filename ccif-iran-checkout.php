<?php
/**
 * Plugin Name: CCIF Iran Checkout
 * Description: افزودن فیلدهای صورتحساب شامل درخواست فاکتور، نوع شخص و لیست استان/شهر ایران به صورت دینامیک در ووکامرس
 * Version: 1.0
 * Author: Your Name
 * Text Domain: ccif-iran-checkout
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class CCIF_Iran_Checkout {

    public function __construct() {
        add_filter( 'woocommerce_checkout_fields', [ $this, 'modify_checkout_fields' ] );
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
            $cities[ $slug ] = array_column( $province['cities'], 'name' );
        }

        return [ 'states' => $states, 'cities' => $cities ];
    }

    /**
     * اصلاح فیلدهای صورتحساب ووکامرس
     */
    public function modify_checkout_fields( $fields ) {
        $billing = $fields['billing'];

        // درخواست صدور فاکتور
        $billing['billing_invoice_request'] = [
            'type'     => 'checkbox',
            'label'    => 'درخواست صدور فاکتور',
            'class'    => [ 'form-row-wide' ],
            'priority' => 50,
        ];

        // نوع شخص: حقیقی یا حقوقی
        $billing['billing_person_type'] = [
            'type'     => 'select',
            'label'    => 'نوع شخص',
            'class'    => [ 'form-row-first' ],
            'options'  => [
                ''      => 'انتخاب کنید',
                'real'  => 'حقیقی',
                'legal' => 'حقوقی',
            ],
            'priority' => 51,
        ];

        // کد ملی (حقیقی)
        $billing['billing_national_code'] = [
            'type'        => 'text',
            'label'       => 'کد ملی',
            'class'       => [ 'form-row-last', 'real-only' ],
            'placeholder' => '۱۰ رقم بدون خط تیره',
            'priority'    => 52,
        ];

        // نام شرکت (حقوقی)
        $billing['billing_company_name'] = [
            'type'        => 'text',
            'label'       => 'نام شرکت',
            'class'       => [ 'form-row-first', 'legal-only' ],
            'priority'    => 52,
        ];

        // شناسه ملی/اقتصادی (حقوقی)
        $billing['billing_economic_code'] = [
            'type'        => 'text',
            'label'       => 'شناسه ملی/اقتصادی',
            'class'       => [ 'form-row-last', 'legal-only' ],
            'priority'    => 53,
        ];

        // لیست استان‌ها (از JSON)
        $iran = $this->load_iran_data();
        $billing['billing_state']['type']     = 'select';
        $billing['billing_state']['label']    = 'استان';
        $billing['billing_state']['options']  = [ '' => 'انتخاب کنید' ] + $iran['states'];
        $billing['billing_state']['class']    = [ 'form-row-first' ];
        $billing['billing_state']['priority'] = 54;

        // لیست شهرها (ابتدا خالی و JS پر می‌کند)
        $billing['billing_city']['type']     = 'select';
        $billing['billing_city']['label']    = 'شهر';
        $billing['billing_city']['options']  = [ '' => 'ابتدا استان را انتخاب کنید' ];
        $billing['billing_city']['class']    = [ 'form-row-last' ];
        $billing['billing_city']['priority'] = 55;

        $fields['billing'] = $billing;
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
            '1.0',
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
            '1.0'
        );
    }
}

new CCIF_Iran_Checkout();