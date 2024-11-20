<?php

namespace App\Form;

use App\Entity\Campus;
use App\Entity\Lieu;
use App\Entity\Sortie;
use App\Entity\Ville;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

class SortieType extends AbstractType
{
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nom')
            ->add('dateHeureDebut', DateTimeType::class, [
                'html5' => true,
                'widget' => 'single_text'
            ])
            ->add('duree')
            ->add('dateLimiteInscription', DateType::class, [
                'html5' => true,
                'widget' => 'single_text'
            ])
            ->add('nbInscriptionsMax')
            ->add('infosSortie')

            ->add('rue', TextType::class, [
                'mapped' => false,
            ])
            ->add('longitude', TextType::class, [
                'mapped' => false,
            ])
            ->add('latitude', TextType::class, [
                'mapped' => false,
            ])
            ->add('codePostal', TextType::class, [
                'mapped' => false,])
            ->add('ville', EntityType::class, [
                'class' => Ville::class,
                'choice_label' => 'nom',
                'placeholder' => 'Sélectionnez une ville',
                'mapped' => false,
                'attr' => [
                    'data-ville-id' => 'ville',
                ],
            ])
            ->add('lieu', EntityType::class, [
                'class' => Lieu::class,
                'choice_label' => 'nom',
                'placeholder' => 'Sélectionnez un lieu',
                'attr' => [
                    'data-lieu-id' => 'lieu',
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Sortie::class,
        ]);
    }
}

