<?php
namespace craft\commerce\paypal\events;

use craft\commerce\paypal\models\PayPalSubscription;
use yii\base\Event;

/**
 * Class SubscriptionRequestEvent
 *
 */
class SubscriptionRequestEvent extends Event
{
    /**
     * @var PayPalSubscription SubscriptionRequest
     */
    public $subscriptionRequest;
    /**
     * @var bool IsValid
     */
    public $isValid = true;
}
