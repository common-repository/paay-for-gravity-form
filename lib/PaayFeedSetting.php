<?php

class PaayFeedSetting
{
    public $isAmount = false;
    public $amount;

    public $isEmail = false;
    public $email;

    public $isBillingAddress = false;
    public $billingAddress = array();

    public $isShippinggAddress = false;
    public $shippingAddress = array();

    public $isOrderInfo = false;
    public $orderInformation = array();

    private $pre = 'billingInformation_';
    private $shippingPre = 'shippingInformation_';
    
    const AMMOUNT_NAME = 'amount';
    const ORDER_ID = 'orderId';
    const ORDER_DETAIL = 'orderDetail';
    const EMAIL_NAME   = 'email';

    const FIRST_NAME = 'billingFirstName';
    const LAST_NAME  = 'billingLastName';
    const ADDRESS1   = 'billingAddress1';
    const ADDRESS2   = 'billingAddress2';
    const CITY       = 'billingCity';
    const POSTCODE   = 'billingPostcode';
    const STATE      = 'billingState';
    const COUNTRY    = 'billingCountry';
    const PHONE    = 'billingPhone';

    const SHIPPING_FIRST_NAME = 'shippingFirstName';
    const SHIPPING_LAST_NAME  = 'shippingLastName';
    const SHIPPING_ADDRESS1   = 'shippingAddress1';
    const SHIPPING_ADDRESS2   = 'shippingAddress2';
    const SHIPPING_CITY       = 'shippingCity';
    const SHIPPING_POSTCODE   = 'shippingPostcode';
    const SHIPPING_STATE      = 'shippingState';
    const SHIPPING_COUNTRY    = 'shippingCountry';
    const SHIPPING_PHONE    = 'shippingPhone';

    public function __construct($feed)
    {
        if(!array_key_exists('meta', $feed)){
            throw new \Exception('Feed settings does not exist');
        }

        $this->setupAmount($feed['meta']);
        $this->setupEmail($feed['meta']);
        $this->setupBillingAddress($feed['meta']);
        $this->setupShippingAddress($feed['meta']);
        $this->setupOrderInformation($feed['meta']);
    }

    public function setupAmount($meta)
    {
        $value = $meta[self::AMMOUNT_NAME];

        if(strlen($value) > 0){
            $this->amount = $value;
            $this->isAmount = true;
        }
    }

    public function setupEmail($meta)
    {
        $key = $this->pre . self::EMAIL_NAME;
        $value = $meta[$key];

        if(strlen($value) > 0){
            $this->email = $value;
            $this->isEmail = true;
        }
    }

    public function setupBillingAddress($meta)
    {
        foreach($meta as $key => $value){
            if($this->clearBillingKey($key) === self::EMAIL_NAME){
                continue;
            }
            if(!$this->isbillingField($key)){
                continue;
            }
            if(strlen($value) < 1){
                continue;
            }

            $billingKey = $this->clearBillingKey($key);
            $this->billingAddress[$billingKey] = $value;
            $this->isBillingAddress = true;
        }
    }

    public function setupShippingAddress($meta)
    {
        foreach($meta as $key => $value){
            if(!$this->isshippingField($key)){
                continue;
            }
            if(strlen($value) < 1){
                continue;
            }

            $shippingKey = $this->clearShippingKey($key);
            $this->shippingAddress[$shippingKey] = $value;
            $this->isShippinggAddress = true;
        }
    }

    public function setupOrderInformation($meta)
    {
        foreach($meta as $key => $value){
            $match = preg_match('/^orderInformation\_/', $key);
            if(!(bool)($match === 1)){
                continue;
            }
            if(strlen($value) < 1){
                continue;
            }

            $orderInfoKey = preg_replace('/^orderInformation\_/','',$key);
            $this->orderInformation[$orderInfoKey] = $value;
            $this->isOrderInfo = true;
        }
    }
    
    /**
     * Remove billing part from keys
     * @param string $key
     * @return string
     */
    private function clearBillingKey($key)
    {
        return preg_replace('/^billingInformation\_/','',$key);
    }

    /**
     * Remove shipping part from keys
     * @param string $key
     * @return string
     */
    private function clearShippingKey($key)
    {
        return preg_replace('/^shippingInformation\_/','',$key);
    }

    /**
     * Check field is "billing" type
     * @param string $key
     * @return bool
     */
    private function isbillingField($key)
    {
        $match = preg_match('/^billingInformation\_/', $key);
        return (bool)($match === 1);
    }

    /**
     * Check field is "shipping" type
     * @param string $key
     * @return bool
     */
    private function isshippingField($key)
    {
        $match = preg_match('/^shippingInformation\_/', $key);
        return (bool)($match === 1);
    }

    public static function feedFields()
    {
        return array(
            array(
                'title'  => 'PAAY Feed Settings',
                'fields' => array(
                    array(
                        'name'    => 'transactionType',
                        'label'=> 'Transaction Type',
                        'type'    => 'select',
                        'choices'=> array(
                            array(
                                'label'=> 'Products and Services',
                                'value'=> 'product'
                            ),
                            array(
                                'label'=> 'Donation',
                                'value'=> 'donation'
                            )

                        ),
                        'default_value' => 'product',
                    ),
                    array(
                        'name'    => 'paymentAmount',
                        'type'    => 'hidden',
                        'default_value' => 'form_total',
                    ),
                    array(
                        'name' => 'feedName',
                        'label' => 'Name',
                        'type' => 'text',
                        'class' => 'medium',
                        'required' => 1,
                        'tooltip' => '<h6>Name</h6> Enter a feed name to uniquely identify this setup.'
                    ),
                    array(
                        'label'   => 'Total Cost',
                        'type'    => 'radio',
                        'horizontal' => true,
                        'name'    => self::AMMOUNT_NAME,
                        'tooltip' => '<h6>Total Cost</h6>This field indicates the total transaction cost',
                        'choices' => array(
                            array(
                                'label' => '(Pricing Fields) Total',
                                'value'  => 'total',
                            ),
                        ),
                        'default_value' => 'total'
                    ),
                    array(
                        'label'   => 'Order Info',
                        'type'    => 'field_map',
                        'name'    => 'orderInfo',
                        'tooltip' => '<h6>Order ID</h6>This field indicates the order id',
                        'field_map' => array(
                            array(
                                'name' => self::ORDER_ID,
                                'label'  => 'Order Id',
                                'required'  => false,
                            ),
                            array(
                                'name' => self::ORDER_DETAIL,
                                'label'  => 'Order Details',
                                'required'  => false,
                            )
                        ),
                        'default_value' => 'total'
                    ),
                    array(
                        'name' => 'billingInformation',
                        'label' => 'Billing Information',
                        'type' => 'field_map',
                        'tooltip' => '<h6>Billing Information</h6>Map your Form Fields to the available listed fields.',
                        'field_map' => array(
                            array(
                                'name' => self::EMAIL_NAME,
                                'label' => 'Email',
                                'required' => true
                            ),
                            array(
                                'name' => self::FIRST_NAME,
                                'label' => 'First name',
                                'required' => false
                            ),
                            array(
                                'name' => self::LAST_NAME,
                                'label' => 'Last name',
                                'required' => false
                            ),
                            array(
                                'name' => self::ADDRESS1,
                                'label' => 'Address',
                                'required' => false
                            ),
                            array(
                                'name' => self::ADDRESS2,
                                'label' => 'Address 2',
                                'required' => false
                            ),
                            array(
                                'name' => self::CITY,
                                'label' => 'City',
                                'required' => false
                            ),
                            array(
                                'name' => self::POSTCODE,
                                'label' => 'Zip',
                                'required' => false
                            ),
                            array(
                                'name' => self::STATE,
                                'label' => 'State',
                                'required' => false
                            ),
                            array(
                                'name' => self::COUNTRY,
                                'label' => 'Country',
                                'required' => false
                            ),
                            array(
                                'name' => self::PHONE,
                                'label' => 'Phone',
                                'required' => false
                            )
                        )
                    ),
                    array(
                        'name' => 'shippingInformation',
                        'label' => 'Shipping Information',
                        'type' => 'field_map',
                        'tooltip' => '<h6>Shipping Information</h6>Map your Form Fields to the available listed fields.',
                        'field_map' => array(
                            array(
                                'name' => self::SHIPPING_FIRST_NAME,
                                'label' => 'First name',
                                'required' => false
                            ),
                            array(
                                'name' => self::SHIPPING_LAST_NAME,
                                'label' => 'Last name',
                                'required' => false
                            ),
                            array(
                                'name' => self::SHIPPING_ADDRESS1,
                                'label' => 'Address',
                                'required' => false
                            ),
                            array(
                                'name' => self::SHIPPING_ADDRESS2,
                                'label' => 'Address 2',
                                'required' => false
                            ),
                            array(
                                'name' => self::SHIPPING_CITY,
                                'label' => 'City',
                                'required' => false
                            ),
                            array(
                                'name' => self::SHIPPING_POSTCODE,
                                'label' => 'Zip',
                                'required' => false
                            ),
                            array(
                                'name' => self::SHIPPING_STATE,
                                'label' => 'State',
                                'required' => false
                            ),
                            array(
                                'name' => self::SHIPPING_COUNTRY,
                                'label' => 'Country',
                                'required' => false
                            ),
                            array(
                                'name' => self::SHIPPING_PHONE,
                                'label' => 'Phone',
                                'required' => false
                            )
                        )
                    )
                )
            )
        );
    }
}
