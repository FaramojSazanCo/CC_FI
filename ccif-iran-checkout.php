<?php
/**
 * Plugin Name: CCIF Iran Checkout
 * Description: افزودن فیلدهای صورتحساب شامل درخواست فاکتور، نوع شخص و لیست استان/شهر ایران به صورت دینامیک در ووکامرس
 * Version: 2.0
 * Author: Your Name
 * Text Domain: ccif-iran-checkout
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class CCIF_Iran_Checkout {

    public function __construct() {
        // Use a high priority to ensure our changes run last
        add_filter( 'woocommerce_checkout_fields', [ $this, 'restructure_checkout_fields' ], 1001 );
        add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_assets' ] );
    }

    private function load_iran_data() {
        $json_file = plugin_dir_path( __FILE__ ) . 'assets/data/iran-cities.json';
        if ( ! file_exists( $json_file ) ) {
            return [ 'states' => [], 'cities' => [] ];
        }
        $data = json_decode( file_get_contents( $json_file ), true );
        $states = [];
        $cities = [];
        foreach ( $data as $province ) {
            $slug = sanitize_title( $province['name'] );
            $states[ $slug ] = $province['name'];
            $cities[ $slug ] = array_column( $province['cities'], 'name' );
        }
        return [ 'states' => $states, 'cities' => $cities ];
    }

    public function restructure_checkout_fields( $fields ) {
        $iran_data = $this->load_iran_data();

        // Define the new structure for billing fields
        $new_billing_fields = [];

        // --- Person & Invoice Section ---

        // 1. Person Type (Always visible)
        $new_billing_fields['billing_person_type'] = [
            'type'      => 'select',
            'label'     => 'نوع شخص',
            'class'     => ['form-row-wide'],
            'priority'  => 10,
            'options'   => [
                ''      => 'انتخاب کنید',
                'real'  => 'حقیقی',
                'legal' => 'حقوقی',
            ],
            'required'  => true, // Always required to choose a type
        ];

        // 2a. Real Person Fields
        $new_billing_fields['billing_first_name'] = [
            'label'     => 'نام',
            'class'     => ['form-row-first', 'real-person-field', 'invoice-required'],
            'priority'  => 20,
        ];
        $new_billing_fields['billing_last_name'] = [
            'label'     => 'نام خانوادگی',
            'class'     => ['form-row-last', 'real-person-field', 'invoice-required'],
            'priority'  => 30,
        ];
        $new_billing_fields['billing_national_code'] = [
            'label'     => 'کد ملی',
            'class'     => ['form-row-wide', 'real-person-field', 'invoice-required'],
            'priority'  => 40,
            'placeholder' => '۱۰ رقم بدون خط تیره',
        ];

        // 2b. Legal Person Fields
        $new_billing_fields['billing_company_name'] = [
            'label'     => 'نام شرکت',
            'class'     => ['form-row-wide', 'legal-person-field', 'invoice-required'],
            'priority'  => 20,
        ];
        $new_billing_fields['billing_economic_code'] = [
            'label'     => 'شناسه ملی/اقتصادی',
            'class'     => ['form-row-wide', 'legal-person-field', 'invoice-required'],
            'priority'  => 30,
        ];
        $new_billing_fields['billing_agent_first_name'] = [
            'label'     => 'نام نماینده',
            'class'     => ['form-row-first', 'legal-person-field', 'invoice-required'],
            'priority'  => 40,
        ];
        $new_billing_fields['billing_agent_last_name'] = [
            'label'     => 'نام خانوادگی نماینده',
            'class'     => ['form-row-last', 'legal-person-field', 'invoice-required'],
            'priority'  => 50,
        ];

        // 3. Invoice Request Checkbox
        $new_billing_fields['billing_invoice_request'] = [
            'type'     => 'checkbox',
            'label'    => 'درخواست صدور فاکتور رسمی',
            'class'    => ['form-row-wide'],
            'priority' => 60,
        ];

        // --- Address Section (Always visible and required) ---

        $new_billing_fields['billing_state'] = [
            'type'      => 'select',
            'label'     => 'استان',
            'required'  => true,
            'class'     => ['form-row-first'],
            'priority'  => 70,
            'options'   => [ '' => 'انتخاب کنید' ] + $iran_data['states'],
        ];
        $new_billing_fields['billing_city'] = [
            'type'      => 'select',
            'label'     => 'شهر',
            'required'  => true,
            'class'     => ['form-row-last'],
            'priority'  => 80,
            'options'   => [ '' => 'ابتدا استان را انتخاب کنید' ],
        ];
        $new_billing_fields['billing_address_1'] = [
            'label'       => 'آدرس دقیق',
            'required'    => true,
            'placeholder' => 'خیابان، کوچه، پلاک، واحد',
            'class'       => ['form-row-wide'],
            'priority'    => 90,
        ];
        $new_billing_fields['billing_postcode'] = [
            'label'       => 'کد پستی',
            'required'    => true,
            'type'        => 'tel',
            'class'       => ['form-row-first'],
            'priority'    => 100,
        ];
        $new_billing_fields['billing_phone'] = [
            'label'       => 'شماره تماس',
            'required'    => true,
            'type'        => 'tel',
            'class'       => ['form-row-last'],
            'priority'    => 110,
        ];

        // Replace the original billing fields with our new structure
        $fields['billing'] = $new_billing_fields;

        // We don't need the shipping fields, so let's make sure they are gone
        unset($fields['shipping']);

        return $fields;
    }

    public function enqueue_assets() {
        if ( ! is_checkout() ) {
            return;
        }
        wp_enqueue_script(
            'ccif-checkout-js',
            plugin_dir_url( __FILE__ ) . 'assets/js/ccif-checkout.js',
            [ 'jquery' ],
            '2.0', // Version bump
            true
        );
        $iran = $this->load_iran_data();
        wp_localize_script(
            'ccif-checkout-js',
            'ccifData',
            [ 'cities' => $iran['cities'] ]
        );
        wp_enqueue_style(
            'ccif-checkout-css',
            plugin_dir_url( __FILE__ ) . 'assets/css/ccif-checkout.css',
            [],
            '2.0' // Version bump
        );
    }
}

new CCIF_Iran_Checkout();
