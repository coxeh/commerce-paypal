<?php

namespace craft\commerce\paypal\records;

use craft\db\ActiveRecord;

/**
 * Class SubscriptionRequest
 * @package craft\commerce\paypal\records
 * @property integer userId
 * @property integer planId
 * @property integer gatewayId
 * @property integer subscriptionId
 * @property string paypalSubscriptionId
 * @property string status
 * @property string redirectLink
 * @property string dateCreated
 * @property string dateUpdated
 * @property string uid
 */

class SubscriptionRequest extends ActiveRecord {

    public static function tableName(): string
    {
        return '{{%commerce_paypal_subscription_requests}}';
    }
}
