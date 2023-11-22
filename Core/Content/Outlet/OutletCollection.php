<?php declare(strict_types=1);

namespace WebkulPOS\Core\Content\Outlet;

use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;

/**
 * @method void              add(OutletEntity $entity)
 * @method void              set(string $key, OutletEntity $entity)
 * @method OutletEntity[]    getIterator()
 * @method OutletEntity[]    getElements()
 * @method OutletEntity|null get(string $key)
 * @method OutletEntity|null first()
 * @method OutletEntity|null last()
 */
class OutletCollection extends EntityCollection
{
    protected function getExpectedClass(): string
    {
        return OutletEntity::class;
    }
}
