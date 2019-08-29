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
use craft\commerce\paypal\models\PaypalSubscriptionFormModel;
use craft\commerce\paypal\PayPalSubscriptionBundle;
use craft\commerce\paypal\Plugin;
use craft\commerce\paypal\services\PayPalApiServiceFactory;
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
        // TODO: Implement getCancelSubscriptionFormHtml() method.
    }

    /**
     * Returns the cancel subscription form model
     *
     * @return CancelSubscriptionForm
     */
    public function getCancelSubscriptionFormModel(): CancelSubscriptionForm
    {
        // TODO: Implement getCancelSubscriptionFormModel() method.
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
        return new PaypalSubscriptionFormModel();
    }

    /**
     * Returns the form model used for switching plans.
     *
     * @return SwitchPlansForm
     */
    public function getSwitchPlansFormModel(): SwitchPlansForm
    {
        // TODO: Implement getSwitchPlansFormModel() method.
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
        // TODO: Implement cancelSubscription() method.
    }

    /**
     * Returns the next payment amount for a subscription, taking into account all discounts.
     *
     * @param Subscription $subscription
     * @return string next payment amount with currency code
     */
    public function getNextPaymentAmount(Subscription $subscription): string
    {
        // TODO: Implement getNextPaymentAmount() method.
    }

    /**
     * Returns a list of subscription payments for a given subscription.
     *
     * @param Subscription $subscription
     * @return SubscriptionPayment[]
     */
    public function getSubscriptionPayments(Subscription $subscription): array
    {
        // TODO: Implement getSubscriptionPayments() method.
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
        // TODO: Implement getSubscriptionPlans() method.
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
        if($parameters instanceof PaypalSubscriptionFormModel === false){
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

                Plugin::getInstance()->subscriptions->saveSubscriptionRequest($paypalRequest);
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
        // TODO: Implement switchSubscriptionPlan() method.
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
        // TODO: Implement supportsPlanSwitch() method.
    }
}
