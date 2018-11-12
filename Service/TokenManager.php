<?php

namespace Sh4bang\UserBundle\Service;

use DateInterval;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Sh4bang\UserBundle\Entity\Sh4bangUserInterface;
use Sh4bang\UserBundle\Entity\Token;

class TokenManager
{
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var TokenGenerator
     */
    private $tokenGenerator;

    /**
     * TokenManager constructor.
     *
     * @param EntityManagerInterface $entityManager
     * @param TokenGenerator         $tokenGenerator
     */
    public function __construct(
        EntityManagerInterface $entityManager,
        TokenGenerator $tokenGenerator
    ) {
        $this->entityManager = $entityManager;
        $this->tokenGenerator = $tokenGenerator;
    }

    /**
     * Generate a new token with an expire date
     *
     * @param string $code
     * @param int    $ttl
     * @return Token
     */
    public function generateToken(string $code, int $ttl = 3600): Token
    {
        $token = new Token();
        $token->setHash($this->tokenGenerator->generateToken());
        $token->setType($code);
        $dateExpire = (new DateTime())
            ->add(new DateInterval('PT' . $ttl . 'S'))
        ;
        $token->setExpiredAt($dateExpire);

        return $token;
    }

    /**
     * Detect if a token has already been generated for a user
     *
     * @param Sh4bangUserInterface $user
     * @param string|null   $type
     * @return bool
     */
    public function isTokenAlreadyGenerated(Sh4bangUserInterface $user, string $type = null): bool
    {
        $oneHourAgo = (new DateTime())
            ->sub(new DateInterval('PT2M'))
        ;

        /** @var Token $token */
        foreach ($user->getTokens() as $token) {
            if ($type === null || $token->getType() === $type) {
                if ($token->getCreatedAt() > $oneHourAgo) {
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * Verify a token validity
     *
     * @param Token       $token
     * @param string|null $type
     * @return bool
     */
    public function isValid(Token $token, string $type = null): bool
    {
        if (null !== $type && $token->getType() !== $type) {
            return false;
        }

        $now = new DateTime();
        if ($now > $token->getExpiredAt()) {
            return false;
        }

        return true;
    }

    /**
     * Clean all token with a specific type for a user
     *
     * @param Sh4bangUserInterface $user
     * @param string        $type
     */
    public function cleanByType(Sh4bangUserInterface $user, string $type): void
    {
        $tokens = $user->getTokens();
        foreach ($tokens as $token) {
            if ($token->getType() === $type) {
                $this->entityManager->remove($token);
            }
        }

        $this->entityManager->flush();
    }

    /**
     * Delete all expired tokens
     *
     * @return integer
     */
    public function cleanAllExpiredTokens(): int
    {
        $tokenRepository = $this->entityManager->getRepository(Token::class);
        $nbDeletedRows = $tokenRepository->deleteExpiredTokens();

        return $nbDeletedRows;
    }

    /**
     * Find a token with a specific hash
     *
     * @param string $hash
     * @return null|Token
     */
    public function getTokenByHash(string $hash): ?Token
    {
        $tokenRepository = $this->entityManager->getRepository(Token::class);
        $token = $tokenRepository->findOneBy(['hash' => $hash]);

        return $token;
    }
}
