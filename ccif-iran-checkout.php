<?php
/**
 * Plugin Name: CCIF Iran Checkout (Final Patch)
 * Description: A plugin to customize the WooCommerce checkout form for Iran.
 * Version: 7.2
 * Author: Your Name
 * Text Domain: ccif-iran-checkout
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class CCIF_Iran_Checkout_Final_Patch {

    public function __construct() {
        add_filter( 'woocommerce_checkout_fields', [ $this, 'define_custom_fields' ], 1001 );
        add_action( 'woocommerce_before_checkout_billing_form', [ $this, 'render_custom_billing_form' ] );
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

    public function define_custom_fields( $fields ) {
        // This function defines all the fields we will use.
        // We will render them manually in `render_custom_billing_form`.
        // First, clear out ALL default billing fields.
        $fields['billing'] = [];

        $iran_data = $this->load_iran_data();

        // Now, add our fields back in the correct order
        $fields['billing']['billing_invoice_request'] = ['type' => 'checkbox', 'label' => 'درخواست صدور فاکتور رسمی', 'class' => ['form-row-wide', 'ccif-invoice-request-field']];
        $fields['billing']['billing_person_type'] = ['type' => 'select', 'label' => 'نوع شخص', 'class' => ['form-row-wide', 'ccif-person-field'], 'options' => ['' => 'انتخاب کنید', 'real' => 'حقیقی', 'legal' => 'حقوقی']];
        $fields['billing']['billing_first_name'] = ['label' => 'نام', 'class' => ['form-row-first', 'ccif-person-field', 'ccif-real-person-field']];
        $fields['billing']['billing_last_name'] = ['label' => 'نام خانوادگی', 'class' => ['form-row-last', 'ccif-person-field', 'ccif-real-person-field']];
        $fields['billing']['billing_national_code'] = ['label' => 'کد ملی', 'class' => ['form-row-wide', 'ccif-person-field', 'ccif-real-person-field'], 'placeholder' => '۱۰ رقم بدون خط تیره', 'custom_attributes' => ['pattern' => '[0-9]*', 'inputmode' => 'numeric']];
        $fields['billing']['billing_company_name'] = ['label' => 'نام شرکت', 'class' => ['form-row-first', 'ccif-person-field', 'ccif-legal-person-field']];
        $fields['billing']['billing_economic_code'] = ['label' => 'شناسه ملی/اقتصادی', 'class' => ['form-row-last', 'ccif-person-field', 'ccif-legal-person-field']];
        $fields['billing']['billing_agent_first_name'] = ['label' => 'نام نماینده', 'class' => ['form-row-first', 'ccif-person-field', 'ccif-legal-person-field']];
        $fields['billing']['billing_agent_last_name'] = ['label' => 'نام خانوادگی نماینده', 'class' => ['form-row-last', 'ccif-person-field', 'ccif-legal-person-field']];
        $fields['billing']['billing_state'] = ['type' => 'select', 'label' => 'استان', 'required' => true, 'class' => ['form-row-first', 'ccif-address-field'], 'options' => [ '' => 'انتخاب کنید' ] + $iran_data['states']];
        $fields['billing']['billing_city'] = ['type' => 'select', 'label' => 'شهر', 'required' => true, 'class' => ['form-row-last', 'ccif-address-field'], 'options' => [ '' => 'ابتدا استان را انتخاب کنید' ]];
        $fields['billing']['billing_address_1'] = ['label' => 'آدرس دقیق', 'required' => true, 'placeholder' => 'خیابان، کوچه، پلاک، واحد', 'class' => ['form-row-wide', 'ccif-address-field']];
        $fields['billing']['billing_postcode'] = ['label' => 'کد پستی', 'required' => true, 'type' => 'tel', 'class' => ['form-row-first', 'ccif-address-field'], 'custom_attributes' => ['pattern' => '[0-9]*', 'inputmode' => 'numeric']];
        $fields['billing']['billing_phone'] = ['label' => 'شماره تماس', 'required' => true, 'type' => 'tel', 'class' => ['form-row-last', 'ccif-address-field'], 'custom_attributes' => ['pattern' => '[0-9]*', 'inputmode' => 'numeric']];

        return $fields;
    }

    public function render_custom_billing_form( $checkout ) {
        $fields = $checkout->get_checkout_fields('billing');

        echo '<div class="ccif-checkout-form"><h3>اطلاعات صورتحساب</h3>';
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
        echo '</div>';
    }

    public function enqueue_assets() {
        if ( ! is_checkout() ) return;
        wp_enqueue_script( 'ccif-checkout-js', plugin_dir_url( __FILE__ ) . 'assets/js/ccif-checkout.js', ['jquery'], '7.2', true );
        wp_localize_script( 'ccif-checkout-js', 'ccifData', [ 'cities' => $this->load_iran_data()['cities'] ] );
        wp_enqueue_style( 'ccif-checkout-css', plugin_dir_url( __FILE__ ) . 'assets/css/ccif-checkout.css', [], '7.2' );
    }
}
new CCIF_Iran_Checkout_Final_Patch();
