<?php

namespace App\Form;

use App\Entity\Presence;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PresenceType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('estPresent', CheckboxType::class, [
                'label' => 'Présent',
                'required' => false,
                'attr' => ['class' => 'h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500']
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Presence::class,
        ]);
    }
}
