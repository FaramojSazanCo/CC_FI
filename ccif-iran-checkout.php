<?php
/**
 * Plugin Name: CCIF Iran Checkout
 * Description: افزودن فیلدهای صورتحساب شامل درخواست فاکتور، نوع شخص و لیست استان/شهر ایران به صورت دینامیک در ووکامرس
 * Version: 4.0
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

        // Unset original fields we want to redefine or remove
        unset(
            $fields['billing']['billing_company'],
            $fields['billing']['billing_address_2']
        );

        // --- Redefine and Reorder All Fields ---

        // Invoice Request Checkbox
        $fields['billing']['billing_invoice_request'] = [
            'type'      => 'checkbox',
            'label'     => 'درخواست صدور فاکتور رسمی',
            'class'     => ['form-row-wide', 'ccif-invoice-request'],
            'priority'  => 5,
        ];

        // Person/Company Fields
        $fields['billing']['billing_person_type'] = [
            'type'      => 'select',
            'label'     => 'نوع شخص',
            'class'     => ['form-row-wide', 'ccif-person-type', 'ccif-invoice-field'],
            'priority'  => 10,
            'required'  => false, // JS controls this
            'options'   => ['' => 'انتخاب کنید', 'real' => 'حقیقی', 'legal' => 'حقوقی'],
        ];
        // Modify existing fields
        $fields['billing']['billing_first_name']['class'] = ['form-row-first', 'ccif-real-person-field', 'ccif-invoice-field'];
        $fields['billing']['billing_first_name']['priority'] = 20;
        $fields['billing']['billing_last_name']['class'] = ['form-row-last', 'ccif-real-person-field', 'ccif-invoice-field'];
        $fields['billing']['billing_last_name']['priority'] = 30;

        // Add new fields
        $fields['billing']['billing_national_code'] = ['label' => 'کد ملی', 'placeholder' => '۱۰ رقم بدون خط تیره', 'class' => ['form-row-wide', 'ccif-real-person-field', 'ccif-invoice-field'], 'priority' => 40];
        $fields['billing']['billing_company_name'] = ['label' => 'نام شرکت', 'class' => ['form-row-wide', 'ccif-legal-person-field', 'ccif-invoice-field'], 'priority' => 20];
        $fields['billing']['billing_economic_code'] = ['label' => 'شناسه ملی/اقتصادی', 'class' => ['form-row-wide', 'ccif-legal-person-field', 'ccif-invoice-field'], 'priority' => 30];
        $fields['billing']['billing_agent_first_name'] = ['label' => 'نام نماینده', 'class' => ['form-row-first', 'ccif-legal-person-field', 'ccif-invoice-field'], 'priority' => 40];
        $fields['billing']['billing_agent_last_name'] = ['label' => 'نام خانوادگی نماینده', 'class' => ['form-row-last', 'ccif-legal-person-field', 'ccif-invoice-field'], 'priority' => 50];

        // Address Fields
        $fields['billing']['billing_state']['class'] = ['form-row-first', 'ccif-address-field'];
        $fields['billing']['billing_state']['priority'] = 60;
        $fields['billing']['billing_state']['options'] = [ '' => 'انتخاب کنید' ] + $iran_data['states'];
        $fields['billing']['billing_city']['class'] = ['form-row-last', 'ccif-address-field'];
        $fields['billing']['billing_city']['priority'] = 70;
        $fields['billing']['billing_city']['options'] = [ '' => 'ابتدا استان را انتخاب کنید' ];
        $fields['billing']['billing_address_1']['label'] = 'آدرس دقیق';
        $fields['billing']['billing_address_1']['placeholder'] = 'خیابان، کوچه، پلاک، واحد';
        $fields['billing']['billing_address_1']['class'] = ['form-row-wide', 'ccif-address-field'];
        $fields['billing']['billing_address_1']['priority'] = 80;
        $fields['billing']['billing_postcode']['class'] = ['form-row-first', 'ccif-address-field'];
        $fields['billing']['billing_postcode']['type'] = 'tel';
        $fields['billing']['billing_postcode']['priority'] = 90;
        $fields['billing']['billing_phone']['class'] = ['form-row-last', 'ccif-address-field'];
        $fields['billing']['billing_phone']['type'] = 'tel';
        $fields['billing']['billing_phone']['priority'] = 100;

        // All address fields are always required
        foreach ($fields['billing'] as $key => $field) {
            if (in_array('ccif-address-field', $field['class'] ?? [])) {
                $fields['billing'][$key]['required'] = true;
            }
        }

        return $fields;
    }

    public function enqueue_assets() {
        if ( ! is_checkout() ) return;
        wp_enqueue_script( 'ccif-checkout-js', plugin_dir_url( __FILE__ ) . 'assets/js/ccif-checkout.js', ['jquery'], '4.0', true );
        wp_localize_script( 'ccif-checkout-js', 'ccifData', [ 'cities' => $this->load_iran_data()['cities'] ] );
        wp_enqueue_style( 'ccif-checkout-css', plugin_dir_url( __FILE__ ) . 'assets/css/ccif-checkout.css', [], '4.0' );
    }
}

new CCIF_Iran_Checkout();
