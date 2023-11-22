<?php declare(strict_types=1);

namespace WebkulPOS\Core\Content\PosProduct;

use Shopware\Core\Content\Product\ProductDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\Field\FkField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\PrimaryKey;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\Required;
use Shopware\Core\Framework\DataAbstractionLayer\Field\IdField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\IntField as FieldIntField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\ManyToOneAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\OneToOneAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\StringField;
use Shopware\Core\Framework\DataAbstractionLayer\FieldCollection;
use WebkulPOS\Core\Content\Outlet\OutletDefinition;

class PosProductDefinition extends EntityDefinition
{
    public function getEntityName(): string
    {
        return 'wkpos_product';
    }
   
    protected function defineFields(): FieldCollection
    {
        return new FieldCollection([
            (new IdField('id', 'id'))->addFlags(new Required(), new PrimaryKey()),
            (new FkField('product_id','productId',ProductDefinition::class))->addFlags(new Required()),
            (new FkField('outlet_id', 'outletId',OutletDefinition::class))->addFlags(new Required()),
            (new FieldIntField('stock','stock'))->addFlags(new Required()),
            new FieldIntField('assign_qty','assignQty'),
            new StringField('barcode','barcode'),
            new OneToOneAssociationField('product','product_id',ProductDefinition::class,'id'),
            new ManyToOneAssociationField('wkpos_outlet','outlet_id',OutletDefinition::class,'id')
        ]);
    }
}