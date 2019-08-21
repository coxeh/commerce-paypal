<?php

namespace craft\commerce\paypal\models;

use craft\commerce\base\Plan;
use craft\commerce\base\PlanInterface;
use craft\commerce\paypal\models\enum\FrequencyType;
use craft\commerce\paypal\models\enum\SetupFeeFailureType;
use yii\validators\NumberValidator;
use yii\validators\RangeValidator;
use yii\validators\RegularExpressionValidator;
use yii\validators\RequiredValidator;
use \craft\commerce\paypal\contracts\PaypalBillingPlan as PaypalBillingPlanContract;

class PayPalBillingPlan extends Plan implements PaypalBillingPlanContract {

    public function rules()
    {
        $rules = parent::rules();
        $rules[] = [['planData'],'validatePlanData'];
        return $rules;
    }

    public function validatePlanData($attribute, $params, $validator){
        $requiredValidator = new RequiredValidator();
        $integerValidator = new NumberValidator([
            'integerOnly'=>true,
            'min'=>1,
            'max'=>999
        ]);
        $trialPeriodValidator = new NumberValidator([
            'integerOnly'=>true,
            'min'=>0,
            'max'=>999
        ]);
        $frequencyValidator = new RangeValidator([
            'range'=>FrequencyType::Keys()
        ]);
        $setupFeeFailureValidator = new RangeValidator([
            'range'=>SetupFeeFailureType::Keys()
        ]);
        $priceValidator = new RegularExpressionValidator([
            'pattern'=>'/^((-?[0-9]+)|(-?([0-9]+)?[.][0-9]+))$/'
        ]);

        $planData = $this->getPlanData();
        if($planData === false){
            $this->addError($attribute, 'Plan Data is not a valid json string');
            return;
        }

        $descriptionExists = isset($planData['description']);

        if($descriptionExists === false){
            $this->addError('planData.description','Description must be set');
        }
        //todo rest of exists

        if($descriptionExists && !$requiredValidator->validate($planData['description'],$error)){
            $this->addError('planData.description',$error);
        }
        if(!$requiredValidator->validate($planData['trialCycles'],$error)){
            $this->addError('planData.trialCycles',$error);
        }
        if(!$trialPeriodValidator->validate($planData['trialPeriod'],$error)){
            $this->addError('planData.trialPeriod',$error);
        }
        if(!$frequencyValidator->validate($planData['trialFrequency'],$error)){
            $this->addError('planData.trialFrequency',$error);
        }
        if(!$integerValidator->validate($planData['trialCycles'],$error)){
            $this->addError('planData.trialCycles',$error);
        }
        if(!$priceValidator->validate($planData['trialPrice'],$error)){
            $this->addError('planData.trialPrice',$error);
        }
        if(!$integerValidator->validate($planData['subscriptionPeriod'],$error)){
            $this->addError('planData.subscriptionPeriod',$error);
        }
        if(!$frequencyValidator->validate($planData['subscriptionFrequency'],$error)){
            $this->addError('planData.subscriptionFrequency',$error);
        }
        if(!$trialPeriodValidator->validate($planData['subscriptionCycles'],$error)){
            $this->addError('planData.subscriptionCycles',$error);
        }
        if(!$priceValidator->validate($planData['subscriptionPrice'],$error)){
            $this->addError('planData.subscriptionPrice',$error);
        }
        if(!$priceValidator->validate($planData['setupFee'],$error)){
            $this->addError('planData.setupFee',$error);
        }
        if(!$setupFeeFailureValidator->validate($planData['setupFeeFailureAction'],$error)){
            $this->addError('planData.setupFeeFailureAction',$error);
        }
        if(!$integerValidator->validate($planData['paymentFailureThreshold'],$error)){
            $this->addError('planData.paymentFailureThreshold',$error);
        }

    }

    /**
     * Returns whether it's possible to switch to this plan from a different plan.
     *
     * @param PlanInterface $currentPlant
     * @return bool
     */
    public function canSwitchFrom(PlanInterface $currentPlant): bool
    {
        // TODO: Implement canSwitchFrom() method.
    }

    public function getName()
    {
       return $this->name;
    }

    public function getDescription()
    {
        return $this->getPlanData()['description'];
    }

    public function getStatus(){
        return 'ACTIVE';
    }
    public function hasTrial()
    {
        return $this->getPlanData()['hasTrial'] === 'yes';
    }
    public function getTrialFrequency()
    {
        return $this->getPlanData()['trialFrequency'];
    }
    public function getTrialPeriod(){
        return $this->getPlanData()['trialPeriod'];
    }
    public function getTrialPrice(){
        return $this->getPlanData()['trialPrice'];
    }
    public function getTrialCycles(){
        return $this->getPlanData()['trialCycles'];
    }
    public function getCurrencyCode()
    {
        return 'GBP';
    }
    public function getSubscriptionFrequency(){
        return $this->getPlanData()['subscriptionFrequency'];
    }
    public function getSubscriptionPeriod(){
        return $this->getPlanData()['subscriptionPeriod'];
    }
    public function getSubscriptionPrice(){
        return $this->getPlanData()['subscriptionPrice'];
    }
    public function getSubscriptionCycles(){
        return $this->getPlanData()['subscriptionCycles'];
    }
    public function getFailureSetupFeeAction(){
        return $this->getPlanData()['setupFeeFailureAction'];
    }
    public function getFailureThreshold(){
        return $this->getPlanData()['paymentFailureThreshold'];
    }
    public function hasSetupFee(){
        return $this->getPlanData()['hasSetupFee'] === 'yes';
    }
    public function getSetupPrice(){
        return $this->getPlanData()['setupFee'];
    }
    public function getProductId(){
        return $this->getGateway()->productId;
    }
    public function getPlanId(){
        return $this->reference;
    }
    public function getAutoRenewPlan(){
        return true;
    }
}