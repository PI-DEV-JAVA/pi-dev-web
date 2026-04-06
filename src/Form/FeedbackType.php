<?php

namespace App\Form;

use App\Entity\Feedback;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class FeedbackType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('note', ChoiceType::class, [
                'label' => 'Note (1 à 5 étoiles)',
                'choices' => [
                    '5 - Excellent' => 5,
                    '4 - Très bien' => 4,
                    '3 - Moyen' => 3,
                    '2 - Passable' => 2,
                    '1 - Mauvais' => 1,
                ],
                'attr' => ['class' => 'mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm']
            ])
            ->add('commentaire', TextareaType::class, [
                'required' => false,
                'attr' => ['class' => 'mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm', 'rows' => 4]
            ])
            ->add('recommanderait', CheckboxType::class, [
                'required' => false,
                'label'    => 'Je recommanderais cet événement',
                'attr' => ['class' => 'h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500']
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Feedback::class,
        ]);
    }
}
