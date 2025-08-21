<?php

namespace App\Controller\Admin;

use App\Entity\CatalogItem;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\MoneyField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;

class CatalogItemCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return CatalogItem::class;
    }

    public function configureFields(string $pageName): iterable
    {
        yield TextField::new('name', 'Intitulé');
        yield TextField::new('sku', 'Code')->hideOnIndex();
        yield MoneyField::new('defaultUnitPriceCents', 'PU HT')->setCurrency('EUR')->setStoredAsCents(true);
        yield AssociationField::new('defaultTaxRate', 'TVA');
        yield TextField::new('unit', 'Unité')->setHelp('ex: pièce, heure, forfait');
        yield BooleanField::new('isActive', 'Actif');
    }
}
