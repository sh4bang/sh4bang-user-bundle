<?php
namespace Sh4bang\UserBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\EmailType;

class AskChangePasswordType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add(
                'email',
                EmailType::class,
                [
                    'mapped' => false,
                    'label' => 'form.ask_pwd.email',
                    'translation_domain' => 'sh4bang_user'
                ]
            )
            ->add(
                'submit',
                SubmitType::class,
                [
                    'label' => 'form.ask_pwd.submit',
                    'translation_domain' => 'sh4bang_user'
                ]
            )
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
//            'data_class' => User::class,
        ]);
    }
}
