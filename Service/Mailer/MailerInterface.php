<?php

namespace Sh4bang\UserBundle\Service\Mailer;


use Sh4bang\UserBundle\Entity\Sh4bangUserInterface;

interface MailerInterface
{
    /**
     * Send an email to a user to confirm his account
     *
     * @param Sh4bangUserInterface $user
     * @param string               $hash
     * @param string               $password
     */
    public function sendConfirmationEmail(Sh4bangUserInterface $user, string $hash, string $password);

    /**
     * Send an email to a user to let him know that his password has changed
     *
     * @param Sh4bangUserInterface $user
     * @param string|null          $password
     * @return mixed
     */
    public function sendPasswordChangedEmail(Sh4bangUserInterface $user, string $password = null);

    /**
     * Send an email to a user to allow him to reopen his closed account
     *
     * @param Sh4bangUserInterface $user
     * @param string               $hash
     * @return mixed
     */
    public function sendReopenAccountEmail(Sh4bangUserInterface $user, string $hash);

    /**
     * Send an email to a user to confirm the password resetting
     *
     * @param Sh4bangUserInterface $user
     * @param string               $hash
     */
    public function sendResettingEmail(Sh4bangUserInterface $user, string $hash);
}
