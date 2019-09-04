<?php

namespace craft\commerce\paypal\records;

use craft\db\ActiveRecord;

/**
 * Class SubscriptionPayment
 * @package craft\commerce\paypal\records
 * @property integer subscriptionId
 * @property float paymentAmount
 * @property string currencyCode
 * @property string paymentDate
 * @property string paymentReference
 * @property boolean paid
 * @property string dateCreated
 * @property string dateUpdated
 * @property string uid
 */

class SubscriptionPayment extends ActiveRecord {

    public static function tableName(): string
    {
        return '{{%commerce_paypal_subscription_payments}}';
    }
}
