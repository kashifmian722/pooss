<?php declare(strict_types=1);

namespace WebkulPOS\Core\Content\Outlet\Aggregate\OutletTranslation;

use Shopware\Core\Framework\DataAbstractionLayer\EntityTranslationDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\Required;
use Shopware\Core\Framework\DataAbstractionLayer\Field\StringField;
use Shopware\Core\Framework\DataAbstractionLayer\FieldCollection;
use WebkulPOS\Core\Content\Outlet\OutletDefinition;

class OutletTranslationDefinition extends EntityTranslationDefinition
{
    public function getEntityName(): string
    {
        return 'wkpos_outlet_translation';
    }

    public function getCollectionClass(): string
    {
        return OutletTranslationCollection::class;
    }

    public function getEntityClass(): string
    {
        return OutletTranslationEntity::class;
    }

    public function getParentDefinitionClass(): string
    {
        return OutletDefinition::class;
    }

    protected function defineFields(): FieldCollection
    {
        return new FieldCollection([
            (new StringField('name', 'name'))->addFlags(new Required()),
            (new StringField('address', 'address')),
            (new StringField('city', 'city'))
        ]);
    }
}
