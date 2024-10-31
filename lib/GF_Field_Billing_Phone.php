<?php

if (!class_exists('GFForms')){
    die();
}

class GF_Field_Billing_Phone extends GF_Field_Phone
{
    const TYPE = 'billing_phone';
    public $type = 'billing_phone';
    public $inputType ='phone';

    public function get_form_editor_field_title()
    {
        return esc_attr__('Billing Phone', 'gravityformspaay');
    }
}