<?php

if (!class_exists('GFForms')){
    die();
}

class GF_Field_Order_Id extends GF_Field_Text
{
    const TYPE = 'orderId';
    public $type = 'orderId';

    public function get_form_editor_field_title()
    {
        return esc_attr__('Invoice Id', 'gravityformspaay');
    }

    function get_form_editor_field_settings() {
        return array(
            'conditional_logic_field_setting',
            'prepopulate_field_setting',
            'error_message_setting',
            'label_setting',
            'label_placement_setting',
            'admin_label_setting',
            'size_setting',
            'maxlen_setting',
            'rules_setting',
            'visibility_setting',
            'duplicate_setting',
            'default_value_setting',
            'placeholder_setting',
            'description_setting',
            'css_class_setting',
        );
    }


}