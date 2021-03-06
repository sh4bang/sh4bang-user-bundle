<?php
namespace Sh4bang\UserBundle\Form;

use Sh4bang\UserBundle\Entity\User;
use Sh4bang\UserBundle\Form\Type\CheckboxLinkType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Validator\Constraints\IsTrue;

class RegisterType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->remove('roles')
            ->add(
                'termsAccepted',
                CheckboxLinkType::class,
                [
                    'mapped' => false,
                    'constraints' => new IsTrue(),
                    'label' => 'form.register.accept_terms',
                    'translation_domain' => 'sh4bang_user',
                    'link_text' => $options['terms_text_link'],
                    'link_route' => $options['terms_route']
                ]
            )
            ->add(
                'submit',
                SubmitType::class,
                [
                    'label' => 'form.register.submit',
                    'translation_domain' => 'sh4bang_user'
                ]
            )
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => User::class,
            'validation_groups' => ['User', 'registration'],
            'terms_text_link' => null,
            'terms_route' => null,
        ]);
    }

    public function getParent()
    {
        return UserType::class;
    }
}
