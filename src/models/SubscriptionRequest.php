<?php

namespace craft\commerce\paypal\models;

use craft\base\Model;

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

class SubscriptionRequest extends Model {
    public $userId;
    public $planId;
    public $gatewayId;
    public $subscriptionId;
    public $paypalSubscriptionId;
    public $status;
    public $redirectLink;
    public $id;

    public function rules()
    {
        $rules = parent::rules();
        $rules[] = [['userId','planId','gatewayId','paypalSubscriptionId','status','redirectLink'],'required'];
        $rules[] = [['userId','planId','gatewayId','paypalSubscriptionId'],'integer'];
    }

    public function handleRedirect(){
        header('Location: '.$this->redirectLink);
        exit();
    }

    public function setResponse(PaypalSubscriptionRequestResponse $response){
        $this->paypalSubscriptionId = $response->id;
        $this->status = $response->status;
        $this->redirectLink = $response->redirectLink;
        return $this;
    }
}