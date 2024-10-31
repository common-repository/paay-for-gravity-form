<?php

class Confirmations
{
    private $confirmations;

    private $isDefault = false;
    private $defaultId;

    private $isActive = false;
    private $activeId;

    public function __construct(array $confirmations)
    {
        $this->confirmations = $confirmations;
    }

    public function url()
    {
        $this->findDefault();
        $this->findActive();

        if(!$this->isDefault && !$this->isActive){
            return null;
        }
        if($this->isActive){
            return $this->selectUrl($this->activeId);
        }

        return $this->selectUrl($this->defaultId);
    }

    private function findDefault()
    {
        foreach($this->confirmations as $confirmation){
            if(!array_key_exists('isDefault', $confirmation)){
                continue;
            }
            if(!$confirmation['isDefault']){
                continue;
            }
            $this->isDefault = true;
            $this->defaultId = $confirmation['id'];
        }
    }

    // find last active confirmation
    private function findActive()
    {
        foreach($this->confirmations as $confirmation){
            if(!array_key_exists('isActive', $confirmation)){
                continue;
            }
            if(!$confirmation['isActive']){
                continue;
            }
            $this->isActive = true;
            $this->activeId = $confirmation['id'];
        }
    }

    private function selectUrl($confirmationId)
    {
        $conf = $this->confirmations[$confirmationId];

        switch($conf['type']){
            case 'redirect':
                return $conf['url'];
            case 'page':
                return get_permalink($conf['pageId']);
            case 'message':
                return trim(get_permalink(), '/') . '?paay_confirmations=' . $confirmationId;
            default:
                return trim(get_permalink(), '/');
        }
    }
}
