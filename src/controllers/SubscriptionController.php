<?php

namespace craft\commerce\paypal\controllers;

use craft\commerce\paypal\models\PayPalProduct;
use craft\web\Controller as BaseController;
use yii\web\NotFoundHttpException;


class SubscriptionController extends BaseController
{

    public function actionPlans($gatewayId){

        $product = new PayPalProduct();
        $product->name = 'Blah';
        $product->type = 'DIGITAL';
        $product->validate();

        /*$apiService = $this->getGatewayByIdOrFail($gatewayId)->getApiService();
        $this->asJson($apiService->getProducts());*/
    }



    protected function getGatewayByIdOrFail($gatewayId){
        $gateway =  \Craft::$app->getModule('commerce')->get('gateways')->getGatewayById($gatewayId);
        if(is_null($gateway)){
            throw new NotFoundHttpException('Gateway Not Found');
        }
        return $gateway;
    }
}