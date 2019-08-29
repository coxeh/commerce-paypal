<?php

namespace craft\commerce\paypal\controllers;

use craft\commerce\paypal\Plugin as PayPalPlugin;
use craft\commerce\paypal\services\PaypalSubscriptionService;
use craft\web\Controller as BaseController;


class SubscriptionController extends BaseController
{
    protected $allowAnonymous = true;
    public function actionSuccess(){
        $request = \Craft::$app->request;
        $subscriptionService = $this->getSubscriptionService();

        $subscriptionId = $request->getQueryParam('subscription_id');
        $subscription = $subscriptionService->getSubscriptionRequestByPayPalSubscriptionId($subscriptionId);
        $subscriptionService->updateSubscriptionStatus($subscription);

    }

    public function actionCancel(){
        $request = \Craft::$app->request;
        $subscriptionId = $request->getBodyParam('subscription_id');
        dd($subscriptionId);
    }

    /**
     * @return PaypalSubscriptionService
     */
    protected function getSubscriptionService(){
        return PayPalPlugin::getInstance()->subscriptions;
    }
}