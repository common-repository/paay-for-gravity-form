<?php

if (!class_exists('GFForms')){
    die();
}

class GF_Field_Shipping_Name extends GF_Field_Name
{
    const TYPE = 'shipping_name';
    public $type = 'shipping_name';

    public function get_form_editor_field_title()
    {
        return esc_attr__('Shipping Name', 'gravityformspaay');
    }
}