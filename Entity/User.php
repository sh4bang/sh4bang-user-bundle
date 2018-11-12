<?php

namespace Sh4bang\UserBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(name="sh4bang_users")
 * @ORM\Entity(repositoryClass="Sh4bang\UserBundle\Repository\UserRepository")
 */
class User extends AbstractUser
{

}
