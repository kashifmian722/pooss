<?php declare(strict_types=1);

namespace WebkulPOS\Core\Content\Product;

use Shopware\Core\Content\Product\ProductDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\EntityExtension;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\Inherited;
use Shopware\Core\Framework\DataAbstractionLayer\Field\OneToManyAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\FieldCollection;
use WebkulPOS\Core\Content\Barcode\BarcodeDefinition;
use WebkulPOS\Core\Content\Outlet\Aggregate\OutletProduct\OutletProductDefinition;

class ProductExtension extends EntityExtension
{
    public function extendFields(FieldCollection $collection): void
    {
        $collection->add(
            (new OneToManyAssociationField(
                'wkpos_barcode',
                BarcodeDefinition::class,
                'product_id'
            ))
        );
    }

    public function getDefinitionClass(): string
    {
        return ProductDefinition::class;
    }
}

