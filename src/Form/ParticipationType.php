<?php

namespace App\Form;

use App\Entity\EvenementRh;
use App\Entity\Participation;
use App\Entity\User;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ParticipationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('statut', ChoiceType::class, [
                'choices'  => [
                    'Inscrit' => 'Inscrit',
                    'Confirmé' => 'Confirme',
                    'Annulé' => 'Annule',
                ],
                'attr' => ['class' => 'mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm']
            ])
            ->add('evenement', EntityType::class, [
                'class' => EvenementRh::class,
                'choice_label' => 'titre',
                'attr' => ['class' => 'mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm']
            ])
            ->add('user', EntityType::class, [
                'class' => User::class,
                'choice_label' => function(User $user) {
                    return $user->getPrenom() . ' ' . $user->getNom();
                },
                'attr' => ['class' => 'mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm']
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Participation::class,
        ]);
    }
}
