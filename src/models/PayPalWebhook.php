<?php

namespace craft\commerce\paypal\models;


use craft\base\Model;
use craft\commerce\paypal\models\traits\DetectsChanges;

class PayPalWebhook extends Model
{
    use DetectsChanges;
    public $id;
    public $url;

    public function rules()
    {
        $rules = parent::rules();
        $rules[] = [['url'], 'required'];
        $rules[] = [['url'], 'url'];

        return $rules;
    }
}