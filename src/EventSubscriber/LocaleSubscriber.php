<?php

namespace App\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class LocaleSubscriber implements EventSubscriberInterface
{
    private const array SUPPORTED_LOCALES = ['fr', 'en', 'de', 'es', 'it'];
    private const string DEFAULT_LOCALE = 'fr';

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => [['onKernelRequest', 20]],
        ];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        $request = $event->getRequest();
        $acceptLanguage = $request->headers->get('Accept-Language');

        if (null === $acceptLanguage || '' === $acceptLanguage) {
            $request->setLocale(self::DEFAULT_LOCALE);

            return;
        }

        $locale = $this->parseAcceptLanguage($acceptLanguage);
        $request->setLocale($locale);
    }

    private function parseAcceptLanguage(string $header): string
    {
        $locales = [];

        foreach (explode(',', $header) as $part) {
            $part = trim($part);
            if ('' === $part) {
                continue;
            }

            $segments = explode(';', $part);
            $lang = strtolower(trim($segments[0]));
            $quality = 1.0;

            if (isset($segments[1])) {
                $qPart = trim($segments[1]);
                if (str_starts_with($qPart, 'q=')) {
                    $quality = (float) substr($qPart, 2);
                }
            }

            // Normalize "en-US" to "en"
            $lang = explode('-', $lang)[0];

            if (\in_array($lang, self::SUPPORTED_LOCALES, true)) {
                $locales[$lang] = $quality;
            }
        }

        if (empty($locales)) {
            return self::DEFAULT_LOCALE;
        }

        arsort($locales);

        return array_key_first($locales);
    }
}
