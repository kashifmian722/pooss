<?php declare(strict_types=1);

namespace WebkulPOS\Core\Content\User\Service;

use Composer\Semver\Constraint\ConstraintInterface;

use WebkulPOS\Core\Content\User\UserEntity;
use WebkulPOS\Core\Content\User\Exception\BadCredentialsException;
use WebkulPOS\Core\Content\User\Exception\UserNotFoundException;
use WebkulPOS\Core\Content\User\Password\LegacyPasswordVerifier;
// use WebkulPOS\Core\Content\User\Validation\Constraint\UserEmailUnique;
// use WebkulPOS\Core\Content\User\Validation\Constraint\UserPasswordMatches;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\EntitySearchResult;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
// use Shopware\Core\Framework\Event\DataMappingEvent;
use Shopware\Core\Framework\Uuid\Exception\InvalidUuidException;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\Framework\Validation\BuildValidationEvent;
use Shopware\Core\Framework\Validation\DataBag\DataBag;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\Framework\Validation\DataValidationDefinition;
use Shopware\Core\Framework\Validation\DataValidator;
use Shopware\Core\Framework\Validation\Exception\ConstraintViolationException;
use Shopware\Core\System\SalesChannel\Context\SalesChannelContextPersister;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\EqualTo;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationList;

class UserService
{

    /**
     * @var EntityRepositoryInterface
     */
    private $userRepository;

    /**
     * @var SalesChannelContextPersister
     */
    private $contextPersister;

    /**
     * @var EventDispatcherInterface
     */
    private $eventDispatcher;

    /**
     * @var DataValidator
     */
    private $validator;

    /**
     * @var LegacyPasswordVerifier
     */
    private $legacyPasswordVerifier;

    /**
     * @var EntityRepositoryInterface
     */
    private $paymentMethodRepository;

    /**
     * @var SystemConfigService
     */
    private $systemConfigService;

    public function __construct(
        EntityRepositoryInterface $userRepository,
        SalesChannelContextPersister $contextPersister,
        DataValidator $validator,
        LegacyPasswordVerifier $legacyPasswordVerifier,
        SystemConfigService $systemConfigService
    ) {
        $this->userRepository = $userRepository;
        $this->contextPersister = $contextPersister;
        $this->validator = $validator;
        $this->legacyPasswordVerifier = $legacyPasswordVerifier;
        $this->systemConfigService = $systemConfigService;
    }

    /**
     * @throws UserNotFoundException
     */
    public function getUserByEmail(string $email, SalesChannelContext $context): UserEntity
    {
        $users = $this->getUsersByEmail($email, $context);

        $userCount = $users->count();
        
        if ($userCount === 1) {
            return $users->first();
        }

        throw new UserNotFoundException($email);
    }

    /**
     * @throws UserNotFoundException
     */
    public function getUserByUsername(string $username, SalesChannelContext $context): UserEntity
    {
        $users = $this->getUsersByUsername($username, $context);

        $userCount = $users->count();
        
        if ($userCount === 1) {
            return $users->first();
        }

        throw new UserNotFoundException($username);
    }    

    public function getUsersByEmail(string $email, SalesChannelContext $context): EntitySearchResult
    {
        $criteria = new Criteria();
        $criteria->addFilter(
            new EqualsFilter('wkpos_user.email', $email),
            new EqualsFilter('active', true)
        );
        
        // TODO NEXT-389 we have to check an option like "bind user to salesChannel"
        // todo in this case we have to filter "user.salesChannelId is null or salesChannelId = :current"

        return $this->userRepository->search($criteria, $context->getContext());
    }

    public function getUsersByUsername(string $username, SalesChannelContext $context): EntitySearchResult
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('wkpos_user.username', $username));
        
        // TODO NEXT-389 we have to check an option like "bind user to salesChannel"
        // todo in this case we have to filter "user.salesChannelId is null or salesChannelId = :current"

        return $this->userRepository->search($criteria, $context->getContext());
    }

    public function savePassword(DataBag $data, SalesChannelContext $context): void
    {
        $this->validateUser($context);

        $this->validatePasswordFields($data, $context);

        $userData = [
            'id' => $context->getUser()->getId(),
            'password' => $data->get('newPassword'),
        ];

        $this->userRepository->update([$userData], $context->getContext());
    }

    public function saveEmail(DataBag $data, SalesChannelContext $context): void
    {
        $this->validateUser($context);

        $this->validateEmail($data, $context);

        $userData = [
            'id' => $context->getUser()->getId(),
            'email' => $data->get('email'),
        ];

        $this->userRepository->update([$userData], $context->getContext());
    }

    /**
     * @throws BadCredentialsException
     * @throws UnauthorizedHttpException
     */
    public function login(string $email, SalesChannelContext $context, bool $includeGuest = false): string
    {
        if (empty($email)) {
            throw new BadCredentialsException();
        }

        $user = $this->getUserByEmail($email, $context);
        

        $newToken = $this->contextPersister->replace($context->getToken(),$context);
        $this->contextPersister->save(
            $newToken,
            [
                'userId' => $user->getId(),
                'billingAddressId' => null,
                'shippingAddressId' => null,
            ], $context->getSalesChannelId()
        );

        $event = new UserLoginEvent($context->getContext(), $user, $newToken, $context->getSalesChannel()->getId());
        $this->eventDispatcher->dispatch($event);

        return $newToken;
    }

    /**
     * @throws BadCredentialsException
     * @throws UnauthorizedHttpException
     */
    public function loginWithPassword(DataBag $data, SalesChannelContext $context): UserEntity
    {
        if (empty($data->get('username')) || empty($data->get('password'))) {
            throw new BadCredentialsException();
        }

        try {
            $user = $this->getUserByLogin(
                $data->get('username'),
                $data->get('password'),
                $context
            );
        } catch (UserNotFoundException | BadCredentialsException $exception) {
            throw new UnauthorizedHttpException('json', $exception->getMessage());
        }

        $newToken = $this->contextPersister->replace($context->getToken(),$context);
        $this->contextPersister->save(
            $newToken,
            [
                'userId' => $user->getId(),
            ], $context->getSalesChannel()->getId()
        );

        $this->userRepository->update([
            [
                'id' => $user->getId(),
                'lastLogin' => new \DateTimeImmutable(),
            ],
        ], $context->getContext());

        // $event = new UserLoginEvent($context->getContext(), $user, $newToken, $context->getSalesChannel()->getId());
        // $this->eventDispatcher->dispatch($event);

        return $user;
    }

    public function logout(SalesChannelContext $context): void
    {
        $this->contextPersister->save(
            $context->getToken(),
            [
                'userId' => null
            ],
            $context->getSalesChannelId()
        );

        // $event = new UserLogoutEvent($context->getContext(), $context->getCustomer(), $context->getSalesChannel()->getId());
        // $this->eventDispatcher->dispatch($event);
    }

    /**
     * @throws UserNotFoundException
     * @throws BadCredentialsException
     */
    public function getUserByLogin(string $username, string $password, SalesChannelContext $context): UserEntity
    {
        $user = $this->getUserByUsername($username, $context);

        if ($user->hasLegacyPassword()) {
            if (!$this->legacyPasswordVerifier->verify($password, $user)) {
                throw new BadCredentialsException();
            }

            $this->updatePasswordHash($password, $user, $context->getContext());

            return $user;
        }

        if (!password_verify($password, $user->getPassword())) {
            throw new BadCredentialsException();
        }

        return $user;
    }
    
    /**
     * @throws UserNotLoggedInException
     */
    private function validateUser(SalesChannelContext $context): void
    {
        if ($context->getCustomer()) {
            return;
        }

        throw new UserNotLoggedInException();
    }

    private function updatePasswordHash(string $password, UserEntity $user, Context $context): void
    {
        $this->customerRepository->update([
            [
                'id' => $user->getId(),
                'password' => $password,
                'legacyPassword' => null,
                'legacyEncoder' => null,
            ],
        ], $context);
    }

    /**
     * @throws ConstraintViolationException
     */
    private function tryValidateEqualtoConstraint(array $data, string $field, DataValidationDefinition $validation): void
    {
        /** @var array $validations */
        $validations = $validation->getProperties();

        if (!array_key_exists($field, $validations)) {
            return;
        }

        /** @var array $fieldValidations */
        $fieldValidations = $validations[$field];

        /** @var EqualTo|null $equalityValidation */
        $equalityValidation = null;

        /** @var ConstraintInterface $emailValidation */
        foreach ($fieldValidations as $emailValidation) {
            if ($emailValidation instanceof EqualTo) {
                $equalityValidation = $emailValidation;
                break;
            }
        }

        if (!$equalityValidation instanceof EqualTo) {
            return;
        }

        $compareValue = $data[$equalityValidation->propertyPath] ?? null;
        if ($data[$field] === $compareValue) {
            return;
        }

        $message = str_replace('{{ compared_value }}', $compareValue, $equalityValidation->message);

        $violations = new ConstraintViolationList();
        $violations->add(new ConstraintViolation($message, $equalityValidation->message, [], '', $field, $data[$field]));

        throw new ConstraintViolationException($violations, $data);
    }

}
