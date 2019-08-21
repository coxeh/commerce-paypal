<?php
namespace craft\commerce\paypal\behaviours;

use craft\commerce\paypal\contracts\PaypalRestApiGateway;
use craft\commerce\paypal\gateways\PayPalRestSubscription;
use craft\commerce\paypal\models\PayPalProduct;
use craft\commerce\paypal\services\PayPalApiService;
use craft\commerce\paypal\services\PayPalApiServiceFactory;
use yii\base\Behavior;
use yii\base\ModelEvent;

class PayPalGatewaySubscriptionBehaviour extends Behavior
{

    public function events()
    {
        return [
            PayPalRestSubscription::EVENT_BEFORE_VALIDATE => 'syncProduct',
        ];
    }


    /**
     * @param ModelEvent $event
     * @throws \yii\base\Exception
     * @throws \yii\base\InvalidConfigException
     */
    public function syncProduct(ModelEvent $event){
        if($event->sender->isArchived === false){
            if(!empty($event->sender->productId)){
                $event->sender->productId = \Craft::$app->security->validateData($event->sender->productId);
                if($event->sender->productId === '') {
                    $event->sender->productId = null;
                }
            }
            $apiService = PayPalApiServiceFactory::CreateForGateway($event->sender);
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
     * @return PayPalProduct|null
     */
    protected function syncPayPalProduct(PayPalApiService $apiService, PaypalRestApiGateway $gateway){
        if (is_null($gateway->getProductId())) {
            $product = $this->createProduct($apiService,$gateway);
            $gateway->productId = $product->id;
        } else {
            $product = $apiService->getProductById($gateway->getProductId());
            if (is_null($product)) {
                $product = $this->createProduct($apiService,$gateway);
                $gateway->productId = $product->id;
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
}
