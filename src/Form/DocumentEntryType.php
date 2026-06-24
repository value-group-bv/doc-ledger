<?php

namespace App\Form;

use App\Entity\DocMainCategory;
use App\Entity\DocSubCategory;
use App\Entity\DocSubsidiary;
use App\Entity\DocType;
use App\Entity\DocumentEntry;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Range;

class DocumentEntryType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $_options): void
    {
        $builder
            ->add('subsidiary', EntityType::class, [
                'class'        => DocSubsidiary::class,
                'choice_label' => fn(DocSubsidiary $s) => "{$s->getCode()} - {$s->getDescription()}",
                'placeholder'  => 'select',
                'constraints'  => [new NotBlank()],
            ])
            ->add('mainCategory', EntityType::class, [
                'class'        => DocMainCategory::class,
                'choice_label' => fn(DocMainCategory $mc) => "{$mc->getCode()} - {$mc->getDescription()}",
                'placeholder'  => 'select',
                'constraints'  => [new NotBlank()],
            ])
            ->add('docType', EntityType::class, [
                'class'        => DocType::class,
                'choice_label' => fn(DocType $dt) => "{$dt->getCode()} - {$dt->getDescription()}",
                'placeholder'  => 'select',
                'constraints'  => [new NotBlank()],
            ])
            ->add('subCategory', EntityType::class, [
                'class'        => DocSubCategory::class,
                'choice_label' => fn(DocSubCategory $sc) => "{$sc->getFormattedCode()} - {$sc->getDescription()}",
                'placeholder'  => 'select',
                'constraints'  => [new NotBlank()],
            ])
            ->add('docNumber', IntegerType::class, [
                'label'       => 'Document number (0-999)',
                'constraints' => [new Range(min: 0, max: 999)],
            ])
            ->add('title', TextType::class, [
                'constraints' => [new NotBlank(), new Length(max: 255)],
            ])
            ->add('comments', TextareaType::class, [
                'required' => false,
                'label'    => 'Comments / search tags',
                'attr'     => ['rows' => 3, 'placeholder' => 'Internal notes or keywords for improved searching'],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => DocumentEntry::class]);
    }
}
