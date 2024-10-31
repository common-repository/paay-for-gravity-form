<?php

if (!class_exists('GFForms')){
    die();
}

class GF_Field_Shipping_Phone extends GF_Field_Phone
{
    const TYPE = 'shipping_phone';
    public $type = 'shipping_phone';
    public $inputType='phone';

    public function get_form_editor_field_title()
    {
        return esc_attr__('Shipping Phone', 'gravityformspaay');
    }
}