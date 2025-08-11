<?php

/**
 * client facturé par une Company
 */
namespace App\Entity;

use App\Repository\CustomerRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CustomerRepository::class)]
#[ORM\Index(name: 'idx_customer_company', columns: ['company_id'])]
#[ORM\Index(name: 'idx_customer_email', columns: ['email'])]
class Customer
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 160)]
    private string $title;

    #[ORM\Column(length: 160, nullable: true)]
    private ?string $email = null;

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $billingAddress = null; // ['street'=>'...', 'zip'=>'...', 'city'=>'...', 'country'=>'FR']

    #[ORM\Column(length: 3)]
    private string $currency = 'EUR';

    #[ORM\Column(length: 32, nullable: true)]
    private ?string $vatNumber = null; // TVA intracom client (B2B)

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private Company $company;

    public function __construct(Company $company, string $title)
    {
        $this->company = $company;
        $this->title = $title;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle($title): static
    {
        $this->title = $title;

        return $this;
    }

    public function getEmail(): string|null
    {
        return $this->email;
    }

    public function setEmail($email): static
    {
        $this->email = $email;

        return $this;
    }

    public function getBillingAddress(): array|null
    {
        return $this->billingAddress;
    }

    public function setBillingAddress($billingAddress): static
    {
        $this->billingAddress = $billingAddress;

        return $this;
    }

    public function getCurrency(): string
    {
        return $this->currency;
    }

    public function setCurrency($currency): static
    {
        $this->currency = $currency;

        return $this;
    }

    public function getVatNumber(): string|null
    {
        return $this->vatNumber;
    }

    public function setVatNumber($vatNumber): static
    {
        $this->vatNumber = $vatNumber;

        return $this;
    }
}
