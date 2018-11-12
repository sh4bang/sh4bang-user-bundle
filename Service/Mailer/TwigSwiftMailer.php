<?php

namespace Sh4bang\UserBundle\Service\Mailer;


use Sh4bang\UserBundle\Entity\Sh4bangUserInterface;
use Swift_Mailer;
use Swift_Message;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Twig_Environment;

class TwigSwiftMailer implements MailerInterface
{
    /**
     * @var Swift_Mailer
     */
    protected $mailer;

    /**
     * @var UrlGeneratorInterface
     */
    protected $router;

    /**
     * @var Twig_Environment
     */
    protected $twig;

    /**
     * @var array
     */
    protected $parameters;

    /**
     * TwigSwiftMailer constructor
     *
     * @param Swift_Mailer          $mailer
     * @param UrlGeneratorInterface $router
     * @param Twig_Environment      $twig
     * @param array                 $parameters
     */
    public function __construct(
        Swift_Mailer $mailer,
        UrlGeneratorInterface $router,
        Twig_Environment $twig,
        array $parameters
    ) {
        $this->mailer = $mailer;
        $this->router = $router;
        $this->twig = $twig;
        $this->parameters = $parameters;
    }

    /**
     * Send an email to a user to confirm his account
     *
     * @param Sh4bangUserInterface $user
     * @param string               $hash
     * @param string|null          $password
     * @throws \Throwable
     * @throws \Twig_Error_Loader
     * @throws \Twig_Error_Runtime
     * @throws \Twig_Error_Syntax
     */
    public function sendConfirmationEmail(Sh4bangUserInterface $user, string $hash, string $password = null)
    {
        $templateName = $this->parameters['email']['template']['confirmation'];
        $from = $this->parameters['email']['from_address'];
        $route = $this->parameters['route']['confirm_account'];

        $url = $this->router->generate(
            $route,
            ['token' => $hash],
            UrlGeneratorInterface::ABSOLUTE_URL
        );

        $content = [
            'confirmationUrl' => $url,
            'generatedPassword' => $password,
        ];

        $this->sendMessage($templateName, $content, $from, $user->getEmail());
    }

    /**
     * Send an email to a user to let him know that his password has changed
     *
     * @param Sh4bangUserInterface $user
     * @param string|null          $password
     * @throws \Throwable
     * @throws \Twig_Error_Loader
     * @throws \Twig_Error_Runtime
     * @throws \Twig_Error_Syntax
     */
    public function sendPasswordChangedEmail(Sh4bangUserInterface $user, string $password = null)
    {
        $templateName = $this->parameters['email']['template']['change_password'];
        $from = $this->parameters['email']['from_address'];

        $content = [
            'password' => $password,
        ];

        $this->sendMessage($templateName, $content, $from, $user->getEmail());
    }

    /**
     * Send an email to a user to allow him to reopen his closed account
     *
     * @param Sh4bangUserInterface $user
     * @param string               $hash
     * @throws \Throwable
     * @throws \Twig_Error_Loader
     * @throws \Twig_Error_Runtime
     * @throws \Twig_Error_Syntax
     */
    public function sendReopenAccountEmail(Sh4bangUserInterface $user, string $hash)
    {
        $templateName = $this->parameters['email']['template']['reopen_account'];
        $from = $this->parameters['email']['from_address'];
        $route = $this->parameters['route']['reopen_account'];

        $url = $this->router->generate(
            $route,
            ['token' => $hash],
            UrlGeneratorInterface::ABSOLUTE_URL
        );

        $content = [
            'reopenAccountUrl' => $url
        ];

        $this->sendMessage($templateName, $content, $from, $user->getEmail());
    }

    /**
     * Send an email to a user to confirm the password resetting
     *
     * @param Sh4bangUserInterface $user
     * @param string               $hash
     * @throws \Throwable
     * @throws \Twig_Error_Loader
     * @throws \Twig_Error_Runtime
     * @throws \Twig_Error_Syntax
     */
    public function sendResettingEmail(Sh4bangUserInterface $user, string $hash)
    {
        $templateName = $this->parameters['email']['template']['resetting'];
        $from = $this->parameters['email']['from_address'];
        $route = $this->parameters['route']['reset_password'];

        $url = $this->router->generate(
            $route,
            ['token' => $hash],
            UrlGeneratorInterface::ABSOLUTE_URL
        );

        $content = [
            'resettingUrl' => $url
        ];

        $this->sendMessage($templateName, $content, $from, $user->getEmail());
    }

    /**
     * Send an email
     *
     * @param $templateName
     * @param $content
     * @param $from
     * @param $to
     * @throws \Throwable
     * @throws \Twig_Error_Loader
     * @throws \Twig_Error_Runtime
     * @throws \Twig_Error_Syntax
     */
    protected function sendMessage($templateName, $content, $from, $to)
    {
        $template = $this->twig->load($templateName);

        $subject = $template->renderBlock('subject', $content);

        $textContent  = $template->renderBlock('header_text', $content);
        $textContent .= $template->renderBlock('body_text', $content);
        $textContent .= $template->renderBlock('footer_text', $content);

        $htmlContent = '';
        if ($template->hasBlock('header_html', $content)) {
            $htmlContent .= $template->renderBlock('header_html', $content);
        }
        if ($template->hasBlock('body_html', $content)) {
            $htmlContent .= $template->renderBlock('body_html', $content);
        }
        if ($template->hasBlock('footer_html', $content)) {
            $htmlContent .= $template->renderBlock('footer_html', $content);
        }

        $message = (new Swift_Message())
            ->setSubject($subject)
            ->setFrom($from)
            ->setTo($to);

        if (!empty($htmlContent)) {
            $message->setBody($htmlContent, 'text/html')
                ->addPart($textContent, 'text/plain');
        } else {
            $message->setBody($textContent);
        }

        $this->mailer->send($message);
    }
}
