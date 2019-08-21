<?php

namespace craft\commerce\paypal\models;

use craft\commerce\models\payments\BasePaymentForm;

class PaypalSubscriptionPaymentFormModel extends BasePaymentForm {
    /**
     * We dont need to create a payment source for subscriptions
     * @param null $attributeNames
     * @param bool $clearErrors
     * @return bool
     */
    public function validate($attributeNames = null, $clearErrors = true)
    {
        return false;
    }
}