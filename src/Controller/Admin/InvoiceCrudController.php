<?php

namespace App\Controller\Admin;

use App\Entity\Invoice;
use App\Enum\InvoiceStatus;
use App\Service\InvoiceCalculator;
use App\Service\PdfRenderer;
use App\Service\Sequencer;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

final class InvoiceCrudController extends AbstractCrudController
{
    public function __construct(
        private readonly Sequencer $sequencer,
        private readonly InvoiceCalculator $calculator,
        private readonly PdfRenderer $pdf,
        private readonly EntityManagerInterface $em,
        private readonly AdminUrlGenerator $urlGen,
        private readonly ParameterBagInterface $params
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
            ->setDefaultSort(['issueDate' => 'DESC']);
    }

    public function configureActions(Actions $actions): Actions
    {
        $issue = Action::new('issue', 'Émettre')
            ->setIcon('fa fa-check')
            ->setCssClass('btn btn-warning')
            ->linkToCrudAction('issueInvoice')
            // Afficher le bouton seulement si status = DRAFT
            ->displayIf(static function ($entity) {
                return $entity instanceof Invoice && $entity->getStatus() === InvoiceStatus::DRAFT;
            });

        return $actions
            ->add(Crud::PAGE_INDEX, $issue)
            ->add(Crud::PAGE_DETAIL, $issue);
    }

    /**
     * Action custom : émettre la facture courante
     */
    public function issueInvoice(AdminContext $context)
    {
        /** @var Invoice $invoice */
        $invoice = $context->getEntity()->getInstance();

        if (!$invoice instanceof Invoice) {
            $this->addFlash('danger', 'Aucune facture à émettre.');
            return $this->redirectBack($context);
        }

        if ($invoice->getStatus() !== InvoiceStatus::DRAFT) {
            $this->addFlash('warning', 'Seules les factures en brouillon peuvent être émises.');
            return $this->redirectBack($context, $invoice);
        }

        try {
            // 1) Numérotation
            $number = $this->sequencer->nextFor($invoice->getCompany());
            $invoice->setNumber($number);
            $invoice->setStatus(InvoiceStatus::ISSUED);

            // 2) Totaux
            $this->calculator->compute($invoice);

            // 3) Persist
            $this->em->flush();

            // 4) PDF
            $projectDir = $this->params->get('kernel.project_dir');
            $pdfPath = $this->pdf->renderTemplateToPdf(
                'pdf/invoice.html.twig',
                ['invoice' => $invoice, 'company' => $invoice->getCompany()],
                $projectDir . '/var/invoices/' . $invoice->getNumber() . '.pdf'
            );

            if (method_exists($invoice, 'setPdfPath')) {
                // $invoice->setPdfPath($pdfPath);
                $this->em->flush();
            }

            $this->addFlash('success', sprintf('Facture %s émise. PDF généré.', $invoice->getNumber()));
        } catch (\Throwable $e) {
            $this->addFlash('danger', 'Échec lors de l’émission : ' . $e->getMessage());
        }

        return $this->redirectBack($context, $invoice);
    }

    private function redirectBack(AdminContext $context, ?Invoice $invoice = null)
    {
        $url = $this->urlGen
            ->setController(self::class)
            ->setAction(Crud::PAGE_DETAIL)
            ->setEntityId($invoice?->getId())
            ->generateUrl();

        return $this->redirect($url);
    }
}
