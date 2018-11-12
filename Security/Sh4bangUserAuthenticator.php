<?php

namespace Sh4bang\UserBundle\Security;

use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Sh4bang\UserBundle\Entity\AbstractUser;
use Sh4bang\UserBundle\Entity\Sh4bangUserInterface;
use Sh4bang\UserBundle\Exception\UserStatusException;
use Sh4bang\UserBundle\Repository\UserRepository;
use Sh4bang\UserBundle\Service\UserManager;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Core\Exception\InvalidCsrfTokenException;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Component\Security\Guard\AbstractGuardAuthenticator;
use Symfony\Component\Security\Http\Util\TargetPathTrait;
use Symfony\Component\Translation\TranslatorInterface;

class Sh4bangUserAuthenticator extends AbstractGuardAuthenticator
{
    use TargetPathTrait;

    /**
     * If user fails too much to log in, add a ban period for security reason
     */
    const FAILED_LOGIN_ATTEMPT_PENALTIES = [
        1 => 0,             // no penalty
        2 => 0,             // no penalty
        3 => 60*1,          // 1 minute
        4 => 60*2,          // 2 minutes
        5 => 60*5,          // 5 minutes
        6 => 60*15,         // 15 minutes
        7 => 60*30,         // 30 minutes
        8 => 60*60,         // 1 hour
        9 => 60*60*24       // 1 day
    ];

    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var RouterInterface
     */
    private $router;

    /**
     * @var CsrfTokenManagerInterface
     */
    private $csrfTokenManager;

    /**
     * @var UserPasswordEncoderInterface
     */
    private $passwordEncoder;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @var UserManager
     */
    private $userManager;

    /**
     * @var array
     */
    private $parameters;

    /**
     * Sh4bangUserAuthenticator constructor.
     *
     * @param EntityManagerInterface       $entityManager
     * @param RouterInterface              $router
     * @param CsrfTokenManagerInterface    $csrfTokenManager
     * @param UserPasswordEncoderInterface $passwordEncoder
     * @param TranslatorInterface          $translator
     * @param UserManager                  $userManager
     * @param array                        $parameters
     */
    public function __construct(
        EntityManagerInterface $entityManager,
        RouterInterface $router,
        CsrfTokenManagerInterface $csrfTokenManager,
        UserPasswordEncoderInterface $passwordEncoder,
        TranslatorInterface $translator,
        UserManager $userManager,
        array $parameters
    ) {
        $this->entityManager = $entityManager;
        $this->router = $router;
        $this->csrfTokenManager = $csrfTokenManager;
        $this->passwordEncoder = $passwordEncoder;
        $this->translator = $translator;
        $this->userManager = $userManager;
        $this->parameters = $parameters;
    }

    /**
     * This will be called on every request and your job is to decide
     * if the authenticator should be used for this request (return true)
     * or if it should be skipped (return false).
     *
     * @param Request $request
     * @return bool
     */
    public function supports(Request $request)
    {
        return $this->parameters['route']['login'] === $request->attributes->get('_route')
            && $request->isMethod('POST');
    }

    /**
     * This will be called on every request and your job is to read
     * the token (or whatever your "authentication" information is)
     * from the request and return it. These credentials are later
     * passed as the first argument of getUser().
     *
     * @param Request $request
     * @return array|mixed
     */
    public function getCredentials(Request $request)
    {
        $credentials = [
            'username' => $request->request->get('_username'),
            'password' => $request->request->get('_password'),
            'csrf_token' => $request->request->get('_csrf_token'),
        ];

        $request->getSession()->set(
            Security::LAST_USERNAME,
            $credentials['username']
        );

        return $credentials;
    }

    /**
     * The $credentials argument is the value returned by getCredentials().
     * Your job is to return an object that implements Sh4bangUserInterface.
     * If you do, then checkCredentials() will be called.
     * If you return null (or throw an AuthenticationException) authentication will fail.
     *
     * @param mixed                 $credentials
     * @param UserProviderInterface $userProvider
     * @return null|Sh4bangUserInterface
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function getUser($credentials, UserProviderInterface $userProvider): ?Sh4bangUserInterface
    {
        $token = new CsrfToken('authenticate', $credentials['csrf_token']);
        if (!$this->csrfTokenManager->isTokenValid($token)) {
            throw new InvalidCsrfTokenException();
        }

        $userClass = $this->parameters['user_class'];
        /**
         * @var UserRepository $userRepository
         */
        $userRepository = $this->entityManager->getRepository($userClass);
        $user = $userRepository->loadUserByUsername($credentials['username']);

        if (!$user) {
            // fail authentication with a custom error
            throw new CustomUserMessageAuthenticationException(
                $this->translator->trans('error.account_not_found', [], 'sh4bang_user')
            );
        }

        return $user;
    }

    /**
     * If getUser() returns a User object, this method is called.
     * Your job is to verify if the credentials are correct.
     * For a login form, this is where you would check that the
     * password is correct for the user. To pass authentication, return true.
     * If you return anything else (or throw an AuthenticationException), authentication will fail.
     *
     * @param mixed         $credentials
     * @param UserInterface $user
     * @return bool
     */
    public function checkCredentials($credentials, UserInterface $user)
    {
        /**
         * @var Sh4bangUserInterface $user
         */
        try {
            $this->userManager->checkForValidAccountStatus(
                $user,
                [AbstractUser::VERIFIED_STATUS]
            );
        } catch (UserStatusException $e) {
            throw new CustomUserMessageAuthenticationException(
                $this->translator->trans($e->getMessage(), [], 'sh4bang_user')
            );
        }

        if ((new DateTime()) <= $user->getLockedUntil()) {
            throw new CustomUserMessageAuthenticationException(
                $this->translator->trans('error.account_locked', [], 'sh4bang_user')
            );
        }

        $isPasswordValid = $this->passwordEncoder->isPasswordValid($user, $credentials['password']);
        if (!$isPasswordValid) {
            $failedLoginStreak = $user->getFailedLoginStreak() + 1;
            if (isset(self::FAILED_LOGIN_ATTEMPT_PENALTIES[$failedLoginStreak])) {
                $penaltyTime = self::FAILED_LOGIN_ATTEMPT_PENALTIES[$failedLoginStreak];
            } else {
                $penaltyTime = self::FAILED_LOGIN_ATTEMPT_PENALTIES[count(self::FAILED_LOGIN_ATTEMPT_PENALTIES)-1];
            }
            $this->userManager->recordFailedConnexionAttempt($user, $penaltyTime);
            $this->userManager->save($user);

            if ($penaltyTime > 0) {
                throw new CustomUserMessageAuthenticationException(
                    $this->translator->trans('error.bad_credential_account_locked', ['%time' => $penaltyTime], 'sh4bang_user')
                );
            } else {
                throw new CustomUserMessageAuthenticationException(
                    $this->translator->trans('error.bad_credential', [], 'sh4bang_user')
                );
            }
        } else {
            $this->userManager->cleanFailedConnexionInfo($user);
            $this->userManager->save($user);
        }

        return true;
    }

    /**
     * This is called after successful authentication and
     * your job is to either return a Response object
     * that will be sent to the client or null to continue
     * the request (e.g. allow the route/controller to be called like normal).
     *
     * @param Request        $request
     * @param TokenInterface $token
     * @param string         $providerKey
     * @return null|RedirectResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function onAuthenticationSuccess(Request $request, TokenInterface $token, $providerKey)
    {
        if ($targetPath = $this->getTargetPath($request->getSession(), $providerKey)) {
            return new RedirectResponse($targetPath);
        }

        return new RedirectResponse($this->router->generate('home'));
    }

    /**
     * This is called if authentication fails.
     * Your job is to return the Response object that should be sent to the client.
     * The $exception will tell you what went wrong during authentication.
     *
     * @param Request                 $request
     * @param AuthenticationException $exception
     * @return null|RedirectResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function onAuthenticationFailure(Request $request, AuthenticationException $exception)
    {
        $request->getSession()->set(Security::AUTHENTICATION_ERROR, $exception);

        return new RedirectResponse($this->router->generate($this->parameters['route']['login']));
    }

    /**
     * This is called if the client accesses a URI/resource that requires
     * authentication, but no authentication details were sent.
     * Your job is to return a Response object that helps
     * the user authenticate (e.g. a 401 response that says "token is missing!").
     *
     * @param Request                      $request
     * @param AuthenticationException|null $authException
     * @return RedirectResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function start(Request $request, AuthenticationException $authException = null)
    {
        return new RedirectResponse($this->router->generate($this->parameters['route']['login']));
    }

    /**
     * If you want to support "remember me" functionality, return true from this method.
     * You will still need to activate remember_me under your firewall for it to work.
     * If this is a stateless API, you do not want to support "remember me" functionality.
     *
     * @return bool
     */
    public function supportsRememberMe()
    {
        return false;
    }
}
