<?php

namespace App\Controller\Admin;

use App\Entity\TaxRate;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\NumberField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;

final class TaxRateCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return TaxRate::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Taux de taxe')
            ->setEntityLabelInPlural('Taux de taxes')
            ->setSearchFields(['title', 'country'])
            ->setDefaultSort(['active' => 'DESC', 'title' => 'ASC']);
    }

    public function configureFields(string $pageName): iterable
    {
        $id = IdField::new('id')->onlyOnIndex();

        $title = TextField::new('title', 'Libellé')
            ->setHelp('Ex. « TVA 20 % »');

        // On saisit 19,60 pour 19,60 %
        $percent = NumberField::new('percent', 'Pourcentage')
            ->setNumDecimals(2)
            ->setFormTypeOption('scale', 2)
            ->setFormTypeOption('html5', true)
            ->setFormTypeOption('attr', [
                'step' => '0.01',
                'min'  => '0',
                'data_suffix' => '%', // 👈 servira à afficher « % » dans le champ (form theme ci-dessous)
            ])
            ->setHelp('Saisir 20 pour 20 %, 5.5 pour 5,5 %.');

        // Affichage « 19,60 % » en index/detail
        $percentFormatted = (clone $percent)->formatValue(function ($value, ?TaxRate $r) {
            $p = $r?->getPercent();
            return $p !== null ? number_format($p, 2, ',', ' ') . ' %' : '—';
        });

        $country = TextField::new('country', 'Pays')
            ->setHelp('Code ISO 3166-1 alpha-2 (ex. FR, BE, CH).')
            ->setMaxLength(2);

        $active = BooleanField::new('active', 'Actif');

        return [
            $id,
            $title,
            // en formulaire : version « input » ; en listing/detail : version formatée avec « % »
            $percent->onlyOnForms(),
            $percentFormatted->hideOnForm(),
            $country,
            $active,
        ];
    }
}
