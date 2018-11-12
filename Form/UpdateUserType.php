<?php
namespace Sh4bang\UserBundle\Form;

use Symfony\Component\Form\AbstractType;

class UpdateUserType extends AbstractType
{
    public function getParent()
    {
        return CreateUserType::class;
    }
}
