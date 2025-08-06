<?php
/**
 * Plugin Name: CCIF Iran Checkout (Final)
 * Description: A plugin to customize the WooCommerce checkout form for Iran.
 * Version: 9.0
 * Author: Your Name
 * Text Domain: ccif-iran-checkout
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class CCIF_Iran_Checkout_Final {

    public function __construct() {
        add_filter( 'woocommerce_checkout_fields', [ $this, 'modify_and_define_fields' ], 1001 );
        add_action( 'woocommerce_before_checkout_billing_form', [ $this, 'render_custom_form_wrapper_start' ], 5 );
        add_action( 'woocommerce_after_checkout_billing_form', [ $this, 'render_custom_form_wrapper_end' ], 15 );
        add_action( 'wp', function() {
            if ( is_checkout() && ! is_wc_endpoint_url() ) {
                remove_action( 'woocommerce_checkout_billing', [ WC()->checkout, 'checkout_form_billing' ] );
            }
        });
        add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_assets' ] );
    }

    private function load_iran_data() {
        $json_file = plugin_dir_path( __FILE__ ) . 'assets/data/iran-cities.json';
        if ( ! file_exists( $json_file ) ) return [ 'states' => [], 'cities' => [] ];
        $data = json_decode( file_get_contents( $json_file ), true );
        $states = []; $cities = [];
        foreach ( $data as $province ) {
            $slug = sanitize_title( $province['name'] );
            $states[ $slug ] = $province['name'];
            $cities[ $slug ] = array_column( $province['cities'], 'name' );
        }
        return [ 'states' => $states, 'cities' => $cities ];
    }

    public function modify_and_define_fields( $fields ) {
        $iran_data = $this->load_iran_data();

        // Unset the few default fields we absolutely don't want.
        unset(
            $fields['billing']['billing_company'], // We have our own company name field
            $fields['billing']['billing_address_2'], // Not needed
            $fields['billing']['billing_country'] // We are only selling to Iran
        );

        // --- Define our custom fields and override existing ones ---
        $custom_fields = [];
        $custom_fields['billing_invoice_request'] = ['type' => 'checkbox', 'label' => 'درخواست صدور فاکتور رسمی', 'class' => ['form-row-wide', 'ccif-invoice-request-field']];
        $custom_fields['billing_person_type'] = ['type' => 'select', 'label' => 'نوع شخص', 'class' => ['form-row-wide', 'ccif-person-field'], 'options' => ['' => 'انتخاب کنید', 'real' => 'حقیقی', 'legal' => 'حقوقی']];
        $custom_fields['billing_first_name'] = $fields['billing']['billing_first_name'];
        $custom_fields['billing_first_name']['class'] = ['form-row-first', 'ccif-person-field', 'ccif-real-person-field'];
        $custom_fields['billing_last_name'] = $fields['billing']['billing_last_name'];
        $custom_fields['billing_last_name']['class'] = ['form-row-last', 'ccif-person-field', 'ccif-real-person-field'];
        $custom_fields['billing_national_code'] = ['label' => 'کد ملی', 'class' => ['form-row-wide', 'ccif-person-field', 'ccif-real-person-field'], 'placeholder' => '۱۰ رقم بدون خط تیره', 'custom_attributes' => ['pattern' => '[0-9]*', 'inputmode' => 'numeric']];
        $custom_fields['billing_company_name'] = ['label' => 'نام شرکت', 'class' => ['form-row-first', 'ccif-person-field', 'ccif-legal-person-field']];
        $custom_fields['billing_economic_code'] = ['label' => 'شناسه ملی/اقتصادی', 'class' => ['form-row-last', 'ccif-person-field', 'ccif-legal-person-field']];
        $custom_fields['billing_agent_first_name'] = ['label' => 'نام نماینده', 'class' => ['form-row-first', 'ccif-person-field', 'ccif-legal-person-field']];
        $custom_fields['billing_agent_last_name'] = ['label' => 'نام خانوادگی نماینده', 'class' => ['form-row-last', 'ccif-person-field', 'ccif-legal-person-field']];
        $custom_fields['billing_state'] = $fields['billing']['billing_state'];
        $custom_fields['billing_state']['class'] = ['form-row-first', 'ccif-address-field'];
        $custom_fields['billing_state']['options'] = [ '' => 'انتخاب کنید' ] + $iran_data['states'];
        $custom_fields['billing_city'] = $fields['billing']['billing_city'];
        $custom_fields['billing_city']['class'] = ['form-row-last', 'ccif-address-field'];
        $custom_fields['billing_city']['options'] = [ '' => 'ابتدا استان را انتخاب کنید' ];
        $custom_fields['billing_address_1'] = $fields['billing']['billing_address_1'];
        $custom_fields['billing_address_1']['label'] = 'آدرس دقیق';
        $custom_fields['billing_address_1']['placeholder'] = 'خیابان، کوچه، پلاک، واحد';
        $custom_fields['billing_address_1']['class'] = ['form-row-wide', 'ccif-address-field'];
        $custom_fields['billing_postcode'] = $fields['billing']['billing_postcode'];
        $custom_fields['billing_postcode']['class'] = ['form-row-first', 'ccif-address-field'];
        $custom_fields['billing_postcode']['custom_attributes'] = ['pattern' => '[0-9]*', 'inputmode' => 'numeric'];
        $custom_fields['billing_phone'] = $fields['billing']['billing_phone'];
        $custom_fields['billing_phone']['class'] = ['form-row-last', 'ccif-address-field'];
        $custom_fields['billing_phone']['custom_attributes'] = ['pattern' => '[0-9]*', 'inputmode' => 'numeric'];

        $fields['billing'] = $custom_fields;
        return $fields;
    }

    public function render_custom_form_wrapper_start($checkout) {
        $fields = $checkout->get_checkout_fields('billing');
        echo '<div class="ccif-checkout-form">';
        echo '<div class="ccif-box invoice-request-box">';
        woocommerce_form_field('billing_invoice_request', $fields['billing_invoice_request'], $checkout->get_value('billing_invoice_request'));
        echo '</div>';
        echo '<div class="ccif-box person-info-box"><h2>اطلاعات خریدار</h2>';
        woocommerce_form_field('billing_person_type', $fields['billing_person_type'], $checkout->get_value('billing_person_type'));
        echo '<div class="ccif-real-person-fields-wrapper">';
        woocommerce_form_field('billing_first_name', $fields['billing_first_name'], $checkout->get_value('billing_first_name'));
        woocommerce_form_field('billing_last_name', $fields['billing_last_name'], $checkout->get_value('billing_last_name'));
        woocommerce_form_field('billing_national_code', $fields['billing_national_code'], $checkout->get_value('billing_national_code'));
        echo '</div>';
        echo '<div class="ccif-legal-person-fields-wrapper">';
        woocommerce_form_field('billing_company_name', $fields['billing_company_name'], $checkout->get_value('billing_company_name'));
        woocommerce_form_field('billing_economic_code', $fields['billing_economic_code'], $checkout->get_value('billing_economic_code'));
        woocommerce_form_field('billing_agent_first_name', $fields['billing_agent_first_name'], $checkout->get_value('billing_agent_first_name'));
        woocommerce_form_field('billing_agent_last_name', $fields['billing_agent_last_name'], $checkout->get_value('billing_agent_last_name'));
        echo '</div></div>';
        echo '<div class="ccif-box address-info-box"><h2>اطلاعات ارسال</h2>';
        woocommerce_form_field('billing_state', $fields['billing_state'], $checkout->get_value('billing_state'));
        woocommerce_form_field('billing_city', $fields['billing_city'], $checkout->get_value('billing_city'));
        woocommerce_form_field('billing_address_1', $fields['billing_address_1'], $checkout->get_value('billing_address_1'));
        woocommerce_form_field('billing_postcode', $fields['billing_postcode'], $checkout->get_value('billing_postcode'));
        woocommerce_form_field('billing_phone', $fields['billing_phone'], $checkout->get_value('billing_phone'));
        echo '</div>';
    }

    public function render_custom_form_wrapper_end() {
        echo '</div>';
    }

    public function enqueue_assets() {
        if ( ! is_checkout() ) return;
        wp_enqueue_script( 'ccif-checkout-js', plugin_dir_url( __FILE__ ) . 'assets/js/ccif-checkout.js', ['jquery'], '9.0', true );
        wp_localize_script( 'ccif-checkout-js', 'ccifData', [ 'cities' => $this->load_iran_data()['cities'] ] );
        wp_enqueue_style( 'ccif-checkout-css', plugin_dir_url( __FILE__ ) . 'assets/css/ccif-checkout.css', [], '9.0' );
    }
}
new CCIF_Iran_Checkout_Final();
