<?php

namespace craft\commerce\paypal\models\enum;


class FrequencyType{

    protected $types = [
        'DAY' => 'Days',
        'WEEK' => 'Weeks',
        'MONTH' => 'Month',
        'YEAR' => 'Years'
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