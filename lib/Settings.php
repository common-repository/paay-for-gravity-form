<?php

use PAAY\Plugin\Helpers\Settings as PaaySettings;
use PAAY\Plugin\Helpers\SettingsInterface;

class Settings extends PaaySettings implements SettingsInterface
{
    const PAAY_HOST             = 'host';
    const PAAY_KEY              = 'key';
    const PAAY_SECRET           = 'secret';
    const PAAY_PAYMENT_STRATEGY = 'payment_strategy';
    const PAAY_IFRAME_OPTION    = 'iframe';
    const PAAY_LOGO_OPTION      = 'logo';
    const PAAY_RETURN_URL       = 'returnUrl';
    const PAAY_CANCEL_URL       = 'cancelUrl';
    const PAAY_STATUS_URL       = 'statusUrl';
    
    const STATUS_URL            = 'paay_payment_status';

    protected $settings;
    protected $baseUrl;
    private $formConfirmations;

    public function __construct($settings, $baseUrl)
    {
        $this->settings = $settings;
        $this->baseUrl = $baseUrl;
    }

    public function setFormConfirmations(Confirmations $formConfirmations)
    {
        $this->formConfirmations = $formConfirmations;

        return $this;
    }

    public function returnUrl($orderId = null)
    {
        if(!empty($this->settings[self::PAAY_RETURN_URL])){
            return trim(get_site_url(), '/') .'/'. $this->settings[self::PAAY_RETURN_URL];
        }

        return $this->formConfirmations->url();
    }

    public function cancelUrl($orderId = null)
    {
        $cancelUrl = $this->settings[self::PAAY_CANCEL_URL];
        $siteUrl = trim(get_permalink(), '/');

        return empty($cancelUrl) ? $siteUrl : $siteUrl .'/'. $cancelUrl;
    }

    public function statusUrl($orderId = null)
    {
        $siteUrl = trim(site_url(), '/');

        return $siteUrl .'/'. self::STATUS_URL .'/'. $orderId;
    }

    public function logo()
    {
        $value = $this->value(static::PAAY_LOGO_OPTION);

        return ($value === false) ? 'show' : $value;
    }

    public function fields()
    {
        return array(
            array(
                'description' => "<ul>
                                    <li>To get your <b>Merchant's api key/secret</b> you have to log on the<b> <a href='https://app.paay.co/' target='_blank'>APP PAAY</a> </b> with your username and password</li>
                                    <li>Next you have to <b>click</b> on <b>My Profile</b> in left menu. </li>
                                    <li>Then in the <b>right section</b> in <b>Merchant Details</b> you will have your <b>api key and secret</b>. </li>
                                  </ul><br><br>",
                'fields' => array(

                    array(
                        'name' => self::PAAY_KEY,
                        'label' => 'Merchant "API KEY"',
                        'type' => 'text'
                    ),
                    array(
                        'name' => self::PAAY_SECRET,
                        'label' => 'Merchant "API SECRET"',
                        'type' => 'text'
                    ),
                    array(
                        'name' => self::PAAY_PAYMENT_STRATEGY,
                        'tooltip' => 'Choose transaction payment strategy, payment in modal window or redirect user to payment page',
                        'label' => 'Payment method',
                        'type'          => 'radio',
                        'default_value' => 'modal',
                        'choices'       => array(
                            array(
                                'label' => 'Redirect',
                                'value' => 'redirect',
                            ),
                            array(
                                'label'    => 'Modal',
                                'value'    => 'modal',
                            ),
                        ),
                        'horizontal'    => true,
                    ),
                    array(
                        'name' => self::PAAY_IFRAME_OPTION,
                        'tooltip' => 'When the transaction is considered to have a high probability of being a fraudulent transaction, 3D Secure prompts the consumer with an extra authentication step. Choose “never show” if you want to avoid the extra authentication step when it’s required, and send the transaction without 3D Secure.<br><br> NOTE: If you choose “never show”, you will not get chargeback protection for transactions that require consumer authentication.',
                        'label' => '3D Secure Prompt',
                        'type' => 'radio',
                        'default_value' => 'never',
                        'choices' => array(
                            array(
                                'label' => 'Never show',
                                'value' => 'never'
                            ),
                            array(
                                'label' => 'Show if needed',
                                'value' => 'detected',
                            )
                        )
                    ),
                    array(
                        'name' => self::PAAY_LOGO_OPTION,
                        'tooltip' => 'Show the logo if the PAAY payment is enabled',
                        'label' => 'PAAY logo',
                        'type' => 'radio',
                        'default_value' => 'show',
                        'choices' => array(
                            array(
                                'label' => 'Show',
                                'value' => 'show'
                            ),
                            array(
                                'label' => 'Hidden',
                                'value' => 'hidden',
                            )
                        )
                    ),
                    array(
                        'name' => self::PAAY_RETURN_URL,
                        'tooltip' => 'Url address after payment process. <br> Add only page name e.g. if page URL is http://example.com/<b>success_transaction</b> add only "succes_transaction"',
                        'label' => 'Return url',
                        'type' => 'text'
                    ),
                    array(
                        'name' => self::PAAY_CANCEL_URL,
                        'tooltip' => 'Url address after faild/cancel payment procces. <br> Add only page name e.g. if page URL is http://example.com/<b>transaction_faild</b> add only "transaction_faild"',
                        'label' => 'Cancel url',
                        'type' => 'text'
                    ),
                    array(
                        'name' => self::PAAY_HOST,
                        'label' => 'PAAY Standalone host',
                        'type' => 'text',
                        'value' => 'https://api2.paay.co'
                    ),
                )
            )
        );
    }
}
