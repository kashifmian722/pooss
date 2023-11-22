<?php

declare(strict_types=1);

namespace WebkulPOS\Storefront\Controller;

use Doctrine\DBAL\Connection;
use Shopware\Storefront\Controller\StorefrontController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Sorting\FieldSorting;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Checkout\Cart\Price\Struct\CalculatedPrice;
use Shopware\Core\Checkout\Cart\Price\Struct\CartPrice;
use Shopware\Core\Checkout\Cart\Tax\Struct\CalculatedTax;
use Shopware\Core\Checkout\Cart\Tax\Struct\CalculatedTaxCollection;
use Shopware\Core\Checkout\Cart\Tax\Struct\TaxRule;
use Shopware\Core\Checkout\Cart\Tax\Struct\TaxRuleCollection;
use Shopware\Core\Framework\Util\Random;
use Shopware\Core\Framework\Uuid\Uuid;
use Symfony\Component\HttpFoundation\JsonResponse;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\Routing\Annotation\RouteScope;
use Symfony\Component\HttpFoundation\Session\Session;

/**
 * @RouteScope(scopes={"storefront"})
 */
class OrderController extends StorefrontController
{

    /**
     * @var EntityRepositoryInterface
     */
    private $customerRepository;
    /**
     * @var EntityRepositoryInterface
     */
    private $orderRepository;

    /**
     * @var EntityRepositoryInterface
     */
    private $orderAddressRepository;

    /**
     * @var EntityRepositoryInterface
     */
    private $orderCustomerRepository;

    /**
     * @var EntityRepositoryInterface
     */
    private $deliveryRepository;

    /**
     * @var EntityRepositoryInterface
     */
    private $lineItemRepository;

    /**
     * @var EntityRepositoryInterface
     */
    private $paymentMethodRepository;

    /**
     * @var EntityRepositoryInterface
     */
    private $posOrderRepository;

    /**
     * @var EntityRepositoryInterface
     */
    private $transactionRepository;

    /**
     * @var EntityRepositoryInterface
     */
    private $shippingMethodRepository;

    /**
     * @var EntityRepositoryInterface
     */
    private $productRepository;
  
  	/**
     * @var EntityRepositoryInterface
     */
    private $merchantOrderRepository;

    /**
     * @var EntityRepositoryInterface
     */
    private $merchantRepository;

    /**
     * @var Connection
     */
    private $connection;

    public function __construct(
        EntityRepositoryInterface $customerRepository,
        EntityRepositoryInterface $orderRepository,
        EntityRepositoryInterface $orderAddressRepository,
        EntityRepositoryInterface $orderCustomerRepository,
        EntityRepositoryInterface $deliveryRepository,
        EntityRepositoryInterface $lineItemRepository,
        EntityRepositoryInterface $paymentMethodRepository,
        EntityRepositoryInterface $posOrderRepository,
        EntityRepositoryInterface $transactionRepository,
        EntityRepositoryInterface $shippingMethodRepository,
        EntityRepositoryInterface $productRepository,
      	EntityRepositoryInterface $merchantOrderRepository,
        EntityRepositoryInterface $merchantRepository
    ) {
        $this->customerRepository = $customerRepository;
        $this->orderRepository = $orderRepository;
        $this->orderAddressRepository = $orderAddressRepository;
        $this->orderCustomerRepository = $orderCustomerRepository;
        $this->deliveryRepository = $deliveryRepository;
        $this->lineItemRepository = $lineItemRepository;
        $this->paymentMethodRepository = $paymentMethodRepository;
        $this->posOrderRepository = $posOrderRepository;
        $this->transactionRepository = $transactionRepository;
        $this->shippingMethodRepository = $shippingMethodRepository;
        $this->productRepository = $productRepository;
      	$this->merchantOrderRepository = $merchantOrderRepository;
        $this->merchantRepository = $merchantRepository;
    }
    /**
     * @Route("/wkpos/sync/order", name="frontend.wkpos.sync.order", defaults={"csrf_protected"=false})
     */
    public function syncAllOrders(Request $request, SalesChannelContext $context)
    {
        $ordersData = $request->request->all();
        foreach ($ordersData['data'] as $data) {
            
            if ($data['grandTotal'] == base64_decode($data['error']['gt'])) {
                $error = false;
            } else {
                $error = true;
            }
           
            $data['cardPayment'] = $data['cardPayment'] ?? 0;
            $data['cashPayment'] = $data['cashPayment'] ?? 0;
    
            $lineItems = [];
            $taxData = array_column($data['cart'], 'tax');
            $tempArr = array_unique(array_column($taxData, 'taxId'));
            $uniqueTax = array_intersect_key($taxData, $tempArr);
            $taxIds = array_column($uniqueTax, 'taxId');
    
            $taxStatus = 'gross';
            $taxElements = [];
            $taxRuleElements = [];
            $shippingTaxElements = [];
            $uuidEntity = new Uuid();
            $orderUuid = $uuidEntity->randomHex();
    
            $deliveries = [];
            $positions = [];
    
            $paymentMethodId = $shippingMethodId = '';
    
            $paymentMethodEntity = $this->paymentMethodRepository->search(
                (new Criteria())->addFilter(new EqualsFilter('handlerIdentifier', 'Shopware\Core\Checkout\Payment\Cart\PaymentHandler\PrePayment')),
                Context::createDefaultContext()
            )->first();
    
            $paymentMethodId = $paymentMethodEntity->getId();
    
            $shippingMethodEntity = $this->shippingMethodRepository->search(
                (new Criteria())->addFilter(new EqualsFilter('name', 'Standard')),
                Context::createDefaultContext()
            )->first();
    
            $shippingMethodId = $shippingMethodEntity->getId();
    
            $stateRepository = $this->container->get('state_machine_state.repository');
            
            $stateEntity = $stateRepository->search(
                (new Criteria())->addFilter(new EqualsFilter('technicalName', 'paid')),
                Context::createDefaultContext()
            )->first();
    
            $customer = $this->customerRepository->search(
                (new Criteria([$data['customer']['customerId']]))
                    ->addAssociation('defaultShippingAddress')
                    ->addAssociation('defaultBillingAddress'),
                Context::createDefaultContext()
            )->first();
    
            $orderCustomer = [
                "customerId" => $customer->getId(),
                "email" => $customer->getEmail(),
                "firstName" => $customer->getFirstName(),
                "lastName" => $customer->getLastName(),
                "salutationId" => $customer->getSalutationId(),
                "title" => $customer->getTitle(),
                "customerNumber" => $customer->getCustomerNumber(),
            ];
    
            foreach ($data['cart'] as $product) {
                $productEntity = $this->productRepository->search(
                    (new Criteria([$product['productId']])),
                    Context::createDefaultContext()
                )->first();
                if ($productEntity->getPrice() == null) {
                    $priceObj = $this->getParentProductPrice($productEntity->getParentId());
                    
                    $productPrice = $priceObj->first();
                    
                } else {
    
                    $productPrice = $productEntity->getPrice()->get($data['currency']['id']);
                }
               
                if ($productPrice) {
                    $netPrice =  $productPrice->getNet();
                    $grossPrice =  $productPrice->getGross();
                } else {
                    $productPrice = $productEntity->getPrice()->first()->getNet();
                    $netPrice =  $productPrice * $data['currency']['factor'];
                    $grossPrice = $productEntity->getPrice()->first()->getGross() * $data['currency']['factor'];
                }
    
                $positionTax = [];
                $positionTaxRule = [];
                $lineItemTax = [];
                $lineItemTaxRule = [];
    
                $lineItemUnitPrice  = $grossPrice;
                $lineItemTotalPrice = $grossPrice * $product['quantity'];
    
                $orderLineItemId = $uuidEntity->randomHex();
    
                $positionTax[$product['tax']['taxRate']] = new CalculatedTax(
                    $product['tax']['tax'],
                    $product['tax']['taxRate'],
                    $grossPrice * $product['quantity']
                );
    
                $lineItemTaxRule[$product['tax']['taxRate']] = $positionTaxRule[$product['tax']['taxRate']] = new TaxRule($product['tax']['taxRate']);
    
                $lineItemTax = $positionTax;
    
                $positions[] = [
                    "price" => new CalculatedPrice(
                        $grossPrice,
                        $grossPrice * $product['quantity'],
                        new CalculatedTaxCollection($positionTax),
                        new TaxRuleCollection($positionTaxRule)
                    ),
                    "orderLineItemId" => $orderLineItemId
                ];
    
                $lineItemPrice = new CalculatedPrice(
                    $lineItemUnitPrice,
                    $lineItemTotalPrice,
                    new CalculatedTaxCollection($lineItemTax),
                    new TaxRuleCollection($lineItemTaxRule)
                );
    
                $lineItems[] = [
                    "id" => $orderLineItemId,
                    "identifier" => $product['productId'],
                    "productId" => $product['productId'],
                    "referencedId" => $product['productId'],
                    "quantity" => $product['quantity'],
                    "type" => 'product',
                    "label" => $product['name'],
                    "good"  => true,
                    "removable" => true,
                    "stackable" => true,
                    "price" => $lineItemPrice,
                    "payload" => $product['payload']
                ];
            }
    
            $totalTaxIndex = [];
    
            foreach ($taxIds as $taxId) {
                $totalTaxIndex[$taxId] = [];
                $totalTaxIndex[$taxId]['price'] = 0;
                $totalTaxIndex[$taxId]['tax'] = 0;
            }
    
            foreach ($taxData as $dataTax) {
                $totalTaxIndex[$dataTax['taxId']]['price'] += $dataTax['price'];
                $totalTaxIndex[$dataTax['taxId']]['tax'] += $dataTax['tax'];
                $totalTaxIndex[$dataTax['taxId']]['taxRate'] = $dataTax['taxRate'];
            }
    
            $finalTaxElements = [];
            $finalTaxRules = [];
    
            foreach ($totalTaxIndex as $taxIndex) {
                $finalTaxElements[$taxIndex['taxRate']] = new CalculatedTax(
                    $taxIndex['tax'],
                    $taxIndex['taxRate'],
                    $taxIndex['price']
                );
    
                $finalTaxRules[$taxIndex['taxRate']] = new TaxRule($taxIndex['taxRate']);
            }
    
            $lastOrder = $this->orderRepository->search(
                (new Criteria())->addSorting(new FieldSorting('createdAt')),
                Context::createDefaultContext()
            );
    
            $address = $customer->getDefaultBillingAddress();
    
            $shippingCosts = new CalculatedPrice(
                0,
                0,
                (new CalculatedTaxCollection($taxElements)),
                new TaxRuleCollection($shippingTaxElements)
            );
    
            if ($lastOrder->getTotal() > 0) {
                $orderNumber = $lastOrder->last()->getOrderNumber() + 1;
            } else {
                $orderNumber = Random::getInteger(1, 10000);
            }
    
            $deliveries = [
                "shippingDateEarliest" => date('Y-m-d H:i:s.') . substr(date('u'), 0, 3),
                "shippingDateLatest" => date('Y-m-d H:i:s.') . substr(date('u'), 0, 3),
                "shippingMethodId" => $shippingMethodId,
                "shippingOrderAddress" => [
                    "id" => Uuid::randomHex(),
                    "company" => $address->getCompany(),
                    "department" => $address->getDepartment(),
                    "salutationId" => $address->getSalutationId(),
                    "title" => $address->getTitle(),
                    "firstName" => $address->getFirstName(),
                    "lastName" => $address->getLastName(),
                    "street" => $address->getStreet(),
                    "zipcode" => $address->getZipcode(),
                    "city" => $address->getCity(),
                    "phoneNumber" => $address->getPhoneNUmber(),
                    "additionalAddressLine1" => "",
                    "additionalAddressLine2" => "",
                    "countryId" => $address->getCountryId(),
                ],
                "shippingCosts" => $shippingCosts,
                "positions" => $positions,
                "stateId" => $stateEntity->getId()
    
            ];
    
            $transactionAmount = new CalculatedPrice(
                (float) $data['grandTotal'],
                (float) $data['grandTotal'],
                (new CalculatedTaxCollection($finalTaxElements)),
                new TaxRuleCollection($finalTaxRules)
            );
    
            $transactions[0] = [
                "paymentMethodId" => $paymentMethodId,
                "amount" => $transactionAmount,
                "stateId" => $stateEntity->getId()
            ];
    
            $cartPrice = new CartPrice(
                (float) $data['subTotal'],
                (float) $data['grandTotal'],
                (float) $data['grandTotal'],
                new CalculatedTaxCollection($finalTaxElements),
                new TaxRuleCollection($finalTaxRules),
                $taxStatus
            );
    
            $billingAddressId = Uuid::randomHex();
            
            $orderData = [
                "orderDateTime" => date('Y-m-d H:i:s.') . substr(date('u'), 0, 3),
                "price" => $cartPrice,
                "shippingCosts" => $shippingCosts,
                "stateId" => $stateEntity->getId(),
                "currencyId" => $data['currency']['id'],
                "currencyFactor" => 1.0,
                "salesChannelId" => $context->getSalesChannel()->getId(),
                "lineItems" => $lineItems,
                "deliveries" => [$deliveries],
                "orderCustomer" => $orderCustomer,
                "languageId" => $context->getSalesChannel()->getLanguageId(),
                "billingAddressId" => $billingAddressId,
                "transactions" => $transactions,
                "id" => $orderUuid,
                "orderNumber" => (string) $orderNumber,
            ];
    
            $message = 'Order has been placed';
    
            $billingAddress = [
                'id' => $billingAddressId,
                'orderId' => $orderUuid,
                "company" => $address->getCompany(),
                "department" => $address->getDepartment(),
                "salutationId" => $address->getSalutationId(),
                "title" => $address->getTitle(),
                "firstName" => $address->getFirstName(),
                "lastName" => $address->getLastName(),
                "street" => $address->getStreet(),
                "zipcode" => $address->getZipcode(),
                "city" => $address->getCity(),
                "countryId" => $address->getCountryId()
                
            ];
            $result = null;
            try {
                $result = $this->orderRepository->create(
                    [$orderData],
                    Context::createDefaultContext()
                );
    
                $this->orderAddressRepository->create(
                    [$billingAddress],
                    Context::createDefaultContext()
                );
            } catch (\Exception $ex) {
                
                $message = $ex->getMessage();
            }
    
            $posOrderData = [];
    
            if (!is_null($result)) {
                $posOrderData = [
                    "id" => $uuidEntity->randomHex(),
                    "orderId" => $orderUuid,
                    "userId" => $data['user']['userId'],
                    "userName" => $data['user']['name'],
                    "orderNote" => $data['orderNote'],
                    "cashPayment" => (string) $data['cashPayment'],
                    "cardPayment" => (string) $data['cardPayment']
                ];
    
                $this->posOrderRepository->create(
                    [$posOrderData],
                    Context::createDefaultContext()
                );
    
                // Manage the product stock
                foreach ($data['cart'] as $product) {
                    $coreProduct = $this->productRepository->search(
                        (new Criteria([$product['productId']])),
                        Context::createDefaultContext()
                    )->first();
    
                    $remainingStock = $coreProduct->getAvailableStock() - $product['quantity'];
    
                    $connection = $this->container->get(Connection::class);
    
                    $connection->update(
                        'product',
                        ['available_stock' => $remainingStock],
                        ['id' => hex2bin($coreProduct->getId())]
                    );
    
                    $posProductRepositroy = $this->container->get('wkpos_product.repository');
    
                    $posProduct = $posProductRepositroy->search(
                        (new Criteria())->addFilter(new EqualsFilter('productId', $product['productId']))
                            ->addFilter(new EqualsFilter('outletId', $data['user']['outletId'])),
                        Context::createDefaultContext()
                    )->first();
    
                    $remainingStock = $posProduct->getStock() - $product['quantity'];
    
                    $connection->update(
                        'wkpos_product',
                        ['stock' => $remainingStock],
                        ['id' => hex2bin($posProduct->getId())]
                    );
                }
            }
        }
        return new JsonResponse([
            'success' => true,
            'message' => $message,
            'error' => $error
        ]);
    }

    /**
     * @Route("/wkpos/order/add", name="frontend.wkpos.order.add", defaults={"csrf_protected"=false})
     */
    public function addOrder(Request $request, SalesChannelContext $context)
    {
        $data = $request->request->all();
        if ($data['grandTotal'] == base64_decode($data['error']['gt'])) {
            $error = false;
        } else {
            $error = true;
        }

        $createdBy = $data['createdBy'];

        $discount = $data['discount'];
       	
        $data['cardPayment'] = $data['cardPayment'] ?? 0;
        $data['cashPayment'] = $data['cashPayment'] ?? 0;

        $merchantId = $data['merchantId'];

        $criteria = new Criteria([$merchantId]);
        $merchant = $this->merchantRepository->search($criteria, $context->getContext())->first();

        $checkins = 0;
        
        if(isset($merchant->getCustomFields()['checkin'])){
            $checkins = (int) $merchant->getCustomFields()['checkin'];
        }

        $this->merchantRepository->update([
            [
                'id' => $merchantId,
                "customFields" => [
                    "checkin" => $checkins + 1
                ]
            ]
        ], $context->getContext());

        $lineItems = [];
        $taxData = array_column($data['cart'], 'tax');
        $tempArr = array_unique(array_column($taxData, 'taxId'));
        $uniqueTax = array_intersect_key($taxData, $tempArr);
        $taxIds = array_column($uniqueTax, 'taxId');

        $taxStatus = 'gross';
        $taxElements = [];
        $taxRuleElements = [];
        $shippingTaxElements = [];
        $uuidEntity = new Uuid();
        $orderUuid = $uuidEntity->randomHex();

        $deliveries = [];
        $positions = [];

        $paymentMethodId = $shippingMethodId = '';

        $paymentMethodEntity = $this->paymentMethodRepository->search(
            (new Criteria())->addFilter(new EqualsFilter('handlerIdentifier', 'Shopware\Core\Checkout\Payment\Cart\PaymentHandler\PrePayment')),
            Context::createDefaultContext()
        )->first();

        $paymentMethodId = $paymentMethodEntity->getId();

        $shippingMethodEntity = $this->shippingMethodRepository->search(
            (new Criteria())->addFilter(new EqualsFilter('name', 'Standard')),
            Context::createDefaultContext()
        )->first();

        $shippingMethodId = $shippingMethodEntity->getId();

        $stateRepository = $this->container->get('state_machine_state.repository');
        
        $stateEntity = $stateRepository->search(
            (new Criteria())->addFilter(new EqualsFilter('technicalName', 'paid')),
            Context::createDefaultContext()
        )->first();

        $customer = $this->customerRepository->search(
            (new Criteria([$data['customer']['customerId']]))
                ->addAssociation('defaultShippingAddress')
                ->addAssociation('defaultBillingAddress'),
            Context::createDefaultContext()
        )->first();

        $orderCustomer = [
            "customerId" => $customer->getId(),
            "email" => $customer->getEmail(),
            "firstName" => $customer->getFirstName(),
            "lastName" => $customer->getLastName(),
            "salutationId" => $customer->getSalutationId(),
            "title" => $customer->getTitle(),
            "customerNumber" => $customer->getCustomerNumber(),
        ];
       
        foreach ($data['cart'] as $product) {
            
            $posProduct = $this->container->get('wkpos_product.repository')->search((new Criteria())->addFilter(new EqualsFilter('productId',$product['productId'])),Context::createDefaultContext())->first();
            
            // if ($posProduct->getStock() < $product['quantity']) {
            //     $message = ' does not have enough quantity';
            //     return new JsonResponse([
            //         'success' => false,
            //         'message' => $message,
            //         'productName' => $product['name']
            //     ]);
            // }
            
            $productEntity = $this->productRepository->search(
                (new Criteria([$product['productId']])),
                Context::createDefaultContext()
            )->first();
            if ($productEntity->getPrice() == null) {
                $priceObj = $this->getParentProductPrice($productEntity->getParentId());
                
                $productPrice = $priceObj->first();
                
            } else {

                $productPrice = $productEntity->getPrice()->get($data['currency']['id']);
            }
           
            if ($productPrice) {
                $netPrice =  $productPrice->getNet();
                $grossPrice =  $productPrice->getGross();
            } else {
                $productPrice = $productEntity->getPrice()->first()->getNet();
                $netPrice =  $productPrice * $data['currency']['factor'];
                $grossPrice = $productEntity->getPrice()->first()->getGross() * $data['currency']['factor'];
            }

            $positionTax = [];
            $positionTaxRule = [];
            $lineItemTax = [];
            $lineItemTaxRule = [];

            $lineItemUnitPrice  = $grossPrice;
            $lineItemTotalPrice = $grossPrice * $product['quantity'];

            $orderLineItemId = $uuidEntity->randomHex();

            $positionTax[$product['tax']['taxRate']] = new CalculatedTax(
                $product['tax']['tax'],
                $product['tax']['taxRate'],
                $grossPrice * $product['quantity']
            );

            $lineItemTaxRule[$product['tax']['taxRate']] = $positionTaxRule[$product['tax']['taxRate']] = new TaxRule($product['tax']['taxRate']);

            $lineItemTax = $positionTax;

            $positions[] = [
                "price" => new CalculatedPrice(
                    $grossPrice,
                    $grossPrice * $product['quantity'],
                    new CalculatedTaxCollection($positionTax),
                    new TaxRuleCollection($positionTaxRule)
                ),
                "orderLineItemId" => $orderLineItemId
            ];

            $lineItemPrice = new CalculatedPrice(
                $lineItemUnitPrice,
                $lineItemTotalPrice,
                new CalculatedTaxCollection($lineItemTax),
                new TaxRuleCollection($lineItemTaxRule)
            );

            $lineItems[] = [
                "id" => $orderLineItemId,
                "identifier" => $product['productId'],
                "quantity" => $product['quantity'],
                "type" => 'product',
                "productId" => $product['productId'],
                "referencedId" => $product['productId'],
                "label" => $product['name'],
                "good"  => true,
                "removable" => true,
                "stackable" => true,
                "price" => $lineItemPrice,
                "payload" => $product['payload']
            ];
        }

        $totalTaxIndex = [];

        foreach ($taxIds as $taxId) {
            $totalTaxIndex[$taxId] = [];
            $totalTaxIndex[$taxId]['price'] = 0;
            $totalTaxIndex[$taxId]['tax'] = 0;
        }

        foreach ($taxData as $dataTax) {
            $totalTaxIndex[$dataTax['taxId']]['price'] += $dataTax['price'];
            $totalTaxIndex[$dataTax['taxId']]['tax'] += $dataTax['tax'];
            $totalTaxIndex[$dataTax['taxId']]['taxRate'] = $dataTax['taxRate'];
        }

        $finalTaxElements = [];
        $finalTaxRules = [];

        foreach ($totalTaxIndex as $taxIndex) {
            $finalTaxElements[$taxIndex['taxRate']] = new CalculatedTax(
                $taxIndex['tax'],
                $taxIndex['taxRate'],
                $taxIndex['price']
            );

            $finalTaxRules[$taxIndex['taxRate']] = new TaxRule($taxIndex['taxRate']);
        }

        $lastOrder = $this->orderRepository->search(
            (new Criteria())->addSorting(new FieldSorting('orderNumber')),
            Context::createDefaultContext()
        );

        $address = $customer->getDefaultBillingAddress();

        $shippingCosts = new CalculatedPrice(
            0,
            0,
            (new CalculatedTaxCollection($taxElements)),
            new TaxRuleCollection($shippingTaxElements)
        );
        
        if ($lastOrder->getTotal() > 0) {
            $orderNumber = (int)$lastOrder->last()->getOrderNumber();
            $orderNumber = $orderNumber + 1;
        } else {
            $orderNumber = Random::getInteger(1, 10000);
        }

        $deliveries = [
            "shippingDateEarliest" => date('Y-m-d H:i:s.') . substr(date('u'), 0, 3),
            "shippingDateLatest" => date('Y-m-d H:i:s.') . substr(date('u'), 0, 3),
            "shippingMethodId" => $shippingMethodId,
            "shippingOrderAddress" => [
                "id" => Uuid::randomHex(),
                "company" => $address->getCompany(),
                "department" => $address->getDepartment(),
                "salutationId" => $address->getSalutationId(),
                "title" => $address->getTitle(),
                "firstName" => $address->getFirstName(),
                "lastName" => $address->getLastName(),
                "street" => $address->getStreet(),
                "zipcode" => $address->getZipcode(),
                "city" => $address->getCity(),
                "phoneNumber" => $address->getPhoneNUmber(),
                "additionalAddressLine1" => "",
                "additionalAddressLine2" => "",
                "countryId" => $address->getCountryId(),
            ],
            "shippingCosts" => $shippingCosts,
            "positions" => $positions,
            "stateId" => $stateEntity->getId()

        ];

        $transactionAmount = new CalculatedPrice(
            (float) $data['grandTotal'],
            (float) $data['grandTotal'],
            (new CalculatedTaxCollection($finalTaxElements)),
            new TaxRuleCollection($finalTaxRules)
        );

        $transactions[0] = [
            "paymentMethodId" => $paymentMethodId,
            "amount" => $transactionAmount,
            "stateId" => $stateEntity->getId()
        ];

        $cartPrice = new CartPrice(
            (float) $data['subTotal'],
            (float) $data['grandTotal'],
            (float) $data['grandTotal'],
            new CalculatedTaxCollection($finalTaxElements),
            new TaxRuleCollection($finalTaxRules),
            $taxStatus
        );

        $billingAddressId = Uuid::randomHex();
        
        $orderData = [
            "orderDateTime" => date('Y-m-d H:i:s.') . substr(date('u'), 0, 3),
            "price" => $cartPrice,
            "shippingCosts" => $shippingCosts,
            "stateId" => $stateEntity->getId(),
            "currencyId" => $data['currency']['id'],
            "currencyFactor" => $data['currency']['factor'],
            "salesChannelId" => $context->getSalesChannel()->getId(),
            "lineItems" => $lineItems,
            "deliveries" => [$deliveries],
            "orderCustomer" => $orderCustomer,
            "languageId" => $context->getSalesChannel()->getLanguageId(),
            "billingAddressId" => $billingAddressId,
            "transactions" => $transactions,
            "id" => $orderUuid,
            "orderNumber" => (string) $orderNumber,
            'customFields' => [
                "createdBy" => $createdBy,
            ]
        ];

        $message = 'Order has been placed';

        $billingAddress = [
            'id' => $billingAddressId,
            'orderId' => $orderUuid,
            "company" => $address->getCompany(),
            "department" => $address->getDepartment(),
            "salutationId" => $address->getSalutationId(),
            "title" => $address->getTitle(),
            "firstName" => $address->getFirstName(),
            "lastName" => $address->getLastName(),
            "street" => $address->getStreet(),
            "zipcode" => $address->getZipcode(),
            "city" => $address->getCity(),
            "countryId" => $address->getCountryId()
            
        ];
        $result = null;
        try {
            $result = $this->orderRepository->create(
                [$orderData],
                Context::createDefaultContext()
            );

            if($discount > 0){
                $this->orderRepository->update([
                    [
                        'id' => $orderUuid,
                        "customFields" => [
                            "discount" => $discount
                        ]
                    ]
                ], Context::createDefaultContext());
            }

            $this->orderAddressRepository->create(
                [$billingAddress],
                Context::createDefaultContext()
            );
        } catch (\Exception $ex) {
            
            $message = 'Error in placing order';
        }

        $posOrderData = [];

        if (!is_null($result)) {
            $posOrderData = [
                "id" => $uuidEntity->randomHex(),
                "orderId" => $orderUuid,
                "userId" => $data['user']['userId'],
                "userName" => $data['user']['name'],
                "orderNote" => $data['orderNote'],
                "cashPayment" => (string) $data['cashPayment'],
                "cardPayment" => (string) $data['cardPayment']
            ];

            $this->posOrderRepository->create(
                [$posOrderData],
                Context::createDefaultContext()
            );

            $merchantOrderData = [
                'merchantId' => $merchantId,
                'orderId' => $orderUuid
            ];

            $this->merchantOrderRepository->create(
                [$merchantOrderData],
                Context::createDefaultContext()
            );

            // Manage the product stock
            foreach ($data['cart'] as $product) {
                $coreProduct = $this->productRepository->search(
                    (new Criteria([$product['productId']])),
                    Context::createDefaultContext()
                )->first();

                $remainingStock = $coreProduct->getAvailableStock() - $product['quantity'];

                $connection = $this->container->get(Connection::class);

                $connection->update(
                    'product',
                    ['available_stock' => $remainingStock],
                    ['id' => hex2bin($coreProduct->getId())]
                );

                $posProductRepositroy = $this->container->get('wkpos_product.repository');

                $posProduct = $posProductRepositroy->search(
                    (new Criteria())->addFilter(new EqualsFilter('productId', $product['productId']))
                        ->addFilter(new EqualsFilter('outletId', $data['user']['outletId'])),
                    Context::createDefaultContext()
                )->first();

                $remainingStock = $posProduct->getStock() - $product['quantity'];

                $connection->update(
                    'wkpos_product',
                    ['stock' => $remainingStock],
                    ['id' => hex2bin($posProduct->getId())]
                );
            }

        }
        return new JsonResponse([
            'success' => true,
            'message' => $message,
            'error' => $error
        ]);
    }

    /**
     * @Route("/wkpos/orders", name="frontend.wkpos.order", defaults={"csrf_protected"=false})
     */
    public function loadOrder(Request $request, SalesChannelContext $context)
    {   
        $session = new Session();
        $userId = $request->request->get('userId');
        $loginUserId = $session->get('userId');
        $orders = [];
        if ($userId === $loginUserId) {
           
          
            $connection = $this->container->get(Connection::class);
            $results = $connection->executeQuery("SELECT *, HEX(`id`) AS `id`, HEX(`user_id`) AS `user_id`, LCASE(HEX(`order_id`)) AS `order_id` FROM `wkpos_order` WHERE LCASE(`user_id`) = UNHEX('" . $userId . "') ORDER BY `auto_increment` DESC")->fetchAll();
            $orderIds = array_column($results, 'order_id');
    
            $orderEntities = $this->orderRepository->search(
                (new Criteria($orderIds))
                    ->addSorting(new FieldSorting('orderNumber', 'DESC'))
                    ->addAssociation('lineItems')
                    ->addAssociation('transactions')
                    ->addAssociation('orderCustomer')
                    ->addAssociation('currency'),
                Context::createDefaultContext()
            );
    
            $elements = $orderEntities->getElements();
            foreach ($results as $result) {

                $order = $elements[$result['order_id']];
                $tempOrderDate = date('Y-m-d', $order->getCreatedAt()->getTimestamp());
                $today = (new \DateTime())->format('Y-m-d');
                
                if($tempOrderDate == $today){

                    
                    
        
                    $tax = 0;
        
                    $taxes = $order->getPrice()->getCalculatedTaxes();
        
                    if ($taxes) {
                        foreach ($taxes->getElements() as $taxElement) {
                            $tax += $taxElement->getTax();
                        }
                    }
        
                    $orderAddressEntity = $this->orderAddressRepository->search(
                        (new Criteria([$order->getBillingAddressId()]))
                            ->addAssociation('country'),
                        Context::createDefaultContext()
                    )->first();
        
                    $orderAddress = [];
        
                    if ($orderAddressEntity) {
                        $orderAddress = [
                            'id' => $orderAddressEntity->getId(),
                            'street' => $orderAddressEntity->getStreet() ?? 'NA',
                            'city' => $orderAddressEntity->getCity(),
                            'zipcode' => $orderAddressEntity->getZipcode(),
                            'country' => $orderAddressEntity->getCountry()
                        ];
                    }
        
                    foreach ($order->getLineItems()->getElements() as $key => $lineItem) {
                        $unitPrice = (float) number_format($lineItem->getUnitPrice(), 2, '.', '');
                        $order->getLineItems()->getElements()[$key]->setUnitPrice($unitPrice);
                        $totalPrice = (float) number_format($lineItem->getTotalPrice(), 2, '.', '');
                        $order->getLineItems()->getElements()[$key]->setTotalPrice($totalPrice);
                    }
                    
                    $orderData = [
                        "id" => $result["id"],
                        "orderId" => $result["order_id"],
                        "cashPayment" => $result["cash_payment"],
                        "cardPayment" => $result["card_payment"],
                        "orderNumber" => $order->getOrderNumber(),
                        "amountNet" => $order->getAmountNet(),
                        "amountTotal" => $order->getAmountTotal(),
                        "orderDate" => date('Y-m-d', $order->getCreatedAt()->getTimestamp()),
                        "orderTime" => date('H:i:s', $order->getCreatedAt()->getTimestamp()),
                        "currency" => $order->getCurrency(),
                        "orderStatus" => $order->getStateMachineState()->getName(),
                        "orderCustomer" => $order->getOrderCustomer(),
                        "lineItems" => $order->getLineItems(),
                        "orderAddress" => $orderAddress,
                        "transactions" => $order->getTransactions(),
                        "customFields" => $order->getCustomFields(),
                        "taxTotal" => round($tax, $order->getCurrency()->getDecimalPrecision())
                    ];
                    array_push($orders,$orderData);
                }
            }
        }
        
        return new JsonResponse($orders);
    }
    public function getParentProductPrice($parentId) {
        $productRepository = $this->container->get('product.repository');
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('id',$parentId));
        return $productRepository->search($criteria,Context::createDefaultContext())->first()->getPrice();
    }

    protected function fetchProfileData( $salesChannelContext, $merchant): array
    {
        $criteria = new Criteria([$merchant]);
        $criteria->addAssociation('media.thumbnails');
        $criteria->addAssociation('cover');
        $criteria->addAssociation('services');

        $profile = $this->merchantRepository->search($criteria, $salesChannelContext)->first();

        $profileData = json_decode(json_encode($profile), true);

        unset($profileData['password'], $profileData['extensions'], $profileData['_uniqueIdentifier']);

        return $profileData;
    }
}