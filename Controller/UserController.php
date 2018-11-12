<?php

namespace Sh4bang\UserBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;

class UserController extends AbstractController
{
    /**
     * User's profile
     *
     * @param Request   $request
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
