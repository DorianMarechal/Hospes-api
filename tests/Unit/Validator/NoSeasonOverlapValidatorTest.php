<?php

namespace App\Tests\Unit\Validator;

use App\Entity\Lodging;
use App\Entity\Season;
use App\Repository\SeasonRepository;
use App\Validator\NoSeasonOverlap;
use App\Validator\NoSeasonOverlapValidator;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Violation\ConstraintViolationBuilderInterface;

class NoSeasonOverlapValidatorTest extends TestCase
{
    private SeasonRepository $seasonRepository;
    private NoSeasonOverlapValidator $validator;
    private ExecutionContextInterface $context;
    private NoSeasonOverlap $constraint;

    protected function setUp(): void
    {
        $this->seasonRepository = $this->createMock(SeasonRepository::class);
        $this->validator = new NoSeasonOverlapValidator($this->seasonRepository);
        $this->context = $this->createMock(ExecutionContextInterface::class);
        $this->validator->initialize($this->context);
        $this->constraint = new NoSeasonOverlap();
    }

    private function setId(object $entity, ?Uuid $id = null): void
    {
        $reflection = new \ReflectionProperty($entity, 'id');
        $reflection->setValue($entity, $id ?? Uuid::v7());
    }

    private function createSeason(Lodging $lodging, string $start, string $end, ?string $name = 'Test'): Season
    {
        $season = new Season();
        $this->setId($season);
        $season->setLodging($lodging);
        $season->setStartDate(new \DateTimeImmutable($start));
        $season->setEndDate(new \DateTimeImmutable($end));
        $season->setName($name);

        return $season;
    }

    public function testNoOverlapPasses(): void
    {
        $lodging = new Lodging();
        $existing = $this->createSeason($lodging, '2026-06-01', '2026-06-30', 'Été');
        $new = $this->createSeason($lodging, '2026-07-01', '2026-07-31', 'Juillet');

        $this->seasonRepository->method('findBy')->willReturn([$existing]);
        $this->context->expects($this->never())->method('buildViolation');

        $this->validator->validate($new, $this->constraint);
    }

    public function testOverlapFails(): void
    {
        $lodging = new Lodging();
        $existing = $this->createSeason($lodging, '2026-06-01', '2026-06-30', 'Été');
        $new = $this->createSeason($lodging, '2026-06-15', '2026-07-15', 'Juillet');

        $this->seasonRepository->method('findBy')->willReturn([$existing]);

        $violationBuilder = $this->createMock(ConstraintViolationBuilderInterface::class);
        $violationBuilder->method('setParameter')->willReturnSelf();
        $violationBuilder->expects($this->once())->method('addViolation');

        $this->context->expects($this->once())
            ->method('buildViolation')
            ->with($this->constraint->message)
            ->willReturn($violationBuilder);

        $this->validator->validate($new, $this->constraint);
    }

    public function testAdjacentSeasonsPass(): void
    {
        $lodging = new Lodging();
        $existing = $this->createSeason($lodging, '2026-06-01', '2026-06-30', 'Juin');
        $new = $this->createSeason($lodging, '2026-06-30', '2026-07-31', 'Juillet');

        $this->seasonRepository->method('findBy')->willReturn([$existing]);
        $this->context->expects($this->never())->method('buildViolation');

        $this->validator->validate($new, $this->constraint);
    }

    public function testSameSeasonUpdateDoesNotConflictWithItself(): void
    {
        $lodging = new Lodging();
        $seasonId = Uuid::v7();
        $existing = $this->createSeason($lodging, '2026-06-01', '2026-06-30', 'Été');
        $this->setId($existing, $seasonId);

        $updated = $this->createSeason($lodging, '2026-06-01', '2026-07-15', 'Été étendu');
        $this->setId($updated, $seasonId);

        $this->seasonRepository->method('findBy')->willReturn([$existing]);
        $this->context->expects($this->never())->method('buildViolation');

        $this->validator->validate($updated, $this->constraint);
    }

    public function testCompleteOverlapFails(): void
    {
        $lodging = new Lodging();
        $existing = $this->createSeason($lodging, '2026-06-01', '2026-08-31', 'Été');
        $new = $this->createSeason($lodging, '2026-06-15', '2026-07-15', 'Juillet');

        $this->seasonRepository->method('findBy')->willReturn([$existing]);

        $violationBuilder = $this->createMock(ConstraintViolationBuilderInterface::class);
        $violationBuilder->method('setParameter')->willReturnSelf();
        $violationBuilder->expects($this->once())->method('addViolation');

        $this->context->expects($this->once())
            ->method('buildViolation')
            ->willReturn($violationBuilder);

        $this->validator->validate($new, $this->constraint);
    }

    public function testNullLodgingSkipsValidation(): void
    {
        $season = new Season();
        $this->setId($season);
        $season->setStartDate(new \DateTimeImmutable('2026-06-01'));
        $season->setEndDate(new \DateTimeImmutable('2026-06-30'));

        $this->seasonRepository->expects($this->never())->method('findBy');
        $this->context->expects($this->never())->method('buildViolation');

        $this->validator->validate($season, $this->constraint);
    }

    public function testNullDatesSkipsValidation(): void
    {
        $lodging = new Lodging();
        $season = new Season();
        $this->setId($season);
        $season->setLodging($lodging);

        $this->seasonRepository->expects($this->never())->method('findBy');
        $this->context->expects($this->never())->method('buildViolation');

        $this->validator->validate($season, $this->constraint);
    }

    public function testMultipleExistingNoOverlap(): void
    {
        $lodging = new Lodging();
        $s1 = $this->createSeason($lodging, '2026-01-01', '2026-03-31', 'Hiver');
        $s2 = $this->createSeason($lodging, '2026-06-01', '2026-08-31', 'Été');
        $new = $this->createSeason($lodging, '2026-04-01', '2026-05-31', 'Printemps');

        $this->seasonRepository->method('findBy')->willReturn([$s1, $s2]);
        $this->context->expects($this->never())->method('buildViolation');

        $this->validator->validate($new, $this->constraint);
    }
}
