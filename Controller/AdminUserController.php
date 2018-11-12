<?php

namespace Sh4bang\UserBundle\Controller;

use Sh4bang\UserBundle\Entity\Token;
use Sh4bang\UserBundle\Entity\User;
use Sh4bang\UserBundle\Form\CreateUserType;
use Sh4bang\UserBundle\Form\UpdateUserType;
use Sh4bang\UserBundle\Form\UserType;
use Sh4bang\UserBundle\Service\Mailer\TwigSwiftMailer;
use Sh4bang\UserBundle\Service\TokenManager;
use Sh4bang\UserBundle\Service\UserManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class AdminUserController extends AbstractController
{
    /**
     * @param Request         $request
     * @param UserManager     $userManager
     * @param TokenManager    $tokenManager
     * @param TwigSwiftMailer $swiftMailer
     * @param Session         $session
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     * @throws \Throwable
     * @throws \Twig_Error_Loader
     * @throws \Twig_Error_Runtime
     * @throws \Twig_Error_Syntax
     */
    public function create(
        Request $request,
        UserManager $userManager,
        TokenManager $tokenManager,
        TwigSwiftMailer $swiftMailer,
        Session $session
    ) {
        $user = new User();

        $form = $this->createForm(CreateUserType::class, $user);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $generatedPassword = null;
            if (empty($form->get('plainPassword')->getData())) {
                $generatedPassword = $userManager->generatePassword();
                $userManager->changePassword($user, $generatedPassword);
            } else {
                $userManager->changePassword($user, $form->get('plainPassword')->getData());
            }

            // If we want the user need to confirm his account via an email
            if ($generatedPassword || true === $form->get('sendEmail')->getData()) {
                $token = $tokenManager->generateToken(
                    Token::ACCOUNT_CONFIRMATION_TYPE,
                    $this->container->getParameter('sh4bang_user_config')['token_confirmation_ttl']
                );
                $user->addToken($token);

                $swiftMailer->sendConfirmationEmail($user, $token->getHash(), $generatedPassword);
            } else {
                $user->setStatus(User::VERIFIED_STATUS);
            }

            $userManager->save($user);

            $session->getFlashBag()->add(
                'success',
                'User ' . $user->getEmail() . ' created'
            );

            return $this->redirectToRoute('sh4bang_user_admin_read');
        }

        return $this->render(
            '@Sh4bangUser/admin/create.html.twig',
            [
                'form' => $form->createView(),
            ]
        );
    }

    public function read(Request $request, UserManager $userManager, int $page)
    {
//        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $users = $userManager->getUserList($page);

        return $this->render(
            '@Sh4bangUser/admin/read.html.twig',
            [
                'users' => $users,
                'page' => $page
            ]
        );
    }

    /**
     * User's profile
     *
     * @param Request         $request
     * @param int             $id User ID
     * @param UserManager     $userManager
     * @param TokenManager    $tokenManager
     * @param TwigSwiftMailer $swiftMailer
     * @param Session         $session
     * @return \Symfony\Component\HttpFoundation\Response
     * @throws \Throwable
     * @throws \Twig_Error_Loader
     * @throws \Twig_Error_Runtime
     * @throws \Twig_Error_Syntax
     */
    public function update(
        Request $request,
        int $id,
        UserManager $userManager,
        TokenManager $tokenManager,
        TwigSwiftMailer $swiftMailer,
        Session $session
    ) {
        $user = $userManager->getUser($id);
        if (null === $user) {
            throw new NotFoundHttpException('User not found');
        }

        $form = $this->createForm(UpdateUserType::class, $user);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $newPassword = null;
            if (!empty($form->get('plainPassword')->getData())) {
                $newPassword = $form->get('plainPassword')->getData();
                $userManager->changePassword($user, $newPassword);
            } elseif ($form->get('generatePassword')->getData()) {
                $newPassword = $userManager->generatePassword();
                $userManager->changePassword($user, $newPassword);
            }

            // If we want the user need to confirm his account via an email
            if ($newPassword || true === $form->get('sendEmail')->getData()) {
                $token = $tokenManager->generateToken(
                    Token::ACCOUNT_CONFIRMATION_TYPE,
                    $this->container->getParameter('sh4bang_user_config')['token_confirmation_ttl']
                );
                $user->addToken($token);

                $swiftMailer->sendConfirmationEmail($user, $token->getHash(), $newPassword);
            } else {
                $user->setStatus(User::VERIFIED_STATUS);
            }

            $userManager->save($user);

            $session->getFlashBag()->add(
                'success',
                'User ' . $user->getEmail() . ' updated'
            );

            return $this->redirectToRoute('sh4bang_user_admin_read');
        }

        return $this->render(
            '@Sh4bangUser/admin/update.html.twig',
            [
                'user' => $user,
                'form' => $form->createView(),
            ]
        );
    }

    public function delete(Request $request, UserManager $userManager, int $id)
    {
        $user = $userManager->getUser($id);

        if (null === $user) {
            throw new NotFoundHttpException('User not found');
        }

        $userManager->deleteUser($id);
        $userManager->save();

        return $this->render(
            '@Sh4bangUser/admin/delete.html.twig',
            [
                'user' => $user
            ]
        );
    }
}
