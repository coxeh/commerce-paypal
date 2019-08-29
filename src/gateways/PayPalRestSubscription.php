<?php
namespace craft\commerce\paypal\gateways;

use craft\commerce\paypal\contracts\PaypalRestApiGateway;
use craft\commerce\paypal\gateways\traits\HandlesRestSubscriptions;
use craft\commerce\paypal\gateways\traits\ProxiesRestGateway;
use \craft\commerce\base\SubscriptionGateway;

class PayPalRestSubscription extends SubscriptionGateway implements PaypalRestApiGateway {
    use HandlesRestSubscriptions, ProxiesRestGateway;
    public $clientId;
    public $secret;
    public $testMode;
    public $productId;
    public $type;
    public $category;
    public $description;
    public $testWebhookId;
    public $webhookId;

    public static function displayName(): string
    {
        return \Craft::t('commerce', 'PayPal REST gateway (with subscriptions)');
    }
    public function getClientId()
    {
        return $this->clientId;
    }

    public function getSecret()
    {
        return $this->secret;
    }

    public function getIsTestMode()
    {
        return $this->testMode == '1';
    }

    public function getName(){
        return $this->name;
    }
    public function getDescription(){
        return $this->description;
    }
    public function getType(){
        return $this->type;
    }
    public function getCategory(){
        return $this->category;
    }
    public function getProductId()
    {
        return $this->productId;
    }
    public function setProductId($id){
        $this->productId = $id;
        return $this;
    }
    public function setWebhookId($id){
        $this->webhookId = $id;
        return $this;
    }
    public function setTestWebhookId($id){
        $this->testWebhookId = $id;
        return $this;
    }
    public function getWebhookId(){
        return $this->webhookId;
    }
    public function getTestWebhookId(){
        return $this->testWebhookId;
    }
}
