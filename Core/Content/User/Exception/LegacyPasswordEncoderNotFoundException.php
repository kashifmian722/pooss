<?php declare(strict_types=1);

namespace WebkulPOS\Core\Content\User\Exception;

use Shopware\Core\Framework\ShopwareHttpException;
use Symfony\Component\HttpFoundation\Response;

class LegacyPasswordEncoderNotFoundException extends ShopwareHttpException
{
    public function __construct(string $encoder)
    {
        parent::__construct(
            'Encoder with name "{{ encoder }}" not found.',
            ['encoder' => $encoder]
        );
    }

    public function getErrorCode(): string
    {
        return 'POS__LEGACY_PASSWORD_ENCODER_NOT_FOUND';
    }

    public function getStatusCode(): int
    {
        return Response::HTTP_BAD_REQUEST;
    }
}
