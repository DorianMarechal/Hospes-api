<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Post;
use ApiPlatform\State\ProcessorInterface;
use App\Repository\LodgingRepository;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpKernel\Exception\HttpException;

class SeasonProcessor implements ProcessorInterface
{
    public function __construct(
        #[Autowire(service: 'api_platform.doctrine.orm.state.persist_processor')]
        private ProcessorInterface $persistProcessor,
        private Security $security,
        private LodgingRepository $lodgingRepository,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = [])
    {
        /** @var \App\Entity\User $user */
        $user = $this->security->getUser();

        if ($operation instanceof Post) {
            $lodging = $this->lodgingRepository->find($uriVariables['lodgingId']);

            if (!$lodging) {
                throw new HttpException(404, 'Lodging not found');
            }

            if ($lodging->getHost()->getId() !== $user->getHostProfile()?->getId()) {
                throw new HttpException(403, 'You do not own this lodging');
            }

            $data->setLodging($lodging);
            $data->setCreatedAt(new \DateTimeImmutable());
        }

        $data->setUpdatedAt(new \DateTimeImmutable());

        return $this->persistProcessor->process($data, $operation, $uriVariables, $context);
    }
}
