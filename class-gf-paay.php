<?php

require_once ('paay_lib/init.php');
require_once ('lib/PaayFeedSetting.php');
require_once ('lib/Excavator.php');
require_once ('lib/Settings.php');
require_once ('lib/GFOrder.php');
require_once ('lib/GF_Field_Billing_Address.php');
require_once ('lib/GF_Field_Billing_Phone.php');
require_once ('lib/GF_Field_Billing_Name.php');
require_once ('lib/GF_Field_Order_Id.php');
require_once ('lib/GF_Field_Detail.php');
require_once ('lib/GF_Field_Shipping_Address.php');
require_once ('lib/GF_Field_Shipping_Phone.php');
require_once ('lib/GF_Field_Shipping_Name.php');
require_once ('lib/Confirmations.php');

use PAAY\Plugin\PaayApiPlugin;

# dasboard filters
add_filter('gform_notification_events', array('GFPaay', 'add_notification_event'), 10, 2);
//add paay fields
add_filter('gform_add_field_buttons', array('GFPaay', 'add_paay_fields'));

# dashboard actions
add_action( 'gform_editor_js',array('GFPaay','editor_script'));
add_action('gform_editor_js_set_default_values', array('GFPaay', 'set_defaults'));

add_action('gform_admin_pre_render', array('GFPaay', 'check_settings'));

# front filters
add_filter('gform_pre_render', array('GFPaay','display_confirmation'));
add_filter('gform_validation', array('GFPaay', 'validation_for_paay_button'));
add_filter('gform_submit_button', array('GFPaay', 'form_submit_button'), 10, 2);
add_filter('gform_confirmation', array('GFPaay', 'confirm'), 10, 4);
add_filter('gform_disable_notification', array('GFPaay','before_email'), 10, 4);

# front actions
add_action('gform_after_submission', array('GFPaay', 'after_submission'), 10, 2);

# wp actions
add_action('admin_head', array('GFPaay', 'dashboard_css'));
add_action('init', array('GFPaay', 'paay_gf_handler'));

# assets
add_action('wp_enqueue_scripts', function() {
    wp_enqueue_style('paay', '//plugins.paay.co/css/paay.css', null, \GF_PAAY_VERSION);
    wp_enqueue_style('paay_style', plugins_url('paay_lib/assets/css/paay_style.css', __FILE__), null, \GF_PAAY_VERSION);

    wp_enqueue_script('paay', '//plugins.paay.co/js/paay_new.js', array(), \GF_PAAY_VERSION, true);
    wp_enqueue_script('paay_modal', plugins_url('paay_lib/assets/js/modal.js', __FILE__), array(), \GF_PAAY_VERSION, true);
});

GFForms::include_payment_addon_framework();
//add payment fields
GF_Fields::register(new GF_Field_Order_Id());
GF_Fields::register(new GF_Field_Billing_Address());
GF_Fields::register(new GF_Field_Shipping_Address());
GF_Fields::register(new GF_Field_Detail());
GF_Fields::register(new GF_Field_Billing_Phone());
GF_Fields::register(new GF_Field_Shipping_Phone());
GF_Fields::register(new GF_Field_Billing_Name());
GF_Fields::register(new GF_Field_Shipping_Name());

class GFPaay extends GFPaymentAddOn
{
    protected $_version = GF_PAAY_VERSION;
    protected $_min_gravityforms_version = '1.9.15';
    protected $_slug = 'gravityformspaay';
    protected $_short_title = 'PAAY';

    private static $_instance = null;

    public static function get_instance()
    {
        if ( self::$_instance == null ) {
            self::$_instance = new GFPaay();
        }
        return self::$_instance;
    }

    public static function paay_gf_handler()
    {
        # start session if is not running
        if(session_status() == PHP_SESSION_NONE) {
            session_start();
        }

        # catch status api url
        $statusUrl = Settings::STATUS_URL;

        $url = $_SERVER["HTTP_HOST"] . $_SERVER["REQUEST_URI"];

        if(preg_match("#{$statusUrl}#", $url)){
            $elements = explode('/', $url);
            if(GFPaay::approvePaayTransaction(end($elements))) {
                echo 'OK';
                exit();
            }
        }
    }

    # DASHBOARD METHODS

    public function feed_list_no_item_message()
    {
        $settings = $this->get_plugin_settings();

        $settings_url = '<a href="' . admin_url('admin.php?page=gf_settings&subview=' . $this->_slug) . '">';
        $message = 'To get started, let\'s go configure your %s PAAY Settings %s!';

        if(!rgar($settings, 'key')){
            $message .= ' (PAAY Key cannot be emtpy)';
            return sprintf(__($message, 'gravityformspaay'), $settings_url, '</a>');
        }
        if(!rgar($settings, 'secret')){
            $message .= ' (PAAY Secret cannot be emtpy)';
            return sprintf(__($message, 'gravityformspaay'), $settings_url, '</a>');
        }
        if(!rgar($settings, 'payment_strategy')){
            $message .= ' (You need to choose payment method)';
            return sprintf(__($message, 'gravityformspaay'), $settings_url, '</a>');
        }
        if(!rgar($settings, 'iframe')){
            $message .= ' (You need to choose 3D Secure Prompt)';
            return sprintf(__($message, 'gravityformspaay'), $settings_url, '</a>');
        }
        if(!rgar($settings, 'host')){
            $message .= ' (Host cannot be empty)';
            return sprintf(__($message, 'gravityformspaay'), $settings_url, '</a>');
        }

        return parent::feed_list_no_item_message();
    }

    public function plugin_settings_fields()
    {
        $settings = GFPaay::pluginSettings();
        return $settings->fields();
    }

    public function feed_settings_fields()
    {
        return PaayFeedSetting::feedFields();
    }

    public function add_notification_event($notification_events, $form)
    {
        if(GFPaay::feedIsEnabled($form['id'])){
            $payment_events = array(
                'complete_paay_payment' => __('Payment PAAY Completed', 'gravityformspaay'),
            );
            return array_merge($notification_events, $payment_events);
        }
        return $notification_events;
    }

    public static function add_paay_fields($fieldGroups)
    {
        $fieldGroups[] = array(
            'name' => 'payment_fields',
            'label' => __('Payment Fields', 'gravityformspaay'),
            'fields' => array(),
        );

        $buttons = array(
            GF_Field_Order_Id::TYPE => array(
                'group' => 'payment_fields',
                'value' => array(
                ),
            ),
            GF_Field_Detail::TYPE => array(
                'group' => 'payment_fields',
                'value' => array(),
            ),
            GF_Field_Billing_Name::TYPE => array(
                'group' => 'payment_fields',
                'value' => array(),
            ),
            GF_Field_Billing_Address::TYPE => array(
                'group' => 'payment_fields',
                'value' => array(),
            ),
            GF_Field_Billing_Phone::TYPE => array(
                'group' => 'payment_fields',
                'value' => array(),
            ),
            GF_Field_Shipping_Name::TYPE => array(
                'group' => 'payment_fields',
                'value' => array(),
            ),
            GF_Field_Shipping_Address::TYPE => array(
                'group' => 'payment_fields',
                'value' => array(),
            ),
            GF_Field_Shipping_Phone::TYPE => array(
                'group' => 'payment_fields',
                'value' => array(),
            )
        );

        foreach ($fieldGroups as $keyGroup => $group) {
            $fields = $group['fields'];
            if(!is_array($fields)){
                continue;
            }
            foreach($buttons as $key => $button){
                $value = array_filter($fields, function($field) use ($key){
                    return $field['data-type'] === $key;
                });
                if(empty($value)){
                    continue;
                }
                $buttons[$key]['value'] = current($value);
                $fieldKey = key($value);
                unset($fieldGroups[$keyGroup]['fields'][$fieldKey]);
            }
        }

        foreach($fieldGroups as $keyGroup => $group){
            foreach($buttons as $key => $button){
                if($group['name'] === $button['group']){
                    $fieldGroups[$keyGroup]['fields'][] = $button['value'];
                }
            }
       }
       return $fieldGroups;
    }

    public static function dashboard_css()
    {
        echo file_get_contents(dirname(__FILE__).'/templates/dashboard_css.php');
        wp_register_style('paay_gravity_form', get_template_directory_uri() . '/css/paay-gravity-form.css', false, GFCommon::$version);
        wp_enqueue_style('paay_gravity_form');
    }

    public static function set_defaults()
    {
        ?>
        case "orderId" :
            field.label = "Invoice/Account Number";
            break;
        case "detail" :
            field.label = "Description";
            break;
        case "billing_address" :
            if (!field.label)
            field.label = <?php echo json_encode( esc_html__( 'Billing Address', 'gravityforms' ) ); ?>;
            field.inputs = [new Input(field.id + 0.1, <?php echo json_encode( gf_apply_filters( array( 'gform_address_street', rgget( 'id' ) ), esc_html__( 'Street Address', 'gravityforms' ), rgget( 'id' ) ) ); ?>), new Input(field.id + 0.2, <?php echo json_encode( gf_apply_filters( array( 'gform_address_street2', rgget( 'id' ) ), esc_html__( 'Address Line 2', 'gravityforms' ), rgget( 'id' ) ) ); ?>), new Input(field.id + 0.3, <?php echo json_encode( gf_apply_filters( array( 'gform_address_city', rgget( 'id' ) ), esc_html__( 'City', 'gravityforms' ), rgget( 'id' ) ) ); ?>),
            new Input(field.id + 0.4, <?php echo json_encode( gf_apply_filters( array( 'gform_address_state', rgget( 'id' ) ), __( 'State / Province', 'gravityforms' ), rgget( 'id' ) ) ); ?>), new Input(field.id + 0.5, <?php echo json_encode( gf_apply_filters( array( 'gform_address_zip', rgget( 'id' ) ), esc_html__( 'ZIP / Postal Code', 'gravityforms' ), rgget( 'id' ) ) ); ?>), new Input(field.id + 0.6, <?php echo json_encode( gf_apply_filters( array( 'gform_address_country', rgget( 'id' ) ), esc_html__( 'Country', 'gravityforms' ), rgget( 'id' ) ) ); ?>)];
            break;
        case "billing_name" :
            field.label = "Billing Name";
            field.id = parseFloat(field.id);
            field.nameFormat = "normal";
            field.inputs = GetAdvancedNameFieldInputs(field, true, true, true);
            break;
        case "billing_phone" :
            field.label = "Billing Phone";
            field.phoneFormat = "standard";
            break;
        case "shipping_address" :
            if (!field.label)
            field.label = <?php echo json_encode( esc_html__( 'Shippping Address', 'gravityforms' ) ); ?>;
            field.inputs = [new Input(field.id + 0.1, <?php echo json_encode( gf_apply_filters( array( 'gform_address_street', rgget( 'id' ) ), esc_html__( 'Street Address', 'gravityforms' ), rgget( 'id' ) ) ); ?>), new Input(field.id + 0.2, <?php echo json_encode( gf_apply_filters( array( 'gform_address_street2', rgget( 'id' ) ), esc_html__( 'Address Line 2', 'gravityforms' ), rgget( 'id' ) ) ); ?>), new Input(field.id + 0.3, <?php echo json_encode( gf_apply_filters( array( 'gform_address_city', rgget( 'id' ) ), esc_html__( 'City', 'gravityforms' ), rgget( 'id' ) ) ); ?>),
            new Input(field.id + 0.4, <?php echo json_encode( gf_apply_filters( array( 'gform_address_state', rgget( 'id' ) ), __( 'State / Province', 'gravityforms' ), rgget( 'id' ) ) ); ?>), new Input(field.id + 0.5, <?php echo json_encode( gf_apply_filters( array( 'gform_address_zip', rgget( 'id' ) ), esc_html__( 'ZIP / Postal Code', 'gravityforms' ), rgget( 'id' ) ) ); ?>), new Input(field.id + 0.6, <?php echo json_encode( gf_apply_filters( array( 'gform_address_country', rgget( 'id' ) ), esc_html__( 'Country', 'gravityforms' ), rgget( 'id' ) ) ); ?>)];
            break;
        case "shipping_phone" :
            field.label = "Shipping Phone";
            field.phoneFormat = "standard";
            break;
        case "shipping_name" :
            field.label = "Shipping Name";
            field.id = parseFloat(field.id);
            field.nameFormat = "normal";
            field.inputs = GetAdvancedNameFieldInputs(field, true, true, true);
            break;
        <?php
    }


    public static function editor_script()
    {
        ?>
        <script type='text/javascript'>
            jQuery(document).bind('gform_load_field_settings', function(event, field) {

                if(field.type == 'billing_address' || field.type == 'shipping_address'){
                    field.type = 'address';
                    field = UpgradeAddressField(field);
                    var defaultState = field.defaultState == undefined ? "" : field.defaultState;
                    var defaultProvince = field.defaultProvince == undefined ? "" : field.defaultProvince; //for backwards compatibility
                    var defaultStateProvince = addressType == "canadian" && defaultState == "" ? defaultProvince : defaultState;

                    jQuery("#field_address_default_state_" + addressType).val(defaultStateProvince);
                    jQuery("#field_address_default_country_" + addressType).val(field.defaultCountry == undefined ? "" : field.defaultCountry);
                    SetAddressType(true);
                }

                if(field["type"] == "billing_name" || field["type"] == "shipping_name"){

                    if(typeof field["nameFormat"] == 'undefined' || field["nameFormat"] != "advanced"){
                        field = MaybeUpgradeNameField(field);
                    } else {
                        SetUpAdvancedNameField();
                    }

                    if(field["nameFormat"] == "simple"){
                        jQuery(".default_value_setting").show();
                        jQuery(".size_setting").show();
                        jQuery('#field_name_fields_container').html('').hide();
                        jQuery('.sub_label_placement_setting').hide();
                        jQuery('.name_prefix_choices_setting').hide();
                        jQuery('.name_format_setting').hide();
                        jQuery('.name_setting').hide();
                        jQuery('.default_input_values_setting').hide();
                        jQuery('.default_value_setting').show();
                    } else if(field["nameFormat"] == "extended") {
                        jQuery('.name_format_setting').show();
                        jQuery('.name_prefix_choices_setting').hide();
                        jQuery('.name_setting').hide();
                        jQuery('.default_input_values_setting').hide();
                        jQuery('.input_placeholders_setting').hide();
                    }
                }
            });
        </script>
        <?php
    }

    public static function check_settings($form)
    {
        echo GFCommon::is_form_editor() ? "
            <script type='text/javascript'>
                gform.addFilter('gform_form_editor_can_field_be_added', function (canFieldBeAdded, type) {
                    if(type == 'paay'){
                        if(GetFieldsByType(['paay']).length > 0) {
                            alert(". json_encode(esc_html__('Only one PAAY Wallet field can be added to the form', 'gravityformspaay')) .");
                            return false;
                        }
                    }
                    if(type == 'orderId'){
                        if(GetFieldsByType(['orderId']).length > 0) {
                            alert(". json_encode(esc_html__('Only one PAAY Invoice Id field can be added to the form', 'gravityformspaay')) .");
                            return false;
                        }
                    }
                    if(type == 'detail'){
                        if(GetFieldsByType(['detail']).length > 0) {
                            alert(". json_encode(esc_html__('Only one PAAY Order detail field can be added to the form', 'gravityformspaay')) .");
                            return false;
                        }
                    }
                    if(type == 'billing_address'){
                        if(GetFieldsByType(['billing_address']).length > 0) {
                            alert(". json_encode(esc_html__('Only one PAAY Billing Address field can be added to the form', 'gravityformspaay')) .");
                            return false;
                        }
                    }
                    if(type == 'shipping_address'){
                        if(GetFieldsByType(['shipping_address']).length > 0) {
                            alert(". json_encode(esc_html__('Only one PAAY Shipping Address field can be added to the form', 'gravityformspaay')) .");
                            return false;
                        }
                    }
                    if(type == 'billing_phone'){
                        if(GetFieldsByType(['billing_phone']).length > 0) {
                            alert(". json_encode(esc_html__('Only one PAAY Billing Phone field can be added to the form', 'gravityformspaay')) .");
                            return false;
                        }
                    }
                    if(type == 'shipping_phone'){
                        if(GetFieldsByType(['shipping_phone']).length > 0) {
                            alert(". json_encode(esc_html__('Only one PAAY Shipping Phone field can be added to the form', 'gravityformspaay')) .");
                            return false;
                        }
                    }
                    return canFieldBeAdded;
                });
            </script>
        " : "";

        return $form;
    }

    # FRONT METHODS

    // WALLET method
    public static function validation_for_paay_button($validation_result)
    {
        if (isset($_POST['paay_button_clicked'])) {
            $validation_result['is_valid'] = true;
            return $validation_result;
        }
        return $validation_result;
    }

    public static function display_confirmation($form)
    {
        if(!GFPaay::feedIsEnabled($form['id'])){
            return $form;
        }
        if(!isset($_GET['paay_confirmations'])){
            return $form;
        }

        $confId = $_GET['paay_confirmations'];
        if(!isset($form['confirmations'][$confId])){
            return $form;
        }

        $confirmation = $form['confirmations'][$confId];
        echo $confirmation['message'];

        $form['fields'] = array();
        $form['title'] = '';
        $form['button']['confirmation'] = true;

        return $form;
    }

    public static function form_submit_button($button, $form)
    {
        if(isset($form['button']['confirmation'])){
            return preg_replace("/\>/", 'style="display:none;">', $button);
        }

        $settings = GFPaay::pluginSettings();
        if(GFPaay::feedIsEnabled($form['id']) && $settings->logo() === 'show'){
            return $button . '<img src="//plugins.paay.co/images/paay/paay-secure.png">';
        }

        return $button;
    }

    public static function before_email($is_disabled, $notification, $form, $entry)
    {
        // notification disabled -> no send
        if($is_disabled){
            return true;
        }

        // PAAY plugin is turn on and 'paay_status' parameter is empty (if 'paay_status' is empty
        // that means it is after submit form action not after confirm payment action 'approvePaayTransaction')
        // -> no send
        if(self::feedIsEnabled($form['id']) && array_key_exists('paay_status', $notification) && $notification['paay_status'] !== 'paay_payment_approved'){
            return true;
        }

        // PAAY plugin is turn off and notification has event 'complete_paay_payment' -> no send
        // don't send any notification with this event if plugin is turn off
        if(!self::feedIsEnabled($form['id']) && $notification['event'] === 'complete_paay_payment'){
            return true;
        }

        return false;
    }

    public static function confirm($confirmation, $form, $entry, $ajax)
    {
        # if PAAY plugin is disable return
        if (!GFPaay::feedIsEnabled($form['id'])){
            return $confirmation;
        }

        # if green box (paay button) was used then skip WALLET part
        if (array_key_exists('paay_button_clicked', $_POST)) {
            return;
        }

        try {
            $settings = GFPaay::pluginSettings();

            # check all required php extensions
            if(!$settings->extensionsEnabled()){
                throw new \Exception('The following extensions are required: cURL.');
            }

            $feedSettings   = new PaayFeedSetting(GFPaay::getFeedByFormID($form['id']));
            $order          = new GFOrder($entry, $form, $feedSettings);
            $confirmations  = new Confirmations($form['confirmations']);
            $settings->setFormConfirmations($confirmations);

            $excavator = new Excavator();
            $excavator->setSettings($settings)
                      ->setOrder($order);

            $api = new PaayApiPlugin();
            $api->setParameters($excavator);
            $api->setAuthorizationParameters($settings->key(), $settings->secret());

            $entry['payment_status'] = Settings::ORDER_STATUS_PENDING;
            GFAPI::update_entry($entry);

            $strategy = $settings->paymentStrategy();
            $apiPaayTransactionUrl = $api->getTransactionUrl($settings->host(), $strategy);

            if($strategy === 'redirect') {
                header('Location: ' . $apiPaayTransactionUrl);
            } else {
                return "The transaction is in progress" . "
                <div id=\"paay-modal\">
                    <div class=\"paay-modal-dialog\">
                        <div class=\"paay-modal-content\">
                            <div class=\"paay-modal-header\">
                                <p class=\"loader\"></p>
                            </div>
                            <div class=\"paay-modal-body\">
                                <iframe src='{$apiPaayTransactionUrl}' id=\"paay_iframe_form\"></iframe>
                            </div>
                        </div>
                    </div>
                </div>";
            }

        } catch(\Exception $e){
            return 'ERROR:' . $e->getMessage();
        }
    }

    // WALLET method
    public static function after_submission($entry, $form)
    {
        if (isset($_POST['paay_button_clicked'])) {
            $idE = $entry['id'];
            $id = rand(9999, 99999);
            $entry['id'] = $id;
            setDataByOrderId($id, array(
                'idE' => $idE,
                'entry' => $entry,
                'post' => $_POST,
                'form' => $form,
            ));
            header('Content-type: application/json');
            echo json_encode($entry);
            exit;
        }
    }

    # HELPER METHODS

    public static function feedIsEnabled($id)
    {
        if ((GFAPI::get_feeds(null, $id, 'gravityformspaay')) instanceof WP_Error){
            return false;
        }
        return true;
    }

    public static function getFeedByFormID($id)
    {
        $feeds = GFAPI::get_feeds(null, $id, 'gravityformspaay');

        foreach($feeds as $feed){
            if(trim((int) $id) === trim((int) $feed['form_id'])){
                return $feed;
            }
        }

        return false;
    }

    public static function approvePaayTransaction($entryId)
    {
        # send notifications
        $entry = GFAPI::get_entry($entryId);
        $form = GFAPI::get_form($entry['form_id']);

        $notifications = $form['notifications'];

        foreach($notifications as $key => $notification){
            if($notification['event'] !== 'complete_paay_payment'){
                continue;
            }
            $notification['paay_status'] = 'paay_payment_approved';
            $notifications[$key] = $notification;
        }

        $form['notifications'] = $notifications;
        GFAPI::send_notifications($form, $entry, 'complete_paay_payment');

        # change entry status
        if(empty($entryId) || !is_numeric($entryId)){
            return false;
        }

        $entry = GFAPI::get_entry($entryId);
        if(empty($entry)){
            return false;
        }

        $paayApi = new PaayApiPlugin();
        $signature = (isset($_POST['signature'])) ? $_POST['signature'] : null;
        $settings = GFPaay::pluginSettings();

        if(!$paayApi->checkSignature($settings->key(), $settings->host(), $_POST, $signature)){
            return false;
        }

        $entry['payment_status'] = 'approved';
        GFAPI::update_entry($entry);

        return true;
    }

    public static function pluginSettings()
    {
        $options = get_option('gravityformsaddon_gravityformspaay_settings');

        $settings = new Settings($options, get_site_url());

        return $settings;
    }
}
