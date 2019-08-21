<?php

namespace craft\commerce\paypal\models\enum;


class ProductType{
    protected $types = [
        'PHYSICAL' => 'Physical Goods',
        'DIGITAL' => 'Digital Goods',
        'SERVICE' => 'Service'
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