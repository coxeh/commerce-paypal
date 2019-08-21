<?php
namespace craft\commerce\paypal\behaviours;

use craft\commerce\paypal\gateways\PayPalRestSubscription;
use yii\base\Behavior;
class DetectsChangesBehaviour extends Behavior
{
    protected $originalPublicAttributes = [];
    public function events()
    {
        return [
            PayPalRestSubscription::EVENT_INIT => 'initOwner'
        ];
    }

    public function initOwner(){
        $this->syncDirtyData();
    }

    public function isDirty($attribute = null){
        if(is_null($attribute)){
            foreach($this->originalPublicAttributes as $key=>$originalValue){
                if($this->owner->{$key} !== $originalValue){
                    return true;
                }
            }
        }
        return $this->originalPublicAttributes[$attribute] !== $this->owner->{$attribute};
    }

    public function syncDirtyData(){
        $reflection = new \ReflectionClass($this->owner);
        $properties = $reflection->getProperties();
        foreach($properties as $property){
            $this->originalPublicAttributes[$property->getName()] = $property->getValue($this->owner);
        }
        return $this->owner;
    }
}