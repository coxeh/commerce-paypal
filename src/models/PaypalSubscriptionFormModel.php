<?php

namespace craft\commerce\paypal\models;

use craft\commerce\models\subscriptions\SubscriptionForm;

class PaypalSubscriptionFormModel extends SubscriptionForm
{
    private $performRedirect = true;
    public function rules()
    {
        return [];
    }

    public function performRedirect(){
        return $this->performRedirect;
    }
}