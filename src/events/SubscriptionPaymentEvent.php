<?php
namespace craft\commerce\paypal\events;

use craft\commerce\paypal\models\PayPalSubscriptionPayment;
use yii\base\Event;

/**
 * Class SubscriptionPaymentEvent
 *
 */
class SubscriptionPaymentEvent extends Event
{
    /**
     * @var PayPalSubscriptionPayment SubscriptionPayment
     */
    public $subscriptionPayment;
    /**
     * @var bool IsValid
     */
    public $isValid = true;
}
