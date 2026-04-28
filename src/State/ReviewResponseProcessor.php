<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Dto\ReviewResponseRequest;
use App\Entity\Review;
use App\Entity\User;
use App\Repository\ReviewRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ReviewResponseProcessor implements ProcessorInterface
{
    public function __construct(
        private Security $security,
        private ReviewRepository $reviewRepository,
        private EntityManagerInterface $em,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): Review
    {
        assert($data instanceof ReviewResponseRequest);

        /** @var User $user */
        $user = $this->security->getUser();

        $review = $this->reviewRepository->find($uriVariables['id']);
        if (!$review) {
            throw new NotFoundHttpException('Review not found');
        }

        $lodging = $review->getLodging();
        if (null === $lodging || null === $lodging->getHost() || !$lodging->getHost()->getId()?->equals($user->getHostProfile()?->getId())) {
            throw new AccessDeniedHttpException('You do not own this lodging');
        }

        if (null !== $review->getHostResponse()) {
            throw new BadRequestHttpException('A response has already been posted for this review');
        }

        $review->setHostResponse($data->response);
        $review->setUpdatedAt(new \DateTimeImmutable());

        $this->em->flush();

        return $review;
    }
}
