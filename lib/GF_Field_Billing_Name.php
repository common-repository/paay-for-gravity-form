<?php

if (!class_exists('GFForms')){
    die();
}

class GF_Field_Billing_Name extends GF_Field_Name
{
    const TYPE = 'billing_name';
    public $type = 'billing_name';

    public function get_form_editor_field_title()
    {
        return esc_attr__('Billing Name', 'gravityformspaay');
    }
}