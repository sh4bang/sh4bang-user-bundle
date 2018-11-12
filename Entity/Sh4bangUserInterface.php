<?php

namespace Sh4bang\UserBundle\Entity;

use DateTime;
use Symfony\Component\Security\Core\User\UserInterface;

interface Sh4bangUserInterface extends UserInterface
{
    /**
     * Will be used as the main identifier for the user
     *
     * @return mixed
     */
    public function getEmail();

    /**
     * Creation date of user's account
     *
     * @return DateTime|null
     */
    public function getCreatedAt(): ?DateTime;

    /**
     * Last update of user's account
     *
     * @return DateTime|null
     */
    public function getUpdatedAt(): ?DateTime;

    /**
     * Last time the user successfully logged in
     *
     * @return DateTime|null
     */
    public function getLastLoggedAt(): ?DateTime;

    /**
     * Date until user's account is locked
     *
     * @return DateTime|null
     */
    public function getLockedUntil(): ?DateTime;

    /**
     * How many time in a row the user failed to log in
     *
     * @return int
     */
    public function getFailedLoginStreak(): int;

    /**
     * Define a state of the user's account lifecycle
     *
     * @return string
     */
    public function getStatus(): string;
}
