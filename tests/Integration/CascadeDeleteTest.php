<?php

namespace App\Tests\Integration;

use App\Entity\Season;
use App\Tests\Factory\LodgingFactory;
use App\Tests\Factory\SeasonFactory;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

class CascadeDeleteTest extends KernelTestCase
{
    use Factories;
    use ResetDatabase;

    private EntityManagerInterface $em;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->em = self::getContainer()->get(EntityManagerInterface::class);
    }

    public function testDeletingLodgingCascadesToSeasons(): void
    {
        $lodging = LodgingFactory::createOne();
        $season = SeasonFactory::createOne([
            'lodging' => $lodging,
            'startDate' => new \DateTimeImmutable('+1 month'),
            'endDate' => new \DateTimeImmutable('+2 months'),
        ]);

        $seasonId = $season->getId();

        $this->em->remove($lodging->_real());
        $this->em->flush();
        $this->em->clear();

        $found = $this->em->find(Season::class, $seasonId);
        $this->assertNull($found);
    }
}
