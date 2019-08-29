<?php
namespace craft\commerce\paypal\behaviours;

use craft\commerce\paypal\contracts\PaypalRestApiGateway;
use craft\commerce\paypal\gateways\PayPalRestSubscription;
use craft\commerce\paypal\models\PayPalProduct;
use craft\commerce\paypal\models\PayPalWebhook;
use craft\commerce\paypal\services\PayPalApiService;
use craft\commerce\paypal\services\PayPalApiServiceFactory;
use PayPal\Exception\PayPalConnectionException;
use yii\base\Behavior;
use yii\base\ModelEvent;

class PayPalGatewaySubscriptionBehaviour extends Behavior
{

    public function events()
    {
        return [
            PayPalRestSubscription::EVENT_BEFORE_VALIDATE => 'syncWithPaypal',
        ];
    }


    /**
     * @param ModelEvent $event
     * @throws \yii\base\Exception
     * @throws \yii\base\InvalidConfigException
     */
    public function syncWithPaypal(ModelEvent $event){
        if($event->sender->isArchived === false){

            $this->handleSecureGatewayParam($event->sender,'productId');
            $this->handleSecureGatewayParam($event->sender,'testWebhookId');
            $this->handleSecureGatewayParam($event->sender,'webhookId');

            $apiService = PayPalApiServiceFactory::CreateForGateway($event->sender);
            $this->syncPayPalWebhooks($apiService, $event->sender);
            $product = $this->syncPayPalProduct($apiService, $event->sender);
            if(!is_null($product) && count($product->errors)>0){
                $event->sender->addErrors($product->errors);
                $event->isValid = false;
            }
        }
    }

    /**
     * @param PayPalApiService $apiService
     * @param PaypalRestApiGateway $gateway
     * @return PayPalWebhook
     * @throws PayPalConnectionException
     */
    protected function syncPayPalWebhooks(PayPalApiService $apiService, PaypalRestApiGateway $gateway){
        $webhookId = $gateway->getIsTestMode() ? $gateway->getTestWebhookId() : $gateway->getWebhookId();
        $newWebhook = new PayPalWebhook([ 'url' => $gateway->getWebhookUrl() ]);
        if(empty($webhookId)){
            $webhook = $apiService->createSubscriptionWebHook($newWebhook);
        } else {
            try{
                $webhook = $apiService->getSubscriptionWebhookById($webhookId);
                $webhook->url = $gateway->getWebhookUrl();
                $webhook = $apiService->updateSubscriptionWebhookUrl($webhook);
            }catch (PayPalConnectionException $e){
                if($e->getCode() == 404) {
                    $webhook = $apiService->createSubscriptionWebHook($newWebhook);
                }else{
                    throw $e;
                }
            }
        }
        if($gateway->getIsTestMode()){
            $gateway->setTestWebhookId($webhook->id);
        }else{
            $gateway->setWebhookId($webhook->id);
        }
        return $webhook;
    }

    /**
     * @param PayPalApiService $apiService
     * @param PaypalRestApiGateway $gateway
     * @return PayPalProduct|null
     */
    protected function syncPayPalProduct(PayPalApiService $apiService, PaypalRestApiGateway $gateway){
        if (is_null($gateway->getProductId())) {
            $product = $this->createProduct($apiService,$gateway);
            $gateway->setProductId($product->id);
        } else {
            $product = $apiService->getProductById($gateway->getProductId());
            if (is_null($product)) {
                $product = $this->createProduct($apiService,$gateway);
                $gateway->setProductId($product->id);
            } else {
                $product = $this->updateProduct($apiService,$gateway,$product);
            }
        }
        return $product;
    }

    /**
     * @param PayPalApiService $apiService
     * @param PaypalRestApiGateway $gateway
     * @return PayPalProduct
     */
    protected function createProduct(PayPalApiService $apiService, PaypalRestApiGateway $gateway){
        $product = new PayPalProduct();
        $product->name = $gateway->getName();
        $product->description = $gateway->getDescription();
        $product->type = $gateway->getType();
        $product->category = $gateway->getCategory();
        return $apiService->createProduct($product);
    }

    /**
     * @param PayPalApiService $apiService
     * @param PaypalRestApiGateway $gateway
     * @param PayPalProduct $product
     * @return PayPalProduct
     */
    protected function updateProduct(PayPalApiService $apiService, PaypalRestApiGateway $gateway, PayPalProduct $product){
        $product->name = $gateway->getName();
        $product->description = $gateway->getDescription();
        $product->type = $gateway->getType();
        $product->category = $gateway->getCategory();
        return $apiService->updateProduct($product);
    }

    protected function handleSecureGatewayParam($gateway, $key){
        if(!empty($gateway->$key)){
            $gateway->$key = \Craft::$app->security->validateData($gateway->$key);
            if($gateway->$key === '') {
                $gateway->$key = null;
            }
        }
        return $gateway;
    }
}
