<?php declare(strict_types = 1);

namespace WebkulPOS\Core\Content\Bundle\Aggregate\OutletTranslation;

use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;

/**
 * @method void                         add(OutletTranslationEntity $entity)
 * @method void                         set(string $key, OutletTranslationEntity $entity)
 * @method OutletTranslationEntity[]    getIterator()
 * @method OutletTranslationEntity[]    getElements()
 * @method OutletTranslationEntity|null get(string $key)
 * @method OutletTranslationEntity|null first()
 * @method OutletTranslationEntity|null last()
 */
class OutletTranslationCollection extends EntityCollection
{
    protected function getExpectedClass(): string
    {
        return OutletTranslationEntity::class;
    }
}
