<?php declare(strict_types = 1);

namespace WebkulPOS\Core\Content\Outlet\Aggregate\OutletProduct;

use Shopware\Core\Framework\DataAbstractionLayer\Entity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityIdTrait;


class OutletProductEntity extends Entity
{
    use EntityIdTrait;

     /**
     * @var string
     */
    protected $outletId;

     /**
     * @var string
     */
    protected $productId;

     /**
     * @var string
     */
    protected $productVersionId;

     /**
     * @var int
     */
    protected $stock;

     /**
     * @var bool
     */
    protected $status;

    public function getOutletId(): ?string
    {
        return $this->outletId;
    }

    public function setOutletId(string $outletId): void
    {
        $this->outletId = $outletId;
    }

    public function getProductId(): ?string
    {
        return $this->productId;
    }

    public function setProductId(string $productId): void
    {
        $this->productId = $productId;
    }

    public function getProductVersionId(): ?string
    {
        return $this->productVersionId;
    }

    public function setProductVersionId(string $productVersionId): void
    {
        $this->productVersionId = $productVersionId;
    }

    public function getStock(): int
    {
        return $this->stock;
    }

    public function setStock(int $stock): void
    {
        $this->stock = $stock;
    }

    public function getStatus(): bool
    {
        return $this->status;
    }

    public function setStatus(bool $status): void
    {
        $this->status = $status;
    }
}