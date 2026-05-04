<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Post;
use ApiPlatform\State\ProcessorInterface;
use App\Entity\MessageTemplate;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class MessageTemplateProcessor implements ProcessorInterface
{
    public function __construct(
        #[Autowire(service: 'api_platform.doctrine.orm.state.persist_processor')]
        private ProcessorInterface $persistProcessor,
        private Security $security,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = [])
    {
        if (!$data instanceof MessageTemplate) {
            return $this->persistProcessor->process($data, $operation, $uriVariables, $context);
        }

        /** @var \App\Entity\User $user */
        $user = $this->security->getUser();
        $hostProfile = $user->getHostProfile();

        if (null === $hostProfile) {
            throw new AccessDeniedHttpException('Host profile required.');
        }

        if ($operation instanceof Post) {
            $data->setHostProfile($hostProfile);
            $data->setCreatedAt(new \DateTimeImmutable());
        } else {
            // PATCH: verify ownership
            if (null === $data->getHostProfile() || !$data->getHostProfile()->getId()?->equals($hostProfile->getId())) {
                throw new AccessDeniedHttpException('You do not own this template.');
            }
        }

        $data->setUpdatedAt(new \DateTimeImmutable());

        return $this->persistProcessor->process($data, $operation, $uriVariables, $context);
    }
}
