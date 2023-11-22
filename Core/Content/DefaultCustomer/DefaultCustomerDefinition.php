<?php declare(strict_types=1);

namespace WebkulPOS\Core\Content\DefaultCustomer;

use Shopware\Core\Checkout\Customer\CustomerDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\Field\FkField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\PrimaryKey;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\Required;
use Shopware\Core\Framework\DataAbstractionLayer\Field\IdField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\ManyToOneAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\FieldCollection;

class DefaultCustomerDefinition extends EntityDefinition
{
    public function getEntityName(): string
    {
        return 'wkpos_default_customer';
    }
   
    protected function defineFields(): FieldCollection
    {
        return new FieldCollection([
            (new IdField('id', 'id'))->addFlags(new Required(), new PrimaryKey()),
            (new IdField('outlet_id', 'outletId'))->addFlags(new Required()),
            (new FkField('customer_id', 'customerId',CustomerDefinition::class))->addFlags(new Required()),
            new ManyToOneAssociationField('customer','customer_id',CustomerDefinition::class,'id')
        ]);
    }
}