<?php

namespace craft\commerce\paypal\migrations;

use Craft;
use craft\commerce\paypal\records\SubscriptionRequest;
use craft\commerce\records\Gateway;
use craft\commerce\records\Plan;
use craft\commerce\records\Subscription;
use craft\db\Migration;
use craft\db\Table;

/**
 * m190821_171645_subscriptionRequest migration.
 */
class m190821_171645_subscriptionRequest extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        $this->createTable(SubscriptionRequest::tableName(), [
            'id' => $this->primaryKey(),
            'userId' => $this->integer()->notNull(),
            'planId' => $this->integer()->notNull(),
            'gatewayId' => $this->integer()->notNull(),
            'subscriptionId' => $this->integer(),
            'paypalSubscriptionId' => $this->string()->notNull(),
            'nextBillingTime' => $this->dateTime(),
            'status'=> $this->string()->notNull(),
            'redirectLink' => $this->string()->notNull(),
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
            'uid'=>$this->char(36)->notNull()
        ]);

        $this->createIndex('idx_cpsr_subscription',SubscriptionRequest::tableName(),'paypalSubscriptionId',true);

        $this->addForeignKey(
            'fk_cpsr_user',
            SubscriptionRequest::tableName(),'userId',
            Table::USERS,'id',
            'CASCADE',
            'CASCADE'
        );
        $this->addForeignKey(
            'fk_cpsr_plan',
            SubscriptionRequest::tableName(),'planId',
            Plan::tableName(),'id',
            'CASCADE',
            'CASCADE'
        );
        $this->addForeignKey(
            'fk_cpsr_gateway',
            SubscriptionRequest::tableName(),'gatewayId',
            Gateway::tableName(),'id',
            'CASCADE',
            'CASCADE'
        );
        $this->addForeignKey(
            'fk_cpsr_subscription',
            SubscriptionRequest::tableName(),'subscriptionId',
            Subscription::tableName(),'id',
            'CASCADE',
            'CASCADE'
        );
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        $this->dropForeignKey('fk_cpsr_subscription',SubscriptionRequest::tableName());
        $this->dropForeignKey('fk_cpsr_gateway',SubscriptionRequest::tableName());
        $this->dropForeignKey('fk_cpsr_plan',SubscriptionRequest::tableName());
        $this->dropForeignKey('fk_cpsr_user',SubscriptionRequest::tableName());
        $this->dropIndex('idx_cpsr_subscription',SubscriptionRequest::tableName());
        $this->dropTableIfExists(SubscriptionRequest::tableName());
    }
}
