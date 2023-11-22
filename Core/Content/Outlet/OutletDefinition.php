<?php declare(strict_types=1);

namespace WebkulPOS\Core\Content\Outlet;

use Shopware\Core\System\Country\CountryDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\PrimaryKey;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\Required;
use Shopware\Core\Framework\DataAbstractionLayer\Field\IdField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\FkField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\ManyToOneAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\StringField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\BoolField;
use Shopware\Core\Framework\DataAbstractionLayer\FieldCollection;
use Shopware\Core\Framework\DataAbstractionLayer\Field\OneToManyAssociationField;
use WebkulPOS\Core\Content\Outlet\Aggregate\OutletProduct\OutletProductDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\CascadeDelete;


class OutletDefinition extends EntityDefinition
{
    public function getEntityName(): string
    {
        return 'wkpos_outlet';
    }

    public function getEntityClass(): string
    {
        return OutletEntity::class;
    }

    public function getCollectionClass(): string
    {
        return OutletCollection::class;
    }

    protected function defineFields(): FieldCollection
    {
        return new FieldCollection([
            (new IdField('id', 'id'))->addFlags(new Required(), new PrimaryKey()),
            new StringField('name', 'name'),
            new StringField('address', 'address'),
            new StringField('city', 'city'),
            (new FkField('country_id', 'countryId', CountryDefinition::class))->addFlags(new Required()),
            (new OneToManyAssociationField('products', OutletProductDefinition::class, 'outlet_id'))->addFlags(new CascadeDelete()),
            new BoolField('active', 'active'),
            new StringField('zipcode', 'zipcode'),
            new ManyToOneAssociationField('country', 'country_id', CountryDefinition::class, 'id', false),
        ]);
    }
}
