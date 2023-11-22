<?php declare(strict_types=1);

namespace WebkulPOS\Core\Content\Order;

use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;

/**
 * @method void              add(WkposOrderEntity $entity)
 * @method void              set(string $key, WkposOrderEntity $entity)
 * @method WkposOrderEntity[]    getIterator()
 * @method WkposOrderEntity[]    getElements()
 * @method WkposOrderEntity|null get(string $key)
 * @method WkposOrderEntity|null first()
 * @method WkposOrderEntity|null last()
 */

class WkposOrderCollection extends EntityCollection
{
    protected function getExpectedClass(): string
    {
        return WkposOrderEntity::class;
    }
}