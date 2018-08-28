<?php
namespace Sh4bang\UserBundle\Form;

use Sh4bang\UserBundle\Entity\User;
use Sh4bang\UserBundle\Form\Type\CheckboxLinkType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Translation\TranslatorInterface;
use Symfony\Component\Validator\Constraints\IsTrue;

/**
 * @property TranslatorInterface translator
 */
class RegisterType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add(
                'username',
                TextType::class,
                [
                    'label' => 'form.register.username',
                    'translation_domain' => 'sh4bang_user'
                ]
            )
            ->add(
                'email',
                EmailType::class,
                [
                    'label' => 'form.register.email',
                    'translation_domain' => 'sh4bang_user'
                ]
            )
            ->add(
                'plainPassword',
                RepeatedType::class,
                [
                    'type' => PasswordType::class,
                    'first_options'  => [
                        'label' => 'form.register.password',
                        'translation_domain' => 'sh4bang_user'
                    ],
                    'second_options' => [
                        'label' => 'form.register.repeat_password',
                        'translation_domain' => 'sh4bang_user'
                    ],
                ]
            )
            ->add(
                'termsAccepted',
                CheckboxLinkType::class,
                [
                    'mapped' => false,
                    'constraints' => new IsTrue(),
                    'label' => 'form.register.accept_terms',
                    'translation_domain' => 'sh4bang_user',
                    'link_text' => 'form.register.terms_text_link',
                    'link_route' => 'terms'
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
