<?php
namespace Sh4bang\UserBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\FormBuilderInterface;

class CreateUserType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add(
                'generatePassword',
                CheckboxType::class,
                [
                    'label' => 'Generate a random password',
                    'mapped' => false,
                    'required' => false,
                    'attr' => [
                        'data-type' => 'generate-password',
                        'data-target' => 'plainPassword',
                    ]
                ]
            )
            ->add(
                'sendEmail',
                CheckboxType::class,
                [
                    'label' => 'Send an account confirmation email to the user',
                    'mapped' => false,
                    'required' => false,
                    'data' => true
                ]
            )
            ->get('plainPassword')->setRequired(false)
        ;
    }

    public function getParent()
    {
        return UserType::class;
    }
}
