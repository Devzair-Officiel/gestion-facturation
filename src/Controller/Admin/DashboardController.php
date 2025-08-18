<?php

namespace App\Controller\Admin;

use App\Entity\Company;
use App\Entity\Customer;
use App\Entity\Invoice;
use App\Entity\Payment;
use App\Entity\Product;
use App\Entity\TaxRate;
use App\Entity\User;

use App\Controller\Admin\InvoiceCrudController; // ⚠️ assure-toi d'avoir ce contrôleur
use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminDashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Symfony\Component\HttpFoundation\Response;

#[AdminDashboard(routePath: '/admin', routeName: 'admin')]
final class DashboardController extends AbstractDashboardController
{
    public function index(): Response
    {
        // ➜ Redirige vers la liste des factures (page d’accueil de ton back-office)
        $url = $this->container->get(AdminUrlGenerator::class)
            ->setController(InvoiceCrudController::class)
            ->setAction('index') // équivaut à Crud::PAGE_INDEX
            ->generateUrl();

        return $this->redirect($url);

        // (Option) Pour afficher une page custom :
        // return $this->render('@EasyAdmin/page/content.html.twig', [
        //     'page_title' => 'Bienvenue',
        //     'content' => '<p>Statistiques, widgets, etc.</p>',
        // ]);
    }

    public function configureDashboard(): Dashboard
    {
        return Dashboard::new()
            ->setTitle('Facturation')
            ->renderContentMaximized(); // optionnel
    }

    public function configureMenuItems(): iterable
    {
        yield MenuItem::linkToDashboard('Tableau de bord', 'fa fa-home');

        yield MenuItem::section('Référentiel');
        yield MenuItem::linkToCrud('Clients',  'fa fa-users', Customer::class);
        yield MenuItem::linkToCrud('Produits', 'fa fa-box',   Product::class);
        yield MenuItem::linkToCrud('Taxes',    'fa fa-percent', TaxRate::class);

        yield MenuItem::section('Facturation');
        yield MenuItem::linkToCrud('Factures',  'fa fa-file-invoice', Invoice::class);
        yield MenuItem::linkToCrud('Paiements', 'fa fa-money-check-alt', Payment::class);

        yield MenuItem::section('Administration');
        yield MenuItem::linkToCrud('Utilisateurs', 'fa fa-user-shield', User::class);
        yield MenuItem::linkToCrud('Société',     'fa fa-building',     Company::class);

        yield MenuItem::section();
        yield MenuItem::linkToLogout('Déconnexion', 'fa fa-sign-out-alt');
    }
}
