<?php

namespace App\Controller\Admin;

use App\Entity\Invoice;
use App\Entity\Payment;
use App\Enum\InvoiceStatus;
use App\Service\PaymentAllocator;
use Doctrine\ORM\EntityManagerInterface;
use App\Service\PaymentReferenceGenerator;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\MoneyField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;

final class PaymentCrudController extends AbstractCrudController
{
    public function __construct(
        private PaymentAllocator $allocator,
        private EntityManagerInterface $em,
        private AdminUrlGenerator $urlGen,
        private PaymentReferenceGenerator $refGen,
    ) {}

    public static function getEntityFqcn(): string
    {
        return Payment::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Paiement')
            ->setEntityLabelInPlural('Paiements')
            ->setSearchFields(['reference', 'method'])
            ->setDefaultSort(['paidAt' => 'DESC']);
    }

    public function configureFields(string $pageName): iterable
    {
        $invoiceId = $this->getContext()?->getRequest()?->query->get('invoiceId');

        // Association à la facture (obligatoire)
        $invoiceField = AssociationField::new('invoice', 'Facture')
            ->setFormTypeOption('choice_label', function (\App\Entity\Invoice $inv) {
                $num   = $inv->getNumber() ?? '—';
                $name  = $inv->getCustomerName() ?? 'Client';
                $total = number_format(($inv->getTotalGross() ?? 0) / 100, 2, ',', ' ');
                $cur   = $inv->getCurrency() ?? 'EUR';
                return sprintf('%s — %s — %s %s', $num, $name, $total, $cur);
            })
            ->setRequired(true);

        if ($pageName === Crud::PAGE_NEW && $invoiceId) {
            // verrouiller l'édition et pré-remplir via un data transformer simple
            $invoice = $this->em->getRepository(Invoice::class)->find($invoiceId);
            if ($invoice) {
                // 1) On force la valeur initiale (l’utilisateur peut quand même changer si on n’interdit pas)
                $invoiceField = $invoiceField
                    ->setFormTypeOption('data', $invoice)
                    ->setFormTypeOption('disabled', true); // optionnel: verrouiller
            }
        }

        // Company : on la calera automatiquement depuis la facture → read-only en form
        $companyField = AssociationField::new('company', 'Société')
            ->setFormTypeOption('disabled', true)
            ->onlyOnForms();

        $amountField = MoneyField::new('amountCents', 'Montant')
            ->setStoredAsCents(true)
            ->setCurrency('EUR'); // ou dynamiquement : voir note ci-dessous

        $paidAtField = DateField::new('paidAt')->setRequired(true);

        $methodField = ChoiceField::new('method', 'Méthode')
            ->setChoices([
                'Virement' => 'transfer',
                'Carte'    => 'card',
                'Espèces'  => 'cash',
                'Chèque'   => 'check',
                'Autre'    => 'other',
            ])
            ->renderExpanded(false)
            ->renderAsBadges(false);

        $refField = TextField::new('reference', 'Référence')
            ->hideOnForm();

        return [
            $invoiceField,
            $companyField,
            $amountField,
            $paidAtField,
            $methodField,
            $refField,
        ];
    }


    public function configureActions(Actions $actions): Actions
    {
        $issue = Action::new('issue', 'Émettre')
            ->setIcon('fa fa-check')
            ->setCssClass('btn btn-warning')
            ->displayIf(fn($entity) => $entity instanceof Invoice && $entity->getStatus() === InvoiceStatus::DRAFT)
            ->linkToUrl(function (Invoice $invoice) {
                $id = (string) $invoice->getId();
                return $this->urlGen
                    ->setController(self::class)
                    ->setAction('issueInvoice')
                    ->set('entityId', $id)
                    ->generateUrl();
            });

        $addPayment = Action::new('addPayment', 'Ajouter un paiement')
            ->setIcon('fa fa-plus')
            ->setCssClass('btn btn-success')
            ->displayIf(fn($entity) => $entity instanceof Invoice && $entity->getStatus() !== InvoiceStatus::CANCELLED)
            ->linkToUrl(function (Invoice $invoice) {
                return $this->urlGen
                    ->setController(PaymentCrudController::class)
                    ->setAction(Crud::PAGE_NEW)
                    ->set('invoiceId', (string) $invoice->getId())
                    ->generateUrl();
            });

        return $actions
            ->add(Crud::PAGE_INDEX, Action::DETAIL)
            ->add(Crud::PAGE_INDEX, $issue)
            ->add(Crud::PAGE_DETAIL, $issue)
            ->add(Crud::PAGE_DETAIL, $addPayment);
    }


    /**
     * Hook création : bloque la Company sur celle de la facture,
     * force paidAt si vide, puis alloue (recalcule statut de facture)
     */
    public function persistEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        if ($entityInstance instanceof Payment) {
            $this->preparePayment($entityInstance);
            parent::persistEntity($entityManager, $entityInstance);
            // Après persist (ID existant), on alloue
            $this->allocator->allocate($entityInstance);
            return;
        }

        parent::persistEntity($entityManager, $entityInstance);
    }

    /**
     * Hook update : idem, on garantit la cohérence puis on alloue
     */
    public function updateEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        if ($entityInstance instanceof Payment) {
            $this->preparePayment($entityInstance);
            parent::updateEntity($entityManager, $entityInstance);
            $this->allocator->allocate($entityInstance);
            return;
        }

        parent::updateEntity($entityManager, $entityInstance);
    }

    private function preparePayment(Payment $payment): void
    {
        // paidAt par défaut : aujourd’hui
        if ($payment->getPaidAt() === null) {
            $payment->setPaidAt(new \DateTimeImmutable());
        }

        // Company héritée de la facture (source de vérité)
        $invoice = $payment->getInvoice();
        if ($invoice instanceof Invoice && $invoice->getCompany() !== null) {
            $payment->setCompany($invoice->getCompany());
        }

        // Auto-référence si vide
        $ref = trim((string) $payment->getReference());
        if ($ref === '') {
            $payment->setReference($this->refGen->generateFor($payment));
        }
    }
}
