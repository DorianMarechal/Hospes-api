<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Repository\LodgingRepository;
use App\Service\AuditLogger;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class AdminLodgingDeleteProcessor implements ProcessorInterface
{
    public function __construct(
        private LodgingRepository $lodgingRepository,
        private EntityManagerInterface $em,
        private AuditLogger $auditLogger,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): null
    {
        $lodging = $this->lodgingRepository->find($uriVariables['id']);
        if (!$lodging) {
            throw new NotFoundHttpException('Lodging not found');
        }

        $this->auditLogger->log('delete_lodging', 'Lodging', $lodging->getId(), ['name' => $lodging->getName()]);
        $this->em->remove($lodging);
        $this->em->flush();

        return null;
    }
}
