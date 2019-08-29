<?php

namespace craft\commerce\paypal\models\traits;

trait DetectsChanges {
    protected $originalPublicAttributes = [];

    public function init(){
        $init = parent::init();
        $this->syncDirtyData();
        return $init;
    }

    public function isDirty($attribute = null){
        if(is_null($attribute)){
            foreach($this->originalPublicAttributes as $key=>$originalValue){
                if($this->{$key} !== $originalValue){
                    return true;
                }
            }
            return false;
        }
        return $this->originalPublicAttributes[$attribute] !== $this->{$attribute};
    }

    public function syncDirtyData(){
        $reflection = new \ReflectionClass($this);
        $properties = $reflection->getProperties(\ReflectionProperty::IS_PUBLIC);
        foreach($properties as $property){
            $this->originalPublicAttributes[$property->getName()] = $property->getValue($this);
        }
        return $this;
    }
}
