<?php declare(strict_types=1);

namespace WebkulPOS\Core\Content\Outlet;

use Shopware\Core\Framework\DataAbstractionLayer\Entity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityIdTrait;
use Shopware\Core\System\Country\CountryEntity;
use WebkulPOS\Core\Content\OutletProduct\OutletProductCollection;

class OutletEntity extends Entity
{
    use EntityIdTrait;

    /**
     * @var string
     */
    protected $name;

    /**
     * @var string
     */
    protected $address;

    /**
     * @var string
     */
    protected $countryId;

    /**
     * @var CountryEntity
     */
    protected $country;

    /**
     * @var string
     */
    protected $city;

    /**
     * @var string
     */
    protected $zipcode;

    /**
     * @var bool
     */
    protected $active;

    /**
     * @var OutletProductCollection
     */
    protected $products;

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getAddress(): string
    {
        return $this->address;
    }

    public function setAddress(string $address): void
    {
        $this->address = $address;
    }

    public function getCity(): string
    {
        return $this->city;
    }

    public function setCity(string $city): void
    {
        $this->city = $city;
    }

    public function getCountryId(): string
    {
        return $this->countryId;
    }

    public function setCountryId($countryId): void
    {
        $this->countryId = $countryId;
    }

    public function getZipcode(): string
    {
        return $this->zipcode;
    }

    public function setZipcode($zipcode): void
    {
        $this->zipcode = $zipcode;
    }

    public function getActive(): bool
    {
        return $this->active;
    }

    public function setActive(bool $active): void
    {
        $this->active = $active;
    }

    public function setProducts(OutletProductCollection $products)
    {
        $this->products = $products;
    }

    public function getProducts(): OutletProductCollection
    {
        return $this->products;
    }

    /**
     * Get the value of country
     *
     * @return  CountryEntity
     */ 
    public function getCountry(): CountryEntity
    {
        return $this->country;
    }

    /**
     * Set the value of country
     *
     * @param  CountryEntity  $country
     *
     * @return  self
     */ 
    public function setCountry(CountryEntity $country)
    {
        $this->country = $country;

        return $this;
    }
}
