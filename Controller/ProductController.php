<?php

namespace WebkulPOS\Controller;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Shopware\Core\Framework\Routing\Annotation\RouteScope;
use Shopware\Core\Framework\Uuid\Uuid;

/**
 * @RouteScope(scopes={"api"})
 */
class ProductController extends AbstractController
{
    static $barcodePath = __DIR__ . '/../../../../../public/media/barcode';

    /**
     * @Route("/api/wkpos/product/list/{outletId}", name="api.wkpos.product", methods={"POST"})
     *
     * @return JsonResponse
     */
    public function getProducts(Context $context, $outletId)
    {
        $connection = $this->container->get(Connection::class);

        $sql = "SELECT wp.*, p.product_number, p.stock, p.media, p.available_stock, pp.price, pt.name FROM `wkpos_product` wp LEFT JOIN `product` p ON (p.id = wp.product_id) LEFT JOIN `product_translation` pt ON (p.id = pt.product_id) LEFT JOIN `product_price` pp ON (pp.product_id = p.id) WHERE 1";

        if ($outletId) {
            $sql .= " AND wp.outlet_id = " . $outletId;
        }

        $results = $connection->executeQuery($sql)->fetchAll();

        return new JsonResponse($results);
    }

    /**
     * @Route("/api/wkpos/product/assigned-status", name="api.wkpos.product.assigned.status", methods={"POST"})
     *
     * @return JsonResponse
     */

    public function checkIsAssigned(Request $request)
    {
        $productRepository = $this->container->get('wkpos_product.repository');

        $productId = $request->request->get('productId');
        $outletId = $request->request->get('outletId');

        $product = $productRepository->search(
            (new Criteria())
                ->addFilter(new EqualsFilter('outletId', $outletId))
                ->addFilter(new EqualsFilter('productId', $productId)),
            Context::createDefaultContext()
        );

        $status = 0;

        if ($product->getTotal() > 0) {
            $status = $product->first()->getActive();
        }


        return new JsonResponse(['status' => $status]);
    }

    /**
     * @Route("/api/wkpos/product/barcode/{productId}", name="api.wkpos.product", methods={"POST"})
     *
     * @return JsonResponse
     */

    protected function getBarcode($productId)
    {
        $connection = $this->container->get(Connection::class);

        $sql = "SELECT wp.*, p.product_number, p.stock, p.media, p.available_stock, pp.price, pt.name FROM `wkpos_product` wp LEFT JOIN `product` p ON (p.id = wp.product_id) LEFT JOIN `product_translation` pt ON (p.id = pt.product_id) LEFT JOIN `product_price` pp ON (pp.product_id = p.id) WHERE wp.outlet_id = " . $productId;

        $result = $connection->executeQuery($sql)->fetch();

        return new JsonResponse($result);
    }

    /**
     * @Route("/api/wkpos/product/barcodes", name="api.wkpos.barcodes", methods={"POST"})
     *
     * @return JsonResponse
     */

    public function getBarcodes(Request $request)
    {

        $barcodes = [];

        $connection = $this->container->get(Connection::class);

        $sql = "SELECT * FROM `wkpos_barcode`";

        $results = $connection->executeQuery($sql)->fetchAll();

        foreach ($results as $result) {
            $barcodes[strtoupper(bin2hex($result['product_id']))]['url'] = $request->getHttpHost() . '/media/barcode/' . $result['barcode'] . '.png';
        }

        return new JsonResponse($barcodes);
    }

    /**
     * @Route("/api/wkpos/assign/products", name="api.wkpos.outlet.assign", methods={"POST"})
     *
     * @return JsonResponse
     */

    public function assign(Request $request)
    {
        $connection = $this->container->get(Connection::class);

        $response = array();

        $productIds = $request->request->get('productIds');

        $outletId = $request->request->get('outletId');

        $stock = $request->request->get('stock');
        
        $productRepository = $this->container->get('product.repository');
        $productEntititis = $productRepository->search(
            (new Criteria($productIds)),
            Context::createDefaultContext()
        );
        $parentProducts = $productEntititis->getElements();

        $variantEntities = array();
        
        foreach ($productIds as $key=>$productId) {
            $variantEntity = $this->fetchProductVariantList($productId);
            $variantEntities[] = $variantEntity;
        }
        $productEntititis = array();
        foreach($variantEntities as $variantEntity){
            foreach($variantEntity as $entity){
                $productEntititis[] = $entity;
            }
        }
        

        $outletProductRepository = $this->container->get('wkpos_product.repository');

        $products = array_merge($parentProducts,$productEntititis);
        ;
        $posProducts = [];

        $count = 0;

        
        foreach ($products as $product) {
            
            //if ($product->getAvailableStock() > 0) {
                $outletProductEntity = $outletProductRepository->searchIds(
                    (new Criteria())
                        ->addFilter(new EqualsFilter('productId', $product->getId()))
                        ->addFilter(new EqualsFilter('outletId', $outletId)),
                    Context::createDefaultContext()
                );
                
                $uuid = Uuid::randomHex();
                $assignStock = null;
                if (is_array($stock)) {
                    if(isset($stock[$product->getId()]) || isset($stock[$product->getParentId()])) {

                        $assignStock = isset($stock[$product->getId()])?$stock[$product->getId()]:$stock[$product->getParentId()];
                    }
                } else {
                    $assignStock = $stock;
                }
                
                
                //if ( $product->getAvailableStock() > $assignStock) {
                    if ($outletProductEntity->getTotal() > 0) {
                        $uuid = $outletProductEntity->firstId();


                        $product = [
                            'stock' => $assignStock?$assignStock:1,
                            'status' => true,
                            'updated_at' => date('Y-m-d H:i:s.') . substr(date('u'), 0, 3)
                        ];

                        try {
                            
                            $result = $connection->update('wkpos_product', $product, ['id' => hex2bin($uuid)]);
                            if ($result) {
                                $count++;
                            }
                        } catch (\Exception $th) { }
                    } else {
                        $product = [
                        'id' => hex2bin($uuid),
                        'product_id' => hex2bin($product->getId()),
                        'outlet_id' => hex2bin($outletId),
                        'stock' => $assignStock?$assignStock:1,
                        'status' => true,
                        'created_at' => date('Y-m-d H:i:s.') . substr(date('u'), 0, 3)
                        ];

                        try {

                            $result = $connection->insert('wkpos_product', $product);
                            if ($result) {
                                $count++;
                            }
                        } catch (\Exception $th) { }
                    }
                //}
            //}
        }


        $response['count'] = $count;

        $response['success'] = 'Product was assigned successfully';

        return new JsonResponse($response);
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
    /**
     * @Route("api/wkpos/barcode/generate/", name="wkpos.barcode.generate", methods={"POST"})
     */
    public function generateProductBarcode(Request $request)
    {
        $productId = $request->request->get('id');
        $barcode = $request->request->get('code');
        $barcodeRepository = $this->container->get('wkpos_barcode.repository');
        $entity = $barcodeRepository->search((new Criteria())->addFilter(new EqualsFilter('productId',$productId)),Context::createDefaultContext())->first();
        if($entity){
            $id = $entity->getId();
        } else {
            $id = Uuid::randomHex();
        }
        $barcodeRepository->upsert([['id'=>$id,'productId'=>$productId,'barcode'=>$barcode]],Context::createDefaultContext());
        return new JsonResponse(true);
        
    }
}
