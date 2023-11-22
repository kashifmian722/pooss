<?php declare(strict_types = 1);

namespace WebkulPOS\Core\Content\Outlet\Aggregate\OutletTranslation;

use WebkulPOS\Core\Content\Outlet\OutletEntity;
use Shopware\Core\Framework\DataAbstractionLayer\TranslationEntity;

class OutletTranslationEntity extends TranslationEntity
{
    /**
     * @var string
     */
    protected $outletId;

    /**
     * @var string
     */
    protected $name;

    /**
     * @var OutletEntity
     */
    protected $outlet;

    /**
     * @return string
     */
    public function getOutletId(): string
    {
        return $this->outletId;
    }

    public function setOutletId(string $outletId): void
    {
        $this->outletId = $outletId;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getOutlet(): OutletEntity
    {
        return $this->outlet;
    }

    public function setOutlet(OutletEntity $outlet): void
    {
        $this->outlet = $outlet;
    }
}
