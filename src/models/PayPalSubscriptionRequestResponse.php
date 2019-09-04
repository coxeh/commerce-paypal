<?php

namespace craft\commerce\paypal\models;

use craft\base\Model;

class PayPalSubscriptionRequestResponse extends Model {
    public $redirectLink;
    public $id;
    public $status;
    public $nextBillingTime;
    public $startTime;
}