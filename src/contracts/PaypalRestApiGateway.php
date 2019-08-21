<?php
namespace craft\commerce\paypal\contracts;

use craft\commerce\base\SubscriptionGatewayInterface;

interface  PaypalRestApiGateway extends SubscriptionGatewayInterface {
    public function getClientId();
    public function getSecret();
    public function getIsTestMode();
    public function getName();
    public function getDescription();
    public function getType();
    public function getCategory();
    public function getProductId();
}