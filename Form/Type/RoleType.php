<?php
namespace Sh4bang\UserBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class RoleType extends AbstractType
{
    /**
     * @var array Roles list
     */
    private $roles;

    /**
     * RoleType constructor.
     *
     * @param array $roleHierarchy
     */
    public function __construct(array $roleHierarchy)
    {
        $this->roles = $this->sanitizeRoleList($roleHierarchy);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'choices' => $this->roles,
            'expanded' => true,
            'multiple' => true,
            'label' => 'Roles'
        ]);
    }

    public function getParent()
    {
        return ChoiceType::class;
    }

    /**
     * Prepare a single unique list of roles
     *
     * @param array $roleHierarchy
     * @return array
     */
    private function sanitizeRoleList(array $roleHierarchy): array
    {
        // transform the role hierarchy in a single unique list
        $roles = [];
        foreach ($roleHierarchy as $key => $roleList) {
            $roles[$key] = $key;
            array_walk_recursive($roleList, function($role) use (&$roles) {
                if ($role !== 'ROLE_USER') {
                    $roles[$role] = $role;
                }
            });
        }

        return $roles;
    }
}
