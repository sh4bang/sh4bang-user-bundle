<?php

namespace Sh4bang\UserBundle\Entity;

use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
//use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;
//use Symfony\Component\Validator\GroupSequenceProviderInterface;

/**
 * @ORM\MappedSuperclass
 * @ORM\HasLifecycleCallbacks()
 * //@--UniqueEntity(fields="email", message="Email already taken")
 * //@--Assert\GroupSequenceProvider
 */
abstract class AbstractUser implements Sh4bangUserInterface, \Serializable
{
    /**
     * Status list for an account
     */
    const PENDING_STATUS = 'PENDING';
    const VERIFIED_STATUS = 'VERIFIED';
    const CLOSED_STATUS = 'CLOSED';
    const BANNED_STATUS = 'BANNED';

    /**
     * const DEFAULT_ROLE Define the default role for a user
     */
    const DEFAULT_ROLE = 'ROLE_USER';

    /**
     * @ORM\Id()
     * @ORM\GeneratedValue(strategy="AUTO")
     * @ORM\Column(type="integer")
     */
    protected $id;

    /**
     * @var string
     * @Assert\Length(min=8, groups={"registration"})
     * @Assert\Length(max=4096)
     * @Assert\NotBlank(groups={"registration"})
     */
    protected $plainPassword;

    /**
     * @var string
     * @ORM\Column(type="string", length=64)
     */
    protected $password;

    /**
     * @var string
     * @ORM\Column(type="string", length=254, unique=true)
     * @Assert\NotBlank()
     * @Assert\Email()
     */
    protected $email;

    /**
     * @ORM\Column(type="array", nullable=true)
     */
    protected $roles = [];

    /**
     * @var DateTime Time when resource is created
     * @ORM\Column(name="created_at", type="datetime", nullable=true)
     */
    protected $createdAt;

    /**
     * @var DateTime Time when resource was updated
     * @ORM\Column(name="updated_at", type="datetime", nullable=true)
     */
    protected $updatedAt;

    /**
     * @var DateTime Time when user was logged for the last time
     * @ORM\Column(name="last_logged_at", type="datetime", nullable=true)
     */
    protected $lastLoggedAt;

    /**
     * @var DateTime Time until the user can't log in
     * @ORM\Column(name="locked_until", type="datetime", nullable=true)
     */
    protected $lockedUntil;

    /**
     * @var integer How many times in a row the user failed to log in
     * @ORM\Column(name="failed_login_streak", type="integer", nullable=true, options={"default":0})
     */
    protected $failedLoginStreak;

    /**
     * Inversed Side
     * @var Collection|Token[]
     * @ORM\OneToMany(targetEntity="Sh4bang\UserBundle\Entity\Token", mappedBy="user", cascade={"persist", "remove"})
     */
    protected $tokens;

    /**
     * @var string Describe the current account status
     * @ORM\Column(type="string", length=50)
     */
    protected $status;

    /**
     * User constructor.
     */
    public function __construct()
    {
        $this->setStatus(self::PENDING_STATUS);
        $this->tokens = new ArrayCollection();
        $this->setFailedLoginStreak(0);
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * Returns the username used to authenticate the user.
     *
     * @return string The username
     */
    public function getUsername()
    {
        return $this->email;
    }

    /**
     * @return mixed
     */
    public function getPlainPassword()
    {
        return $this->plainPassword;
    }

    /**
     * @param mixed $plainPassword
     */
    public function setPlainPassword($plainPassword): void
    {
        $this->plainPassword = $plainPassword;
    }

    /**
     * Returns the password used to authenticate the user.
     *
     * This should be the encoded password. On authentication, a plain-text
     * password will be salted, encoded, and then compared to this value.
     *
     * @return string The password
     */
    public function getPassword()
    {
        return $this->password;
    }

    /**
     * @param mixed $password
     */
    public function setPassword($password): void
    {
        $this->password = $password;
    }

    /**
     * @return mixed
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * @param mixed $email
     */
    public function setEmail($email): void
    {
        $this->email = $email;
    }

    /**
     * Returns the roles granted to the user.
     *
     * Alternatively, the roles might be stored on a ``roles`` property,
     * and populated in any number of different ways when the user object
     * is created.
     *
     * @return (Role|string)[] The user roles
     */
    public function getRoles()
    {
        $roles = $this->roles;
        if (empty($roles)) {
            // We need to make sure to have at least one role
            return [self::DEFAULT_ROLE];
        }
        return array_unique($roles);
    }

    /**
     * Set one or more roles to a user
     *
     * @param array $roles
     * @return User
     */
    public function setRoles(array $roles): User
    {
        $this->roles = array();

        foreach ($roles as $role) {
            $this->addRole($role);
        }
        return $this;
    }

    /**
     * Add a single role to a user
     *
     * @param string $role
     * @return $this
     */
    public function addRole(string $role)
    {
        $role = strtoupper($role);

        if (!$this->hasRole($role)) {
            $this->roles[] = $role;
        }
        return $this;
    }

    /**
     * Find out if user already has this role
     *
     * @param string $role
     * @return bool
     */
    public function hasRole(string $role)
    {
        return in_array(strtoupper($role), $this->getRoles(), true);
    }

    /**
     * Remove a specific role from a user
     *
     * @param string $role
     * @return $this
     */
    public function removeRole(string $role)
    {
        if (false !== $key = array_search(strtoupper($role), $this->roles, true)) {
            unset($this->roles[$key]);
            $this->roles = array_values($this->roles);
        }
        return $this;
    }

    /**
     * @return DateTime
     */
    public function getCreatedAt(): ?DateTime
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
     * @return DateTime
     */
    public function getUpdatedAt(): ?DateTime
    {
        return $this->updatedAt;
    }

    /**
     * @param DateTime $updatedAt
     */
    public function setUpdatedAt(DateTime $updatedAt): void
    {
        $this->updatedAt = $updatedAt;
    }

    /**
     * @return DateTime
     */
    public function getLastLoggedAt(): ?DateTime
    {
        return $this->lastLoggedAt;
    }

    /**
     * @param DateTime $lastLoggedAt
     */
    public function setLastLoggedAt(DateTime $lastLoggedAt): void
    {
        $this->lastLoggedAt = $lastLoggedAt;
    }

    /**
     * @return DateTime
     */
    public function getLockedUntil(): ?DateTime
    {
        return $this->lockedUntil;
    }

    /**
     * @param DateTime|null $lockedUntil
     */
    public function setLockedUntil(?DateTime $lockedUntil): void
    {
        $this->lockedUntil = $lockedUntil;
    }

    /**
     * @return int
     */
    public function getFailedLoginStreak(): int
    {
        return $this->failedLoginStreak;
    }

    /**
     * @param int $failedLoginStreak
     */
    public function setFailedLoginStreak(int $failedLoginStreak): void
    {
        $this->failedLoginStreak = $failedLoginStreak;
    }

    /**
     * @return Collection|Token[]
     */
    public function getTokens(): Collection
    {
        return $this->tokens;
    }

    /**
     * @param Token $token
     * @return User
     */
    public function addToken(Token $token): self
    {
        if (!$this->tokens->contains($token)) {
            $this->tokens->add($token);
            $token->setUser($this);
        }
        return $this;
    }

    /**
     * @param Token $token
     * @return User
     */
    public function removeToken(Token $token): self
    {
        if ($this->tokens->contains($token)) {
            $this->tokens->removeElement($token);
            // set the owning side to null (unless already changed)
            if ($token->getUser() === $this) {
                $token->setUser(null);
            }
        }
        return $this;
    }

    /**
     * @return string
     */
    public function getStatus(): string
    {
        return $this->status;
    }

    /**
     * @param string $status
     */
    public function setStatus(string $status): void
    {
        $this->status = $status;
    }

    /**
     * Returns the salt that was originally used to encode the password.
     *
     * This can return null if the password was not encoded using a salt.
     *
     * @return string|null The salt
     */
    public function getSalt()
    {
        // you *may* need a real salt depending on your encoder
        // see section on salt below
        return null;
    }

    /**
     * Removes sensitive data from the user.
     *
     * This is important if, at any given point, sensitive information like
     * the plain-text password is stored on this object.
     */
    public function eraseCredentials()
    {
        // not needed
    }

    /**
     * @ORM\PrePersist()
     */
    public function prePersist()
    {
        $this->setCreatedAt(new DateTime());
    }

//    public function getGroupSequence()
//    {
//        if (empty($this->getPlainPassword())) {
//            return ['User'];
//        } else {
//            return ['User', 'registration'];
//        }
//    }

    /**
     * String representation of object
     *
     * @link  http://php.net/manual/en/serializable.serialize.php
     * @return string the string representation of the object or null
     * @since 5.1.0
     */
    public function serialize()
    {
        return serialize([
            $this->id,
            $this->email,
            $this->password,
            // see section on salt below
            // $this->salt,
        ]);
    }

    /**
     * Constructs the object
     *
     * @link  http://php.net/manual/en/serializable.unserialize.php
     * @param string $serialized <p>
     *                           The string representation of the object.
     *                           </p>
     * @return void
     * @since 5.1.0
     */
    public function unserialize($serialized)
    {
        list (
            $this->id,
            $this->email,
            $this->password,
            // see section on salt below
            // $this->salt
            ) = unserialize($serialized, ['allowed_classes' => false]);
    }
}
