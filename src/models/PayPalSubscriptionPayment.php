<?php

namespace craft\commerce\paypal\models;

use craft\commerce\models\subscriptions\SubscriptionPayment;
use craft\commerce\paypal\models\traits\DetectsChanges;

/**
 * Class SubscriptionPayment
 * @package craft\commerce\paypal\models
 * @property integer subscriptionId
 * @property float paymentAmount
 * @property string currencyCode
 * @property string paymentDate
 * @property string paymentReference
 * @property boolean paid
 */

class PayPalSubscriptionPayment extends SubscriptionPayment
{
    use DetectsChanges;
    public $currencyCode;
    public function rules()
    {
        return [
            [['subscriptionId','paymentAmount','currencyCode','paymentReference','paid'],'required'],
            [['subscriptionId'],'integer'],
            [['paymentAmount'],'float'],
            [['paid'],'boolean']
        ];
    }

}