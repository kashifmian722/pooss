<?php

namespace WebkulPOS\Core\Content\User;

use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;
use WebkulPOS\Core\Content\Outlet\OutletCollection;
use WebkulPOS\Core\Content\Outlet\OutletEntity;

/**
 * @method void              add(UserEntity $entity)
 * @method void              set(string $key, UserEntity $entity)
 * @method UserEntity[]    getIterator()
 * @method UserEntity[]    getElements()
 * @method UserEntity|null get(string $key)
 * @method UserEntity|null first()
 * @method UserEntity|null last()
 */
class UserCollection extends EntityCollection
{
    protected function getExpectedClass(): string
    {
        return UserEntity::class;
    }

    public function getOutlet(): OutletCollection
    {
        return new OutletCollection(
            $this->fmap(function (OutletEntity $outlet) {
                return $outlet->getName();
            })
        );
    }
}
