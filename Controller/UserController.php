<?php

namespace Sh4bang\UserBundle\Controller;

use DateTime;
use Sh4bang\UserBundle\Entity\User;
use Sh4bang\UserBundle\Form\AskPasswordType;
use Sh4bang\UserBundle\Form\RegisterType;
use Sh4bang\UserBundle\Service\TokenGenerator;
use Swift_Mailer;
use Swift_Message;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Component\Translation\TranslatorInterface;

class UserController extends AbstractController
{
    /**
     * @Route("/login", name="sh4bang_user_login")
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
     * Register a user
     *
     * @Route("/register", name="sh4bang_user_register")
     * @param Request                      $request
     * @param UserPasswordEncoderInterface $passwordEncoder
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function register(Request $request, UserPasswordEncoderInterface $passwordEncoder)
    {
        // 1) build the form
        $user = new User();
        $form = $this->createForm(RegisterType::class, $user);
        $form->add(
            'submit',
            SubmitType::class,
            [
                'label' => 'form.register.submit',
                'translation_domain' => 'sh4bang_user'
            ]
        );

        // 2) handle the submit (will only happen on POST)
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {

            // 3) Encode the password (you could also do this via Doctrine listener)
            $password = $passwordEncoder->encodePassword($user, $user->getPlainPassword());
            $user->setPassword($password);

            // 4) save the User!
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($user);
            $entityManager->flush();

            // ... do any other work - like sending them an email, etc
            // maybe set a "flash" success message for the user

            return $this->redirectToRoute('sh4bang_user_login');
        }

        return $this->render(
            '@Sh4bangUser/security/register.html.twig',
            [
                'form' => $form->createView(),
                'terms_route' => 'terms'
                //TODO: mettre la route en config
            ]
        );
    }

    /**
     * Ask for a new password form
     *
     * @Route("/ask-password", name="sh4bang_user_ask_pwd")
     * @param Request             $request
     * @param TokenGenerator      $tokenGenerator
     * @param TranslatorInterface $translator
     * @param Swift_Mailer        $mailer
     * @param Session             $session
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function askPassword(
        Request $request,
        TokenGenerator $tokenGenerator,
        TranslatorInterface $translator,
        Swift_Mailer $mailer,
        Session $session
    ) {
        // 1) build the form
        $form = $this->createForm(AskPasswordType::class);
        $form->add(
            'submit',
            SubmitType::class,
            [
                'label' => 'form.ask_pwd.submit',
                'translation_domain' => 'sh4bang_user'
            ]
        );

        // 2) handle the submit (will only happen on POST)
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {

            // 3) Prepare resetting
            $userRepository = $this->getDoctrine()->getRepository('Sh4bangUser:User');
            $user = $userRepository->findOneBy(['email' => $form->get('email')->getData()]);
            if ($user === null) {
                throw new NotFoundHttpException($translator->trans('error.account_not_found', [], 'sh4bang_user'));
            }

            // 4) save the User (if I decide to add a special token for the request) ?
            $user->setToken($tokenGenerator->getToken());
            $user->setTokenExpiredAt((new DateTime())->add(new \DateInterval('PT24H')));

            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($user);
            $entityManager->flush();

            // 5) Send email
            $message = (new Swift_Message($translator->trans('email.reset_pwd.subject', [], 'sh4bang_user')))
                ->setFrom('loic.sambourg@tva.ca')
                ->setTo($user->getEmail())
                ->setBody(
                    $this->renderView(
                        '@Sh4bangUser/emails/reset_password.html.twig',
                        [
                            'name' => $user->getUsername()
                        ]
                    ),
                    'text/html'
                )
            ;
            $mailer->send($message);

            // 6) Send a flash success message for the user
            $session->getFlashBag()->add(
                'success',
                $translator->trans('form.ask_pwd.flashbag_validate', [], 'sh4bang_user')
            );

            return $this->redirectToRoute('sh4bang_user_login');
        }

        return $this->render(
            '@Sh4bangUser/security/ask_password.html.twig',
            [
                'form' => $form->createView(),
            ]
        );
    }

    /**
     * User's profile
     *
     * @Route("/profile", name="sh4bang_user_profile")
     * @param Request                      $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function profile(Request $request)
    {
        return $this->render(
            '@Sh4bangUser/profile/consult.html.twig',
            []
        );
    }
}
