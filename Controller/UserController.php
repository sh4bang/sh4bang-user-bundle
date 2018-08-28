<?php

namespace Sh4bang\UserBundle\Controller;

use Sh4bang\UserBundle\Entity\User;
use Sh4bang\UserBundle\Form\ForgotPasswordType;
use Sh4bang\UserBundle\Form\RegisterType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

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
     * Forgot your password form
     *
     * @Route("/forgot-password", name="sh4bang_user_forgot")
     * @param Request                      $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function forgotPassword(Request $request)
    {
        // 1) build the form
        $user = new User();
        $form = $this->createForm(ForgotPasswordType::class, $user);
        $form->add(
            'submit',
            SubmitType::class,
            [
                'label' => 'form.forgot.submit',
                'translation_domain' => 'sh4bang_user'
            ]
        );

        // 2) handle the submit (will only happen on POST)
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {

            // 3) Send email
//            $password = $passwordEncoder->encodePassword($user, $user->getPlainPassword());
//            $user->setPassword($password);

            // 4) save the User (if I decide to add a special token for the request) ?
//            $entityManager = $this->getDoctrine()->getManager();
//            $entityManager->persist($user);
//            $entityManager->flush();

            // ... do any other work - like sending them an email, etc
            // maybe set a "flash" success message for the user

            return $this->redirectToRoute('sh4bang_user_login');
        }

        return $this->render(
            '@Sh4bangUser/security/forgot_password.html.twig',
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
