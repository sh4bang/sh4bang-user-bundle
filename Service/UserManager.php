<?php

namespace Sh4bang\UserBundle\Service;

use DateInterval;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Sh4bang\UserBundle\Entity\AbstractUser;
use Sh4bang\UserBundle\Entity\Sh4bangUserInterface;
use Sh4bang\UserBundle\Entity\User;
use Sh4bang\UserBundle\Exception\UserStatusException;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

class UserManager
{
    /**
     * Number of user displayed in "paginator" interfaces
     */
    const NB_USER_PER_PAGE = 20;

    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var
     */
    private $passwordEncoder;

    /**
     * TokenManager constructor.
     *
     * @param EntityManagerInterface       $entityManager
     * @param UserPasswordEncoderInterface $passwordEncoder
     */
    public function __construct(
        EntityManagerInterface $entityManager,
        UserPasswordEncoderInterface $passwordEncoder
    ) {
        $this->entityManager = $entityManager;
        $this->passwordEncoder = $passwordEncoder;
    }

    /**
     * @param string      $email
     * @param string      $password
     * @param array|null  $roles
     * @return Sh4bangUserInterface
     */
    public function createUser(
        string $email,
        string $password,
        array $roles = null
    ): Sh4bangUserInterface {
        $user = new User();
        $user->setEmail($email);
        $this->changePassword($user, $password);
        $user->setRoles($roles);
        $user->setStatus(AbstractUser::PENDING_STATUS);

        return $user;
    }

    /**
     * Encode and set a new password for a user
     *
     * @param Sh4bangUserInterface   $user
     * @param string $newPassword
     */
    public function changePassword(Sh4bangUserInterface $user, string $newPassword): void
    {
        $password = $this->passwordEncoder->encodePassword($user, $newPassword);
        $user->setPassword($password);
    }

    /**
     * [Util] Generate a random password
     *
     * @param int $length
     * @return bool|string
     */
    public function generatePassword($length = 8): string
    {
        $chars = 'abcdefghkmnpqrstuvwxyzABCDEFGHKMNPQRSTUVWXYZ23456789';

        return substr(str_shuffle($chars), 0, $length);
    }

    /**
     * Get a User with a specific email
     *
     * @param $email
     * @return null|Sh4bangUserInterface
     */
    public function getUserByEmail($email): ?Sh4bangUserInterface
    {
        $userRepository = $this->entityManager->getRepository(User::class);
        $user = $userRepository->findOneBy(['email' => $email]);

        return $user;
    }

    /**
     * Record a failed login attempt for a user and apply time penalty if needed
     *
     * @param Sh4bangUserInterface $user
     * @param int           $penaltyTime
     */
    public function recordFailedConnexionAttempt(Sh4bangUserInterface $user, int $penaltyTime = 0)
    {
        $failedLoginStreak = $user->getFailedLoginStreak();
        $user->setFailedLoginStreak(++$failedLoginStreak);

        if ($penaltyTime > 0) {
            $bannedTime = (new DateTime())
                ->add(new DateInterval('PT' . $penaltyTime . "S"))
            ;
            $user->setSoftBannedUntil($bannedTime);
        }
    }

    /**
     * Clean all data about his failed attempt
     *
     * @param Sh4bangUserInterface $user
     */
    public function cleanFailedConnexionInfo(Sh4bangUserInterface $user): void
    {
        $user->setFailedLoginStreak(0);
        $user->setLockedUntil(null);
    }

    /**
     * Check the user's status if valid, or throw an UserStatusException
     *
     * @param Sh4bangUserInterface $user
     * @param array                $authorizedStatus
     * @return bool
     */
    public function checkForValidAccountStatus(Sh4bangUserInterface $user, array $authorizedStatus)
    {
        $status = $user->getStatus();

        if (in_array($status, $authorizedStatus)) {
            return true;
        }

        switch ($status) {
            case User::VERIFIED_STATUS:
                throw new UserStatusException('error.account_status_verified');
                break;
            case User::PENDING_STATUS:
                throw new UserStatusException('error.account_status_pending');
                break;
            case User::BANNED_STATUS:
                throw new UserStatusException('error.account_status_banned');
                break;
            case User::CLOSED_STATUS:
                throw new UserStatusException('error.account_status_closed');
                break;
        }

        // Return false is case of a non handled status
        return false;
    }

    /**
     * Find users for making lists
     *
     * @param $page
     * @return array|object[]|User[]
     */
    public function getUserList($page)
    {
        $userRepository = $this->entityManager->getRepository(User::class);
        $users = $userRepository->findBy(
            [],
            ['email' => 'ASC'],
            self::NB_USER_PER_PAGE,
            ($page - 1) * self::NB_USER_PER_PAGE
        );

        return $users;
    }

    /**
     * Find one user by his id
     *
     * @param $id
     * @return null|object|User
     */
    public function getUser($id)
    {
        $userRepository = $this->entityManager->getRepository(User::class);
        $user = $userRepository->find($id);

        return $user;
    }

    /**
     * Persist a User
     *
     * @param Sh4bangUserInterface $user
     */
    public function save(Sh4bangUserInterface $user): void
    {
        $this->entityManager->persist($user);
        $this->entityManager->flush();
    }
}
