<?php declare(strict_types=1);

namespace WebkulPOS\Core\Content\User\Password\LegacyEncoder;

interface LegacyEncoderInterface
{
    public function getName(): string;

    public function isPasswordValid(string $password, string $hash): bool;
}
