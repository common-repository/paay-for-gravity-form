<?php

/**
 * Class GFOrder
 *
 *  $entry = array(
 *      'id' => 'orderId',
 *      ...
 *      '2'  => 'field_id'
 *      ...
 *      '3.3' => 'sub_field_id'
 *  );
 *
 *  ###################
 *
 *  $form = array(
 *      ...
 *      'fields' => array(
 *          0 => array(
 *              'type' => 'field_type',
 *              'id'   => 'field_id',
 *              ...
 *              'inputs' => array(
 *                  0 => array(
 *                      'id' => 'sub_field_id'
 *                  ),
 *                  1 => array(...),
 *                  ...
 *              )
 *          ),
 *          1 => array(...)
 *          ...
 *      ),
 *      ...
 *  );
 *
 *  ###################
 *
 *  $feedSettings->billingAddress = array(
 *      'feed_key_name' => 'id_field' OR 'sub_field_id'
 *  );
 */

class GFOrder
{
    /**
     * @var
     * @deprecated remove when removing feedSettings support
     */
    private $entry;
    /**
     * @var
     * @deprecated remove when removing feedSettings support
     */
    private $form;
    /**
     * @var PaayFeedSetting
     * @deprecated remove when removing feedSettings support
     */
    private $feedSettings;


    private $firstNameFieldType = 'name';
    private $firstNameIdSubField = '3';

    private $lastNameFieldType = 'name';
    private $lastNameIdSubField = '6';

    private $address1FieldType = 'address';
    private $address1IdSubField = '1';

    private $address2FieldType = 'address';
    private $address2IdSubField = '2';

    private $cityFieldType = 'address';
    private $cityIdSubField = '3';

    private $shippingPhoneType = 'phone';
    private $billingPhoneType = 'phone';
    private $orderIdType = 'text';
    private $orderDetailType = 'text';

    private $postCodeIdSubField = '5';

    private $stateFieldType = 'address';
    private $stateIdSubField = '4';

    private $countryFieldType = 'address';
    private $countryIdSubField = '6';

    //Order properties
    private $orderId;
    private $description;
    private $email;
    private $amount;
    private $billingAddressFirstName;
    private $billingAddressLastName;
    private $billingAddressCompany;
    private $billingAddressAddress1;
    private $billingAddressAddress2;
    private $billingAddressCity;
    private $billingAddressState;
    private $billingAddressZip;
    private $billingAddressCountry;
    private $billingPhone;

    private $shippingAddressFirstName;
    private $shippingAddressLastName;
    private $shippingAddressAddress1;
    private $shippingAddressAddress2;
    private $shippingAddressCity;
    private $shippingAddressState;
    private $shippingAddressZip;
    private $shippingAddressCountry;
    private $shippingPhone;



    public function __construct($entry, $form, PaayFeedSetting $feedSettings = null)
    {
        $this->entry = $entry;
        $this->feedSettings = $feedSettings;
        $this->form = $form;
        
        //check if the form has an order_id field
        $this->__parseForm($form,$entry);
        if ($feedSettings !== null) {
            $this->__oldParse($form,$entry,$feedSettings);
        }
    }

    public function getAmount()
    {
        return $this->amount;
    }
    
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * @return mixed
     * @deprecated too ambiguous
     */
    public function getFirstName()
    {
        return $this->billingAddressFirstName;
    }

    /**
     * @return mixed
     * @deprecated too ambiguous
     */
    public function getLastName()
    {
        return $this->billingAddressLastName;
    }

    /**
     * @return mixed
     * @deprecated too ambiguous
     */
    public function getAddress1()
    {
        return $this->billingAddressAddress1;
    }

    /**
     * @return mixed
     * @deprecated too ambiguous
     */
    public function getAddress2()
    {
        return $this->billingAddressAddress2;
    }

    /**
     * @return mixed
     * @deprecated too ambiguous
     */
    public function getCity()
    {
        return $this->billingAddressCity;
    }

    /**
     * @return mixed
     * @deprecated too ambiguous
     */
    public function getPostCode()
    {
        return $this->billingAddressZip;
    }

    /**
     * @return mixed
     * @deprecated too ambiguous
     */
    public function getState()
    {
        return $this->billingAddressState;
    }

    /**
     * @return mixed
     * @deprecated too ambiguous
     */
    public function getCountry()
    {
        return $this->billingAddressCountry;
    }
    /**
     * @param $feedKey
     * @param $fieldType
     * @param null $subFieldId
     * @deprecated 08/17/2016 users should use the Paay fields when building a form
     * @return bool|null
     */
    private function getBillingField($feedKey, $fieldType, $subFieldId = null)
    {
        # if field is mapped then get id field from feed settings
        # and return entry value if exist
        if($this->isMapped($feedKey)){
            return $this->mappedField($feedKey);
        }

        $field = current(GFAPI::get_fields_by_type($this->form, array($fieldType), true));

        if($subFieldId !== null) {
            return $this->subfieldValue($field, $subFieldId);
        }

        if($value = $this->notMappedValue($field)){
            return $value;
        }

        return null;
    }
    /**
     * @param $feedKey
     * @param $fieldType
     * @param null $subFieldId
     * @deprecated 08/17/2016 users should use the Paay fields when building a form
     * @return bool|null
     */
    private function getShippingField($feedKey, $fieldType, $subFieldId = null)
    {
        # if field is mapped then get id field from feed settings
        # and return entry value if exist
        if($this->isMapped($feedKey)){
            return $this->shippingMappedField($feedKey);
        }

        $field = current(GFAPI::get_fields_by_type($this->form, array($fieldType), true));

        if($subFieldId !== null) {
            return $this->subfieldValue($field, $subFieldId);
        }

        if($value = $this->notMappedValue($field)){
            return $value;
        }

        return null;
    }

    /**
     * @param $feedKey
     * @param $fieldType
     * @param null $subFieldId
     * @deprecated 08/17/2016 users should use the Paay fields when building a form
     * @return bool|null
     */
    private function getOrderField($feedKey, $fieldType, $subFieldId = null)
    {
        # if field is mapped then get id field from feed settings
        # and return entry value if exist
        if($this->isMapped($feedKey)){
            return $this->orderMappedField($feedKey);
        }

        $field = current(GFAPI::get_fields_by_type($this->form, array($fieldType), true));

        if($subFieldId !== null) {
            return $this->subfieldValue($field, $subFieldId);
        }

        if($value = $this->notMappedValue($field)){
            return $value;
        }

        return null;
    }

    /**
     * @param $feedKey
     * @return bool|null
     * @deprecated remove when removing feedSettings support
     */
    private function mappedField($feedKey)
    {
        $entryId = $this->feedSettings->billingAddress[$feedKey];
        
        if($value = $this->entry($entryId)){
            return $value;
        }
        
        return null;
    }

    /**
     * @param $feedKey
     * @return bool|null
     * @deprecated remove when removing feedSettings support
     */
    private function shippingMappedField($feedKey)
    {
        $entryId = $this->feedSettings->shippingAddress[$feedKey];

        if($value = $this->entry($entryId)){
            return $value;
        }

        return null;
    }

    /**
     * @param $feedKey
     * @return bool|null
     * @deprecated remove when removing feedSettings support
     */
    private function orderMappedField($feedKey)
    {
        $entryId = $this->feedSettings->orderInformation[$feedKey];

        if($value = $this->entry($entryId)){
            return $value;
        }

        return null;
    }

    /**
     * @param $key
     * @return bool
     * @deprecated remove when removing feedSettings support
     */
    private function isMapped($key)
    {
        if(!$this->feedSettings->isBillingAddress && !$this->feedSettings->isShippinggAddress){
            return false;
        }

        if(!array_key_exists($key, $this->feedSettings->billingAddress) && !array_key_exists($key, $this->feedSettings->shippingAddress)){
            return false;
        }

        return true;
    }

    /**
     * @param $field
     * @param $subId
     * @return bool|null
     * 
     * @deprecated remove when removing feedSettings support
     */
    private function subfieldValue($field, $subId)
    {
        if(!$field || !array_key_exists('id', $field)){
            return null;
        }

        if(!array_key_exists('inputs', $field)){
            return null;
        }

        $subId = $field['id'] .'.'.$subId;
        foreach($field['inputs'] as $subField){
            if($subField['id'] == $subId && $value = $this->entry($subId)){
                return $value;
            }
        }

        return null;
    }

    /**
     * @param $field
     * @return bool
     * @deprecated remove when removing feedSettings support
     */
    private function notMappedValue($field)
    {
        if(!$field || !array_key_exists('id', $field)){
            return false;
        }

        if($value = $this->entry($field['id'])){
            return $value;
        }
        
        return false;
    }

    /**
     * @param $key
     * @return bool
     * @deprecated remove when removing feedSettings support
     */
    private function entry($key)
    {
        if(!array_key_exists($key, $this->entry)){
            return false;
        }

        return $this->entry[$key];
    }
    /**
     * Get order information from Paay fields
     * 
     * @param $form GFForms
     * @param $entry
     */
    private function __parseForm($form,$entry)
    {
        /**
         * @var $field GF_Field
         */
        foreach ($form['fields'] as $field)
        {
            //let's take care of the new Paay Fields first
            switch ($field->get_input_type()) {
                case GF_Field_Order_Id::TYPE:
                    $this->orderId = $field->get_value_export( $entry );
                    break;
                case 'total':
                    $this->amount = $field->get_value_export( $entry );
                    break;
                case GF_Field_Detail::TYPE:
                    $this->description = $field->get_value_export( $entry );
                    break;
                case GF_Field_Billing_Address::TYPE:
                    $this->billingAddressAddress1 = $field->get_value_export( $entry ,$field->id.'.1');
                    $this->billingAddressAddress2 = $field->get_value_export( $entry ,$field->id.'.2');
                    $this->billingAddressCity = $field->get_value_export( $entry ,$field->id.'.3');
                    $this->billingAddressState = $field->get_value_export( $entry ,$field->id.'.4');
                    $this->billingAddressZip = $field->get_value_export( $entry ,$field->id.'.5');
                    $this->billingAddressCountry = $field->get_value_export( $entry ,$field->id.'.6');
                    break;
                case GF_Field_Shipping_Address::TYPE:
                    $this->shippingAddressAddress1 = $field->get_value_export( $entry ,$field->id.'.1');
                    $this->shippingAddressAddress2 = $field->get_value_export( $entry ,$field->id.'.2');
                    $this->shippingAddressCity = $field->get_value_export( $entry ,$field->id.'.3');
                    $this->shippingAddressState = $field->get_value_export( $entry ,$field->id.'.4');
                    $this->shippingAddressZip = $field->get_value_export( $entry ,$field->id.'.5');
                    $this->shippingAddressCountry = $field->get_value_export( $entry ,$field->id.'.6');
                    break;
                case GF_Field_Billing_Phone::TYPE:
                    $this->billingPhone = $field->get_value_export( $entry );
                    break;
                case GF_Field_Shipping_Phone::TYPE:
                    $this->shippingPhone = $field->get_value_export( $entry );
                    break;
                case GF_Field_Billing_Name::TYPE:
                    $this->billingAddressFirstName = $field->get_value_export( $entry ,$field->id.'.3');
                    $this->billingAddressLastName = $field->get_value_export( $entry ,$field->id.'.6');
                    break;
                case GF_Field_Shipping_Name::TYPE:
                    $this->shippingAddressFirstName = $field->get_value_export( $entry ,$field->id.'.3');
                    $this->shippingAddressLastName = $field->get_value_export( $entry ,$field->id.'.6');
                    break;
            }
        }
    }
    /**
     * This is to maintain backward compatibility with PaayFeedSetting
     * 
     * @param $entry
     * @param PaayFeedSetting $feedSettings
     */
    private function __oldParse($form,$entry,PaayFeedSetting $feedSettings)
    {
        //set order if if not already set
        if (!isset($this->orderId) || empty($this->orderId)) {
            $this->orderId = $entry['id'];
        }

        //set the amount
        if (!isset($this->amount)) {
            if(!$feedSettings->isAmount){
                $this->amount =  0.0;
            }
            $type = $feedSettings->amount;
            $field = current(GFAPI::get_fields_by_type($form, array($type), true));

            if(!$field || !array_key_exists('id', $field)){
                $this->amount =  0.0;
            }

            $key = $field['id'];
            if(!array_key_exists($key, $entry)){
                $this->amount =  0.0;
            }

            $this->amount =  $entry[$key];
        }
        //set email
        if (!isset($this->email)) {
            if($feedSettings->isEmail){
                $key = $feedSettings->email;
                if(array_key_exists($key, $entry)){
                    $this->email = $entry[$key];
                }
            }
        }

        //set billing first name
        if (!isset($this->billingAddressFirstName)) {
            $this->billingAddressFirstName =  $this->getBillingField(
                PaayFeedSetting::FIRST_NAME,
                $this->firstNameFieldType,
                $this->firstNameIdSubField
            );
        }

        //set billing last name
        if (!isset($this->billingAddressLastName)) {
            $this->billingAddressLastName =  $this->getBillingField(
                PaayFeedSetting::LAST_NAME,
                $this->lastNameFieldType,
                $this->lastNameIdSubField
            );
        }
        
        //set billing address
        if (!isset($this->billingAddressAddress1)) {
            $this->billingAddressAddress1 = $this->getBillingField(
                PaayFeedSetting::ADDRESS1,
                $this->address1FieldType,
                $this->address1IdSubField
            );
        }
        
        if (!isset($this->billingAddressAddress2)) {
            $this->billingAddressAddress2 = $this->getBillingField(
                PaayFeedSetting::ADDRESS2,
                $this->address2FieldType,
                $this->address2IdSubField
            );
        }
        
        if (!isset($this->billingAddressCity)) {
            $this->billingAddressCity = $this->getBillingField(
                PaayFeedSetting::CITY,
                $this->cityFieldType,
                $this->cityIdSubField
            );
        }

        if (!isset($this->billingAddressCountry)) {
            $this->billingAddressCountry = $this->getBillingField(
                PaayFeedSetting::COUNTRY,
                $this->countryFieldType,
                $this->countryIdSubField
            );
        }

        if (!isset($this->billingAddressState)) {
            $this->billingAddressState = $this->getBillingField(
                PaayFeedSetting::STATE,
                $this->stateFieldType,
                $this->stateIdSubField
            );
        }

        if (!isset($this->billingAddressZip)) {
            $this->billingAddressZip = $this->getBillingField(
                PaayFeedSetting::POSTCODE,
                $this->postCodeFieldType,
                $this->postCodeIdSubField
            );
        }

        if (!isset($this->billingPhone)) {
            $this->billingPhone = $this->getBillingField(
                PaayFeedSetting::PHONE,
                $this->billingPhoneType
            );
        }

        //set shipping first name
        if (!isset($this->shippingAddressFirstName)) {
            $this->shippingAddressFirstName =  $this->getShippingField(
                PaayFeedSetting::SHIPPING_FIRST_NAME,
                $this->firstNameFieldType,
                $this->firstNameIdSubField
            );
        }

        //set shipping last name
        if (!isset($this->shippingAddressLastName)) {
            $this->shippingAddressLastName =  $this->getShippingField(
                PaayFeedSetting::SHIPPING_LAST_NAME,
                $this->lastNameFieldType,
                $this->lastNameIdSubField
            );
        }

        //set shipping address
        if (!isset($this->shippingAddressAddress1)) {
            $this->shippingAddressAddress1 = $this->getShippingField(
                PaayFeedSetting::SHIPPING_ADDRESS1,
                $this->address1FieldType,
                $this->address1IdSubField
            );
        }

        if (!isset($this->shippingAddressAddress2)) {
            $this->shippingAddressAddress2 = $this->getShippingField(
                PaayFeedSetting::SHIPPING_ADDRESS2,
                $this->address2FieldType,
                $this->address2IdSubField
            );
        }

        if (!isset($this->shippingAddressCity)) {
            $this->shippingAddressCity = $this->getShippingField(
                PaayFeedSetting::SHIPPING_CITY,
                $this->cityFieldType,
                $this->cityIdSubField
            );
        }

        if (!isset($this->shippingAddressCountry)) {
            $this->shippingAddressCountry = $this->getShippingField(
                PaayFeedSetting::SHIPPING_COUNTRY,
                $this->countryFieldType,
                $this->countryIdSubField
            );
        }

        if (!isset($this->shippingAddressState)) {
            $this->shippingAddressState = $this->getShippingField(
                PaayFeedSetting::SHIPPING_STATE,
                $this->stateFieldType,
                $this->stateIdSubField
            );
        }

        if (!isset($this->shippingAddressZip)) {
            $this->shippingAddressZip = $this->getShippingField(
                PaayFeedSetting::SHIPPING_POSTCODE,
                $this->postCodeFieldType,
                $this->postCodeIdSubField
            );
        }

        if (!isset($this->shippingPhone)) {
            $this->shippingPhone = $this->getShippingField(
                PaayFeedSetting::SHIPPING_PHONE,
                $this->shippingPhoneType
            );
        }

        if (!isset($this->orderId)) {
            $this->orderId = $this->getOrderField(
                PaayFeedSetting::ORDER_ID,
                $this->orderIdType
            );
        }

        if (!isset($this->description)) {
            $this->description = $this->getOrderField(
                PaayFeedSetting::ORDER_DETAIL,
                $this->orderDetailType
            );
        }
        
    }

    /**
     * @return string
     */
    public function getOrderId()
    {
        return $this->orderId;
    }

    /**
     * @return mixed
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @return mixed
     */
    public function getBillingAddressFirstName()
    {
        return $this->billingAddressFirstName;
    }

    /**
     * @return mixed
     */
    public function getBillingAddressLastName()
    {
        return $this->billingAddressLastName;
    }
    

    /**
     * @return mixed
     */
    public function getBillingAddressCompany()
    {
        return $this->billingAddressCompany;
    }

    /**
     * @return mixed
     */
    public function getBillingAddressAddress1()
    {
        return $this->billingAddressAddress1;
    }

    /**
     * @return mixed
     */
    public function getBillingAddressAddress2()
    {
        return $this->billingAddressAddress2;
    }
    

    /**
     * @return mixed
     */
    public function getBillingAddressCity()
    {
        return $this->billingAddressCity;
    }

    /**
     * @return mixed
     */
    public function getBillingAddressState()
    {
        return $this->billingAddressState;
    }

    /**
     * @return mixed
     */
    public function getBillingAddressZip()
    {
        return $this->billingAddressZip;
    }

    /**
     * @return mixed
     */
    public function getBillingAddressCountry()
    {
        return $this->billingAddressCountry;
    }

    /**
     * @return mixed
     */
    public function getBillingPhone()
    {
        return $this->billingPhone;
    }

    /**
     * @return mixed
     */
    public function getShippingAddressAddress1()
    {
        return $this->shippingAddressAddress1;
    }

    /**
     * @return mixed
     */
    public function getShippingAddressAddress2()
    {
        return $this->shippingAddressAddress2;
    }

    /**
     * @return mixed
     */
    public function getShippingAddressCity()
    {
        return $this->shippingAddressCity;
    }

    /**
     * @return mixed
     */
    public function getShippingAddressState()
    {
        return $this->shippingAddressState;
    }

    /**
     * @return mixed
     */
    public function getShippingAddressZip()
    {
        return $this->shippingAddressZip;
    }

    /**
     * @return mixed
     */
    public function getShippingAddressCountry()
    {
        return $this->shippingAddressCountry;
    }

    /**
     * @return mixed
     */
    public function getShippingPhone()
    {
        return $this->shippingPhone;
    }

    /**
     * @return mixed
     */
    public function getShippingAddressFirstName()
    {
        return $this->shippingAddressFirstName;
    }

    /**
     * @return mixed
     */
    public function getShippingAddressLastName()
    {
        return $this->shippingAddressLastName;
    }
    
}
