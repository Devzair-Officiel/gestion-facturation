<?php

/**
 * document légal de facturation. Une fois émise (status ISSUED), elle devient immuable (sauf suivi : envoi, paiements). Totaux en centimes.
 */

namespace App\Entity;

use App\Enum\InvoiceStatus;
use Doctrine\DBAL\Types\Types;
use Symfony\Component\Uid\Uuid;
use Doctrine\ORM\Mapping as ORM;
use App\Repository\InvoiceRepository;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\ArrayCollection;

#[ORM\Entity(repositoryClass: InvoiceRepository::class)]
#[ORM\Index(name: 'idx_invoice_company', columns: ['company_id'])]
#[ORM\Index(name: 'idx_invoice_customer', columns: ['customer_id'])]
#[ORM\Index(name: 'idx_invoice_status', columns: ['status'])]
#[ORM\Index(name: 'idx_invoice_due', columns: ['due_date'])]
#[ORM\UniqueConstraint(name: 'uniq_invoice_company_number', columns: ['company_id', 'number'])]
class Invoice
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: 'doctrine.uuid_generator')]
    private ?Uuid $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true)]
    private ?Company $company = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true)]
    private ?Customer $customer = null;

    private ?string $customerName = null;

    #[ORM\Column(type: 'string', length: 60, nullable: false)]
    private ?string $number = null; // ex: "2025-000123" (défini à l’émission)

    #[ORM\Column(type: 'string', enumType: InvoiceStatus::class)]
    private InvoiceStatus $status = InvoiceStatus::DRAFT;

    #[ORM\Column(type: 'date_immutable')]
    private \DateTimeImmutable $issueDate;

    #[ORM\Column(type: 'date_immutable')]
    private \DateTimeImmutable $dueDate;

    #[ORM\Column(length: 3)]
    private string $currency = 'EUR';

    // Totaux en centimes
    #[ORM\Column(type: 'integer')]
    private int $totalNet = 0;

    #[ORM\Column(type: 'integer')]
    private int $totalVat = 0;

    #[ORM\Column(type: 'integer')]
    private int $totalGross = 0;

    // Snapshots pour traçabilité (client & mentions légales au moment de l’émission)
    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $customerSnapshot = null;

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $legalMentions = null;

    #[ORM\OneToMany(mappedBy: 'invoice', targetEntity: InvoiceLine::class, cascade: ['persist'], orphanRemoval: true)]
    private Collection $lines;

    public function __construct(?Company $company = null, ?Customer $customer = null)
    {
        $this->company = $company;
        $this->customer = $customer;
        $this->issueDate = new \DateTimeImmutable();
        $this->dueDate = $this->issueDate->modify('+30 days');
        $this->lines = new ArrayCollection();
        $this->number = 'DRAFT-' . Uuid::v4()->toRfc4122();
    }

    public function getId(): ?Uuid
    {
        return $this->id;
    }


    public function getNumber(): ?string
    {
        return $this->number;
    }

    public function setNumber(string $number): static
    {
        $this->number = $number;

        return $this;
    }

    public function getIssueDate(): ?\DateTimeImmutable
    {
        return $this->issueDate;
    }

    public function setIssueDate(\DateTimeImmutable $issueDate): static
    {
        $this->issueDate = $issueDate;

        return $this;
    }

    public function getDueDate(): ?\DateTimeImmutable
    {
        return $this->dueDate;
    }

    public function setDueDate(\DateTimeImmutable $dueDate): static
    {
        $this->dueDate = $dueDate;

        return $this;
    }

    public function getCurrency(): ?string
    {
        return $this->currency;
    }

    public function setCurrency(string $currency): static
    {
        $this->currency = $currency;

        return $this;
    }

    public function getTotalNet(): ?int
    {
        return $this->totalNet;
    }

    public function setTotalNet(int $totalNet): static
    {
        $this->totalNet = $totalNet;

        return $this;
    }

    public function getTotalVat(): ?int
    {
        return $this->totalVat;
    }

    public function setTotalVat(int $totalVat): static
    {
        $this->totalVat = $totalVat;

        return $this;
    }

    public function getTotalGross(): ?int
    {
        return $this->totalGross;
    }

    public function setTotalGross(int $totalGross): static
    {
        $this->totalGross = $totalGross;

        return $this;
    }

    public function getCustomerSnapshot(): ?array
    {
        return $this->customerSnapshot;
    }

    public function setCustomerSnapshot(?array $customerSnapshot): static
    {
        $this->customerSnapshot = $customerSnapshot;

        return $this;
    }

    public function getLegalMentions(): ?array
    {
        return $this->legalMentions;
    }

    public function setLegalMentions(?array $legalMentions): static
    {
        $this->legalMentions = $legalMentions;

        return $this;
    }

    public function getStatus(): ?InvoiceStatus
    {
        return $this->status;
    }

    public function setStatus(InvoiceStatus $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function getCompany(): ?Company
    {
        return $this->company;
    }

    public function setCompany(?Company $company): static
    {
        $this->company = $company;

        return $this;
    }

    public function getCustomer(): ?Customer
    {
        return $this->customer;
    }

    public function setCustomer(?Customer $customer): static
    {
        $this->customer = $customer;

        return $this;
    }

    public function getCustomerName(): ?string
    {
        return $this->customer?->getTitle() ?? $this->customerName;
    }

    public function setCustomerName(?string $name): void
    {
        $this->customerName = $name;
    }

    /** Helpers lignes **/
    public function addLine(InvoiceLine $line): self
    {
        if (!$this->lines->contains($line)) {
            $this->lines->add($line);
        }
        return $this;
    }
    public function removeLine(InvoiceLine $line): self
    {
        $this->lines->removeElement($line);
        return $this;
    }
    public function getLines(): Collection
    {
        return $this->lines;
    }
}
