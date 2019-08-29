<?php

namespace craft\commerce\paypal;

use craft\commerce\paypal\behaviours\DetectsChangesBehaviour;
use craft\commerce\paypal\behaviours\PayPalGatewaySubscriptionBehaviour;
use craft\commerce\paypal\behaviours\PayPalSubscriptionPlanBehaviour;
use craft\commerce\paypal\gateways\PayPalExpress;
use craft\commerce\paypal\gateways\PayPalRest;
use craft\commerce\paypal\gateways\PayPalPro;
use craft\commerce\paypal\gateways\PayPalRestSubscription;
use craft\commerce\paypal\models\PayPalBillingPlan;
use craft\commerce\paypal\models\PayPalProduct;
use craft\commerce\paypal\models\PayPalWebhook;
use craft\commerce\paypal\services\PaypalSubscriptionService;
use craft\commerce\services\Gateways;
use craft\events\DefineBehaviorsEvent;
use craft\events\RegisterComponentTypesEvent;
use craft\events\RegisterUrlRulesEvent;
use craft\web\UrlManager;
use yii\base\Event;


/**
 * Plugin represents the PayPAl integration plugin.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  1.0
 */
class Plugin extends \craft\base\Plugin
{
    const LogCategory = 'paypal-api';
    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();
        $this->registerGateways();
        $this->registerCPRoutes();
        $this->registerBehaviours();
        $this->registerComponents();
    }

    protected function registerComponents(){
        $this->setComponents([
            'subscriptions' => PaypalSubscriptionService::class
        ]);
    }
    protected function registerBehaviours(){
        Event::on(
            PayPalRestSubscription::class,
            PayPalRestSubscription::EVENT_DEFINE_BEHAVIORS,
            function(DefineBehaviorsEvent $event){
                $event->behaviors[] = PayPalGatewaySubscriptionBehaviour::class;
            }
        );

        Event::on(
            PayPalBillingPlan::class,
            PayPalBillingPlan::EVENT_DEFINE_BEHAVIORS,
            function(DefineBehaviorsEvent $event){
                $event->behaviors[] = PayPalSubscriptionPlanBehaviour::class;
            }
        );

        //Handle Currency Changes
    }

    protected function registerCPRoutes(){
        Event::on(
            UrlManager::class,
            UrlManager::EVENT_REGISTER_CP_URL_RULES,
            function(RegisterUrlRulesEvent $event) {
                $event->rules['api/paypal/subscription/<gatewayId:[0-9]+>/plans'] = 'commerce-paypal/subscription/plans';
            }
        );
    }
    protected function registerGateways(){
        Event::on(Gateways::class, Gateways::EVENT_REGISTER_GATEWAY_TYPES,  function(RegisterComponentTypesEvent $event) {
            $event->types[] = PayPalPro::class;
            $event->types[] = PayPalRest::class;
            $event->types[] = PayPalExpress::class;
            $event->types[] = PayPalRestSubscription::class;
        });
    }
}
