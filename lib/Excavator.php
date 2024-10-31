<?php

use PAAY\Plugin\Helpers\SettingsInterface;
use PAAY\Plugin\Helpers\DataExcavatorInterface;

class Excavator implements DataExcavatorInterface
{
    /**
     * @var SettingsInterface
     */
    private $settings;
    /**
     * @var GFOrder
     */
    private $order;

    private $defaultDetails = array(
        'name' => 'Price',
        'cost' => 0.0
    );

    public function setOrder(GFOrder $order)
    {
        $this->order = $order;

        return $this;
    }

    public function setSettings(SettingsInterface $settings)
    {
        $this->settings = $settings;

        return $this;
    }

    public function excavateBasicParameters()
    {
        return array(
            'amount'    => $this->order->getAmount(),
            'orderId'   => $this->order->getOrderId(),
            'details'   => $this->getDetails(),
            'returnUrl' => $this->settings->returnUrl(),
            'cancelUrl' => $this->settings->cancelUrl(),
            'statusUrl' => $this->settings->statusUrl($this->order->getOrderId()),
            'email'     => $this->order->getEmail(),
            'threeds_visibility' => $this->settings->iframeOption()
        );
    }

    private function getDetails()
    {
        $this->defaultDetails['cost'] = $this->order->getAmount();
        $description = $this->order->getDescription();
        if (!empty($description)) {
            $this->defaultDetails['name'] = $this->order->getDescription();
        }
//
//        return json_encode(array($this->defaultDetails));
        return !empty($description) ? $description : json_encode(array($this->defaultDetails));
    }

    public function excavateBillingParameters()
    {
        return array(
            'billingFirstName'  => $this->order->getBillingAddressFirstName(),
            'billingLastName'   => $this->order->getBillingAddressLastName(),
            'billingEmail'      => $this->order->getEmail(),
            'billingAddress1'   => $this->order->getBillingAddressAddress1(),
            'billingAddress2'   => $this->order->getBillingAddressAddress2(),
            'billingCity'       => $this->order->getBillingAddressCity(),
            'billingPostcode'   => $this->order->getBillingAddressZip(),
            'billingState'      => $this->order->getBillingAddressState(),
            'billingCountry'    => $this->order->getBillingAddressCountry(),
            'billingPhone'    => $this->order->getBillingPhone(),
        );
    }

    public function excavateShippingParameters()
    {
        return array(
            'shippingFirstName'  => $this->order->getShippingAddressFirstName(),
            'shippingLastName'   => $this->order->getShippingAddressLastName(),
            'shippingEmail'      => $this->order->getEmail(),
            'shippingAddress1'   => $this->order->getShippingAddressAddress1(),
            'shippingAddress2'   => $this->order->getShippingAddressAddress2(),
            'shippingCity'       => $this->order->getShippingAddressCity(),
            'shippingPostcode'   => $this->order->getShippingAddressZip(),
            'shippingState'      => $this->order->getShippingAddressState(),
            'shippingCountry'    => $this->order->getShippingAddressCountry(),
            'shippingPhone'    => $this->order->getShippingPhone(),
        );
    }
}
