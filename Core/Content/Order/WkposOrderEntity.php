<?php declare(strict_types=1);

namespace WebkulPOS\Core\Content\Order;

use Shopware\Core\Framework\DataAbstractionLayer\Entity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityIdTrait;
use Shopware\Core\Checkout\Order\OrderEntity;
use WebkulPOS\Core\Content\User\UserEntity;

class WkposOrderEntity extends Entity
{
    use EntityIdTrait;

    /**
     * @var string
     */
    protected $userId;

    /**
     * @var UserEntity
     */
    protected $user;

    /**
     * @var string
     */
    protected $userName;

    /**
     * @var string
     */
    protected $orderId;

    /**
     * @var int
     */
    protected $autoIncrement;

    /**
     * @var float
     */
    protected $cashPayment;

    /**
     * @var float
     */
    protected $cardPayment;

    /**
     * @var string
     */
    protected $orderNote;

    /**
     * @var OrderEntity
     */
    protected $order;


    /**
     * Get the value of userId
     *
     * @return  string
     */ 
    public function getUserId()
    {
        return $this->userId;
    }

    /**
     * Set the value of userId
     *
     * @param  string  $userId
     *
     * @return  self
     */ 
    public function setUserId(string $userId)
    {
        $this->userId = $userId;

        return $this;
    }

    /**
     * Get the value of userName
     *
     * @return  string
     */ 
    public function getUserName()
    {
        return $this->userName;
    }

    /**
     * Set the value of userName
     *
     * @param  string  $userName
     *
     * @return  self
     */ 
    public function setUserName(string $userName)
    {
        $this->userName = $userName;

        return $this;
    }

    /**
     * Get the value of orderId
     *
     * @return  string
     */ 
    public function getOrderId()
    {
        return $this->orderId;
    }

    /**
     * Set the value of orderId
     *
     * @param  string  $orderId
     *
     * @return  self
     */ 
    public function setOrderId(string $orderId)
    {
        $this->orderId = $orderId;

        return $this;
    }

    /**
     * Get the value of orderNote
     *
     * @return  string
     */ 
    public function getOrderNote()
    {
        return $this->orderNote;
    }

    /**
     * Set the value of orderNote
     *
     * @param  string  $orderNote
     *
     * @return  self
     */ 
    public function setOrderNote(string $orderNote)
    {
        $this->orderNote = $orderNote;

        return $this;
    }

    /**
     * Get the value of order
     *
     * @return  OrderEntity
     */ 
    public function getOrder(): ?OrderEntity
    {
        return $this->order;
    }

    /**
     * Set the value of order
     *
     * @param  OrderEntity  $order
     *
     * @return  self
     */ 
    public function setOrder(OrderEntity $order)
    {
        $this->order = $order;

        return $this;
    }

    /**
     * Get the value of autoIncrement
     *
     * @return  int
     */ 
    public function getAutoIncrement()
    {
        return $this->autoIncrement;
    }

    /**
     * Set the value of autoIncrement
     *
     * @param  int  $autoIncrement
     *
     * @return  self
     */ 
    public function setAutoIncrement(int $autoIncrement)
    {
        $this->autoIncrement = $autoIncrement;

        return $this;
    }

    /**
     * Get the value of cashPayment
     *
     * @return  float
     */ 
    public function getCashPayment()
    {
        return $this->cashPayment;
    }

    /**
     * Set the value of cashPayment
     *
     * @param  float  $cashPayment
     *
     * @return  self
     */ 
    public function setCashPayment(float $cashPayment)
    {
        $this->cashPayment = $cashPayment;

        return $this;
    }

    /**
     * Get the value of cardPayment
     *
     * @return  float
     */ 
    public function getCardPayment()
    {
        return $this->cardPayment;
    }

    /**
     * Set the value of cardPayment
     *
     * @param  float  $cardPayment
     *
     * @return  self
     */ 
    public function setCardPayment(float $cardPayment)
    {
        $this->cardPayment = $cardPayment;

        return $this;
    }

    /**
     * Get the value of user
     *
     * @return  UserEntity
     */ 
    public function getUser()
    {
        return $this->user;
    }

    /**
     * Set the value of user
     *
     * @param  UserEntity  $user
     *
     * @return  self
     */ 
    public function setUser(UserEntity $user)
    {
        $this->user = $user;

        return $this;
    }
}