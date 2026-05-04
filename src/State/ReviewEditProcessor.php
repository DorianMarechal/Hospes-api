<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Entity\Review;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;

class ReviewEditProcessor implements ProcessorInterface
{
    private const int EDIT_WINDOW_DAYS = 14;

    public function __construct(
        private EntityManagerInterface $em,
        private Security $security,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): Review
    {
        if (!$data instanceof Review) {
            throw new \InvalidArgumentException('Expected '.Review::class);
        }

        $user = $this->security->getUser();
        if (!$user instanceof User) {
            throw new \RuntimeException('Expected authenticated user');
        }

        if (true !== $data->getCustomer()?->getId()?->equals($user->getId())) {
            throw new AccessDeniedHttpException('You can only edit your own reviews');
        }

        $createdAt = $data->getCreatedAt();
        if (null !== $createdAt) {
            $deadline = $createdAt->modify('+'.self::EDIT_WINDOW_DAYS.' days');
            if (new \DateTimeImmutable() > $deadline) {
                throw new HttpException(422, sprintf('Reviews can only be edited within %d days of creation', self::EDIT_WINDOW_DAYS));
            }
        }

        $data->setUpdatedAt(new \DateTimeImmutable());

        $this->em->flush();

        return $data;
    }
}
