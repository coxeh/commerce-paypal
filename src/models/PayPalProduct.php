<?php

namespace craft\commerce\paypal\models;


use craft\base\Model;
use craft\commerce\paypal\models\enum\ProductCategories;
use craft\commerce\paypal\models\enum\ProductType;
use craft\commerce\paypal\models\traits\DetectsChanges;

class PayPalProduct extends Model{
    use DetectsChanges;
    public $id;
    public $name;
    public $type;
    public $description;
    public $category;
    public $image_url;
    public $home_url;
    public $create_time;
    public $update_time;

    public function rules()
    {
        $rules = parent::rules();
        $rules[] = [['name','type'],'required'];
        $rules[] = [['name','type','description','category','image_url','home_url', 'id','create_time','update_time'],'string'];
        $rules[] = [['category'],'validateCategory'];
        $rules[] = [['type'],'validateType'];

        return $rules;
    }

    public function validateCategory($attribute, $params, $validator){
        if (!in_array($this->$attribute, ProductCategories::Keys())) {
            $this->addError($attribute, 'Category is invalid');
        }
    }
    public function validateType($attribute, $params, $validator){
        if (!in_array($this->$attribute, ProductType::Keys())) {
            $this->addError($attribute, 'Type is invalid');
        }
    }
}