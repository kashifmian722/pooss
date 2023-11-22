<?php declare(strict_types=1);

namespace WebkulPOS\Core\Content\Outlet\Aggregate\OutletProduct;

use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;

/**
 * @method void                     add(OutletProductEntity $entity)
 * @method void                     set(string $key, OutletProductEntity $entity)
 * @method OutletProductEntity[]    getIterator()
 * @method OutletProductEntity[]    getElements()
 * @method OutletProductEntity|null get(string $key)
 * @method OutletProductEntity|null first()
 * @method OutletProductEntity|null last()
 */

class OutletProductCollection extends EntityCollection
{
    public function getOutletIds(): array
    {
        return $this->fmap(function (OutletProductEntity $outletProduct) {
            return $outletProduct->getOutletId();
        });
    }

    public function filterByOutletId(string $id): self
    {
        return $this->filter(function (OutletProductEntity $outletProduct) use ($id) {
            return $outletProduct->getOutletId() === $id;
        });
    }

    protected function getExpectedClass(): string
    {
        return OutletProductEntity::class;
    }
}