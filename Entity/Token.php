<?php

namespace Sh4bang\UserBundle\Entity;

use DateTime;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

/**
 * @ORM\Table(name="sh4bang_token")
 * @ORM\Entity(repositoryClass="Sh4bang\UserBundle\Repository\TokenRepository")
 * @ORM\HasLifecycleCallbacks()
 * @UniqueEntity(fields="hash", message="Duplicated token detected")
 */
class Token
{
    const RENEW_PASSWORD_TYPE = 'RENEW_PASSWORD';
    const ACCOUNT_CONFIRMATION_TYPE = 'ACCOUNT_CONFIRMATION';
    const REOPEN_ACCOUNT_TYPE = 'REOPEN_ACCOUNT';

    /**
     * @var string token value
     * @ORM\Id
     * @ORM\Column(type="string", length=32)
     */
    private $hash;

    /**
     * @var DateTime Time until the token is still good
     * @ORM\Column(name="token_expired_at", type="datetime", nullable=true)
     */
    private $expiredAt;

    /**
     * @var string Type code
     * @ORM\Column(type="string")
     */
    private $type;

    /**
     * @var DateTime Time when resource is created
     * @ORM\Column(name="created_at", type="datetime", nullable=true)
     */
    private $createdAt;

    /**
     * Owner Side
     * @var User
     * @ORM\ManyToOne(targetEntity="Sh4bang\UserBundle\Entity\User", inversedBy="tokens")
     */
    private $user;

    /**
     * @return string
     */
    public function getHash(): string
    {
        return $this->hash;
    }

    /**
     * @param string $hash
     */
    public function setHash(string $hash): void
    {
        $this->hash = $hash;
    }

    /**
     * @return DateTime
     */
    public function getExpiredAt(): DateTime
    {
        return $this->expiredAt;
    }

    /**
     * @param DateTime|null $expiredAt
     */
    public function setExpiredAt(?DateTime $expiredAt): void
    {
        $this->expiredAt = $expiredAt;
    }

    /**
     * @return DateTime
     */
    public function getCreatedAt(): DateTime
    {
        return $this->createdAt;
    }

    /**
     * @param DateTime $createdAt
     */
    public function setCreatedAt(DateTime $createdAt): void
    {
        $this->createdAt = $createdAt;
    }

    /**
     * @return string
     */
    public function getType(): string
    {
        return strtoupper($this->type);
    }

    /**
     * @param string $type
     */
    public function setType(string $type): void
    {
        $this->type = strtoupper($type);
    }

    /**
     * @return null|User
     */
    public function getUser(): ?User
    {
        return $this->user;
    }

    /**
     * @param null|User $user
     * @return Token
     */
    public function setUser(?User $user): self
    {
        $this->user = $user;
        return $this;
    }

    /**
     * @ORM\PrePersist()
     */
    public function prePersist()
    {
        $this->setCreatedAt(new DateTime());
    }
}
