<?php

namespace App\Controller\Admin;

use App\Entity\Invoice;
use App\Entity\Customer;
use App\Service\Sequencer;
use App\Enum\InvoiceStatus;
use App\Service\PdfRenderer;
use App\Service\InvoiceCalculator;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Response;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Field\Field;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\String\Slugger\AsciiSlugger;
use App\Controller\Admin\InvoiceLineCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ArrayField;
use EasyCorp\Bundle\EasyAdminBundle\Field\MoneyField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Field\CollectionField;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use EasyCorp\Bundle\EasyAdminBundle\Field\FormField;



final class InvoiceCrudController extends AbstractCrudController
{
    public function __construct(
        private readonly Sequencer $sequencer,
        private readonly InvoiceCalculator $calculator,
        private readonly PdfRenderer $pdf,
        private readonly EntityManagerInterface $em,
        private readonly AdminUrlGenerator $urlGen,
        private readonly ParameterBagInterface $params,
        private RequestStack $requestStack,
    ) {}

    public static function getEntityFqcn(): string
    {
        return Invoice::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Facture')
            ->setEntityLabelInPlural('Factures')
            ->setDefaultSort(['issueDate' => 'DESC'])
            ->showEntityActionsInlined()   // 👈 actions dans la colonne
            ->setPageTitle(Crud::PAGE_DETAIL, 'Détails de la facture');
    }

    public function configureFields(string $pageName): iterable
    {
        // Badges sur INDEX
        $paidBadgeIndex = TextField::new('paidBadge', 'Payé')
            ->onlyOnIndex()
            ->setTemplatePath('admin/invoice/_badge_paid.html.twig')
            ->setSortable(false);

        $dueBadgeIndex = TextField::new('dueBadge', 'Reste à payer')
            ->onlyOnIndex()
            ->setTemplatePath('admin/invoice/_badge_due.html.twig')
            ->setSortable(false);

        // Badges sur DETAIL
        $paidBadgeDetail = TextField::new('paidBadge', 'Payé')
            ->onlyOnDetail()
            ->setTemplatePath('admin/invoice/_badge_paid.html.twig');

        $dueBadgeDetail = TextField::new('dueBadge', 'Reste à payer')
            ->onlyOnDetail()
            ->setTemplatePath('admin/invoice/_badge_due.html.twig');

        // Section "Paiements" sur DETAIL
        $paymentsPanel = FormField::addPanel('Paiements')->onlyOnDetail();


        $paymentsTable = TextField::new('paymentsTable')
            ->onlyOnDetail()
            ->setLabel(false)
            ->setTemplatePath('admin/invoice/_payments_table.html.twig');

        // --- Tes champs existants (adaptés) ---
        $fields = [
            IdField::new('id')->onlyOnDetail(),

            TextField::new('number', 'Numéro')->setDisabled(true),

            AssociationField::new('company', 'Société')->setRequired(true),

            AssociationField::new('customer', 'Client existant')
                ->setFormTypeOption('choice_label', 'title')
                ->setRequired(false),

            ChoiceField::new('status', 'Statut')
                ->setChoices(array_combine(
                    array_map(fn($c) => ucfirst(strtolower($c->value)), InvoiceStatus::cases()),
                    InvoiceStatus::cases()
                ))
                ->renderAsBadges([
                    InvoiceStatus::DRAFT->value => 'secondary',
                    InvoiceStatus::ISSUED->value => 'warning',
                    InvoiceStatus::SENT->value => 'info',
                    InvoiceStatus::PARTIALLY_PAID->value => 'primary',
                    InvoiceStatus::PAID->value => 'success',
                    InvoiceStatus::OVERDUE->value => 'danger',
                    InvoiceStatus::CANCELLED->value => 'dark',
                ]),

            DateField::new('issueDate', 'Émission'),
            DateField::new('dueDate', 'Échéance'),


            ArrayField::new('legalMentions', 'Mentions légales')->onlyOnDetail(),

            CollectionField::new('lines', 'Préstation')
                ->onlyOnForms()
                ->setFormTypeOption('by_reference', false)
                ->allowAdd()
                ->allowDelete()
                ->setRequired(true)
                ->useEntryCrudForm(InvoiceLineCrudController::class),

            MoneyField::new('totalNet', 'Total HT')
                ->setStoredAsCents(true)
                ->setCurrency('EUR') // affichage, on remet la vraie devise en détail via template
                ->setFormTypeOption('attr', ['readonly' => 'readonly']),

            MoneyField::new('totalGross', 'Total TTC')
                ->setStoredAsCents(true)
                ->setCurrency('EUR')
                ->setFormTypeOption('attr', ['readonly' => 'readonly']),

            // Sur DETAIL on ré-affiche les badges + table Paiements
            $paidBadgeIndex,
            $dueBadgeIndex,
            $paidBadgeDetail,
            $dueBadgeDetail,
            $paymentsPanel,
            $paymentsTable,
        ];

        return $fields;
    }


    public function configureActions(Actions $actions): Actions
    {
        $addPayment = Action::new('addPayment', 'Ajouter un paiement')
            ->setIcon('fa fa-plus')
            ->setCssClass('btn btn-success')
            ->displayIf(fn($e) => $e instanceof Invoice && $e->getStatus() !== InvoiceStatus::CANCELLED)
            ->linkToUrl(function (Invoice $invoice) {
                return $this->urlGen
                    ->setController(PaymentCrudController::class)
                    ->setAction(Crud::PAGE_NEW)
                    ->set('invoiceId', (string) $invoice->getId())
                    ->generateUrl();
            });

        $issue = Action::new('issue', 'Émettre')
            ->setIcon('fa fa-check')
            ->setCssClass('btn btn-warning')
            ->displayIf(fn($entity) => $entity instanceof Invoice && $entity->getStatus() === InvoiceStatus::DRAFT)
            // ✅ on construit explicitement l’URL avec l’entityId
            ->linkToUrl(function (Invoice $invoice) {
                // si ton id est un Uuid, on le caste en string
                $id = (string) $invoice->getId();
                return $this->urlGen
                    ->setController(self::class)
                    ->setAction('issueInvoice')
                    ->set('entityId', $id)
                    ->generateUrl();
            });

        return $actions
            ->add(Crud::PAGE_INDEX, Action::DETAIL)
            ->add(Crud::PAGE_INDEX, $issue)
            ->add(Crud::PAGE_DETAIL, $issue);
    }

    /**
     * Action custom : émettre la facture courante
     */
    public function issueInvoice(AdminContext $context): Response
    {
        // ✅ on récupère l’ID passé dans l’URL par le bouton
        $id = $context->getRequest()->query->get('entityId');
        if (!$id) {
            $this->addFlash('danger', 'Aucune facture sélectionnée.');
            return $this->redirectToIndex();
        }

        /** @var Invoice|null $invoice */
        $invoice = $this->em->getRepository(Invoice::class)->find($id);
        if (!$invoice) {
            $this->addFlash('danger', 'Facture introuvable.');
            return $this->redirectToIndex();
        }

        if ($invoice->getStatus() !== InvoiceStatus::DRAFT) {
            $this->addFlash('warning', 'Seules les factures en brouillon peuvent être émises.');
            return $this->redirectToIndex();
        }

        if (null === $invoice->getCustomer()) {
            $this->addFlash('danger', 'Impossible d’émettre : aucun client n’est associé à cette facture.');
            return $this->redirectToIndex();
        }

        try {
            // 1) Numéro
            $invoice->setNumber($this->sequencer->nextFor($invoice->getCompany()));
            $invoice->setStatus(InvoiceStatus::ISSUED);

            // 2) Totaux
            $this->calculator->compute($invoice);

            // 3) Sauvegarde
            $this->em->flush();

            // 4) PDF
            $projectDir = $this->params->get('kernel.project_dir');
            $slugger    = new AsciiSlugger();

            // Récupère le nom du client (adapte: getTitle() ou getName())
            $customerName = $invoice->getCustomer()?->getTitle() ?? 'client';

            // Slug pour un nom de fichier propre
            $base = $slugger->slug($customerName)->lower()->toString();
            $filename = sprintf('%s-%s.pdf', $base, $invoice->getNumber());

            // Chemin disque (public/) et URL publique
            $destAbs = $projectDir . '/public/invoices/' . $filename;

            // Si ton app n’est pas à la racine, récupère le basePath pour l’URL :
            $basePath = $this->requestStack->getCurrentRequest()->getBasePath(); // ex: "" ou "/index.php"
            $destUrl  = rtrim($basePath, '/') . '/invoices/' . $filename;

            // Génère le PDF
            $this->pdf->renderTemplateToPdf(
                'pdf/invoice.html.twig',
                ['invoice' => $invoice, 'company' => $invoice->getCompany()],
                $destAbs
            );

            // Flash avec lien cliquable
            $this->addFlash(
                'success',
                sprintf(
                    'Facture %s émise. <a href="%s" target="_blank" rel="noopener" class="btn btn-sm btn-primary">Ouvrir le PDF</a>',
                    $invoice->getNumber(),
                    $destUrl
                )
            );


            $this->addFlash('success', sprintf('Facture %s émise avec succès.', $invoice->getNumber()));
        } catch (\Throwable $e) {
            $this->addFlash('danger', 'Erreur : ' . $e->getMessage());
        }

        return $this->redirectToIndex();
    }

    private function redirectToIndex(): Response
    {
        return $this->redirect(
            $this->urlGen->setController(self::class)->setAction(Crud::PAGE_INDEX)->generateUrl()
        );
    }



    public function persistEntity(EntityManagerInterface $em, $entityInstance): void
    {
        if ($entityInstance instanceof Invoice) {
            foreach ($entityInstance->getLines() as $line) {
                if ($line->getInvoice() !== $entityInstance) {
                    $line->setInvoice($entityInstance); // 🔗
                }
            }
            $this->calculator->compute($entityInstance); // (voir §2)
        }
        parent::persistEntity($em, $entityInstance);
    }

    public function updateEntity(EntityManagerInterface $em, $entityInstance): void
    {
        if ($entityInstance instanceof Invoice) {
            foreach ($entityInstance->getLines() as $line) {
                if ($line->getInvoice() !== $entityInstance) {
                    $line->setInvoice($entityInstance);
                }
            }
            $this->calculator->compute($entityInstance);
        }
        parent::updateEntity($em, $entityInstance);
    }
}
