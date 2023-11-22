<?php

declare(strict_types=1);

namespace WebkulPOS\Core\Content\Outlet\Aggregate\OutletProduct;

use Shopware\Core\Content\Product\ProductDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\Field\BoolField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\FkField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\PrimaryKey;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\Required;
use Shopware\Core\Framework\DataAbstractionLayer\Field\ManyToOneAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\FieldCollection;
use Shopware\Core\Framework\DataAbstractionLayer\MappingEntityDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\Field\IdField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\IntField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\StringField;
use WebkulPOS\Core\Content\Outlet\OutletDefinition;
use WebkulPOS\Core\Content\Outlet\Aggregate\OutletProduct\OutletProductCollection;
use WebkulPOS\Core\Content\Outlet\Aggregate\OutletProduct\OutletProductEntity;

class OutletProductDefinition extends MappingEntityDefinition
{
    public function getEntityName(): string
    {
        return 'wkpos_product';
    }

    public function getCollectionClass(): string
    {
        return OutletProductCollection::class;
    }

    public function getEntityClass(): string
    {
        return OutletProductEntity::class;
    }

    protected function defineFields(): FieldCollection
    {
        return new FieldCollection([
            (new IdField('id', 'id'))->addFlags(new Required(), new PrimaryKey()),
            (new FkField('outlet_id', 'outletId', OutletDefinition::class))->addFlags(new Required()),
            (new FkField('product_id', 'productId', ProductDefinition::class))->addFlags(new Required()),
            new BoolField('status', 'status'),
            (new IntField('stock', 'stock'))->addFlags(new Required()),
            new ManyToOneAssociationField('outlet', 'outlet_id', OutletDefinition::class),
            new ManyToOneAssociationField('product', 'product_id', ProductDefinition::class)
        ]);
    }
}
