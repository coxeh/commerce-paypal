<?php
namespace craft\commerce\paypal\gateways;

use Craft;
use craft\commerce\base\Plan;
use craft\commerce\base\RequestResponseInterface;
use craft\commerce\base\SubscriptionResponseInterface;
use craft\commerce\elements\Subscription;
use craft\commerce\errors\SubscriptionException;
use craft\commerce\models\payments\BasePaymentForm;
use craft\commerce\models\PaymentSource;
use craft\commerce\models\subscriptions\CancelSubscriptionForm;
use craft\commerce\models\subscriptions\SubscriptionForm;
use craft\commerce\models\subscriptions\SubscriptionPayment;
use craft\commerce\models\subscriptions\SwitchPlansForm;
use craft\commerce\models\Transaction;
use craft\commerce\paypal\contracts\PaypalRestApiGateway;
use craft\commerce\paypal\models\enum\FrequencyType;
use craft\commerce\paypal\models\enum\ProductCategories;
use craft\commerce\paypal\models\enum\ProductType;
use craft\commerce\paypal\models\enum\SetupFeeFailureType;
use craft\commerce\paypal\models\PayPalBillingPlan;
use craft\commerce\paypal\models\PaypalSubscriptionFormModel;
use craft\commerce\paypal\models\PaypalSubscriptionPaymentFormModel;
use craft\commerce\paypal\models\PaypalSubscriptionPaymentSource;
use craft\commerce\paypal\models\SubscriptionRequest;
use craft\commerce\paypal\PayPalSubscriptionBundle;
use craft\commerce\paypal\Plugin;
use craft\commerce\paypal\services\PayPalApiServiceFactory;
use craft\elements\User;
use craft\web\Response as WebResponse;
use \craft\commerce\base\SubscriptionGateway;
use craft\web\View;
use PayPal\Api\Payment;
use Throwable;

class PayPalRestSubscription extends SubscriptionGateway implements PaypalRestApiGateway {
    public $clientId;
    public $secret;
    public $testMode;
    public $productId;
    public $type;
    public $category;
    public $description;
    private $paymentSource;

    public static function displayName(): string
    {
        return 'Paypal Subscriptions';
    }

    public function getSettingsHtml()
    {
        $types = (new ProductType())->asOptions();
        $categories = (new ProductCategories())->asOptions();
        return \Craft::$app->getView()->renderTemplate('commerce-paypal/restSubscription/gatewaySettings', [
            'gateway' => $this,
            'types'=>$types,
            'categories'=>$categories
        ]);
    }

    /**
     * Returns payment Form HTML
     *
     * @param array $params
     * @return string|null
     */
    public function getPaymentFormHtml(array $params)
    {
        return 'test123';
    }

    /**
     * Makes an authorize request.
     *
     * @param Transaction $transaction The authorize transaction
     * @param BasePaymentForm $form A form filled with payment info
     * @return RequestResponseInterface
     */
    public function authorize(Transaction $transaction, BasePaymentForm $form): RequestResponseInterface
    {
        // TODO: Implement authorize() method.
    }

    /**
     * Makes a capture request.
     *
     * @param Transaction $transaction The capture transaction
     * @param string $reference Reference for the transaction being captured.
     * @return RequestResponseInterface
     */
    public function capture(Transaction $transaction, string $reference): RequestResponseInterface
    {
        // TODO: Implement capture() method.
    }

    /**
     * Complete the authorization for offsite payments.
     *
     * @param Transaction $transaction The transaction
     * @return RequestResponseInterface
     */
    public function completeAuthorize(Transaction $transaction): RequestResponseInterface
    {
        // TODO: Implement completeAuthorize() method.
    }

    /**
     * Complete the purchase for offsite payments.
     *
     * @param Transaction $transaction The transaction
     * @return RequestResponseInterface
     */
    public function completePurchase(Transaction $transaction): RequestResponseInterface
    {
        // TODO: Implement completePurchase() method.
    }

    /**
     * Creates a payment source from source data and user id.
     *
     * @param BasePaymentForm $sourceData
     * @param int $userId
     * @return PaymentSource
     */
    public function createPaymentSource(BasePaymentForm $sourceData, int $userId): PaymentSource
    {
        return new PaymentSource();
    }

    /**
     * Deletes a payment source on the gateway by its token.
     *
     * @param string $token
     * @return bool
     */
    public function deletePaymentSource($token): bool
    {
        // TODO: Implement deletePaymentSource() method.
    }

    /**
     * Returns payment form model to use in payment forms.
     *
     * @return BasePaymentForm
     */
    public function getPaymentFormModel(): BasePaymentForm
    {
        return new PaypalSubscriptionPaymentFormModel();
    }

    /**
     * Makes a purchase request.
     *
     * @param Transaction $transaction The purchase transaction
     * @param BasePaymentForm $form A form filled with payment info
     * @return RequestResponseInterface
     */
    public function purchase(Transaction $transaction, BasePaymentForm $form): RequestResponseInterface
    {
        // TODO: Implement purchase() method.
    }

    /**
     * Makes an refund request.
     *
     * @param Transaction $transaction The refund transaction
     * @return RequestResponseInterface
     */
    public function refund(Transaction $transaction): RequestResponseInterface
    {
        // TODO: Implement refund() method.
    }

    /**
     * Processes a webhook and return a response
     *
     * @return WebResponse
     * @throws Throwable if something goes wrong
     */
    public function processWebHook(): WebResponse
    {
        // TODO: Implement processWebHook() method.
    }

    /**
     * Returns true if gateway supports authorize requests.
     *
     * @return bool
     */
    public function supportsAuthorize(): bool
    {
        // TODO: Implement supportsAuthorize() method.
    }

    /**
     * Returns true if gateway supports capture requests.
     *
     * @return bool
     */
    public function supportsCapture(): bool
    {
        // TODO: Implement supportsCapture() method.
    }

    /**
     * Returns true if gateway supports completing authorize requests
     *
     * @return bool
     */
    public function supportsCompleteAuthorize(): bool
    {
        // TODO: Implement supportsCompleteAuthorize() method.
    }

    /**
     * Returns true if gateway supports completing purchase requests
     *
     * @return bool
     */
    public function supportsCompletePurchase(): bool
    {
        // TODO: Implement supportsCompletePurchase() method.
    }

    /**
     * Returns true if gateway supports payment sources
     *
     * @return bool
     */
    public function supportsPaymentSources(): bool
    {
        // TODO: Implement supportsPaymentSources() method.
    }

    /**
     * Returns true if gateway supports purchase requests.
     *
     * @return bool
     */
    public function supportsPurchase(): bool
    {
        // TODO: Implement supportsPurchase() method.
    }

    /**
     * Returns true if gateway supports refund requests.
     *
     * @return bool
     */
    public function supportsRefund(): bool
    {
        // TODO: Implement supportsRefund() method.
    }

    /**
     * Returns true if gateway supports partial refund requests.
     *
     * @return bool
     */
    public function supportsPartialRefund(): bool
    {
        // TODO: Implement supportsPartialRefund() method.
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

                $paypalRequest = new SubscriptionRequest();
                $paypalRequest->userId = $user->getId();
                $paypalRequest->planId = $plan->id;
                $paypalRequest->gatewayId = $this->id;

                $subscriptionResponse = $apiService->createSubscriptionRequest($plan);
                if(is_null($subscriptionResponse)){
                    throw new SubscriptionException('Could Not Create Subscription');
                }
                $paypalRequest->setResponse($subscriptionResponse);

                Plugin::getInstance()->subscriptions->saveSubscriptionRequest($paypalRequest);

                $paypalRequest->handleRedirect();
            }else{
                throw new SubscriptionException('Subscription Plan is invalid');
            }
        }else{
            dd('handle confirmation');
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
}
