<?php

namespace App\Entity;

use App\Repository\CompanyRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CompanyRepository::class)]
class Company
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(unique: true)]
    private ?int $id = null;

    #[ORM\Column(length: 160)]
    private ?string $title = null;

 #[ORM\Column(length: 160)]
    private string $legalName; // Nom juridique exact (ex: "Mon Entreprise SARL")

    #[ORM\Column(length: 32, nullable: true)]
    private ?string $siren = null; // Numéro SIREN (France)

    #[ORM\Column(length: 32, nullable: true)]
    private ?string $siret = null; // SIRET (SIREN + établissement)

    #[ORM\Column(length: 32, nullable: true)]
    private ?string $rcsNumber = null; // Numéro RCS (si société enregistrée au registre du commerce)

    #[ORM\Column(length: 32, nullable: true)]
    private ?string $apeCode = null; // Code APE/NAF

    #[ORM\Column(length: 32, nullable: true)]
    private ?string $vatNumber = null; // Numéro de TVA intracommunautaire

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $address = null; // Adresse complète sous forme de tableau : rue, CP, ville, pays

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $phone = null;

    #[ORM\Column(length: 160, nullable: true)]
    private ?string $email = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $website = null;

    #[ORM\Column(length: 3)]
    private string $defaultCurrency = 'EUR';

    #[ORM\Column(length: 32, nullable: true)]
    private ?string $iban = null; // Pour paiement par virement

    #[ORM\Column(length: 32, nullable: true)]
    private ?string $bic = null; // Code BIC/SWIFT

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): static
    {
        $this->title = $title;

        return $this;
    }

    public function getSiren(): ?string
    {
        return $this->siren;
    }

    public function setSiren(?string $siren): static
    {
        $this->siren = $siren;

        return $this;
    }

    public function getLegalName()
    {
        return $this->legalName;
    }

    public function setLegalName($legalName)
    {
        $this->legalName = $legalName;

        return $this;
    }

    public function getSiret()
    {
        return $this->siret;
    }

    public function setSiret($siret)
    {
        $this->siret = $siret;

        return $this;
    }

    public function getRcsNumber()
    {
        return $this->rcsNumber;
    }

    public function setRcsNumber($rcsNumber)
    {
        $this->rcsNumber = $rcsNumber;

        return $this;
    }

    public function getApeCode()
    {
        return $this->apeCode;
    }

    public function setApeCode($apeCode)
    {
        $this->apeCode = $apeCode;

        return $this;
    }

    public function getVatNumber()
    {
        return $this->vatNumber;
    }

    public function setVatNumber($vatNumber)
    {
        $this->vatNumber = $vatNumber;

        return $this;
    }

    public function getAddress()
    {
        return $this->address;
    }

    public function setAddress($address)
    {
        $this->address = $address;

        return $this;
    }

    public function getPhone()
    {
        return $this->phone;
    }

    public function setPhone($phone)
    {
        $this->phone = $phone;

        return $this;
    }

    public function getEmail()
    {
        return $this->email;
    }

    public function setEmail($email)
    {
        $this->email = $email;

        return $this;
    }

    public function getWebsite()
    {
        return $this->website;
    }

    public function setWebsite($website)
    {
        $this->website = $website;

        return $this;
    }

    public function getDefaultCurrency()
    {
        return $this->defaultCurrency;
    }

    public function setDefaultCurrency($defaultCurrency)
    {
        $this->defaultCurrency = $defaultCurrency;

        return $this;
    }

    public function getIban()
    {
        return $this->iban;
    }

    public function setIban($iban)
    {
        $this->iban = $iban;

        return $this;
    }

    public function getBic()
    {
        return $this->bic;
    }

    public function setBic($bic)
    {
        $this->bic = $bic;

        return $this;
    }

    public function __toString(): string
    {
        return $this->legalName ?? $this->name ?? 'Société';
    }
}
