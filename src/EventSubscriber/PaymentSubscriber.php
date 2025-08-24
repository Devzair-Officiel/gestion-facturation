<?php

namespace App\EventSubscriber;

use App\Entity\Payment;
use App\Service\PaymentAllocator;
use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Events;
use Doctrine\Persistence\Event\LifecycleEventArgs;
use Doctrine\ORM\Event\PostUpdateEventArgs;

final class PaymentSubscriber implements EventSubscriber
{
    public function __construct(private PaymentAllocator $allocator) {}

    public function getSubscribedEvents(): array
    {
        return [
            Events::postPersist,
            Events::postUpdate,
        ];
    }

    public function postPersist(LifecycleEventArgs $args): void
    {
        $entity = $args->getObject();
        if (!$entity instanceof Payment) {
            return;
        }
        // Un nouveau paiement a été créé → recalcul des statuts de facture
        $this->allocator->allocate($entity);
    }

    public function postUpdate(LifecycleEventArgs $args): void
    {
        $entity = $args->getObject();
        if (!$entity instanceof Payment) {
            return;
        }

        // Pas de getEntityChangeSet ici → on relance systématiquement l’allocateur
        $this->allocator->allocate($entity);
    }
}
