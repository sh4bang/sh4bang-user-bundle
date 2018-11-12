<?php
namespace Sh4bang\UserBundle\Form;

use Sh4bang\UserBundle\Entity\User;
use Sh4bang\UserBundle\Form\Type\RoleType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;

class UserType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add(
                'email',
                EmailType::class,
                [
                    'label' => 'form.user.email',
                    'translation_domain' => 'sh4bang_user'
                ]
            )
            ->add(
                'plainPassword',
                RepeatedType::class,
                [
                    'type' => PasswordType::class,
                    'first_options'  => [
                        'label' => 'form.user.password',
                        'translation_domain' => 'sh4bang_user'
                    ],
                    'second_options' => [
                        'label' => 'form.user.repeat_password',
                        'translation_domain' => 'sh4bang_user'
                    ],
                ]
            )
            ->add(
                'roles',
                RoleType::class
            )
            ->add(
                'submit',
                SubmitType::class,
                [
                    'label' => 'form.user.submit',
                    'translation_domain' => 'sh4bang_user'
                ]
            )
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }
}
