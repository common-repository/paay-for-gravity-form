<?php
/*
Plugin Name: Gravity Forms PAAY Standard Add-On
Plugin URI: http://www.gravityforms.com
Description: Integrates Gravity Forms with PAAY Payments Standard.
Version: 2.4.9
Author: PAAY
Author URI: https://www.paay.co/
Text Domain: gravityformspaay
*/

define('GF_PAAY_VERSION', '2.4.9');

//add_action('init', 'paay_gf_handler');
add_action('gform_loaded', array('GF_Paay_Bootstrap', 'load'), 5);

class GF_Paay_Bootstrap
{
    public static function load()
    {
        if (!method_exists('GFForms', 'include_payment_addon_framework')) {
            return;
        }

        require_once('class-gf-paay.php');
        GFAddOn::register('GFPaay');
    }
}
