<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Repository\ReviewRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class AdminReviewDeleteProcessor implements ProcessorInterface
{
    public function __construct(
        private ReviewRepository $reviewRepository,
        private EntityManagerInterface $em,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): null
    {
        $review = $this->reviewRepository->find($uriVariables['id']);
        if (!$review) {
            throw new NotFoundHttpException('Review not found');
        }

        $this->em->remove($review);
        $this->em->flush();

        return null;
    }
}
