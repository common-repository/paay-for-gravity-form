<?php

if (!class_exists('GFForms')){
    die();
}

class GF_Field_Billing_Address extends GF_Field_Address
{
    const TYPE = 'billing_address';
    public $type = 'billing_address';

    public function get_form_editor_field_title()
    {
        return esc_attr__('Billing Address', 'gravityformspaay');
    }

    function get_form_editor_field_settings() {
        return array(
            'conditional_logic_field_setting',
            'prepopulate_field_setting',
            'error_message_setting',
            'label_setting',
            'admin_label_setting',
            'label_placement_setting',
            'sub_label_placement_setting',
            'default_input_values_setting',
            'input_placeholders_setting',
            'rules_setting',
            'copy_values_option',
            'description_setting',
            'visibility_setting',
            'css_class_setting',
        );
    }
}