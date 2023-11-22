<?php declare(strict_types=1);

namespace WebkulPOS\Core\Content\User\Exception;

use Shopware\Core\Framework\ShopwareHttpException;
use Symfony\Component\HttpFoundation\Response;

class UserNotFoundException extends ShopwareHttpException
{
    public function __construct(string $email)
    {
        parent::__construct(
            'No matching customer for email "{{ email }}" was found.',
            ['email' => $email]
        );
    }

    public function getErrorCode(): string
    {
        return 'POS_USER_NOT_FOUND';
    }

    public function getStatusCode(): int
    {
        return Response::HTTP_NOT_FOUND;
    }
}
