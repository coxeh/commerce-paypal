<?php
namespace craft\commerce\paypal\services;

use Craft;
use craft\base\Component;
use craft\commerce\paypal\events\SubscriptionRequestEvent;
use craft\commerce\paypal\models\SubscriptionRequest;
use craft\commerce\paypal\records\SubscriptionRequest as SubscriptionRecord;
use yii\base\InvalidConfigException;

class PaypalSubscriptionService extends Component{
    const EVENT_BEFORE_VALIDATE_SUBSCRIPTION_REQUEST = 'beforeValidateSubscriptionRequest';
    const EVENT_BEFORE_SAVE_SUBSCRIPTION_REQUEST = 'beforeSaveSubscriptionRequest';
    const EVENT_AFTER_SAVE_SUBSCRIPTION_REQUEST= 'afterSaveSubscriptionRequest';
    const EVENT_AFTER_VALIDATE_SUBSCRIPTION_REQUEST= 'afterValidateSubscriptionRequest';


    public function saveSubscriptionRequest(SubscriptionRequest $request, $runValidation = true){
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
}