<?php

namespace App\MessageHandler;

use App\Message\SyncIcalFeedsMessage;
use App\Repository\IcalFeedRepository;
use App\Service\IcalSyncService;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final class SyncIcalFeedsHandler
{
    public function __construct(
        private IcalFeedRepository $icalFeedRepository,
        private IcalSyncService $icalSyncService,
    ) {
    }

    public function __invoke(SyncIcalFeedsMessage $message): void
    {
        if (null !== $message->lodgingId) {
            $feeds = $this->icalFeedRepository->findBy(['lodging' => $message->lodgingId, 'direction' => 'import']);
        } else {
            $feeds = $this->icalFeedRepository->findBy(['direction' => 'import']);
        }

        foreach ($feeds as $feed) {
            $this->icalSyncService->sync($feed);
        }
    }
}
