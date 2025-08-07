<?php
/**
 * Plugin Name: CCIF Iran Checkout (Fresh Rebuild)
 * Description: A plugin to customize the WooCommerce checkout form for Iran.
 * Version: 6.0
 * Author: Your Name
 * Text Domain: ccif-iran-checkout
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define( 'CCIF_IRAN_CHECKOUT_VERSION', '6.0' );

class CCIF_Iran_Checkout_Rebuild {

    private $iran_data = null;

    public function __construct() {
        // Hook into WooCommerce to render our custom form.
        // The priority of 5 on 'before_checkout_billing_form' is to ensure it runs before the default form is rendered.
        add_action( 'woocommerce_before_checkout_billing_form', [ $this, 'output_custom_billing_form_start' ], 5 );
        add_action( 'woocommerce_after_checkout_billing_form', [ $this, 'output_custom_billing_form_end' ] );

        // Remove the default WooCommerce billing form by returning false to the filter.
        add_filter('woocommerce_checkout_billing', '__return_false');

        // Enqueue scripts and styles
        add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_assets' ] );
    }

    private function load_iran_data() {
        if ( $this->iran_data !== null ) {
            return $this->iran_data;
        }

        $json_file = plugin_dir_path( __FILE__ ) . 'assets/data/iran-cities.json';
        if ( ! file_exists( $json_file ) ) {
            $this->iran_data = [ 'states' => [], 'cities' => [] ];
            return $this->iran_data;
        }

        $data = json_decode( file_get_contents( $json_file ), true );
        $states = [];
        $cities = [];
        foreach ( $data as $province ) {
            $slug = sanitize_title( $province['name'] );
            $states[ $slug ] = $province['name'];
            $cities[ $slug ] = array_column( $province['cities'], 'name' );
        }
        $this->iran_data = [ 'states' => $states, 'cities' => $cities ];
        return $this->iran_data;
    }

    public function get_all_checkout_fields() {
        $iran_data = $this->load_iran_data();
        $fields = [];

        // --- Define All Fields ---
        $fields['billing_invoice_request'] = ['type' => 'checkbox', 'label' => __( 'Request official invoice', 'ccif-iran-checkout' ), 'class' => ['form-row-wide', 'ccif-invoice-request-field']];

        $fields['billing_person_type'] = ['type' => 'select', 'label' => __( 'Person Type', 'ccif-iran-checkout' ), 'class' => ['form-row-wide', 'ccif-person-field'], 'options' => ['' => __( 'Select', 'ccif-iran-checkout' ), 'real' => __( 'Real', 'ccif-iran-checkout' ), 'legal' => __( 'Legal', 'ccif-iran-checkout' )]];

        $fields['billing_first_name'] = ['label' => __( 'First Name', 'ccif-iran-checkout' ), 'class' => ['form-row-first', 'ccif-person-field', 'ccif-real-person-field']];
        $fields['billing_last_name'] = ['label' => __( 'Last Name', 'ccif-iran-checkout' ), 'class' => ['form-row-last', 'ccif-person-field', 'ccif-real-person-field']];
        $fields['billing_national_code'] = ['label' => __( 'National Code', 'ccif-iran-checkout' ), 'class' => ['form-row-wide', 'ccif-person-field', 'ccif-real-person-field'], 'placeholder' => __( '10 digits without dashes', 'ccif-iran-checkout' )];

        $fields['billing_company_name'] = ['label' => __( 'Company Name', 'ccif-iran-checkout' ), 'class' => ['form-row-first', 'ccif-person-field', 'ccif-legal-person-field']];
        $fields['billing_economic_code'] = ['label' => __( 'National ID / Economic Code', 'ccif-iran-checkout' ), 'class' => ['form-row-last', 'ccif-person-field', 'ccif-legal-person-field']];
        $fields['billing_agent_first_name'] = ['label' => __( 'Agent First Name', 'ccif-iran-checkout' ), 'class' => ['form-row-first', 'ccif-person-field', 'ccif-legal-person-field']];
        $fields['billing_agent_last_name'] = ['label' => __( 'Agent Last Name', 'ccif-iran-checkout' ), 'class' => ['form-row-last', 'ccif-person-field', 'ccif-legal-person-field']];

        $fields['billing_state'] = ['type' => 'select', 'label' => __( 'State', 'ccif-iran-checkout' ), 'required' => true, 'class' => ['form-row-first', 'ccif-address-field'], 'options' => [ '' => __( 'Select', 'ccif-iran-checkout' ) ] + $iran_data['states']];
        $fields['billing_city'] = ['type' => 'select', 'label' => __( 'City', 'ccif-iran-checkout' ), 'required' => true, 'class' => ['form-row-last', 'ccif-address-field'], 'options' => [ '' => __( 'Select a state first', 'ccif-iran-checkout' ) ]];
        $fields['billing_address_1'] = ['label' => __( 'Full Address', 'ccif-iran-checkout' ), 'required' => true, 'placeholder' => __( 'Street, alley, plaque, unit', 'ccif-iran-checkout' ), 'class' => ['form-row-wide', 'ccif-address-field']];
        $fields['billing_postcode'] = ['label' => __( 'Postal Code', 'ccif-iran-checkout' ), 'required' => true, 'type' => 'tel', 'class' => ['form-row-first', 'ccif-address-field']];
        $fields['billing_phone'] = ['label' => __( 'Phone Number', 'ccif-iran-checkout' ), 'required' => true, 'type' => 'tel', 'class' => ['form-row-last', 'ccif-address-field']];

        return apply_filters( 'ccif_checkout_fields', $fields );
    }

    public function output_custom_billing_form_start($checkout) {
        $all_fields = $this->get_all_checkout_fields();

        echo '<div class="ccif-checkout-form">';

        // Box 1: Invoice Request
        echo '<div class="ccif-box invoice-request-box">';
        woocommerce_form_field('billing_invoice_request', $all_fields['billing_invoice_request'], $checkout->get_value('billing_invoice_request'));
        echo '</div>';

        // Box 2: Person/Company Info
        echo '<div class="ccif-box person-info-box"><h2>' . esc_html__( 'Buyer Information', 'ccif-iran-checkout' ) . '</h2>';
        woocommerce_form_field('billing_person_type', $all_fields['billing_person_type'], $checkout->get_value('billing_person_type'));

        echo '<div class="ccif-real-person-fields-wrapper">';
        woocommerce_form_field('billing_first_name', $all_fields['billing_first_name'], $checkout->get_value('billing_first_name'));
        woocommerce_form_field('billing_last_name', $all_fields['billing_last_name'], $checkout->get_value('billing_last_name'));
        woocommerce_form_field('billing_national_code', $all_fields['billing_national_code'], $checkout->get_value('billing_national_code'));
        echo '</div>';

        echo '<div class="ccif-legal-person-fields-wrapper">';
        woocommerce_form_field('billing_company_name', $all_fields['billing_company_name'], $checkout->get_value('billing_company_name'));
        woocommerce_form_field('billing_economic_code', $all_fields['billing_economic_code'], $checkout->get_value('billing_economic_code'));
        woocommerce_form_field('billing_agent_first_name', $all_fields['billing_agent_first_name'], $checkout->get_value('billing_agent_first_name'));
        woocommerce_form_field('billing_agent_last_name', $all_fields['billing_agent_last_name'], $checkout->get_value('billing_agent_last_name'));
        echo '</div>';
        echo '</div>';

        // Box 3: Address Info
        echo '<div class="ccif-box address-info-box"><h2>' . esc_html__( 'Shipping Information', 'ccif-iran-checkout' ) . '</h2>';
        woocommerce_form_field('billing_state', $all_fields['billing_state'], $checkout->get_value('billing_state'));
        woocommerce_form_field('billing_city', $all_fields['billing_city'], $checkout->get_value('billing_city'));
        woocommerce_form_field('billing_address_1', $all_fields['billing_address_1'], $checkout->get_value('billing_address_1'));
        woocommerce_form_field('billing_postcode', $all_fields['billing_postcode'], $checkout->get_value('billing_postcode'));
        woocommerce_form_field('billing_phone', $all_fields['billing_phone'], $checkout->get_value('billing_phone'));
        echo '</div>';
    }

    public function output_custom_billing_form_end() {
        echo '</div>'; // Close .ccif-checkout-form
    }

    public function enqueue_assets() {
        if ( ! is_checkout() ) return;
        wp_enqueue_script( 'ccif-checkout-js', plugin_dir_url( __FILE__ ) . 'assets/js/ccif-checkout.js', ['jquery'], CCIF_IRAN_CHECKOUT_VERSION, true );
        wp_localize_script( 'ccif-checkout-js', 'ccifData', [
            'cities' => $this->load_iran_data()['cities'],
            'i18n' => [
                'select_state_first' => __( 'Select a state first', 'ccif-iran-checkout' ),
            ]
        ] );
        wp_enqueue_style( 'ccif-checkout-css', plugin_dir_url( __FILE__ ) . 'assets/css/ccif-checkout.css', [], CCIF_IRAN_CHECKOUT_VERSION );
    }
}

function ccif_iran_checkout_rebuild_init() {
    new CCIF_Iran_Checkout_Rebuild();
}
add_action( 'plugins_loaded', 'ccif_iran_checkout_rebuild_init' );
