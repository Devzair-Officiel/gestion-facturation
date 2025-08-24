<?php

namespace App\Doctrine\Subscriber;

use App\Entity\InvoiceLine;
use Doctrine\ORM\Events;
use Doctrine\ORM\Event\PrePersistEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;

/**
 * Mini-doc
 * ----------
 * Pré-remplit côté serveur les champs d'une ligne de facture à partir
 * du CatalogItem lié (si présent) et applique des valeurs par défaut
 * sûres (quantity=1, discount=0) si l'utilisateur n'a rien saisi.
 *
 * Utile même si le JS de pré-remplissage ne tourne pas : on garantit
 * l'intégrité des données *avant* l'écriture en base.
 */
#[AsDoctrineListener(event: Events::prePersist)]
#[AsDoctrineListener(event: Events::preUpdate)]
final class InvoiceLineItemSubscriber
{
    public function prePersist(PrePersistEventArgs $args): void
    {
        $this->applyDefaults($args->getObject());
    }

    public function preUpdate(PreUpdateEventArgs $args): void
    {
        $this->applyDefaults($args->getObject());
        // si on modifie des champs, on peut forcer Doctrine à recalculer les changements
        // $em = $args->getObjectManager();
        // $em->getUnitOfWork()->recomputeSingleEntityChangeSet(
        //     $em->getClassMetadata(InvoiceLine::class),
        //     $args->getObject()
        // );
    }

    private function applyDefaults(object $entity): void
    {
        if (!$entity instanceof InvoiceLine) {
            return;
        }

        $item = $entity->getItem();

        // 1) Valeurs héritées du CatalogItem (sans écraser ce que l'utilisateur a saisi)
        if ($item) {
            if (!$entity->getDesignation()) {
                $entity->setDesignation($item->getName());
            }
            if ($entity->getUnitPriceCents() === 0) {
                $entity->setUnitPriceCents($item->getDefaultUnitPriceCents());
            }
            if (null === $entity->getTaxRate()) {
                $entity->setTaxRate($item->getDefaultTaxRate());
            }
        }

        // 2) Valeurs par défaut sûres si rien n'est saisi
        // quantity est un DECIMAL stocké en string → on normalise
        $qty = trim((string) $entity->getQuantity());
        if ($qty === '' || (float) str_replace(',', '.', $qty) <= 0) {
            $entity->setQuantity('1'); // "1" en string pour rester cohérent avec le type
        }

        if ($entity->getDiscountCents() === null || $entity->getDiscountCents() < 0) {
            $entity->setDiscountCents(0);
        }
    }
}
