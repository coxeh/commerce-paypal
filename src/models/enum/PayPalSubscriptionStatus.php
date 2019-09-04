<?php

namespace craft\commerce\paypal\models\enum;


class PayPalSubscriptionStatus{
    const APPROVAL_PENDING = 'APPROVAL_PENDING';
    const APPROVED = 'APPROVED';
    const ACTIVE = 'ACTIVE';
    const SUSPENDED = 'SUSPENDED';
    const CANCELLED = 'CANCELLED';
    const EXPIRED = 'EXPIRED';
}