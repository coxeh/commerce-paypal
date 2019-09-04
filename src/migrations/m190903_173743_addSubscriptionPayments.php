<?php

namespace craft\commerce\paypal\migrations;

use Craft;
use craft\commerce\paypal\records\SubscriptionPayment;
use craft\commerce\records\Subscription;
use craft\db\Migration;

/**
 * m190903_173743_addSubscriptionPayments migration.
 */
class m190903_173743_addSubscriptionPayments extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        $this->createTable(SubscriptionPayment::tableName(), [
            'id' => $this->primaryKey(),
            'subscriptionId' => $this->integer(),
            'paymentAmount' => $this->string()->notNull(),
            'currencyCode' => $this->dateTime(),
            'paymentReference'=> $this->string()->notNull(),
            'paid' => $this->string()->notNull(),
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
            'uid'=>$this->char(36)->notNull()
        ]);

        $this->createIndex('idx_sp_subscription',SubscriptionPayment::tableName(),'paymentReference',true);

        $this->addForeignKey(
            'fk_sp_subscription',
            SubscriptionPayment::tableName(),'subscriptionId',
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
        $this->dropForeignKey('fk_sp_subscription', SubscriptionPayment::tableName());
        $this->dropTableIfExists(SubscriptionPayment::tableName());
    }
}
