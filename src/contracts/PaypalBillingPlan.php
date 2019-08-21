<?php
namespace craft\commerce\paypal\contracts;

interface PaypalBillingPlan{
    public function getName();
    public function getDescription();
    public function getStatus();
    public function hasTrial();
    public function getTrialFrequency();
    public function getTrialPeriod();
    public function getTrialPrice();
    public function getTrialCycles();
    public function getCurrencyCode();
    public function getSubscriptionFrequency();
    public function getSubscriptionPeriod();
    public function getSubscriptionPrice();
    public function getSubscriptionCycles();
    public function getFailureSetupFeeAction();
    public function getFailureThreshold();
    public function hasSetupFee();
    public function getSetupPrice();
    public function getProductId();
    public function getPlanId();
    public function getAutoRenewPlan();
    public function addError($attribute, $error = '');
}