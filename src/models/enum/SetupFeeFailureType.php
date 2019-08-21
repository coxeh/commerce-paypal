<?php

namespace craft\commerce\paypal\models\enum;


class SetupFeeFailureType{

    protected $types = [
        'CONTINUE' => 'Continue',
        'CANCEL' => 'Cancel'
    ];

    public function getKeys(){
        return array_keys($this->types);
    }

    public static function Keys(){
        return (new self())->getKeys();
    }

    public function asOptions(){
        return array_map(function($key){
            return [
                'value'=>$key,
                'label'=>$this->types[$key]
            ];
        },array_keys($this->types));
    }
}