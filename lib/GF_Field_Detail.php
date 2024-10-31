<?php

if (!class_exists('GFForms')){
    die();
}

class GF_Field_Detail extends GF_Field_Textarea
{
    const TYPE = 'detail';
    public $type = 'detail';

    public function get_form_editor_field_title()
    {
        return esc_attr__('Description', 'gravityformspaay');
    }

}