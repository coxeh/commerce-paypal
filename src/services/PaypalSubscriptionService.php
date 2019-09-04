<?php
namespace craft\commerce\paypal\services;

use Craft;
use craft\base\Component;
use craft\commerce\paypal\events\SubscriptionPaymentEvent;
use craft\commerce\paypal\events\SubscriptionRequestEvent;
use craft\commerce\paypal\models\PayPalSubscription;
use craft\commerce\paypal\models\PayPalSubscriptionPayment;
use craft\commerce\paypal\records\SubscriptionPayment;
use craft\commerce\paypal\records\SubscriptionRequest as SubscriptionRecord;
use craft\commerce\Plugin as CommercePlugin;
use craft\helpers\Db;
use yii\base\InvalidConfigException;

class PaypalSubscriptionService extends Component{
    const EVENT_BEFORE_VALIDATE_SUBSCRIPTION_REQUEST = 'beforeValidateSubscriptionRequest';
    const EVENT_BEFORE_SAVE_SUBSCRIPTION_REQUEST = 'beforeSaveSubscriptionRequest';
    const EVENT_AFTER_SAVE_SUBSCRIPTION_REQUEST= 'afterSaveSubscriptionRequest';
    const EVENT_AFTER_VALIDATE_SUBSCRIPTION_REQUEST= 'afterValidateSubscriptionRequest';
    const EVENT_BEFORE_VALIDATE_SUBSCRIPTION_PAYMENT = 'beforeValidateSubscriptionPayment';
    const EVENT_BEFORE_SAVE_SUBSCRIPTION_PAYMENT = 'beforeSaveSubscriptionPayment';
    const EVENT_AFTER_SAVE_SUBSCRIPTION_PAYMENT= 'afterSaveSubscriptionRPayment';
    const EVENT_AFTER_VALIDATE_SUBSCRIPTION_PAYMENT = 'afterValidateSubscriptionPayment';


    public function updateSubscriptionStatus(PayPalSubscription $payPalSubscription){
        $apiService = PayPalApiServiceFactory::CreateForGateway($payPalSubscription->plan->gateway);
        $subscriptionApiResponse = $apiService->getSubscription($payPalSubscription->paypalSubscriptionId);
        if($payPalSubscription->status !== $subscriptionApiResponse->status){
            switch($subscriptionApiResponse->status){
                case 'ACTIVE':
                    $payPalSubscription->setResponse( $subscriptionApiResponse);

                    $subscriptionForm = $payPalSubscription->plan->gateway
                        ->getSubscriptionFormModel()
                        ->disableRedirect()
                        ->setPayPalSubscription($payPalSubscription);

                    $subscription = CommercePlugin::getInstance()
                        ->subscriptions
                        ->createSubscription($payPalSubscription->user,$payPalSubscription->plan,$subscriptionForm);

                    $payPalSubscription->subscriptionId = $subscription->id;

                    $this->saveSubscriptionRequest($payPalSubscription);

                    break;
            }
        }
    }

    /**
     * @param integer $subscriptionId
     * @return PayPalSubscription|null
     */
    public function getSubscriptionRequestBySubscriptionId($subscriptionId){
        return $this->getSubscriptionRequestByField('subscriptionId',$subscriptionId);
    }

    /**
     * @param string $subscriptionId
     * @return PayPalSubscription|null
     */
    public function getSubscriptionRequestByPayPalSubscriptionId($subscriptionId){
        return $this->getSubscriptionRequestByField('paypalSubscriptionId',$subscriptionId);
    }

    public function saveSubscriptionPayment(PayPalSubscriptionPayment $payment, $runValidation = true){
        if ($payment->id) {
            $record = SubscriptionPayment::findOne($payment->id);
            if (!$record) {
                throw new InvalidConfigException(Craft::t('commerce', 'No subscription payment exists with the ID “{id}”', ['id' => $record->id]));
            }
        } else {
            $record = new SubscriptionPayment();
        }

        if ($this->hasEventHandlers(self::EVENT_BEFORE_VALIDATE_SUBSCRIPTION_PAYMENT)) {
            $event = new SubscriptionPaymentEvent([
                'subscriptionPayment' => $payment,
            ]);
            $this->trigger(self::EVENT_BEFORE_VALIDATE_SUBSCRIPTION_PAYMENT, $event);
            if($event->isValid === false){
                $record->addError('Plugin Failed To Validate');
                return false;
            }
        }

        if ($runValidation && !$payment->validate()) {
            Craft::info('Subscription Payment not saved due to validation error.', __METHOD__);
            return false;
        }

        if ($this->hasEventHandlers(self::EVENT_AFTER_VALIDATE_SUBSCRIPTION_PAYMENT)) {
            $event = new SubscriptionPaymentEvent([
                'subscriptionPayment' => $payment,
            ]);
            $this->trigger(self::EVENT_AFTER_VALIDATE_SUBSCRIPTION_PAYMENT, $event);
            if($event->isValid === false){
                $record->addError('Plugin Failed To Validate');
                return false;
            }
        }

        $record->subscriptionId = $payment->subscriptionId;
        $record->paymentAmount = $payment->paymentAmount;
        $record->currencyCode = $payment->currencyCode;
        $record->subscriptionId = $payment->subscriptionId;
        $record->paymentReference = $payment->paymentReference;
        $record->paid = $payment->paid;
        if($payment->paymentDate instanceof \DateTime){
            $record->paymentDate = Db::prepareDateForDb($payment->paymentDate);
        }else{
            $record->paymentDate = $payment->paymentDate;
        }


        if ($this->hasEventHandlers(self::EVENT_BEFORE_SAVE_SUBSCRIPTION_PAYMENT)) {
            $this->trigger(self::EVENT_BEFORE_SAVE_SUBSCRIPTION_PAYMENT, new SubscriptionRequestEvent([
                'subscriptionPayment' => $payment,
            ]));
        }

        $record->save(false);

        $payment->id = $record->id;

        if ($this->hasEventHandlers(self::EVENT_AFTER_SAVE_SUBSCRIPTION_PAYMENT)) {
            $this->trigger(self::EVENT_AFTER_SAVE_SUBSCRIPTION_PAYMENT, new SubscriptionRequestEvent([
                'subscriptionPayment' => $payment,
            ]));
        }

        return $payment;
    }

    //todo No Longer a request. refactor name
    public function saveSubscriptionRequest(PayPalSubscription $request, $runValidation = true){

        if ($request->id) {
            $record = SubscriptionRecord::findOne($request->id);
            if (!$record) {
                throw new InvalidConfigException(Craft::t('commerce', 'No subscription exists with the ID “{id}”', ['id' => $record->id]));
            }
        } else {
            $record = new SubscriptionRecord();
        }

        if ($this->hasEventHandlers(self::EVENT_BEFORE_VALIDATE_SUBSCRIPTION_REQUEST)) {
            $event = new SubscriptionRequestEvent([
                'subscriptionRequest' => $request,
            ]);
            $this->trigger(self::EVENT_BEFORE_VALIDATE_SUBSCRIPTION_REQUEST, $event);
            if($event->isValid === false){
                $record->addError('Plugin Failed To Validate');
                return false;
            }
        }
        if ($runValidation && !$request->validate()) {
            Craft::info('Subscription Request not saved due to validation error.', __METHOD__);
            return false;
        }

        if ($this->hasEventHandlers(self::EVENT_AFTER_VALIDATE_SUBSCRIPTION_REQUEST)) {
            $event = new SubscriptionRequestEvent([
                'subscriptionRequest' => $request,
            ]);
            $this->trigger(self::EVENT_AFTER_VALIDATE_SUBSCRIPTION_REQUEST, $event);
            if($event->isValid === false){
                $record->addError('Plugin Failed To Validate');
                return false;
            }
        }
        $record->userId = $request->userId;
        $record->planId = $request->planId;
        $record->gatewayId = $request->gatewayId;
        $record->subscriptionId = $request->subscriptionId;
        $record->paypalSubscriptionId = $request->paypalSubscriptionId;
        $record->redirectLink = $request->redirectLink;
        $record->status = $request->status;

        if($request->nextBillingTime instanceof \DateTime){
            $record->nextBillingTime = Db::prepareDateForDb($request->nextBillingTime);
        }else{
            $record->nextBillingTime = $request->nextBillingTime;
        }


        if ($this->hasEventHandlers(self::EVENT_BEFORE_SAVE_SUBSCRIPTION_REQUEST)) {
            $this->trigger(self::EVENT_BEFORE_SAVE_SUBSCRIPTION_REQUEST, new SubscriptionRequestEvent([
                'subscriptionRequest' => $request,
            ]));
        }

        $record->save(false);

        $request->id = $record->id;

        if ($this->hasEventHandlers(self::EVENT_AFTER_SAVE_SUBSCRIPTION_REQUEST)) {
            $this->trigger(self::EVENT_AFTER_SAVE_SUBSCRIPTION_REQUEST, new SubscriptionRequestEvent([
                'subscriptionRequest' => $request,
            ]));
        }

        return $request;
    }

    protected function getSubscriptionRequestByField($key,$value){
        $subscription = SubscriptionRecord::find()->where([$key=>$value])->one();
        if(!is_null($subscription)){
            $payPalSubscription = new PayPalSubscription();
            $payPalSubscription->setAttributes($subscription->toArray(),false);
            return $payPalSubscription;
        }
        return null;
    }
}