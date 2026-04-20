<?php

namespace App\DataFixtures;

use App\Entity\HostProfile;
use App\Entity\Lodging;
use App\Entity\User;
use App\Enum\CancellationPolicy;
use App\Enum\LodgingType;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class DemoFixtures extends Fixture
{
    public function __construct(
        private UserPasswordHasherInterface $passwordHasher,
    ) {
    }

    public function load(ObjectManager $manager): void
    {
        $admin = new User();
        $admin->setEmail('admin@hospes.dev');
        $admin->setFirstName('Admin');
        $admin->setLastName('Hospes');
        $admin->setRoles(['ROLE_ADMIN']);
        $admin->setPassword($this->passwordHasher->hashPassword($admin, 'Admin@Hospes2026'));
        $admin->setIsActive(true);
        $admin->setCreatedAt(new \DateTimeImmutable());
        $admin->setUpdatedAt(new \DateTimeImmutable());
        $manager->persist($admin);

        $host = new User();
        $host->setEmail('host@hospes.dev');
        $host->setFirstName('Demo');
        $host->setLastName('Host');
        $host->setRoles(['ROLE_HOST']);
        $host->setPassword($this->passwordHasher->hashPassword($host, 'Host@Hospes2026'));
        $host->setIsActive(true);
        $host->setCreatedAt(new \DateTimeImmutable());
        $host->setUpdatedAt(new \DateTimeImmutable());
        $manager->persist($host);

        $hostProfile = new HostProfile();
        $hostProfile->setUser($host);
        $hostProfile->setBusinessName('Demo Hosting');
        $hostProfile->setCountry('FR');
        $hostProfile->setBillingAddress('1 rue de la Demo');
        $hostProfile->setBillingCity('Paris');
        $hostProfile->setBillingPostalCode('75001');
        $hostProfile->setBillingCountry('FR');
        $hostProfile->setTimezone('Europe/Paris');
        $hostProfile->setCreatedAt(new \DateTimeImmutable());
        $hostProfile->setUpdatedAt(new \DateTimeImmutable());
        $manager->persist($hostProfile);

        // Lodging 1 — Gîte rural en Provence
        $gite = new Lodging();
        $gite->setHost($hostProfile);
        $gite->setName('Le Mas des Oliviers');
        $gite->setType(LodgingType::GITE);
        $gite->setDescription('Gîte provençal avec piscine, au cœur des oliviers. Vue sur le Luberon.');
        $gite->setCapacity(6);
        $gite->setBasePriceWeek(8500);
        $gite->setBasePriceWeekend(11000);
        $gite->setCleaningFee(5000);
        $gite->setTouristTaxPerPerson(120);
        $gite->setDepositAmount(30000);
        $gite->setCancellationPolicy(CancellationPolicy::MODERATE);
        $gite->setMinStay(2);
        $gite->setMaxStay(14);
        $gite->setOrphanProtection(true);
        $gite->setCheckinTime(new \DateTimeImmutable('16:00'));
        $gite->setCheckoutTime(new \DateTimeImmutable('10:00'));
        $gite->setAddress('250 chemin des Oliviers');
        $gite->setCity('Gordes');
        $gite->setRegion('Provence-Alpes-Côte d\'Azur');
        $gite->setPostalCode('84220');
        $gite->setCountry('FR');
        $gite->setLatitude('43.9115000');
        $gite->setLongitude('5.2005000');
        $gite->setIsActive(true);
        $gite->setCreatedAt(new \DateTimeImmutable());
        $gite->setUpdatedAt(new \DateTimeImmutable());
        $manager->persist($gite);

        // Lodging 2 — Appartement urbain à Lyon
        $apartment = new Lodging();
        $apartment->setHost($hostProfile);
        $apartment->setName('Appart Presqu\'île');
        $apartment->setType(LodgingType::APARTMENT);
        $apartment->setDescription('Appartement moderne en plein centre de Lyon, à 5 min de Bellecour.');
        $apartment->setCapacity(2);
        $apartment->setBasePriceWeek(6500);
        $apartment->setBasePriceWeekend(7500);
        $apartment->setCleaningFee(3000);
        $apartment->setTouristTaxPerPerson(110);
        $apartment->setDepositAmount(15000);
        $apartment->setCancellationPolicy(CancellationPolicy::FLEXIBLE);
        $apartment->setMinStay(1);
        $apartment->setMaxStay(30);
        $apartment->setOrphanProtection(false);
        $apartment->setCheckinTime(new \DateTimeImmutable('14:00'));
        $apartment->setCheckoutTime(new \DateTimeImmutable('11:00'));
        $apartment->setAddress('12 rue de la République');
        $apartment->setCity('Lyon');
        $apartment->setRegion('Auvergne-Rhône-Alpes');
        $apartment->setPostalCode('69002');
        $apartment->setCountry('FR');
        $apartment->setLatitude('45.7578000');
        $apartment->setLongitude('4.8320000');
        $apartment->setIsActive(true);
        $apartment->setCreatedAt(new \DateTimeImmutable());
        $apartment->setUpdatedAt(new \DateTimeImmutable());
        $manager->persist($apartment);

        // Lodging 3 — Cabane en montagne
        $cabin = new Lodging();
        $cabin->setHost($hostProfile);
        $cabin->setName('La Cabane du Trappeur');
        $cabin->setType(LodgingType::CABIN);
        $cabin->setDescription('Cabane en bois isolée avec vue sur le Mont-Blanc. Idéal pour déconnecter.');
        $cabin->setCapacity(4);
        $cabin->setBasePriceWeek(7000);
        $cabin->setBasePriceWeekend(9500);
        $cabin->setCleaningFee(4000);
        $cabin->setTouristTaxPerPerson(100);
        $cabin->setDepositAmount(20000);
        $cabin->setCancellationPolicy(CancellationPolicy::STRICT);
        $cabin->setMinStay(2);
        $cabin->setMaxStay(7);
        $cabin->setOrphanProtection(true);
        $cabin->setCheckinTime(new \DateTimeImmutable('17:00'));
        $cabin->setCheckoutTime(new \DateTimeImmutable('09:00'));
        $cabin->setAddress('Lieu-dit Les Grands Montets');
        $cabin->setCity('Chamonix-Mont-Blanc');
        $cabin->setRegion('Auvergne-Rhône-Alpes');
        $cabin->setPostalCode('74400');
        $cabin->setCountry('FR');
        $cabin->setLatitude('45.9237000');
        $cabin->setLongitude('6.8694000');
        $cabin->setIsActive(true);
        $cabin->setCreatedAt(new \DateTimeImmutable());
        $cabin->setUpdatedAt(new \DateTimeImmutable());
        $manager->persist($cabin);

        // Lodging 4 — Chambre d'hôtes (inactive)
        $bnb = new Lodging();
        $bnb->setHost($hostProfile);
        $bnb->setName('Chambre Lavande');
        $bnb->setType(LodgingType::BED_AND_BREAKFAST);
        $bnb->setDescription('Chambre d\'hôtes dans une bastide du XVIIe siècle. En cours de rénovation.');
        $bnb->setCapacity(2);
        $bnb->setBasePriceWeek(5000);
        $bnb->setBasePriceWeekend(5500);
        $bnb->setCleaningFee(2000);
        $bnb->setTouristTaxPerPerson(80);
        $bnb->setCancellationPolicy(CancellationPolicy::FLEXIBLE);
        $bnb->setMinStay(1);
        $bnb->setCheckinTime(new \DateTimeImmutable('15:00'));
        $bnb->setCheckoutTime(new \DateTimeImmutable('10:00'));
        $bnb->setAddress('8 place du Château');
        $bnb->setCity('Uzès');
        $bnb->setRegion('Occitanie');
        $bnb->setPostalCode('30700');
        $bnb->setCountry('FR');
        $bnb->setIsActive(false);
        $bnb->setCreatedAt(new \DateTimeImmutable());
        $bnb->setUpdatedAt(new \DateTimeImmutable());
        $manager->persist($bnb);

        $manager->flush();
    }
}
