<?php
namespace craft\commerce\paypal\services;

use craft\commerce\paypal\contracts\PaypalRestApiGateway;

class PayPalApiServiceFactory{
    /**
     * @param PaypalRestApiGateway $gateway
     * @return PayPalApiService
     */
    public static function CreateForGateway(PaypalRestApiGateway $gateway){
        return new PayPalApiService([
            'clientId'=>$gateway->getClientId(),
            'secret'=>$gateway->getSecret(),
            'testMode'=>$gateway->getIsTestMode()
        ]);
    }
}