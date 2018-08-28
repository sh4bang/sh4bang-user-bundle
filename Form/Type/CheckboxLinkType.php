<?php
namespace Sh4bang\UserBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CheckboxLinkType extends AbstractType
{
    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        $view->vars = array_replace($view->vars, [
            'link_text' => $options['link_text'],
            'link_uri' => $options['link_uri'],
            'link_route' => $options['link_route'],
        ]);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'link_text' => null,
            'link_uri' => null,
            'link_route' => null
        ]);
    }

    public function getParent()
    {
        return CheckboxType::class;
    }
}
