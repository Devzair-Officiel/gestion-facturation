<?php

namespace App\Service;

use App\Entity\Payment;
use App\Entity\Invoice;
use Symfony\Component\String\Slugger\AsciiSlugger;

final class PaymentReferenceGenerator
{
    private AsciiSlugger $slugger;

    public function __construct()
    {
        $this->slugger = new AsciiSlugger();
    }

    public function generateFor(Payment $payment): string
    {
        $method = strtolower((string) $payment->getMethod());
        $date   = $payment->getPaidAt() ?? new \DateTimeImmutable();

        $invoice = $payment->getInvoice();
        $invNo   = $this->sanitize($invoice?->getNumber() ?? 'INV');
        $company = $invoice?->getCompany()?->getLegalName() ?? '';
        $compCd  = substr($this->sanitize($company), 0, 6) ?: 'COMP';

        // Normalise quelques variantes FR/EN
        $method = match ($method) {
            'virement', 'vir', 'transfer', 'bank', 'wire' => 'transfer',
            'chèque', 'cheque', 'check'                   => 'check',
            'espèces', 'especes', 'cash'                  => 'cash',
            'prélèvement', 'prelevement', 'sdd', 'sepa'   => 'sepa',
            default                                       => $method,
        };

        return match ($method) {
            'transfer' => $this->buildTransferRef($compCd, $invNo),   // TRF-RFxx....
            'check'    => sprintf('CHQ-%s-%s', $date->format('Ymd'), substr($invNo, -6)),
            'cash'     => sprintf('CASH-%s-%s', $date->format('Ymd'), substr($invNo, -6)),
            'sepa'     => 'SDD-' . $this->buildRum($compCd, $invoice, $date),
            default    => sprintf('PAY-%s-%s', $date->format('Ymd'), substr($invNo, -6)),
        };
    }

    private function buildTransferRef(string $companyCode, string $invoiceNumber): string
    {
        // Option "propre" pour les virements : ISO 11649 "RF Creditor Reference"
        // On calcule RF + 2 digits de contrôle sur base "<COMPANY>-<INVNO>"
        $base = $companyCode . '-' . $invoiceNumber;
        $rf = $this->rfCreditorReference($base); // ex: RF18COMPANYINV00123
        return 'TRF-' . $rf;
    }

    private function buildRum(string $companyCode, ?Invoice $invoice, \DateTimeImmutable $date): string
    {
        // Pseudo-RUM interne (utile juste pour archivage local)
        $clientCode = substr($this->sanitize($invoice?->getCustomer()?->getTitle() ?? ''), 0, 6) ?: 'CLIENT';
        $rand = strtoupper(substr(bin2hex(random_bytes(2)), 0, 4)); // 4 hex
        return sprintf('%s-%s-%s-%s', $companyCode, $clientCode, $date->format('Ymd'), $rand);
    }

    /** RF Creditor Reference (ISO 11649) — calcule les 2 digits de contrôle mod 97 */
    private function rfCreditorReference(string $base): string
    {
        $ref = strtoupper(preg_replace('/[^A-Z0-9]/', '', $base));
        // Construire la chaîne pour mod 97 : "<REF>RF00"
        $candidate = $ref . 'RF00';
        $numeric = $this->lettersToNumbers($candidate);
        $mod = $this->mod97($numeric);
        $check = 98 - $mod;
        $check = str_pad((string)$check, 2, '0', STR_PAD_LEFT);
        return 'RF' . $check . $ref;
    }

    private function lettersToNumbers(string $s): string
    {
        $out = '';
        $len = strlen($s);
        for ($i = 0; $i < $len; $i++) {
            $ch = $s[$i];
            if ($ch >= 'A' && $ch <= 'Z') {
                $out .= (string)(ord($ch) - 55); // A=10, B=11, ..., Z=35
            } else {
                $out .= $ch;
            }
        }
        return $out;
    }

    private function mod97(string $num): int
    {
        // Modulo 97 sans bigint : on consomme chiffre par chiffre
        $remainder = 0;
        $len = strlen($num);
        for ($i = 0; $i < $len; $i++) {
            $remainder = ($remainder * 10 + (int)$num[$i]) % 97;
        }
        return $remainder;
    }

    private function sanitize(string $raw): string
    {
        // Uppercase alphanum sans tirets/espaces
        $slug = $this->slugger->slug($raw)->toString(); // ex: devzair-sarl-2025-000123
        return strtoupper(preg_replace('/[^A-Z0-9]/', '', $slug)); // DEVZAIRSARL2025000123
    }
}
