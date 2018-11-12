<?php

namespace Sh4bang\UserBundle\EventListener;

use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;

class LoginListener
{
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * LoginListener constructor.
     *
     * @param EntityManagerInterface $entityManager
     */
    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * @param InteractiveLoginEvent $event
     */
    public function onSecurityInteractiveLogin(InteractiveLoginEvent $event)
    {
        /**
         * Get the User entity
         * @var \Sh4bang\UserBundle\Entity\User $user
         */
        $user = $event->getAuthenticationToken()->getUser();

        $user->setLastLoggedAt(new DateTime());
        $this->entityManager->persist($user);
        $this->entityManager->flush();
    }
}
