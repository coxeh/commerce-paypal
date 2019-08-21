<?php
namespace craft\commerce\paypal\events;

use craft\commerce\paypal\models\SubscriptionRequest;
use yii\base\Event;

/**
 * Class SubscriptionRequestEvent
 *
 */
class SubscriptionRequestEvent extends Event
{
    /**
     * @var SubscriptionRequest SubscriptionRequest
     */
    public $subscriptionRequest;
    /**
     * @var bool IsValid
     */
    public $isValid = true;
}
