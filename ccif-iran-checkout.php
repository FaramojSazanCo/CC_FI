<?php
/**
 * Plugin Name: CCIF Iran Checkout
 * Description: A plugin to customize the WooCommerce checkout form for Iran.
 * Version: 5.0
 * Author: Your Name
 * Text Domain: ccif-iran-checkout
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class CCIF_Iran_Checkout {

    public function __construct() {
        add_filter( 'woocommerce_checkout_fields', [ $this, 'setup_checkout_fields' ], 1001 );
        add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_assets' ] );
    }

    private function load_iran_data() {
        $json_file = plugin_dir_path( __FILE__ ) . 'assets/data/iran-cities.json';
        if ( ! file_exists( $json_file ) ) return [ 'states' => [], 'cities' => [] ];
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

    public function setup_checkout_fields( $fields ) {
        $iran_data = $this->load_iran_data();

        // Unset original fields we want to remove completely
        unset(
            $fields['billing']['billing_company'],
            $fields['billing']['billing_address_2']
        );

        // --- Define Field Groups & Order ---

        // Group 1: Invoice Request
        $fields['billing']['billing_invoice_request'] = [
            'type'      => 'checkbox',
            'label'     => 'درخواست صدور فاکتور رسمی',
            'class'     => ['form-row-wide', 'ccif-invoice-request-field'],
            'priority'  => 10,
        ];

        // Group 2: Person/Company Info
        $fields['billing']['billing_person_type'] = [
            'type'      => 'select',
            'label'     => 'نوع شخص',
            'class'     => ['form-row-wide', 'ccif-person-field'],
            'priority'  => 20,
            'required'  => true,
            'options'   => ['' => 'انتخاب کنید', 'real' => 'حقیقی', 'legal' => 'حقوقی'],
        ];

        // Real Person Fields
        $fields['billing']['billing_first_name']['class'] = ['form-row-first', 'ccif-person-field', 'ccif-real-person-field'];
        $fields['billing']['billing_first_name']['priority'] = 30;
        $fields['billing']['billing_last_name']['class'] = ['form-row-last', 'ccif-person-field', 'ccif-real-person-field'];
        $fields['billing']['billing_last_name']['priority'] = 40;
        $fields['billing']['billing_national_code'] = ['label' => 'کد ملی', 'class' => ['form-row-wide', 'ccif-person-field', 'ccif-real-person-field'], 'priority' => 50, 'placeholder' => '۱۰ رقم بدون خط تیره'];

        // Legal Person Fields
        $fields['billing']['billing_company_name'] = ['label' => 'نام شرکت', 'class' => ['form-row-first', 'ccif-person-field', 'ccif-legal-person-field'], 'priority' => 30];
        $fields['billing']['billing_economic_code'] = ['label' => 'شناسه ملی/اقتصادی', 'class' => ['form-row-last', 'ccif-person-field', 'ccif-legal-person-field'], 'priority' => 40];
        $fields['billing']['billing_agent_first_name'] = ['label' => 'نام نماینده', 'class' => ['form-row-first', 'ccif-person-field', 'ccif-legal-person-field'], 'priority' => 50];
        $fields['billing']['billing_agent_last_name'] = ['label' => 'نام خانوادگی نماینده', 'class' => ['form-row-last', 'ccif-person-field', 'ccif-legal-person-field'], 'priority' => 60];

        // Group 3: Address Info
        $fields['billing']['billing_state']['class'] = ['form-row-first', 'ccif-address-field'];
        $fields['billing']['billing_state']['priority'] = 70;
        $fields['billing']['billing_state']['options'] = [ '' => 'انتخاب کنید' ] + $iran_data['states'];
        $fields['billing']['billing_state']['required'] = true;
        $fields['billing']['billing_city']['class'] = ['form-row-last', 'ccif-address-field'];
        $fields['billing']['billing_city']['priority'] = 80;
        $fields['billing']['billing_city']['options'] = [ '' => 'ابتدا استان را انتخاب کنید' ];
        $fields['billing']['billing_city']['required'] = true;
        $fields['billing']['billing_address_1']['label'] = 'آدرس دقیق';
        $fields['billing']['billing_address_1']['placeholder'] = 'خیابان، کوچه، پلاک، واحد';
        $fields['billing']['billing_address_1']['class'] = ['form-row-wide', 'ccif-address-field'];
        $fields['billing']['billing_address_1']['priority'] = 90;
        $fields['billing']['billing_address_1']['required'] = true;
        $fields['billing']['billing_postcode']['class'] = ['form-row-first', 'ccif-address-field'];
        $fields['billing']['billing_postcode']['type'] = 'tel';
        $fields['billing']['billing_postcode']['priority'] = 100;
        $fields['billing']['billing_postcode']['required'] = true;
        $fields['billing']['billing_phone']['class'] = ['form-row-last', 'ccif-address-field'];
        $fields['billing']['billing_phone']['type'] = 'tel';
        $fields['billing']['billing_phone']['priority'] = 110;
        $fields['billing']['billing_phone']['required'] = true;

        return $fields;
    }

    public function enqueue_assets() {
        if ( ! is_checkout() ) return;
        wp_enqueue_script( 'ccif-checkout-js', plugin_dir_url( __FILE__ ) . 'assets/js/ccif-checkout.js', ['jquery'], '5.0', true );
        wp_localize_script( 'ccif-checkout-js', 'ccifData', [ 'cities' => $this->load_iran_data()['cities'] ] );
        wp_enqueue_style( 'ccif-checkout-css', plugin_dir_url( __FILE__ ) . 'assets/css/ccif-checkout.css', [], '5.0' );
    }
}

new CCIF_Iran_Checkout();
