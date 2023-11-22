<?php declare(strict_types=1);

namespace WebkulPOS\Core\Content\User\Password;

use WebkulPOS\Core\Content\User\UserEntity;
use WebkulPOS\Core\Content\User\Exception\LegacyPasswordEncoderNotFoundException;
use WebkulPOS\Core\Content\User\Password\LegacyEncoder\LegacyEncoderInterface;

class LegacyPasswordVerifier
{
    /**
     * @var LegacyEncoderInterface[]
     */
    private $encoder;

    public function __construct(iterable $encoder)
    {
        $this->encoder = $encoder;
    }

    public function verify(string $password, UserEntity $user): bool
    {
        foreach ($this->encoder as $encoder) {
            if ($encoder->getName() !== $user->getLegacyEncoder()) {
                continue;
            }

            return $encoder->isPasswordValid($password, $user->getLegacyPassword());
        }

        throw new LegacyPasswordEncoderNotFoundException($user->getLegacyEncoder());
    }
}
