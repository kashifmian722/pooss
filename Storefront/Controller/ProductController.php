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
use Shopware\Core\Content\Product\Aggregate\ProductVisibility\ProductVisibilityDefinition;
use Shopware\Core\Content\Product\SalesChannel\ProductAvailableFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\ContainsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsAnyFilter;
use Symfony\Component\HttpFoundation\JsonResponse;
use Shopware\Core\Framework\Routing\Annotation\RouteScope;

/**
 * @RouteScope(scopes={"storefront"})
 */
class ProductController extends StorefrontController
{
  /**
   * @Route("/wkpos/products", name="frontend.wkpos.product", defaults={"csrf_protected"=false})
   */
  public function actionProducts(Request $request, SalesChannelContext $salesChannelContext)
  {
    $taxEntity = $salesChannelContext->getTaxRules();
	$salesChannelLanguageId = $salesChannelContext->getSalesChannel()->getLanguageId();
    $taxes = [];
    $categoryId = $request->request->get('categoryId');

    foreach ($taxEntity->getElements() as $key => $tax) {
      $taxes[$tax->getId()] = [
        'id'        => $tax->getId(),
        'name'      => $tax->getName(),
        'rate'      => $tax->getTaxRate()
      ];
    }
    
    $token = $salesChannelContext->getToken();
        $connection = $this->container->get(Connection::class);
        
        $payload = $connection->fetchAll("SELECT `payload` FROM `sales_channel_api_context` WHERE `token` = '" . $token . "'");
        if(count($payload) > 0 && isset(json_decode($payload[0]['payload'])->currencyId)){
          
            $currncyId = json_decode($payload[0]['payload'])->currencyId;
            $currencyEntity = $this->container->get('currency.repository')->search((new Criteria())->addFilter(new EqualsFilter('id',$currncyId)), Context::createDefaultContext())->first();
          
        } else {
          $currencyEntity = $salesChannelContext->getSalesChannel()->getCurrency();
        }
      $currency = [
      'id'           => $currencyEntity->getId(),
      'name'         => $currencyEntity->getName(),
      'code'         => $currencyEntity->getIsoCode(),
      'factor'         => $currencyEntity->getFactor(),
      'symbol'       => $currencyEntity->getSymbol(),
      'decimalPlace' => $currencyEntity->getDecimalPrecision()
     ];
    
    $productArray = [];

    $outletId = $request->get('outletId');

    $outlet = $this->container->get('wkpos_outlet.repository')->search(
      (new Criteria())->addFilter(new EqualsFilter('id',$outletId))->addFilter(new EqualsFilter('active', true)),
      Context::createDefaultContext()
    );

    if ($outlet->getTotal() > 0) {
      $productEntitities = [];
      $outletProductRepository = $this->container->get('wkpos_product.repository');
      $productEntitities = $outletProductRepository->search( 
        (new Criteria())->addFilter(new EqualsFilter('outletId', $outletId))->addAssociation('product'),
        Context::createDefaultContext()
      );
      
      if ($productEntitities->getTotal() > 0) {
        $productRepository = $this->container->get('product.repository');
        $products = $productEntitities->getElements();
        
        foreach ($products as $key=>$productEntity) {
          
          $criteria = (new Criteria([$productEntity->getProductId()]))
            ->addSorting(new FieldSorting('name'))
            ->addAssociation('media')
            ->addAssociation('prices')
            ->addAssociation('manufacturer')
            ->addAssociation('translations')
            ->addAssociation('cover');
          if ($productEntity->product->getParentId() == null) {
            $criteria->addFilter(new ProductAvailableFilter($salesChannelContext->getSalesChannel()->getId(), ProductVisibilityDefinition::VISIBILITY_LINK))
            ->addFilter(new EqualsFilter('active', true));
          }
          if($categoryId){
            $criteria->addFilter(new ContainsFilter('categoryTree',$categoryId));
          }  

          $product = $productRepository->search(
            $criteria,
            Context::createDefaultContext()
          )->first();
         
         
          if ($product) {
            
            $variants  = $this->fetchProductVariantList($product->getId());
            $formattedVariant = $this->formatProductVariants($variants,$product->getPrice(),$salesChannelLanguageId);
            if ($product->getPrice() == null) {
              $priceObject = $this->getParentProductPrice($product->getParentId());
            }
            else{

              $priceObject = $product->getPrice();
            }
            if ($product->getTax() == null) {
              $tax = $this->getParentProductTax($product->getParentId());

            } else {
              $tax = $product->getTax();
            }
            $price = $priceObject->get($currencyEntity->getId());
            $priceElements = $priceObject->getElements();
            $currencyRepository = $this->container->get('currency.repository');
            $priceCurrency = $currencyRepository->search(
              new Criteria(array_keys($priceElements)),
              Context::createDefaultContext()
            )->first();

            if ($priceCurrency->getId() != $currencyEntity->getId() || !$price) {
              $price = $priceObject->first();
              $gross = $price->getGross() * $currencyEntity->getFactor();
              $net = $price->getNet() * $currencyEntity->getFactor();
              $price->setGross($gross);
              $price->setNet($net);
            }
            
            $image = '';

            if ($product->getCover()) {
              $image = $product->getCover()->getMedia()->getUrl();
            } 
            if ($product->getParentId() && $product->getCover() == null) {
              $image = $this->getParentProductImage($product->getParentId());
            }
            $options = '';
            $optionsArray = [];
            
            $name = $product->getName();
            if ($product->getTranslations()) {

              foreach ($product->getTranslations() as $translation) {
                
              if ($translation->getLanguageId() == $salesChannelLanguageId) {
                $name = $translation->getName();
              } 
            }

            }
           
            if ($product->getParentId() != null) {
             
                [$name, $options, $optionsArray] = $this->getParentProductName($product->getParentId(),$product->getOptionIds());
            }
           
            $productArray[$product->getId()] = [
              'id'             => $product->getId(),
              'parentId'       => $product->getParentId(),
              'name'           => $name,
              'description'    => $product->getDescription(),
              'productNumber'  => $product->getProductNumber(),
              'properties'     => $product->getProperties(),
              'ean'            => $product->getEan(),
              'stock'          => $product->getStock(),
              'availableStock' => $product->getAvailableStock(),
              'available'      => $product->getAvailable(),
              'priceNetUf'       => (float) number_format($price->getNet(), 2, '.', ''),
              'priceNetF'        => $currencyEntity->getSymbol() . number_format($price->getNet(), 2),
              'priceGrossUf'       => (float) number_format($price->getGross(), 2, '.', ''),
              'priceGrossF'        => $currencyEntity->getSymbol() . number_format($price->getGross(), 2),
              'image'          => $image,
              'tax'            => $tax,
              'unit'           => $product->getUnit(),
              'categories'     => $product->getCategoryTree(),
              'variant'       => $formattedVariant,
              'options'       => $options,
              'optionsArray'  => $optionsArray,
              'posStock'     => $productEntity->getStock()
            ];
          }
        }
      }
    }
    // collect categories
    $categoryRepository = $this->container->get('category.repository');

    $categoryCriteria = (new Criteria())
        ->addFilter(new EqualsFilter('active',1))
        ->addFilter(new EqualsFilter('parentId', null))
		->addAssociation('translations');

    $categoryCollection = $categoryRepository->search($categoryCriteria, Context::createDefaultContext());
    
    $parentIdCollection = $categoryCollection->getElements();
    $childCategoryCollection = [];
    foreach ($parentIdCollection as $key => $value) {
      $criteria = (new Criteria())
            ->addFilter(new EqualsFilter('active',1))
            ->addFilter(new EqualsFilter('parentId', $value->getId()))
            ->addAssociation('children')
			->addAssociation('translations')
			->addAssociation('children.translations');

        $categoryCollection = $categoryRepository->search($criteria, Context::createDefaultContext());
        $childCategoryCollection[] = $categoryCollection;

    }
	$channelLanguageId = $salesChannelContext->getSalesChannel()->getLanguageId();
    $subCategoryCollection = array();
    foreach($childCategoryCollection as $childCategory){
        foreach($childCategory as $catgory){

            $subCategoryCollection[] = $catgory;
			
        }
	}
    $formattedCategoryCollection = [];
    foreach ($subCategoryCollection as $collection) {
		
      $categoryName = $collection->getName();
		foreach ($collection->getTranslations() as $translation) {
			if ($channelLanguageId == $translation->getLanguageId()) {
				$categoryName = $translation->getName();

			}
		}
		foreach ($collection->getChildren() as $children) {

      $childName = $children->getName();
			foreach ($children->getTranslations() as $translation) {
				if ($channelLanguageId == $translation->getLanguageId()) {
					$childName = $translation->getName();
	
				}
			}
			$subCategory[] = ['name' => $childName?$childName:$children->getName(), 'id'=>$children->getId()] ;
			
		}
		$formattedCategoryCollection[] = [
			'name' => $categoryName?$categoryName:$collection->getName(),
			'id' => $collection->getId(),
			'children' => $subCategory

		];
	}
	
    return new JsonResponse(array(
      'products' => $productArray,
      'total'    => count($productArray),
      'currency' => $currency,
      'taxes'    => $taxes,
      'categories' => $formattedCategoryCollection
    ));
  }
  public function fetchProductVariantList($productId)
    {
        $productRepository = $this->container->get('product.repository');

        $variantCriteria = (new Criteria())
        ->addFilter(new EqualsFilter('parentId', $productId));

        return $productRepository->search($variantCriteria, Context::createDefaultContext())
            ->getEntities()
            ->getElements();
    }
    public function formatProductVariants($variants, $productPrice, $languageId)
    {
      $formattedVariantCollection = [];
      $propertyGroupOption = $this->container->get('property_group_option.repository');

      foreach ($variants as $variant) {
          $name = $variant->getName();

          if (!$name) {
              $optionIds = $variant->getOptionIds();

              $name = '';
              foreach ($optionIds as $optionId) {
                  $criteria = (new Criteria())
                  ->addFilter(new EqualsFilter('id', $optionId))
                  ->addAssociation('group')
				  ->addAssociation('group.translations')
				  ->addAssociation('translations');
                  if ($name)
                      $name .= ' & ';

                  $propertyGroup = $propertyGroupOption->search($criteria, Context::createDefaultContext())->first();
                  $groupName = $propertyGroup->getGroup()->getName();
				  foreach ($propertyGroup->getGroup()->getTranslations() as $groupTrans) {
					  if ($groupTrans->getLanguageId() == $languageId) {

						  $groupName = $groupTrans->getName();
					  }
				  }
          $propertyName = $propertyGroup->getName();
				  foreach ($propertyGroup->getTranslations() as $propertyTrans) {
					  if ($propertyTrans->getLanguageId() == $languageId) {
						  $propertyName = $propertyTrans->getName();
					  } 
				  }
                  $name .= $groupName .'-'.$propertyName;
              }
          }


          $price = $variant->getPrice() ?? $productPrice;

          if (getType($price) == "object") {
              $prices = $price->getElements();
              $price = $prices[array_keys($prices)[0]]->getGross();
          }

          $formattedVariant = [
              'name' => $name,
              'id' => $variant->get('id'),
              'price' => $price,
              'stock' => $variant->getStock(),
              'active' => $variant->get('active')?$variant->get('active'):true,
          ];

          $formattedVariantCollection[] = $formattedVariant;
      }
      return $formattedVariantCollection;
    }
    public function getParentProductPrice($parentId) {
      $productRepository = $this->container->get('product.repository');
      $criteria = new Criteria();
      $criteria->addFilter(new EqualsFilter('id',$parentId));
      return $productRepository->search($criteria,Context::createDefaultContext())->first()->getPrice();
    }
    public function getParentProductTax($parentId) {
      $productRepository = $this->container->get('product.repository');
      $criteria = new Criteria();
      $criteria->addFilter(new EqualsFilter('id',$parentId));
      return $productRepository->search($criteria,Context::createDefaultContext())->first()->getTax();
    }
    public function getParentProductImage($parentId) {
      $productRepository = $this->container->get('product.repository');
      $criteria = new Criteria();
      $criteria->addFilter(new EqualsFilter('id',$parentId))->addAssociation('cover');
    
      $cover = $productRepository->search($criteria,Context::createDefaultContext())->first()->getCover();
      if($cover){
        return $cover->getMedia()->getUrl();
      } else{
        return;
      }
      
    }
    public function getParentProductName($parentId,$optionIds) {
      $productRepository = $this->container->get('product.repository');
      $propertyGroupOption = $this->container->get('property_group_option.repository');
      $criteria = new Criteria();
      $criteria->addFilter(new EqualsFilter('id',$parentId))->addAssociation('group');
      $name =  $productRepository->search($criteria,Context::createDefaultContext())->first()->getName();
      $options = '';
      $optionsArray = [];
      foreach ($optionIds as $optionId) {
          $criteria = (new Criteria())
          ->addFilter(new EqualsFilter('id', $optionId))
          ->addAssociation('group');
          $entity = $propertyGroupOption->search($criteria, Context::createDefaultContext())->first();
          

          if ($options)
              $options .= ' , ';

          $propertyGroup = $propertyGroupOption->search($criteria, Context::createDefaultContext())->first();
          $options .= $propertyGroup->getGroup()->getName() .'-'.$propertyGroup->getName();
          $optionsArray[] = ['group'=>$propertyGroup->getGroup()->getName(),'option'=>$propertyGroup->getName()];
      }
      
      return [$name, $options, $optionsArray];
      
    }
    
   
}
