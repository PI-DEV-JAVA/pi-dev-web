<?php

namespace App\Form;

use App\Entity\Activity;
use App\Entity\Project;
use App\Entity\User;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ActivityType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('activityDate', DateType::class, [
                'widget' => 'single_text',
                'attr' => ['class' => 'form-control']
            ])
            ->add('description', TextareaType::class, [
                'attr' => ['class' => 'form-control', 'rows' => 3]
            ])
            ->add('hoursWorked', NumberType::class, [
                'html5' => true,
                'attr' => ['class' => 'form-control', 'step' => '0.1']
            ])
            ->add('Project', EntityType::class, [
                'class' => Project::class,
                'choice_label' => 'name',
                'attr' => ['class' => 'form-select']
            ])
            ->add('employee', EntityType::class, [
                'class' => User::class,
                'choice_label' => 'email',
                'attr' => ['class' => 'form-select']
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => Activity::class]);
    }
}