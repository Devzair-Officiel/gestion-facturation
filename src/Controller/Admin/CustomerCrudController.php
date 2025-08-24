<?php

namespace App\Controller\Admin;

use App\Entity\Customer;
use App\Entity\Company;
use App\Form\Type\AddressJsonType;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ArrayField;
use EasyCorp\Bundle\EasyAdminBundle\Field\EmailField;
use EasyCorp\Bundle\EasyAdminBundle\Field\FormField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

final class CustomerCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Customer::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Client')
            ->setEntityLabelInPlural('Clients')
            ->setSearchFields(['title', 'email', 'vatNumber'])
            ->setDefaultSort(['title' => 'ASC']);
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->add(Crud::PAGE_INDEX, Action::DETAIL);
    }

    public function configureFields(string $pageName): iterable
    {
        // ID (index + détail)
        $id = IdField::new('id')->onlyOnIndex();

        // Raison sociale / nom du client
        $title = TextField::new('title', 'Nom / Raison sociale')
            ->setHelp('Ex: « Garage Dupont » ou « Mme Martin »');

        // Email
        $email = EmailField::new('email', 'Email')
            ->setHelp('Optionnel — pour l’envoi des factures.');

        // Adresse de facturation (JSON) via un sous-formulaire
        $addrPanel = FormField::addPanel('Adresse de facturation');

        $billing = \EasyCorp\Bundle\EasyAdminBundle\Field\Field::new('billingAddress', 'Adresse')
            ->setFormType(AddressJsonType::class)
            ->onlyOnForms();

        // Affichage en détail (lecture seule) — on montre le JSON joliment
        $billingRead = ArrayField::new('billingAddress', 'Adresse de facturation')->onlyOnDetail();

        // Devise
        $currency = ChoiceField::new('currency', 'Devise')
            ->setChoices([
                'Euro (EUR)'        => 'EUR',
                'Dollar (USD)'      => 'USD',
                'Livre Sterling (GBP)' => 'GBP',
                'Franc Suisse (CHF)' => 'CHF',
                'Dirham (MAD)'      => 'MAD',
                'Dinar Tunisien (TND)' => 'TND',
            ])
            ->renderExpanded(false)
            ->allowMultipleChoices(false)
            ->setHelp('Devise par défaut pour ce client (si différente de la société).');

        // TVA intracom
        $vat = TextField::new('vatNumber', 'TVA intracom')
            ->setHelp('Ex: FR12345678901. Laisser vide pour un particulier.');

        // Société (multi-tenant)
        $company = AssociationField::new('company', 'Société')
            ->setFormTypeOption('class', Company::class)
            ->setFormTypeOption('choice_label', 'title') // ou 'name' selon ton entité
            ->setHelp('Associe ce client à une société.')
            ->setRequired(false);

        return [
            $id,
            $title,
            $email,
            $addrPanel,
            $billing,
            $billingRead,
            FormField::addPanel('Informations fiscales'),
            $currency,
            $vat,
            FormField::addPanel('Multi-sociétés'),
            $company,
        ];
    }
}
