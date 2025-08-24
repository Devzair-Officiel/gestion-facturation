<?php

namespace App\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\DataMapperInterface;
use Symfony\Component\Form\Extension\Core\Type\CountryType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Édite un tableau JSON d’adresse de facturation:
 * [
 *   "street" => "...",
 *   "zip"    => "...",
 *   "city"   => "...",
 *   "country"=> "FR"
 * ]
 *
 * - Champ composé (compound) avec DataMapper: mappe vers un array PHP.
 * - À utiliser sur la propriété "billingAddress" (json) de Customer.
 */
final class AddressJsonType extends AbstractType implements DataMapperInterface
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('street', TextType::class, [
                'label' => 'Rue / adresse',
                'required' => false,
            ])
            ->add('zip', TextType::class, [
                'label' => 'Code postal',
                'required' => false,
            ])
            ->add('city', TextType::class, [
                'label' => 'Ville',
                'required' => false,
            ])
            ->add('country', CountryType::class, [
                'label' => 'Pays',
                'required' => false,
                'placeholder' => '—',
                'preferred_choices' => ['FR', 'BE', 'CH', 'LU', 'DE', 'ES', 'IT', 'MA'],
            ])
        ;

        $builder->setDataMapper($this);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        // le champ reçoit/renvoie un array
        $resolver->setDefaults([
            'data_class' => null,
            'empty_data' => fn() => [
                'street'  => null,
                'zip'     => null,
                'city'    => null,
                'country' => null,
            ],
        ]);
    }

    /** @param array|null $viewData */
    public function mapDataToForms($viewData, \Traversable $forms): void
    {
        $data = \is_array($viewData) ? $viewData : [];
        $forms = iterator_to_array($forms);
        $forms['street']->setData($data['street'] ?? null);
        $forms['zip']->setData($data['zip'] ?? null);
        $forms['city']->setData($data['city'] ?? null);
        $forms['country']->setData($data['country'] ?? null);
    }

    /** @param array $forms */
    public function mapFormsToData(\Traversable $forms, &$viewData): void
    {
        $forms = iterator_to_array($forms);
        $addr = [
            'street'  => trim((string) $forms['street']->getData() ?? ''),
            'zip'     => trim((string) $forms['zip']->getData() ?? ''),
            'city'    => trim((string) $forms['city']->getData() ?? ''),
            'country' => $forms['country']->getData() ?: null,
        ];
        // Si tout est vide, renvoyer null pour éviter du JSON vide
        $allEmpty = ($addr['street'] === '' && $addr['zip'] === '' && $addr['city'] === '' && $addr['country'] === null);
        $viewData = $allEmpty ? null : $addr;
    }
}
