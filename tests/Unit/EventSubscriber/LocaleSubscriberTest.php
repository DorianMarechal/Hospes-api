<?php

namespace App\Tests\Unit\EventSubscriber;

use App\EventSubscriber\LocaleSubscriber;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class LocaleSubscriberTest extends TestCase
{
    private LocaleSubscriber $subscriber;

    protected function setUp(): void
    {
        $this->subscriber = new LocaleSubscriber();
    }

    /**
     * Builds a RequestEvent mock whose getRequest() returns the given Request object.
     */
    private function makeEvent(Request $request): RequestEvent
    {
        $event = $this->createMock(RequestEvent::class);
        $event->method('getRequest')->willReturn($request);

        return $event;
    }

    // -------------------------------------------------------------------------
    // getSubscribedEvents
    // -------------------------------------------------------------------------

    public function testGetSubscribedEventsListensOnKernelRequest(): void
    {
        $events = LocaleSubscriber::getSubscribedEvents();

        $this->assertArrayHasKey(KernelEvents::REQUEST, $events);
    }

    public function testGetSubscribedEventsRegistersOnKernelRequestWithPriority20(): void
    {
        $events = LocaleSubscriber::getSubscribedEvents();

        // Format: [['onKernelRequest', 20]]
        $listeners = $events[KernelEvents::REQUEST];
        $this->assertContains(['onKernelRequest', 20], $listeners);
    }

    // -------------------------------------------------------------------------
    // Default locale when no Accept-Language header is present
    // -------------------------------------------------------------------------

    public function testOnKernelRequestWithNoAcceptLanguageHeaderSetsLocaleToFr(): void
    {
        $request = Request::create('/');
        $request->headers->remove('Accept-Language');

        $this->subscriber->onKernelRequest($this->makeEvent($request));

        $this->assertSame('fr', $request->getLocale());
    }

    public function testOnKernelRequestWithEmptyAcceptLanguageHeaderSetsLocaleToFr(): void
    {
        $request = Request::create('/');
        $request->headers->set('Accept-Language', '');

        $this->subscriber->onKernelRequest($this->makeEvent($request));

        $this->assertSame('fr', $request->getLocale());
    }

    // -------------------------------------------------------------------------
    // Simple supported locales
    // -------------------------------------------------------------------------

    public function testOnKernelRequestWithAcceptLanguageEnSetsLocaleToEn(): void
    {
        $request = Request::create('/');
        $request->headers->set('Accept-Language', 'en');

        $this->subscriber->onKernelRequest($this->makeEvent($request));

        $this->assertSame('en', $request->getLocale());
    }

    public function testOnKernelRequestWithAcceptLanguageFrSetsLocaleToFr(): void
    {
        $request = Request::create('/');
        $request->headers->set('Accept-Language', 'fr');

        $this->subscriber->onKernelRequest($this->makeEvent($request));

        $this->assertSame('fr', $request->getLocale());
    }

    public function testOnKernelRequestWithAcceptLanguageDeSetsLocaleToDe(): void
    {
        $request = Request::create('/');
        $request->headers->set('Accept-Language', 'de');

        $this->subscriber->onKernelRequest($this->makeEvent($request));

        $this->assertSame('de', $request->getLocale());
    }

    public function testOnKernelRequestWithAcceptLanguageEsSetsLocaleToEs(): void
    {
        $request = Request::create('/');
        $request->headers->set('Accept-Language', 'es');

        $this->subscriber->onKernelRequest($this->makeEvent($request));

        $this->assertSame('es', $request->getLocale());
    }

    public function testOnKernelRequestWithAcceptLanguageItSetsLocaleToIt(): void
    {
        $request = Request::create('/');
        $request->headers->set('Accept-Language', 'it');

        $this->subscriber->onKernelRequest($this->makeEvent($request));

        $this->assertSame('it', $request->getLocale());
    }

    // -------------------------------------------------------------------------
    // Region subtag is stripped — "en-US" → "en", "de-DE" → "de"
    // -------------------------------------------------------------------------

    public function testOnKernelRequestWithRegionSubtagStripsRegionAndSetsLocale(): void
    {
        $request = Request::create('/');
        $request->headers->set('Accept-Language', 'en-US');

        $this->subscriber->onKernelRequest($this->makeEvent($request));

        $this->assertSame('en', $request->getLocale());
    }

    public function testOnKernelRequestWithDeDEStripsRegionAndSetsLocaleToDe(): void
    {
        $request = Request::create('/');
        $request->headers->set('Accept-Language', 'de-DE,en;q=0.5');

        $this->subscriber->onKernelRequest($this->makeEvent($request));

        $this->assertSame('de', $request->getLocale());
    }

    // -------------------------------------------------------------------------
    // Unsupported locales fall back to default (fr)
    // -------------------------------------------------------------------------

    public function testOnKernelRequestWithUnsupportedLocaleJaSetsLocaleToFr(): void
    {
        $request = Request::create('/');
        $request->headers->set('Accept-Language', 'ja');

        $this->subscriber->onKernelRequest($this->makeEvent($request));

        $this->assertSame('fr', $request->getLocale());
    }

    public function testOnKernelRequestWithUnsupportedLocaleZhSetsLocaleToFr(): void
    {
        $request = Request::create('/');
        $request->headers->set('Accept-Language', 'zh-CN');

        $this->subscriber->onKernelRequest($this->makeEvent($request));

        $this->assertSame('fr', $request->getLocale());
    }

    public function testOnKernelRequestWithOnlyUnsupportedLocalesSetsLocaleToFr(): void
    {
        $request = Request::create('/');
        $request->headers->set('Accept-Language', 'ja,zh;q=0.9,ar;q=0.8');

        $this->subscriber->onKernelRequest($this->makeEvent($request));

        $this->assertSame('fr', $request->getLocale());
    }

    // -------------------------------------------------------------------------
    // Quality weights — highest-quality supported locale wins
    // -------------------------------------------------------------------------

    public function testOnKernelRequestPicksHighestQualitySupportedLocale(): void
    {
        // de has q=0.9, en has q=0.8 → de wins
        $request = Request::create('/');
        $request->headers->set('Accept-Language', 'de;q=0.9,en;q=0.8');

        $this->subscriber->onKernelRequest($this->makeEvent($request));

        $this->assertSame('de', $request->getLocale());
    }

    public function testOnKernelRequestWithDefaultQualityOneBeatsLowerQuality(): void
    {
        // fr has no q (defaults to 1.0), de has q=0.7 → fr wins
        $request = Request::create('/');
        $request->headers->set('Accept-Language', 'fr,de;q=0.7');

        $this->subscriber->onKernelRequest($this->makeEvent($request));

        $this->assertSame('fr', $request->getLocale());
    }

    public function testOnKernelRequestSkipsUnsupportedHighQualityAndPicksNextSupported(): void
    {
        // ja is highest quality but unsupported; en is next supported at q=0.8
        $request = Request::create('/');
        $request->headers->set('Accept-Language', 'ja,en;q=0.8,de;q=0.6');

        $this->subscriber->onKernelRequest($this->makeEvent($request));

        $this->assertSame('en', $request->getLocale());
    }

    public function testOnKernelRequestWithMixedSupportedAndUnsupportedPicksHighestSupportedQuality(): void
    {
        // Unsupported: ja (q=1.0), zh (q=0.9). Supported: es (q=0.7), it (q=0.5) → es wins
        $request = Request::create('/');
        $request->headers->set('Accept-Language', 'ja,zh;q=0.9,es;q=0.7,it;q=0.5');

        $this->subscriber->onKernelRequest($this->makeEvent($request));

        $this->assertSame('es', $request->getLocale());
    }

    public function testOnKernelRequestWithRegionSubtagsAndQualityWeightsPicksCorrectLocale(): void
    {
        // "de-DE" → "de" at q=0.9, "en-GB" → "en" at q=0.8
        $request = Request::create('/');
        $request->headers->set('Accept-Language', 'de-DE;q=0.9,en-GB;q=0.8');

        $this->subscriber->onKernelRequest($this->makeEvent($request));

        $this->assertSame('de', $request->getLocale());
    }

    // -------------------------------------------------------------------------
    // Case insensitivity — "EN", "FR" are normalized via strtolower
    // -------------------------------------------------------------------------

    public function testOnKernelRequestWithUpperCaseLocaleNormalizesToLowerCase(): void
    {
        $request = Request::create('/');
        $request->headers->set('Accept-Language', 'EN');

        $this->subscriber->onKernelRequest($this->makeEvent($request));

        $this->assertSame('en', $request->getLocale());
    }

    public function testOnKernelRequestWithMixedCaseLocaleNormalizesToLowerCase(): void
    {
        $request = Request::create('/');
        $request->headers->set('Accept-Language', 'Fr');

        $this->subscriber->onKernelRequest($this->makeEvent($request));

        $this->assertSame('fr', $request->getLocale());
    }

    // -------------------------------------------------------------------------
    // Whitespace handling — spaces around commas and semicolons are trimmed
    // -------------------------------------------------------------------------

    public function testOnKernelRequestTrimsWhitespaceAroundLocaleTokens(): void
    {
        $request = Request::create('/');
        $request->headers->set('Accept-Language', ' en , de ; q=0.8 ');

        $this->subscriber->onKernelRequest($this->makeEvent($request));

        $this->assertSame('en', $request->getLocale());
    }
}
