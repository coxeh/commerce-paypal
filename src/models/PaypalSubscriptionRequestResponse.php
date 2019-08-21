<?php

namespace craft\commerce\paypal\models;

use craft\base\Model;

class PaypalSubscriptionRequestResponse extends Model {
    public $redirectLink;
    public $id;
    public $status;
}