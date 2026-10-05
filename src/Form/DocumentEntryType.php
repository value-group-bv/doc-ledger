<?php

namespace App\Form;

use App\Entity\DocMainCategory;
use App\Entity\DocSubCategory;
use App\Entity\DocSubsidiary;
use App\Entity\DocType;
use App\Entity\DocumentEntry;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Range;

class DocumentEntryType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $_options): void
    {
        /** @var DocumentEntry $entry */
        $entry = $builder->getData();
        $savedSubsidiary = $entry->getSubsidiary();
        $savedSubCategory = $entry->getSubCategory();

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
            ->add('alternateMainCategories', EntityType::class, [
                'class'        => DocMainCategory::class,
                'choice_label' => fn(DocMainCategory $mc) => "{$mc->getCode()} - {$mc->getDescription()}",
                'query_builder' => fn(EntityRepository $r) => $r->createQueryBuilder('mc')->orderBy('mc.code', 'ASC'),
                'multiple'     => true,
                'expanded'     => true,
                'required'     => false,
                'by_reference' => false,
                'label'        => 'Also valid under',
            ])
            ->add('docType', EntityType::class, [
                'class'        => DocType::class,
                'choice_label' => fn(DocType $dt) => "{$dt->getCode()} - {$dt->getDescription()}",
                'placeholder'  => 'select',
                'constraints'  => [new NotBlank()],
            ])
            ->add('docNumber', IntegerType::class, [
                'label'       => 'Document number (0-999)',
                'constraints' => [new Range(min: 0, max: 999)],
            ])
            ->add('title', TextType::class, [
                'constraints' => [new NotBlank(), new Length(max: 48)],
                'attr'        => ['maxlength' => 48],
            ])
            ->add('comments', TextareaType::class, [
                'required' => false,
                'label'    => 'Comments / search tags',
                'attr'     => ['rows' => 3, 'placeholder' => 'Internal notes or keywords for improved searching'],
            ]);

        // Sub categories follow the chosen subsidiary, so rebuild the field from the submitted one
        $addSubCategory = function (FormInterface $form, ?int $subsidiaryId) use ($savedSubsidiary, $savedSubCategory): void {
            $form->add('subCategory', EntityType::class, [
                'class'        => DocSubCategory::class,
                'choice_label' => fn(DocSubCategory $sc) => "{$sc->getFormattedCode()} - {$sc->getDescription()}",
                // All main categories (for the alternates); the saved one stays selectable under its own subsidiary
                'query_builder' => function (EntityRepository $r) use ($subsidiaryId, $savedSubsidiary, $savedSubCategory) {
                    $qb = $r->createQueryBuilder('sc')
                        ->where('sc.subsidiary = :subsidiary OR sc.subsidiary IS NULL')
                        ->setParameter('subsidiary', $subsidiaryId)
                        ->orderBy('sc.code', 'ASC');
                    if ($subsidiaryId === $savedSubsidiary->getId()) {
                        $qb->orWhere('sc = :saved')->setParameter('saved', $savedSubCategory);
                    }
                    return $qb;
                },
                'placeholder'  => 'select',
                'constraints'  => [new NotBlank()],
            ]);
        };

        $builder->addEventListener(FormEvents::PRE_SET_DATA, fn(FormEvent $e) => $addSubCategory($e->getForm(), $savedSubsidiary->getId()));
        $builder->addEventListener(FormEvents::PRE_SUBMIT, fn(FormEvent $e) => $addSubCategory($e->getForm(), (int) ($e->getData()['subsidiary'] ?? 0) ?: null));
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => DocumentEntry::class]);
    }
}
