<?php

namespace App\Enum;

enum InvoiceStatus: string {
    case DRAFT = 'Brouillon';                       // la facture est créée mais pas encore finalisée (modifiable librement).
    case ISSUED = 'Émise';                          // la facture a été validée / signée, elle porte un numéro officiel (non modifiable).
    case SENT = 'Envoyée';                          // la facture a été transmise au client (par email, courrier, portail).
    case PARTIALLY_PAID = 'Partiellement_payée';    // un acompte ou un règlement partiel a été reçu.
    case PAID = 'Payée';                            // le règlement complet est reçu, facture soldée.
    case OVERDUE = 'En_retard';                     // la facture est échue (date dépassée) et toujours impayée ou partiellement payée.
    case CANCELLED = 'Annulée';                     // la facture est annulée, souvent par émission d’un avoir.
}
