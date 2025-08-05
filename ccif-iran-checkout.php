<?php
/**
 * Plugin Name: CCIF Iran Checkout
 * Description: افزودن فیلدهای صورتحساب شامل درخواست فاکتور، نوع شخص و لیست استان/شهر ایران به صورت دینامیک در ووکامرس
 * Version: 1.2
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
            $cities[ $slug ] = array_column( $province['cities'], 'name' );
        }

        return [ 'states' => $states, 'cities' => $cities ];
    }

    /**
     * اصلاح فیلدهای صورتحساب ووکامرس
     */
    public function modify_checkout_fields( $fields ) {
        // Start with a clean slate by removing all default billing fields
        // We will re-add the ones we need with custom settings
        unset(
            $fields['billing']['billing_first_name'],
            $fields['billing']['billing_last_name'],
            $fields['billing']['billing_company'],
            $fields['billing']['billing_country'],
            $fields['billing']['billing_address_1'],
            $fields['billing']['billing_address_2'],
            $fields['billing']['billing_postcode'],
            $fields['billing']['billing_phone'],
            $fields['billing']['billing_email'],
            $fields['billing']['billing_state'],
            $fields['billing']['billing_city']
        );

        $iran_data = $this->load_iran_data();

        // --- New Field Order ---

        // 1. Invoice Request Checkbox
        $fields['billing']['billing_invoice_request'] = [
            'type'     => 'checkbox',
            'label'    => 'درخواست صدور فاکتور رسمی',
            'class'    => ['form-row-wide'],
            'priority' => 10,
        ];

        // 2. Person Type (part of invoice section)
        $fields['billing']['billing_person_type'] = [
            'type'     => 'select',
            'label'    => 'نوع شخص',
            'class'    => ['form-row-wide', 'invoice-section'],
            'priority' => 20,
            'options'  => [
                ''      => 'انتخاب کنید',
                'real'  => 'حقیقی',
                'legal' => 'حقوقی',
            ],
        ];

        // 3a. Real Person Fields (part of invoice section)
        $fields['billing']['billing_first_name'] = [
            'label'       => 'نام',
            'class'       => ['form-row-first', 'invoice-section', 'real-person-field', 'invoice-required'],
            'priority'    => 30,
        ];
        $fields['billing']['billing_last_name'] = [
            'label'       => 'نام خانوادگی',
            'class'       => ['form-row-last', 'invoice-section', 'real-person-field', 'invoice-required'],
            'priority'    => 40,
        ];
        $fields['billing']['billing_national_code'] = [
            'label'       => 'کد ملی',
            'class'       => ['form-row-wide', 'invoice-section', 'real-person-field', 'invoice-required'],
            'placeholder' => '۱۰ رقم بدون خط تیره',
            'priority'    => 50,
        ];

        // 3b. Legal Person Fields (part of invoice section)
        $fields['billing']['billing_company_name'] = [
            'label'       => 'نام شرکت',
            'class'       => ['form-row-wide', 'invoice-section', 'legal-person-field', 'invoice-required'],
            'priority'    => 30,
        ];
        $fields['billing']['billing_economic_code'] = [
            'label'       => 'شناسه ملی/اقتصادی',
            'class'       => ['form-row-wide', 'invoice-section', 'legal-person-field', 'invoice-required'],
            'priority'    => 40,
        ];
        $fields['billing']['billing_agent_first_name'] = [
            'label'       => 'نام نماینده',
            'class'       => ['form-row-first', 'invoice-section', 'legal-person-field', 'invoice-required'],
            'priority'    => 50,
        ];
        $fields['billing']['billing_agent_last_name'] = [
            'label'       => 'نام خانوادگی نماینده',
            'class'       => ['form-row-last', 'invoice-section', 'legal-person-field', 'invoice-required'],
            'priority'    => 60,
        ];

        // 4. Address Fields (Always Visible)
        $fields['billing']['billing_state'] = [
            'type'      => 'select',
            'label'     => 'استان',
            'required'  => true,
            'class'     => ['form-row-first'],
            'priority'  => 70,
            'options'   => [ '' => 'انتخاب کنید' ] + $iran_data['states'],
        ];
        $fields['billing']['billing_city'] = [
            'type'      => 'select',
            'label'     => 'شهر',
            'required'  => true,
            'class'     => ['form-row-last'],
            'priority'  => 80,
            'options'   => [ '' => 'ابتدا استان را انتخاب کنید' ],
        ];
        $fields['billing']['billing_address_1'] = [
            'label'       => 'آدرس دقیق',
            'required'    => true,
            'placeholder' => 'خیابان، کوچه، پلاک، واحد',
            'class'       => ['form-row-wide'],
            'priority'    => 90,
        ];
        $fields['billing']['billing_postcode'] = [
            'label'       => 'کد پستی',
            'required'    => true,
            'class'       => ['form-row-first'],
            'priority'    => 100,
        ];
        $fields['billing']['billing_phone'] = [
            'label'       => 'شماره تماس',
            'required'    => true,
            'type'        => 'tel',
            'class'       => ['form-row-last'],
            'priority'    => 110,
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
            '1.2', // Version bump
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
            '1.2' // Version bump
        );
    }
}

new CCIF_Iran_Checkout();
