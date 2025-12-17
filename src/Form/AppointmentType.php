<?php

namespace App\Form;

use App\Entity\Appointment;
use App\Entity\Doctor;
use App\Entity\Patient;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AppointmentType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('date')
            ->add('reason')
            ->add('status')
            ->add('Doctor', EntityType::class, [
                'class' => Doctor::class,
                'choice_label' => 'id',
            ])
            ->add('Patient', EntityType::class, [
                'class' => Patient::class,
                'choice_label' => 'id',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Appointment::class,
            // Add CSRF configuration
            'csrf_protection' => true,
            'csrf_field_name' => '_token',
            'csrf_token_id'   => 'appointment_form', // Unique token ID for this form
        ]);
    }
}