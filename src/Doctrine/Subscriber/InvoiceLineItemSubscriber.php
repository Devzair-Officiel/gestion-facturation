<?php
// src/Doctrine/Subscriber/InvoiceLineItemSubscriber.php
namespace App\Doctrine\Subscriber;

use App\Entity\InvoiceLine;
use Doctrine\Bundle\DoctrineBundle\EventSubscriber\EventSubscriberInterface;
use Doctrine\ORM\Events;
use Doctrine\Persistence\Event\LifecycleEventArgs;

final class InvoiceLineItemSubscriber implements EventSubscriberInterface
{
    public function getSubscribedEvents(): array
    {
        return [Events::prePersist, Events::preUpdate];
    }

    public function prePersist(LifecycleEventArgs $args): void
    {
        $this->applyDefaults($args->getObject());
    }

    public function preUpdate(LifecycleEventArgs $args): void
    {
        $this->applyDefaults($args->getObject());
    }

    private function applyDefaults(object $entity): void
    {
        if (!$entity instanceof InvoiceLine) return;
        $item = $entity->getItem();
        if (!$item) return;

        // On “pré-remplit” uniquement les champs vides
        if (!$entity->getDesignation()) {
            $entity->setDesignation($item->getName());
        }
        if ($entity->getUnitPriceCents() === 0) {
            $entity->setUnitPriceCents($item->getDefaultUnitPriceCents());
        }
        if (null === $entity->getTaxRate()) {
            $entity->setTaxRate($item->getDefaultTaxRate());
        }
        // quantity/discount : à toi de décider (ex: quantity=1 par défaut)
    }
}
