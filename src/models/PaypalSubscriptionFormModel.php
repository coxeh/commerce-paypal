<?php

namespace craft\commerce\paypal\models;

use craft\commerce\models\subscriptions\SubscriptionForm;

class PaypalSubscriptionFormModel extends SubscriptionForm
{
    private $performRedirect = true;
    public $successUrl;
    public $payPalSubscription;
    public $cancelUrl;
    public $redirect;
    public function rules()
    {
        return [];
    }

    public function performRedirect(){
        return $this->performRedirect;
    }

    public function disableRedirect(){
        $this->performRedirect = false;
        return $this;
    }

    public function setPayPalSubscription(PayPalSubscription $payPalSubscription){
        $this->payPalSubscription = $payPalSubscription;
        return $this;
    }
}