<?php //declare(strict_types=1);

namespace Sh4bang\UserBundle\Controller;

use Sh4bang\UserBundle\Entity\AbstractUser;
use Sh4bang\UserBundle\Entity\Token;
use Sh4bang\UserBundle\Entity\User;
use Sh4bang\UserBundle\Exception\UserStatusException;
use Sh4bang\UserBundle\Form\AskChangePasswordType;
use Sh4bang\UserBundle\Form\RegisterType;
use Sh4bang\UserBundle\Form\ResetPasswordType;
use Sh4bang\UserBundle\Service\Mailer\TwigSwiftMailer;
use Sh4bang\UserBundle\Service\TokenManager;
use Sh4bang\UserBundle\Service\UserManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Component\Translation\TranslatorInterface;

class SecurityController extends AbstractController
{
    /**
     * Display and handle login form
     *
     * @param AuthenticationUtils $authenticationUtils
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function login(AuthenticationUtils $authenticationUtils)
    {
        // get the login error if there is one
        $error = $authenticationUtils->getLastAuthenticationError();

        // last username entered by the user
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('@Sh4bangUser/security/login.html.twig', [
            'last_username' => $lastUsername,
            'error'         => $error,
        ]);
    }

    /**
     * Display and handle a registration form for users.
     * It can handle reopening account as well.
     *
     * @param Request             $request
     * @param TranslatorInterface $translator
     * @param TokenManager        $tokenManager
     * @param UserManager         $userManager
     * @param TwigSwiftMailer     $swiftMailer
     * @param Session             $session
     * @return \Symfony\Component\HttpFoundation\Response
     * @throws \Throwable
     * @throws \Twig_Error_Loader
     * @throws \Twig_Error_Runtime
     * @throws \Twig_Error_Syntax
     */
    public function register(
        Request $request,
        TranslatorInterface $translator,
        TokenManager $tokenManager,
        UserManager $userManager,
        TwigSwiftMailer $swiftMailer,
        Session $session
    ) {
        // Build the form
        $user = new User();
        $form = $this->createForm(
            RegisterType::class,
            $user,
            [
                'terms_route' => $this->container->getParameter('sh4bang_user_config')['route']['terms'],
                'terms_text_link' => 'form.register.terms_text_link'
            ]
        );

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            // Find if a user already exists with that email
            $existingUser = $userManager->getUserByEmail($form->get('email')->getData());
            if ($existingUser) {

                $flashBagMessage = '';

                switch ($existingUser->getStatus()) {
                    // Account already active
                    case User::VERIFIED_STATUS:
                        throw new UserStatusException(
                            $translator->trans('error.account_already_registered', [], 'sh4bang_user')
                        );
                        break;

                    // Account not yet active, maybe the user lost his confirmation token
                    case User::PENDING_STATUS:
                        $user = $existingUser;
                        if ($tokenManager->isTokenAlreadyGenerated($user, Token::ACCOUNT_CONFIRMATION_TYPE)) {
                            throw new AccessDeniedHttpException(
                                $translator->trans('error.token_already_generated.register', [], 'sh4bang_user')
                            );
                        }
                        $token = $tokenManager->generateToken(
                            Token::ACCOUNT_CONFIRMATION_TYPE,
                            $this->container->getParameter('sh4bang_user_config')['token_confirmation_ttl']
                        );
                        $swiftMailer->sendConfirmationEmail($user, $token->getHash());
                        $flashBagMessage = 'form.register.flashbag_validate';
                        break;

                    // Account banned, we do not forgive
                    case User::BANNED_STATUS:
                        throw new UserStatusException(
                            $translator->trans('error.account_status_banned', [], 'sh4bang_user')
                        );
                        break;

                    // Account closed, we can let user reopen it
                    case User::CLOSED_STATUS:
                        $user = $existingUser;
                        if ($tokenManager->isTokenAlreadyGenerated($user, Token::REOPEN_ACCOUNT_TYPE)) {
                            throw new AccessDeniedHttpException(
                                $translator->trans('error.token_already_generated.reopen', [], 'sh4bang_user')
                            );
                        }
                        $token = $tokenManager->generateToken(
                            Token::REOPEN_ACCOUNT_TYPE,
                            $this->container->getParameter('sh4bang_user_config')['token_reopen_ttl']
                        );
                        $swiftMailer->sendReopenAccountEmail($user, $token->getHash());
                        $flashBagMessage = 'form.register.flashbag_reopen';
                        break;
                }
            } else {
                // We need a new account
                $token = $tokenManager->generateToken(
                    Token::ACCOUNT_CONFIRMATION_TYPE,
                    $this->container->getParameter('sh4bang_user_config')['token_confirmation_ttl']
                );

                $swiftMailer->sendConfirmationEmail($user, $token->getHash());
                $flashBagMessage = 'form.register.flashbag_validate';
            }

            $userManager->changePassword($user, $form->get('plainPassword')->getData());
            $user->addToken($token);

            $userManager->save($user);

            $session->getFlashBag()->add(
                'success',
                $translator->trans($flashBagMessage, [], 'sh4bang_user')
            );

            return $this->redirectToRoute('sh4bang_user_security_login');
        }

        return $this->render('@Sh4bangUser/security/register.html.twig',[
            'form' => $form->createView(),
        ]);
    }

    /**
     * Confirm an account with a token
     *
     * @param Request             $request
     * @param TranslatorInterface $translator
     * @param TokenManager        $tokenManager
     * @param UserManager         $userManager
     * @param Session             $session
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function confirmAccount(
        Request $request,
        TranslatorInterface $translator,
        TokenManager $tokenManager,
        UserManager $userManager,
        Session $session
    ) {
        $hash = $request->query->get('token');
        if (is_null($hash)) {
            throw new NotFoundHttpException(
                $translator->trans('error.token_not_found', [], 'sh4bang_user')
            );
        }

        $token = $tokenManager->getTokenByHash($hash);
        if (null === $token || !$tokenManager->isValid($token, Token::ACCOUNT_CONFIRMATION_TYPE)) {
            throw new AccessDeniedHttpException(
                $translator->trans('error.token_invalid', [], 'sh4bang_user')
            );
        }

        $user = $token->getUser();
        if (null === $user) {
            throw new NotFoundHttpException(
                $translator->trans('error.token_invalid', [], 'sh4bang_user')
            );
        }

        $user->setStatus(User::VERIFIED_STATUS);

        $userManager->save($user);

        $tokenManager->cleanByType($user, Token::ACCOUNT_CONFIRMATION_TYPE);

        $session->getFlashBag()->add(
            'success',
            $translator->trans('form.confirm_account.flashbag_validate', [], 'sh4bang_user')
        );

        return $this->redirectToRoute('sh4bang_user_security_login');
    }

    /**
     * Reopen a closed account
     *
     * @param Request             $request
     * @param TranslatorInterface $translator
     * @param TokenManager        $tokenManager
     * @param UserManager         $userManager
     * @param Session             $session
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function reopenAccount(
        Request $request,
        TranslatorInterface $translator,
        TokenManager $tokenManager,
        UserManager $userManager,
        Session $session
    ) {
        $hash = $request->query->get('token');
        if (is_null($hash)) {
            throw new NotFoundHttpException(
                $translator->trans('error.token_not_found', [], 'sh4bang_user')
            );
        }

        $token = $tokenManager->getTokenByHash($hash);
        if (null === $token || !$tokenManager->isValid($token, Token::REOPEN_ACCOUNT_TYPE)) {
            throw new AccessDeniedHttpException(
                $translator->trans('error.token_invalid', [], 'sh4bang_user')
            );
        }

        $user = $token->getUser();
        if (null === $user) {
            throw new NotFoundHttpException(
                $translator->trans('error.token_invalid', [], 'sh4bang_user')
            );
        }

        $user->setStatus(User::VERIFIED_STATUS);

        $userManager->save($user);

        $tokenManager->cleanByType($user, Token::REOPEN_ACCOUNT_TYPE);

        $session->getFlashBag()->add(
            'success',
            $translator->trans('form.reopen_account.flashbag_validate', [], 'sh4bang_user')
        );

        return $this->redirectToRoute('sh4bang_user_security_login');
    }

    /**
     * Display a form so that a user can request a new password
     *
     * @param Request             $request
     * @param TranslatorInterface $translator
     * @param Session             $session
     * @param TokenManager        $tokenManager
     * @param UserManager         $userManager
     * @param TwigSwiftMailer     $swiftMailer
     * @return \Symfony\Component\HttpFoundation\Response
     * @throws \Throwable
     * @throws \Twig_Error_Loader
     * @throws \Twig_Error_Runtime
     * @throws \Twig_Error_Syntax
     */
    public function askChangePassword(
        Request $request,
        TranslatorInterface $translator,
        Session $session,
        TokenManager $tokenManager,
        UserManager $userManager,
        TwigSwiftMailer $swiftMailer
    ) {
        $form = $this->createForm(AskChangePasswordType::class);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {

            $user = $userManager->getUserByEmail($form->get('email')->getData());
            if (null === $user) {
                throw new NotFoundHttpException(
                    $translator->trans('error.account_not_found', [], 'sh4bang_user')
                );
            }

            // Check status of user if legitimate
            try {
                $userManager->checkForValidAccountStatus(
                    $user,
                    [AbstractUser::VERIFIED_STATUS]
                );
            } catch (UserStatusException $e) {
                throw new AccessDeniedHttpException(
                    $translator->trans($e->getMessage(), [], 'sh4bang_user')
                );
            }

            // Prevent abuse
            if ($tokenManager->isTokenAlreadyGenerated($user, Token::RENEW_PASSWORD_TYPE)) {
                throw new AccessDeniedHttpException(
                    $translator->trans('error.token_already_generated.generic', [], 'sh4bang_user')
                );
            }

            // Create a new token and update the User
            $token = $tokenManager->generateToken(
                Token::RENEW_PASSWORD_TYPE,
                $this->container->getParameter('sh4bang_user_config')['token_security_ttl']
            );
            $user->addToken($token);

            $userManager->save($user);

            $swiftMailer->sendResettingEmail($user, $token->getHash());

            $session->getFlashBag()->add(
                'success',
                $translator->trans('form.ask_pwd.flashbag_validate', [], 'sh4bang_user')
            );

            return $this->redirectToRoute('sh4bang_user_security_login');
        }

        return $this->render('@Sh4bangUser/security/ask_password.html.twig',[
            'form' => $form->createView(),
        ]);
    }

    /**
     *
     *
     * @param Request             $request
     * @param TranslatorInterface $translator
     * @param TokenManager        $tokenManager
     * @param UserManager         $userManager
     * @param Session             $session
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function resetPassword(
        Request $request,
        TranslatorInterface $translator,
        TokenManager $tokenManager,
        UserManager $userManager,
        Session $session
    ) {
        $hash = $request->query->get('token');
        if (is_null($hash)) {
            throw new NotFoundHttpException(
                $translator->trans('error.token_not_found', [], 'sh4bang_user')
            );
        }

        $token = $tokenManager->getTokenByHash($hash);
        if (null === $token || !$tokenManager->isValid($token, Token::RENEW_PASSWORD_TYPE)) {
            throw new AccessDeniedHttpException(
                $translator->trans('error.token_invalid', [], 'sh4bang_user')
            );
        }

        $user = $token->getUser();
        if (null === $user) {
            throw new NotFoundHttpException(
                $translator->trans('error.token_invalid', [], 'sh4bang_user')
            );
        }

        // Build the form
        $form = $this->createForm(ResetPasswordType::class, $user);

        // Handle the submit (will only happen on POST)
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            // Check status of user if legitimate
            try {
                $userManager->checkForValidAccountStatus(
                    $user,
                    [AbstractUser::VERIFIED_STATUS]
                );
            } catch (UserStatusException $e) {
                throw new AccessDeniedHttpException(
                    $translator->trans($e->getMessage(), [], 'sh4bang_user')
                );
            }

            $userManager->changePassword($user, $form->get('plainPassword')->getData());

            $userManager->save($user);

            $tokenManager->cleanByType($user, Token::RENEW_PASSWORD_TYPE);

            $session->getFlashBag()->add(
                'success',
                $translator->trans('form.reset_pwd.flashbag_validate', [], 'sh4bang_user')
            );

            return $this->redirectToRoute('sh4bang_user_security_login');
        }

        return $this->render('@Sh4bangUser/security/reset_password.html.twig',[
            'form' => $form->createView(),
        ]);
    }
}
