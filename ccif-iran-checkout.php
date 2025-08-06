<?php
/**
 * Plugin Name: CCIF Iran Checkout
 * Description: افزودن فیلدهای صورتحساب شامل درخواست فاکتور، نوع شخص و لیست استان/شهر ایران به صورت دینامیک در ووکامرس
 * Version: 3.0
 * Author: Your Name
 * Text Domain: ccif-iran-checkout
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class CCIF_Iran_Checkout {

    public function __construct() {
        // Define fields with high priority
        add_filter( 'woocommerce_checkout_fields', [ $this, 'define_checkout_fields' ], 1001 );

        // Override the default billing form rendering
        remove_action( 'woocommerce_checkout_billing', [ WC()->checkout(), 'checkout_form_billing' ] );
        add_action( 'woocommerce_checkout_billing', [ $this, 'custom_render_billing_form' ] );

        // Enqueue scripts and styles
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

    public function define_checkout_fields( $fields ) {
        $iran_data = $this->load_iran_data();

        // Unset all original billing fields to prevent them from appearing elsewhere
        $fields['billing'] = [];

        // --- Define All Fields Here ---

        // Invoice Box Fields
        $fields['billing']['billing_invoice_request'] = [
            'type'  => 'checkbox',
            'label' => 'درخواست صدور فاکتور رسمی',
            'class' => ['form-row-wide'],
        ];

        // Person/Company Box Fields
        $fields['billing']['billing_person_type'] = [
            'type'      => 'select', 'label' => 'نوع شخص', 'required' => true,
            'class'     => ['form-row-wide', 'invoice-field'],
            'options'   => ['' => 'انتخاب کنید', 'real' => 'حقیقی', 'legal' => 'حقوقی'],
        ];
        $fields['billing']['billing_first_name'] = ['label' => 'نام', 'class' => ['form-row-first', 'invoice-field', 'real-person-field']];
        $fields['billing']['billing_last_name'] = ['label' => 'نام خانوادگی', 'class' => ['form-row-last', 'invoice-field', 'real-person-field']];
        $fields['billing']['billing_national_code'] = ['label' => 'کد ملی', 'placeholder' => '۱۰ رقم بدون خط تیره', 'class' => ['form-row-wide', 'invoice-field', 'real-person-field']];
        $fields['billing']['billing_company_name'] = ['label' => 'نام شرکت', 'class' => ['form-row-wide', 'invoice-field', 'legal-person-field']];
        $fields['billing']['billing_economic_code'] = ['label' => 'شناسه ملی/اقتصادی', 'class' => ['form-row-wide', 'invoice-field', 'legal-person-field']];
        $fields['billing']['billing_agent_first_name'] = ['label' => 'نام نماینده', 'class' => ['form-row-first', 'invoice-field', 'legal-person-field']];
        $fields['billing']['billing_agent_last_name'] = ['label' => 'نام خانوادگی نماینده', 'class' => ['form-row-last', 'invoice-field', 'legal-person-field']];

        // Address Box Fields
        $fields['billing']['billing_state'] = ['type' => 'select', 'label' => 'استان', 'required' => true, 'class' => ['form-row-first', 'address-field'], 'options' => [ '' => 'انتخاب کنید' ] + $iran_data['states']];
        $fields['billing']['billing_city'] = ['type' => 'select', 'label' => 'شهر', 'required' => true, 'class' => ['form-row-last', 'address-field'], 'options' => [ '' => 'ابتدا استان را انتخاب کنید' ]];
        $fields['billing']['billing_address_1'] = ['label' => 'آدرس دقیق', 'required' => true, 'placeholder' => 'خیابان، کوچه، پلاک، واحد', 'class' => ['form-row-wide', 'address-field']];
        $fields['billing']['billing_postcode'] = ['label' => 'کد پستی', 'required' => true, 'type' => 'tel', 'class' => ['form-row-first', 'address-field']];
        $fields['billing']['billing_phone'] = ['label' => 'شماره تماس', 'required' => true, 'type' => 'tel', 'class' => ['form-row-last', 'address-field']];

        return $fields;
    }

    public function custom_render_billing_form( $checkout ) {
        $fields = $checkout->get_checkout_fields( 'billing' );

        echo '<div class="ccif-checkout-form">';

        // Box 1: Invoice Request
        echo '<div class="ccif-box invoice-request-box">';
        woocommerce_form_field( 'billing_invoice_request', $fields['billing_invoice_request'], $checkout->get_value( 'billing_invoice_request' ) );
        echo '</div>';

        // Box 2: Person/Company Info
        echo '<div class="ccif-box person-info-box">';
        echo '<h2>اطلاعات شخص/شرکت</h2>';
        woocommerce_form_field( 'billing_person_type', $fields['billing_person_type'], $checkout->get_value( 'billing_person_type' ) );
        woocommerce_form_field( 'billing_company_name', $fields['billing_company_name'], $checkout->get_value( 'billing_company_name' ) );
        woocommerce_form_field( 'billing_economic_code', $fields['billing_economic_code'], $checkout->get_value( 'billing_economic_code' ) );
        woocommerce_form_field( 'billing_first_name', $fields['billing_first_name'], $checkout->get_value( 'billing_first_name' ) );
        woocommerce_form_field( 'billing_last_name', $fields['billing_last_name'], $checkout->get_value( 'billing_last_name' ) );
        woocommerce_form_field( 'billing_national_code', $fields['billing_national_code'], $checkout->get_value( 'billing_national_code' ) );
        woocommerce_form_field( 'billing_agent_first_name', $fields['billing_agent_first_name'], $checkout->get_value( 'billing_agent_first_name' ) );
        woocommerce_form_field( 'billing_agent_last_name', $fields['billing_agent_last_name'], $checkout->get_value( 'billing_agent_last_name' ) );
        echo '</div>';

        // Box 3: Address Info
        echo '<div class="ccif-box address-info-box">';
        echo '<h2>اطلاعات آدرس</h2>';
        woocommerce_form_field( 'billing_state', $fields['billing_state'], $checkout->get_value( 'billing_state' ) );
        woocommerce_form_field( 'billing_city', $fields['billing_city'], $checkout->get_value( 'billing_city' ) );
        woocommerce_form_field( 'billing_address_1', $fields['billing_address_1'], $checkout->get_value( 'billing_address_1' ) );
        woocommerce_form_field( 'billing_postcode', $fields['billing_postcode'], $checkout->get_value( 'billing_postcode' ) );
        woocommerce_form_field( 'billing_phone', $fields['billing_phone'], $checkout->get_value( 'billing_phone' ) );
        echo '</div>';

        echo '</div>';
    }

    public function enqueue_assets() {
        if ( ! is_checkout() ) return;
        wp_enqueue_script( 'ccif-checkout-js', plugin_dir_url( __FILE__ ) . 'assets/js/ccif-checkout.js', ['jquery'], '3.0', true );
        wp_localize_script( 'ccif-checkout-js', 'ccifData', [ 'cities' => $this->load_iran_data()['cities'] ] );
        wp_enqueue_style( 'ccif-checkout-css', plugin_dir_url( __FILE__ ) . 'assets/css/ccif-checkout.css', [], '3.0' );
    }
}

new CCIF_Iran_Checkout();
