<?php
namespace craft\commerce\paypal\services;

use craft\base\Component;
use craft\commerce\paypal\contracts\PaypalBillingPlan;
use craft\commerce\paypal\models\PayPalProduct;
use craft\commerce\paypal\models\PayPalSubscription;
use craft\commerce\paypal\models\PayPalSubscriptionRequestResponse;
use craft\commerce\paypal\models\PayPalWebhook;
use craft\commerce\paypal\Plugin;
use craft\helpers\UrlHelper;
use craft\web\Request;
use PayPal\Api\ApplicationContext;
use PayPal\Api\BillingCycle;
use PayPal\Api\Frequency;
use PayPal\Api\Money;
use PayPal\Api\Patch;
use PayPal\Api\PatchRequest;
use PayPal\Api\PaymentPreferences;
use PayPal\Api\Plan;
use PayPal\Api\PricingScheme;
use PayPal\Api\Subscription;
use PayPal\Api\SubscriptionActivateRequest;
use PayPal\Api\SubscriptionCancelRequest;
use PayPal\Api\SubscriptionPlan;
use PayPal\Api\SubscriptionRequest;
use PayPal\Api\SubscriptionSuspendRequest;
use PayPal\Api\VerifyWebhookSignature;
use PayPal\Api\Webhook;
use PayPal\Api\WebhookEventType;
use PayPal\Auth\OAuthTokenCredential;
use PayPal\Exception\PayPalConnectionException;
use PayPal\Rest\ApiContext;
use PayPal\Api\Product;

class PayPalApiService extends Component{
    public $clientId;
    public $secret;
    public $testMode;
    protected $context;
    protected $subscriptionWebhookEvents = [
        'BILLING.SUBSCRIPTION.ACTIVATED',
        'BILLING.SUBSCRIPTION.CANCELLED',
        'BILLING.SUBSCRIPTION.CREATED',
        'BILLING.SUBSCRIPTION.EXPIRED',
        'BILLING.SUBSCRIPTION.RE-ACTIVATED',
        'BILLING.SUBSCRIPTION.RENEWED',
        'BILLING.SUBSCRIPTION.SUSPENDED',
        'BILLING.SUBSCRIPTION.UPDATED',
        'PAYMENT.SALE.COMPLETED'
    ];

    public function init()
    {
        parent::init();
        if(is_null($this->clientId) === false && is_null($this->secret) === false){
            $this->context = new ApiContext(
                new OAuthTokenCredential(
                    $this->clientId,
                    $this->secret
                )
            );
            if($this->testMode === false) {
                $config = $this->context->getConfig();
                $config['mode'] = 'live';
                $this->context->setConfig($config);
            }
        }
    }

    public function verifyWebhookRequest($webhookId, Request $request){
        $validator = new VerifyWebhookSignature();
        $validator->setWebhookId($webhookId);
        $validator->setAuthAlgo($request->headers->get('paypal-auth-algo'));
        $validator->setTransmissionId($request->headers->get('paypal-transmission-id'));
        $validator->setCertUrl($request->headers->get('paypal-cert-url'));
        $validator->setTransmissionSig($request->headers->get('paypal-transmission-sig'));
        $validator->setTransmissionTime($request->headers->get('paypal-transmission-time'));
        $validator->setRequestBody($request->getRawBody());
        try{
            $output = $validator->post($this->context);
            return $output->getVerificationStatus()==='SUCCESS';
        }catch (\Exception $e){
            \Craft::info($e->getMessage(),Plugin::LogCategory);
            \Craft::info($e->getTraceAsString(),Plugin::LogCategory);
        }
        return false;
    }

    public function updateSubscriptionWebhookUrl(PayPalWebhook $paypalWebhook){
        if($paypalWebhook->isDirty() && $paypalWebhook->validate()){
            $patchRequest = new PatchRequest();
            if($paypalWebhook->isDirty('url')){
                $patch = new Patch();
                $patch->setValue($paypalWebhook->url)->setOp('replace')->setPath('/url');
                $patchRequest->addPatch($patch);
            }
            if(count($patchRequest->getPatches()) > 0){
                try {
                    $payPalProduct = Product::get($paypalWebhook->id, $this->context);
                    $payPalProduct->update($patchRequest, $this->context);

                    return $paypalWebhook->syncDirtyData();
                }catch (PayPalConnectionException $e){
                    \Craft::info($e->getData(),Plugin::LogCategory);
                    $paypalWebhook->addError('api',$e->getMessage());
                }
            }
        }
        return $paypalWebhook;
    }
    public function getSubscriptionWebhookById($id){
        $webhook = Webhook::get($id, $this->context);
        return new PayPalWebhook([
            'id'=>$webhook->getId(),
            'url'=> $webhook->getUrl()
        ]);
    }
    /**
     * @param PayPalWebhook $paypalWebhook
     * @return PayPalWebhook
     */
    public function createSubscriptionWebHook(PayPalWebhook $paypalWebhook) {
        try{
            if($paypalWebhook->validate()){
                $eventTypes = array_map(function($eventName){
                    $event = new WebhookEventType();
                    $event->setName($eventName);
                    return $event;
                }, $this->subscriptionWebhookEvents);
                $webhook = new Webhook();
                $webhook->setUrl($paypalWebhook->url);
                $webhook->setEventTypes($eventTypes);
                $webhook->create($this->context);
                $paypalWebhook->id = $webhook->getId();
            }
        }catch (PayPalConnectionException $e){
            \Craft::info($e->getData(),Plugin::LogCategory);
            $paypalWebhook->addError($e->getMessage());
        }
        return $paypalWebhook;
    }

    /**
     * @param $subscriptionId
     * @return PayPalSubscriptionRequestResponse
     */
    public function getSubscription($subscriptionId){
        $subscription = Subscription::get($subscriptionId, $this->context);
        return new PayPalSubscriptionRequestResponse([
            'id'=>$subscription->getId(),
            'status'=>$subscription->getStatus(),
            'nextBillingTime'=>\DateTime::createFromFormat(
                DATE_ISO8601,
                $subscription->getBillingInfo()->getNextBillingTime()
            )
        ]);
    }

    public function suspendSubscription(string $subscriptionId, string $reason){
        $suspendRequest = new SubscriptionSuspendRequest();
        $suspendRequest->setId($subscriptionId);
        $suspendRequest->setReason($reason);
        try{
            $suspendRequest->create($this->context);
            return true;
        }catch (PayPalConnectionException $e){
            \Craft::info($e->getData(),Plugin::LogCategory);
            return false;
        }
    }

    public function activateSubscription(string $subscriptionId, string $reason){
        $activateRequest = new SubscriptionActivateRequest();
        $activateRequest->setId($subscriptionId);
        $activateRequest->setReason($reason);
        try{
            $activateRequest->create($this->context);
            return true;
        }catch (PayPalConnectionException $e){
            \Craft::info($e->getData(),Plugin::LogCategory);
            return false;
        }
    }

    public function cancelSubscription(string $subscriptionId, string $reason){
        $cancelRequest = new SubscriptionCancelRequest();
        $cancelRequest->setId($subscriptionId);
        $cancelRequest->setReason($reason);
        try{
            $cancelRequest->create($this->context);
            return true;
        }catch (PayPalConnectionException $e){
            \Craft::info($e->getData(),Plugin::LogCategory);
            return false;
        }
    }

    public function updateSubscription(PayPalSubscription $payPalSubscription){
        if($payPalSubscription->isDirty() && $payPalSubscription->validate()) {
            $patchRequest = new PatchRequest();
            if ($payPalSubscription->isDirty('planId')) {
                $patch = new Patch();
                $patch->setValue($payPalSubscription->planId)->setOp('replace')->setPath('/plan_id');
                $patchRequest->addPatch($patch);
            }
            if(count($patchRequest->getPatches()) > 0){
                try {
                    $payPalApiSubscription = Subscription::get($payPalSubscription->paypalSubscriptionId, $this->context);
                    $payPalApiSubscription->update($patchRequest, $this->context);

                    return $payPalSubscription;
                }catch (PayPalConnectionException $e){
                    \Craft::info($e->getData(),Plugin::LogCategory);
                    $payPalSubscription->addError('api',$e->getMessage());
                }
            }
        }
        return $payPalSubscription;
    }

    /**
     * @param PaypalBillingPlan $paypalBillingPlan
     * @return PayPalSubscriptionRequestResponse|null
     */
    public function createSubscriptionRequest(PaypalBillingPlan $paypalBillingPlan){

        $subscription = new SubscriptionRequest();
        $subscription->setPlanId($paypalBillingPlan->getPlanId());
        $subscription->setAutoRenewal($paypalBillingPlan->getAutoRenewPlan());

        $successUrl = UrlHelper::actionUrl('commerce-paypal/subscription/complete');
        $cancelUrl = UrlHelper::actionUrl('commerce-paypal/subscription/cancel');

        $applicationContext = new ApplicationContext();
        $applicationContext->setCancelUrl($cancelUrl);
        $applicationContext->setReturnUrl($successUrl);

        $subscription->setApplicationContext($applicationContext);
        try{
            $subscription->create($this->context);
            foreach($subscription->getLinks() as $link){
                if($link->getRel() === 'approve') {
                    return new PayPalSubscriptionRequestResponse([
                        'redirectLink'=>$link->getHref(),
                        'id'=>$subscription->getId(),
                        'status'=>$subscription->getStatus()
                    ]);
                }
            }
        }catch (PayPalConnectionException $e){
            \Craft::info($e->getData(),Plugin::LogCategory);
            $paypalBillingPlan->addError('api',$e->getMessage());
        }
        return null;
    }

    public function createPlan(PaypalBillingPlan $paypalBillingPlan){
        $subscriptionPlan = new SubscriptionPlan();
        $subscriptionPlan->setStatus('ACTIVE');
        $subscriptionPlan->setName($paypalBillingPlan->getName());
        $subscriptionPlan->setDescription($paypalBillingPlan->getDescription());
        $subscriptionPlan->setStatus($paypalBillingPlan->getStatus());
        $subscriptionPlan->setProductId($paypalBillingPlan->getProductId());

        $billingCycles = [];
        if($paypalBillingPlan->hasTrial()){
            $billingCycle = new BillingCycle();
            $billingCycle->setTenureType('TRIAL');
            $billingCycle->setSequence(count($billingCycles)+1);
            $billingCycle->setTotalCycles($paypalBillingPlan->getTrialCycles());

            $frequency = new Frequency();
            $frequency->setIntervalCount($paypalBillingPlan->getTrialPeriod());
            $frequency->setIntervalUnit($paypalBillingPlan->getTrialFrequency());
            $billingCycle->setFrequency($frequency);

            $pricingScheme = new PricingScheme();
            $fixedPrice = new Money();
            $fixedPrice->setValue($paypalBillingPlan->getTrialPrice());
            $fixedPrice->setCurrencyCode($paypalBillingPlan->getCurrencyCode());
            $pricingScheme->setFixedPrice($fixedPrice);
            $billingCycle->setPricingScheme($pricingScheme);

            $billingCycles[] = $billingCycle;
        }

        $billingCycle = new BillingCycle();
        $billingCycle->setTenureType('REGULAR');
        $billingCycle->setSequence(count($billingCycles)+1);
        if($paypalBillingPlan->getSubscriptionCycles() > 0){
            $billingCycle->setTotalCycles($paypalBillingPlan->getSubscriptionCycles());
        }

        $frequency = new Frequency();
        $frequency->setIntervalCount($paypalBillingPlan->getSubscriptionPeriod());
        $frequency->setIntervalUnit($paypalBillingPlan->getSubscriptionFrequency());
        $billingCycle->setFrequency($frequency);

        $pricingScheme = new PricingScheme();
        $fixedPrice = new Money();
        $fixedPrice->setValue($paypalBillingPlan->getSubscriptionPrice());
        $fixedPrice->setCurrencyCode($paypalBillingPlan->getCurrencyCode());
        $pricingScheme->setFixedPrice($fixedPrice);
        $billingCycle->setPricingScheme($pricingScheme);

        $billingCycles[] = $billingCycle;
        $subscriptionPlan->setBillingCycles($billingCycles);

        $paymentPreferences = new PaymentPreferences();
        $paymentPreferences->setSetupFeeFailureAction($paypalBillingPlan->getFailureSetupFeeAction());
        $paymentPreferences->setPaymentFailureThreshold($paymentPreferences->getPaymentFailureThreshold());
        if($paypalBillingPlan->hasSetupFee()){
            $setupFee = new Money();
            $setupFee->setValue($paypalBillingPlan->getSetupPrice());
            $setupFee->setCurrencyCode($paypalBillingPlan->getCurrencyCode());
            $paymentPreferences->setSetupFee($setupFee);
        }
        $subscriptionPlan->setPaymentPreferences($paymentPreferences);
        //todo taxes
        try{
            $subscriptionPlan->create($this->context);
            return $subscriptionPlan->getId();
        }catch (PayPalConnectionException $e){
            \Craft::info($e->getData(),Plugin::LogCategory);
            $paypalBillingPlan->addError('api',$e->getMessage());
        }
        return null;
    }

    /**
     * @param $productId
     * @return PayPalProduct|null
     */
    public function getProductById($productId){
        $payPalProduct = Product::get($productId, $this->context);
        if($payPalProduct instanceof Product){
            return new PayPalProduct([
                'id'=>$payPalProduct->getId(),
                'name'=>$payPalProduct->getName(),
                'description'=>$payPalProduct->getDescription(),
                'type'=>$payPalProduct->getType(),
                'category'=>$payPalProduct->getCategory(),
                'image_url'=>$payPalProduct->getImageUrl(),
                'home_url'=>$payPalProduct->getHomeUrl(),
                'create_time'=>$payPalProduct->getCreateTime(),
                'update_time'=>$payPalProduct->getUpdateTime()
            ]);
        }
        return null;
    }

    public function createProduct(PayPalProduct $product){
        if($product->validate()){
            try{
                $paypalProduct = new Product();
                $paypalProduct->setName($product->name);
                $paypalProduct->setCategory($product->category);
                $paypalProduct->setDescription($product->description);
                $paypalProduct->setType($product->type);
                $paypalProduct->create($this->context);
                $product->id = $paypalProduct->getId();
            }catch (PayPalConnectionException $e){
                \Craft::info($e->getData(),Plugin::LogCategory);
                $product->addError('api',$e->getMessage());
            }

        }
        return $product;
    }

    public function updateProduct(PayPalProduct $product){
        if($product->isDirty() && $product->validate()){
            $patchRequest = new PatchRequest();
            if($product->isDirty('name')){
                $patch = new Patch();
                $patch->setValue($product->name)->setOp('replace')->setPath('/name');
                $patchRequest->addPatch($patch);
            }
            if($product->isDirty('description')){
                $patch = new Patch();
                $patch->setValue($product->description)->setOp('replace')->setPath('/description');
                $patchRequest->addPatch($patch);
            }
            if($product->isDirty('category')){
                $patch = new Patch();
                $patch->setValue($product->category)->setOp('replace')->setPath('/category');
                $patchRequest->addPatch($patch);
            }
            if($product->isDirty('type')){
                $patch = new Patch();
                $patch->setValue($product->type)->setOp('replace')->setPath('/type');
                $patchRequest->addPatch($patch);
            }
            if($product->isDirty('image_url')){
                $patch = new Patch();
                $patch->setValue($product->image_url)->setOp('replace')->setPath('/image_url');
                $patchRequest->addPatch($patch);
            }
            if($product->isDirty('home_url')){
                $patch = new Patch();
                $patch->setValue($product->home_url)->setOp('replace')->setPath('/home_url');
                $patchRequest->addPatch($patch);
            }
            if(count($patchRequest->getPatches()) > 0){
                try {
                    $payPalProduct = Product::get($product->id, $this->context);
                    $payPalProduct->update($patchRequest, $this->context);

                    return $product->syncDirtyData();
                }catch (PayPalConnectionException $e){
                    \Craft::info($e->getData(),Plugin::LogCategory);
                    $product->addError('api',$e->getMessage());
                }
            }
        }
        return $product;
    }

    public function getProducts(){
        return Product::all([], $this->context);
    }

    public function getPlans(){
        return Plan::all([],$this->context);
    }
}