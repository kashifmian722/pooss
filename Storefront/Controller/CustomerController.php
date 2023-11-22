<?php declare(strict_types=1);

namespace WebkulPOS\Storefront\Controller;

use Shopware\Core\Checkout\Customer\SalesChannel\RegisterRoute;
use Shopware\Storefront\Controller\StorefrontController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Sorting\FieldSorting;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Symfony\Component\HttpFoundation\JsonResponse;
use Shopware\Core\Framework\Routing\Annotation\RouteScope;

/**
 * @RouteScope(scopes={"storefront"})
 */
class CustomerController extends StorefrontController
{
    /**
     * @var RegisterRoute
     */
    private $registerRoute;

    public function __construct(RegisterRoute $registerRoute) {
        $this->registerRoute = $registerRoute;
    }

     /**
     * @Route("/wkpos/customer", name="frontend.wkpos.customer", defaults={"csrf_protected"=false})
     */
     public function customerAction(Request $request, SalesChannelContext $salesChannelContext)
     {

        $countries = [];
        $customers = [];
        $salutations = [];
        $countryRepository = $this->container->get('country.repository');
        $countryEntities = $countryRepository->search(
            (new Criteria())->addSorting(new FieldSorting('id')),
            Context::createDefaultContext()
        );

        $countries = $countryEntities->getElements();

        $salutationRepository = $this->container->get('salutation.repository');
        $salutationEntities = $salutationRepository->search(
            (new Criteria())->addSorting(new FieldSorting('id')),
            Context::createDefaultContext()
        );

        $salutations = $salutationEntities->getElements();

        $customerRepository = $this->container->get('customer.repository');
        $entities = $customerRepository->search(
            (new Criteria())
            ->addSorting(new FieldSorting('firstName'))
            ->addAssociation('defaultBillingAddress')
            ->addFilter(new EqualsFilter('active', 1)),
            Context::createDefaultContext()
        );

        $customers = $entities->getElements();

        $posCustomers = [];
        $countryRepository = $this->container->get('country.repository');

        foreach ($customers as $customer) {
            $defaultAddress = $customer->getDefaultBillingAddress();

            $countryEntity = $countryRepository->search(
                (new Criteria())->addFilter(new EqualsFilter('id', $defaultAddress->getCountryId())),
                Context::createDefaultContext()
            )->first();

            $posCustomers[] = [
                'customerId' => $customer->getId(),
                'name'       => $customer->__toString(),
                'email'      => $customer->getEmail(),
                'address'    => [
                    'id'            => $defaultAddress->getId(),
                    'street'        => $defaultAddress->getStreet(),
                    'city'          => $defaultAddress->getCity(),
                    'country'       => $countryEntity->getName(),
                    'zipcode'       => $defaultAddress->getZipcode(),
                    'phoneNumber'   => $defaultAddress->getPhoneNumber()
                ]
            ];

        }
        // get default customer for current outlet
        $outletId = $request->request->get('outletId');
        $defaultCustomerRepository = $this->container->get('wkpos_default_customer.repository');
        $defaultCustomer = $defaultCustomerRepository->search((new Criteria())->addFilter(new EqualsFilter('outletId',$outletId)),$salesChannelContext->getContext())->first();
        $defaultCustomerId = '';
        if($defaultCustomer) {
            $defaultCustomerId = $defaultCustomer->get('customerId');
        }
        
        return new JsonResponse(array(
            'countries' => $countries,
            'customers' => $posCustomers,
            'salutations' => $salutations,
            'defaultCustomerId' => $defaultCustomerId
        ));
     }

    /**
     * @Route("/wkpos/customer/register", name="frontend.wkpos.customer.register", defaults={"csrf_protected"=false})
     */
     public function registerCustomer(Request $request, SalesChannelContext $context)
     {
        $data = $request->request->all();
        $data = $data['register'];
        $address = [
            'street' => $data['street'],
            'zipcode' => $data['zipcode'],
            'city' => $data['city'],
            'countryId' => $data['countryId']
        ];

        $parameters = [
            'salutationId' => $data['salutationId'],
            'firstName' => $data['firstName'],
            'lastName' => $data['lastName'],
            'email' => $data['email'],
            'password' => $data['password'],
            'billingAddress' => new RequestDataBag($address)
        ];

        $error = false;
        $errorMessages = [];
        $customerId = '';
        $customer = [];

        try {
            $customer = $this->registerRoute->register(new RequestDataBag($parameters), $context, false);
            
        } catch (\Exception $e) {
           
            foreach ($e->getViolations() as $message) {
                
                $path = str_replace('/', '', $message->getPropertyPath());
                $errorMessages[] = str_replace('This', ucfirst($path) , $message->getMessage());
            }
            

            $error = true;
        }

        if ($customer) {
            $customerRepository = $this->container->get('customer.repository');
            $customerEntity = $customerRepository->search(
                (new Criteria([$customer->getCustomer()->getId()]))->addAssociation('defaultBillingAddress')->addFilter(new EqualsFilter('active', 1)),
                Context::createDefaultContext()
            )->first();

            $defaultAddress = $customerEntity->getDefaultBillingAddress();

            $countryRepository = $this->container->get('country.repository');
            $countryEntity = $countryRepository->search(
                (new Criteria())->addFilter(new EqualsFilter('id', $defaultAddress->getCountryId())),
                Context::createDefaultContext()
            )->first();

            $customer = [
                'customerId' => $customerEntity->getId(),
                'name'       => $customerEntity->__toString(),
                'email'      => $customerEntity->getEmail(),
                'address'    => [
                    'id'            => $defaultAddress->getId(),
                    'street'        => $defaultAddress->getStreet(),
                    'city'          => $defaultAddress->getCity(),
                    'country'       => $countryEntity->getName(),
                    'zipcode'       => $defaultAddress->getZipcode(),
                    'phoneNumber'   => $defaultAddress->getPhoneNumber()
                ]
            ];
        }

        return new JsonResponse([
            'customer' => $customer,
            'error' => $error,
            'errorMessages' => $errorMessages
        ]);
     }
}
