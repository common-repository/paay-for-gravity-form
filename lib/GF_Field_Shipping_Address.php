<?php

if (!class_exists('GFForms')){
    die();
}

class GF_Field_Shipping_Address extends GF_Field_Address
{
    const TYPE = 'shipping_address';
    public $type = 'shipping_address';

    public function get_form_editor_field_title()
    {
        return esc_attr__('Shipping Address', 'gravityformspaay');
    }
}