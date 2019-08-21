<?php
namespace craft\commerce\paypal;

use Craft;
use craft\web\AssetBundle;
use craft\web\View;

/**
 * Asset bundle for the PayPal REST Subscriptions
 */
class PayPalSubscriptionBundle extends AssetBundle
{
    /**
     * @inheritdoc
     */
    public function init()
    {
        $this->sourcePath = '@craft/commerce/paypal/resources';

        $view = Craft::$app->getView();
        $templateMode = $view->getTemplateMode();
        if($templateMode === View::TEMPLATE_MODE_CP){
            $this->registerAssetForCp();
        }
        parent::init();
    }


    protected function registerAssetForCp(){
        $this->js = [
            'js/subscription/planSettings.js',
        ];
    }
}
