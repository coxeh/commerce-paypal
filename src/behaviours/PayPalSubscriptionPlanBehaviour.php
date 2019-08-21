<?php
namespace craft\commerce\paypal\behaviours;

use craft\commerce\paypal\models\PayPalBillingPlan;
use craft\commerce\paypal\services\PayPalApiServiceFactory;
use yii\base\Behavior;
use yii\base\Event;
use yii\base\Exception;
use yii\base\ModelEvent;

class PayPalSubscriptionPlanBehaviour extends Behavior
{

    public function events()
    {
        return [
            PayPalBillingPlan::EVENT_BEFORE_VALIDATE => 'validateSettings',
            PayPalBillingPlan::EVENT_AFTER_VALIDATE => 'syncPlan',
        ];
    }

    public function validateSettings(ModelEvent $event){
        if(empty($event->sender->reference)){
            $event->sender->reference = 'new';
        }
    }

    public function syncPlan(Event $event){
        if($event->sender->hasErrors() === false){
            $apiService = PayPalApiServiceFactory::CreateForGateway($event->sender->getGateway());
            if($event->sender->reference === 'new'){
                $event->sender->reference = $apiService->createPlan($event->sender);
                if($event->sender->hasErrors()){
                    throw new Exception('PayPal API Returned an Error');
                }
            }
        }

    }

}
