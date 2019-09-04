<?php

namespace craft\commerce\paypal\gateways\traits;

use craft\commerce\base\Plan;
use craft\commerce\base\SubscriptionResponseInterface;
use craft\commerce\elements\Subscription;
use craft\commerce\errors\SubscriptionException;
use craft\commerce\models\subscriptions\CancelSubscriptionForm;
use craft\commerce\models\subscriptions\SubscriptionForm;
use craft\commerce\models\subscriptions\SubscriptionPayment;
use craft\commerce\models\subscriptions\SwitchPlansForm;
use craft\commerce\paypal\models\enum\FrequencyType;
use craft\commerce\paypal\models\enum\SetupFeeFailureType;
use craft\commerce\paypal\models\PayPalBillingPlan;
use craft\commerce\paypal\models\PayPalSubscription;
use craft\commerce\paypal\models\PayPalSubscriptionCancelFormModel;
use craft\commerce\paypal\models\PayPalSubscriptionFormModel;
use craft\commerce\paypal\models\PayPalSubscriptionSwitchFormModel;
use craft\commerce\paypal\PayPalSubscriptionBundle;
use craft\commerce\paypal\Plugin;
use craft\commerce\paypal\services\PayPalApiServiceFactory;
use craft\commerce\paypal\services\PaypalSubscriptionService;
use craft\elements\User;
use craft\web\Response as WebResponse;
use craft\web\View;
use Craft;
use Throwable;

trait HandlesRestSubscriptions{
    /**
     * Processes a webhook and return a response
     *
     * @return WebResponse
     * @throws Throwable if something goes wrong
     */
    public function processWebHook(): WebResponse
    {
        $apiService = PayPalApiServiceFactory::CreateForGateway($this);
        $webhookId = $this->getIsTestMode() ? $this->getTestWebhookId() : $this->getWebhookId();
        $validated = $apiService->verifyWebhookRequest($webhookId, \Craft::$app->request);
        if($validated){
            $webhookData = \Craft::$app->request->getBodyParams();
            switch($webhookData['event_type']){
                case 'BILLING.SUBSCRIPTION.CANCELLED':
                case 'BILLING.SUBSCRIPTION.SUSPENDED':
                case 'BILLING.SUBSCRIPTION.EXPIRED':
                    //Handle Cancellaations

                case 'PAYMENT.SALE.COMPLETED':
                    // $subscriptionRequest = $this->getPayPalSubscriptionService()->getSubscriptionRequestByPayPalSubscriptionId()
                    //Handle Payments

            }
        }

        $response = \Craft::$app->getResponse();
        $response->data = 'ok';
        return $response;
    }
    /**
     * Returns true if gateway supports webhooks.
     *
     * @return bool
     */
    public function supportsWebhooks(): bool
    {
        return true;
    }

    /**
     * Returns the cancel subscription form HTML
     *
     * @param Subscription $subscription the subscription to cancel
     *
     * @return string
     */
    public function getCancelSubscriptionFormHtml(Subscription $subscription): string
    {
        return '';
    }

    /**
     * Returns the cancel subscription form model
     *
     * @return CancelSubscriptionForm
     */
    public function getCancelSubscriptionFormModel(): CancelSubscriptionForm
    {
        return new PayPalSubscriptionCancelFormModel();
    }

    /**
     * Returns the subscription plan settings HTML
     *
     * @param array $params
     * @return string|null
     */
    public function getPlanSettingsHtml(array $params = [])
    {
        $view = Craft::$app->getView();
        $previousMode = $view->getTemplateMode();

        $view->setTemplateMode(View::TEMPLATE_MODE_CP);

        $view->registerAssetBundle(PayPalSubscriptionBundle::class);
        $html = \Craft::$app->getView()->renderTemplate('commerce-paypal/restSubscription/planSettings', [
            'gateway' => $this,
            'plan' => $params['plan'],
            'frequencies' => (new FrequencyType())->asOptions(),
            'setupFeeFailureTypes' => (new SetupFeeFailureType())->asOptions()
        ]);

        $view->setTemplateMode($previousMode);
        return $html;
    }

    /**
     * Returns the subscription plan model.
     *
     * @return Plan
     */
    public function getPlanModel(): Plan
    {
        return new PayPalBillingPlan();
    }

    /**
     * Returns the subscription form model
     *
     * @return SubscriptionForm
     */
    public function getSubscriptionFormModel(): SubscriptionForm
    {
        return new PayPalSubscriptionFormModel();
    }

    /**
     * Returns the form model used for switching plans.
     *
     * @return SwitchPlansForm
     */
    public function getSwitchPlansFormModel(): SwitchPlansForm
    {
        return new PayPalSubscriptionSwitchFormModel();
    }

    /**
     * Cancels a subscription.
     *
     * @param Subscription $subscription the subscription to cancel
     * @param CancelSubscriptionForm $parameters additional parameters to use
     * @return SubscriptionResponseInterface
     * @throws SubscriptionException for all subscription-related errors.
     */
    public function cancelSubscription(Subscription $subscription, CancelSubscriptionForm $parameters): SubscriptionResponseInterface
    {
        $subscriptionRequest = $this->getSubscriptionRequestFromSubscription($subscription);
        $apiService = PayPalApiServiceFactory::CreateForGateway($subscriptionRequest->getPlan()->getGateway());
        $hasCancelled = $apiService->cancelSubscription($subscriptionRequest->paypalSubscriptionId, 'User Cancelled');
        if($hasCancelled === false){
            throw new SubscriptionException('Subscription Could not be cancelled. Paypal Returned an error');
        }

        try{
            $subscriptionService = $this->getPayPalSubscriptionService();
            $subscriptionRequest->setAsCancelled();
            $subscriptionRequest = $subscriptionService->saveSubscriptionRequest($subscriptionRequest);
        }catch (\Exception $e){
            \Craft::info($e->getMessage(),Plugin::LogCategory);
            \Craft::info($e->getTraceAsString(),Plugin::LogCategory);
            throw new SubscriptionException('Could not update Subscription');
        }

        return $subscriptionRequest;
    }

    /**
     * Returns the next payment amount for a subscription, taking into account all discounts.
     *
     * @param Subscription $subscription
     * @return string next payment amount with currency code
     */
    public function getNextPaymentAmount(Subscription $subscription): string
    {
        return $subscription->plan->getPlanData()['subscriptionPrice'];
    }

    /**
     * Returns a list of subscription payments for a given subscription.
     *
     * @param Subscription $subscription
     * @return SubscriptionPayment[]
     */
    public function getSubscriptionPayments(Subscription $subscription): array
    {
        //todo
        return [];
    }

    /**
     * Returns a subscription plan by its reference
     *
     * @param string $reference
     * @return string
     */
    public function getSubscriptionPlanByReference(string $reference): string
    {
        $fieldPrefix = 'gateway.'.$this->id;
        $request = Craft::$app->getRequest();
        $gatewayParams = $request->getBodyParam($fieldPrefix);
        return json_encode($gatewayParams);
    }

    /**
     * Returns all subscription plans as array containing hashes with `reference` and `name` as keys.
     *
     * @return array
     */
    public function getSubscriptionPlans(): array
    {
        // This seems to be here only for the stripe gateway ???
       $apiService = PayPalApiServiceFactory::CreateForGateway($this);
       return array_map(
           function(\PayPal\Api\Plan $plan){
               return [
                   'reference'=>$plan->getId(),
                   'name'=>$plan->getName()
               ];
           },
           $apiService->getPlans()->toArray()
       );
    }

    /**
     * Subscribe user to a plan.
     *
     * @param User $user the Craft user to subscribe
     * @param Plan $plan the plan to subscribe to
     * @param SubscriptionForm $parameters additional parameters to use
     * @return SubscriptionResponseInterface
     * @throws SubscriptionException for all subscription-related errors.
     */
    public function subscribe(User $user, Plan $plan, SubscriptionForm $parameters): SubscriptionResponseInterface
    {
        if($parameters instanceof PayPalSubscriptionFormModel === false){
            throw new SubscriptionException('Subscription form model is invalid');
        }

        if($parameters->performRedirect() === true){

            if($plan instanceof \craft\commerce\paypal\contracts\PaypalBillingPlan){
                $apiService = PayPalApiServiceFactory::CreateForGateway($this);

                $paypalRequest = new PayPalSubscription();
                $paypalRequest->userId = $user->getId();
                $paypalRequest->planId = $plan->id;
                $paypalRequest->gatewayId = $this->id;

                $subscriptionResponse = $apiService->createSubscriptionRequest($plan);

                if(is_null($subscriptionResponse)){
                    throw new SubscriptionException('Could Not Create Subscription');
                }
                $paypalRequest->setResponse($subscriptionResponse);

                $this->getPayPalSubscriptionService()->saveSubscriptionRequest($paypalRequest);
                if($paypalRequest->hasErrors()){
                    throw new SubscriptionException('Could not save paypal request due to errors');
                }

                $paypalRequest->handleRedirect();
            }else{
                throw new SubscriptionException('Subscription Plan is invalid');
            }
        }else{
            return $parameters->payPalSubscription;
        }

    }

    /**
     * Switch a subscription to a different subscription plan.
     *
     * @param Subscription $subscription the subscription to modify
     * @param Plan $plan the plan to change the subscription to
     * @param SwitchPlansForm $parameters additional parameters to use
     * @return SubscriptionResponseInterface
     */
    public function switchSubscriptionPlan(Subscription $subscription, Plan $plan, SwitchPlansForm $parameters): SubscriptionResponseInterface
    {
        $subscriptionRequest = $this->getSubscriptionRequestFromSubscription($subscription);
        $subscriptionRequest->planId = $plan->id;
        $apiService = PayPalApiServiceFactory::CreateForGateway($subscriptionRequest->getPlan()->getGateway());
        $apiService->updateSubscription($subscriptionRequest);
        $this->getPayPalSubscriptionService()->saveSubscriptionRequest($subscriptionRequest);

        return $subscriptionRequest;
    }

    /**
     * Reactivates the subscription if it has been cancelled?? or suspended
     * @param Subscription $subscription
     * @return SubscriptionResponseInterface
     */
    public function reactivateSubscription(Subscription $subscription): SubscriptionResponseInterface
    {
        $subscriptionRequest = $this->getSubscriptionRequestFromSubscription($subscription);

        $apiService = PayPalApiServiceFactory::CreateForGateway($subscriptionRequest->getPlan()->getGateway());
        $hasReactivated = $apiService->activateSubscription($subscriptionRequest->paypalSubscriptionId, 'User Reactivated');
        if($hasReactivated === false){
            throw new SubscriptionException('Subscription Could not be cancelled. Paypal Returned an error');
        }

        try{
            $subscriptionService = $this->getPayPalSubscriptionService();
            $subscriptionRequest->setAsActive();
            $subscriptionRequest = $subscriptionService->saveSubscriptionRequest($subscriptionRequest);
        }catch (\Exception $e){
            \Craft::info($e->getMessage(),Plugin::LogCategory);
            \Craft::info($e->getTraceAsString(),Plugin::LogCategory);
            throw new SubscriptionException('Could not update Subscription');
        }

        return $subscriptionRequest;
    }

    /**
     * Returns whether this gateway supports reactivating subscriptions.
     *
     * @return bool
     */
    public function supportsReactivation(): bool
    {
        return true;
    }

    /**
     * Returns whether this gateway supports switching plans.
     *
     * @return bool
     */
    public function supportsPlanSwitch(): bool
    {
        return true;
    }

    /**
     * @return PaypalSubscriptionService
     */
    protected function getPayPalSubscriptionService(){
        return Plugin::getInstance()->subscriptions;
    }

    protected function getSubscriptionRequestFromSubscription(Subscription $subscription){
        $subscriptionService = $this->getPayPalSubscriptionService();
        $subscriptionRequest = $subscriptionService->getSubscriptionRequestBySubscriptionId($subscription->getId());
        if(is_null($subscriptionRequest)){
            throw new SubscriptionException('Subscription Request Not Found');
        }
        return $subscriptionRequest;
    }
}
