<?php declare(strict_types=1);

namespace WebkulPOS\Core\Content\Order;

use Shopware\Core\Checkout\Order\OrderDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\PrimaryKey;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\Required;
use Shopware\Core\Framework\DataAbstractionLayer\Field\IdField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\FkField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\WriteProtected;
use Shopware\Core\Framework\DataAbstractionLayer\Field\FloatField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\IntField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\ManyToOneAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\OneToOneAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\StringField;
use Shopware\Core\Framework\DataAbstractionLayer\FieldCollection;
use WebkulPOS\Core\Content\User\UserDefinition;
use WebkulPOS\Core\Content\Order\WkposOrderEntity;
use WebkulPOS\Core\Content\Order\WkposOrderCollection;


class WkposOrderDefinition extends EntityDefinition
{
    public function getEntityName(): string
    {
        return 'wkpos_order';
    }

    public function getEntityClass(): string
    {
        return WkposOrderEntity::class;
    }
    public function getCollectionClass(): string
    {
        return WkposOrderCollection::class;
    }

    public function defineFields(): FieldCollection
    {
        return new FieldCollection([
            (new IdField('id', 'id'))->addFlags(new Required(), new PrimaryKey()),
            new StringField('user_name', 'userName'),
            new StringField('order_note', 'orderNote'),
            new FloatField('cash_payment', 'cashPayment'),
            new FloatField('card_payment', 'cardPayment'),
            (new IntField('auto_increment', 'autoIncrement'))->addFlags(new WriteProtected()),
            (new FkField('user_id', 'userId', UserDefinition::class))->addFlags(new Required()),
            (new FkField('order_id', 'orderId', OrderDefinition::class))->addFlags(new Required()),
            (new OneToOneAssociationField('order', 'order_id', 'id', OrderDefinition::class)),
            (new ManyToOneAssociationField('user', 'user_id', UserDefinition::class, 'id', true)),
        ]);
    }
}