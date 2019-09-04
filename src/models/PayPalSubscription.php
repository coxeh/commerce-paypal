<?php

namespace craft\commerce\paypal\models;

use craft\base\Model;
use craft\commerce\base\SubscriptionResponseInterface;
use craft\commerce\paypal\models\enum\PayPalSubscriptionStatus;
use craft\commerce\Plugin as CommercePlugin;
use craft\helpers\DateTimeHelper;
use DateTime;

/**
 * Class SubscriptionRequest
 * @package craft\commerce\paypal\models
 * @property integer userId
 * @property integer planId
 * @property integer gatewayId
 * @property integer subscriptionId
 * @property string paypalSubscriptionId
 * @property string status
 * @property string redirectLink
 * @property string dateCreated
 * @property string dateUpdated
 * @property string uid
 */

class PayPalSubscription extends Model implements SubscriptionResponseInterface {
    public $userId;
    public $planId;
    public $gatewayId;
    public $subscriptionId;
    public $paypalSubscriptionId;
    public $status;
    public $redirectLink;
    public $nextBillingTime;
    public $id;
    protected $_gateway;
    protected $_plan;
    protected $_user;

    public function rules()
    {
        $rules = parent::rules();
        $rules[] = [['userId','planId','gatewayId','paypalSubscriptionId','status','redirectLink'],'required'];
        $rules[] = [['userId','planId','gatewayId'],'integer'];
        return $rules;
    }

    public function setAsCancelled(){
        $this->status = PayPalSubscriptionStatus::CANCELLED;
        return $this;
    }
    public function setAsActive(){
        $this->status = PayPalSubscriptionStatus::ACTIVE;
        return $this;
    }

    public function handleRedirect(){
        header('Location: '.$this->redirectLink);
        exit();
    }

    public function setResponse(PayPalSubscriptionRequestResponse $response){
        $this->paypalSubscriptionId = $response->id;
        $this->status = $response->status;
        $this->redirectLink = $response->redirectLink;
        $this->nextBillingTime = $response->nextBillingTime;
        return $this;
    }

    public function getGateway(){
        if($this->_gateway === null){
            $this->_gateway = CommercePlugin::getInstance()->gateways->getGatewayById($this->gatewayId);
        }
        return $this->_gateway;
    }
    public function getPlan(){
        if($this->_plan === null){
            $this->_plan = CommercePlugin::getInstance()->plans->getPlanById($this->planId);
        }
        return $this->_plan;
    }
    public function getUser(){
        if($this->_user === null){
            $this->_user = \Craft::$app->users->getUserById($this->userId);
        }
        return $this->_user;
    }

    /**
     * Returns the response data.
     *
     * @return mixed
     */
    public function getData()
    {
        return [
            'id'=>$this->id
        ];
    }

    /**
     * Returns the subscription reference.
     *
     * @return string
     */
    public function getReference(): string
    {
        return $this->paypalSubscriptionId;
    }

    /**
     * Returns the number of trial days on the subscription.
     *
     * @return int
     */
    public function getTrialDays(): int
    {
        $planData = $this->getPlan()->getPlanData();
        if($planData['hasTrial'] == 1){
            $days = 0;
            if($planData['trialFrequency'] == 'DAY'){
                $days = (int) $planData['trialPeriod'];
            }
            if($planData['trialFrequency'] == 'MONTH'){
                $days = (int) $planData['trialPeriod'] * 31;
            }
            if($planData['trialFrequency'] == 'YEAR'){
                $days = (int) $planData['trialPeriod'] * 356;
            }
            $days = $days * (int) $planData['trialCycles'];
            return floor($days);
        }
        return 0;
    }

    /**
     * Returns the time of next payment.
     *
     * @return DateTime
     */
    public function getNextPaymentDate(): DateTime
    {
        if ($this->nextBillingTime && !$this->nextBillingTime instanceof \DateTime) {
            // Just automatically convert it rather than complaining about it
            $this->nextBillingTime = DateTimeHelper::toDateTime($this->nextBillingTime);
        }
        return $this->nextBillingTime;
    }

    /**
     * Returns whether the subscription is canceled.
     *
     * @return bool
     */
    public function isCanceled(): bool
    {
        return $this->status === PayPalSubscriptionStatus::CANCELLED;
    }

    /**
     * Returns whether the subscription is scheduled to be canceled.
     *
     * @return bool
     */
    public function isScheduledForCancellation(): bool
    {
        return $this->status !== PayPalSubscriptionStatus::ACTIVE;
    }

    /**
     * Whether the subscription is unpaid.
     *
     * @return bool
     */
    public function isInactive(): bool
    {
        return $this->status !== PayPalSubscriptionStatus::ACTIVE;
    }
}