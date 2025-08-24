<?php

namespace App\Controller\Admin;

use App\Entity\TaxRate;
use App\Entity\CatalogItem;
use App\Entity\InvoiceLine;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\MoneyField;
use EasyCorp\Bundle\EasyAdminBundle\Field\NumberField;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;

final class InvoiceLineCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return InvoiceLine::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Ligne de facture')
            ->setEntityLabelInPlural('Lignes de facture')
            ->setSearchFields(['designation'])
            ->setDefaultSort(['id' => 'ASC']);
    }

    public function configureFields(string $pageName): iterable
    {
        // Désignation (cible de pré-remplissage)
        $designation = TextField::new('designation', 'Description')
            ->setFormTypeOption('attr', ['data-prefill-target' => 'designation'])
            ->setColumns(11);

        // Quantité
        $qty = NumberField::new('quantity', 'Qté')
            ->setNumDecimals(3)
            ->setFormTypeOption('input', 'string') // DECIMAL stocké en string
            ->setFormTypeOption('scale', 2)
            ->setFormTypeOption('attr', [
                'step' => '0.01',
                'min'  => '0.01',
                'data-prefill-target' => 'quantity',
            ])
            ->setColumns(3);

        // Prix unitaire
        $unit = MoneyField::new('unitPriceCents', 'PU HT')
            ->setCurrency('EUR')->setStoredAsCents(true)
            ->setFormTypeOption('attr', ['data-prefill-target' => 'unitPrice'])
            ->setColumns(3);

        // Remise
        $discount = MoneyField::new('discountCents', 'Remise')
            ->setCurrency('EUR')
            ->setStoredAsCents(true)
            ->setFormTypeOption('data', 0)
            ->setColumns(3);


        $tax = AssociationField::new('taxRate', 'TVA')
            ->setRequired(false)
            ->setFormTypeOption('placeholder', 'Aucun(e)')
            // label lisible: "19,60 %"
            ->setFormTypeOption('choice_label', function (?TaxRate $t) {
                return $t ? number_format($t->getPercent(), 2, ',', ' ') . ' %' : '';
            })
            // ⚠️ le JS attend un ratio (0.196)
            ->setFormTypeOption('choice_attr', function ($choice) {
                if (!$choice instanceof TaxRate) return [];
                return ['data-percent' => (string)$choice->getRatio()];
            })
            ->setColumns(3);

        // Sélecteur de prestation pré-définie
        $item = AssociationField::new('item', 'Prédef.')
            ->setFormTypeOption('choice_label', 'name')
            ->setRequired(false)
            ->setFormTypeOption('choice_attr', function ($choice) {
                if (!$choice instanceof \App\Entity\CatalogItem) {
                    return [];
                }

                $payload = [
                    'name'  => $choice->getName(),
                    'price' => $choice->getDefaultUnitPriceCents(), // cents
                    'tax'   => $choice->getDefaultTaxRate()?->getId() ?? '',
                ];

                return [
                    // legacy fallbacks (when not using TomSelect)
                    'data-name'  => $payload['name'],
                    'data-price' => (string) $payload['price'],
                    'data-tax'   => (string) $payload['tax'],

                    // ✅ TomSelect-friendly: always available in ts.options[value]
                    'data-data'  => json_encode($payload, JSON_UNESCAPED_UNICODE),
                ];
            })
            ->setFormTypeOption('attr', ['data-controller' => 'prefill'])
            ->setColumns(6)
            ->setHelp('Choisir une prestation pour pré-remplir.');

        // 👉 YIELD UNIQUEMENT les champs configurés ci-dessus
        return [$designation, $qty, $unit, $discount, $tax, $item];
    }
}

