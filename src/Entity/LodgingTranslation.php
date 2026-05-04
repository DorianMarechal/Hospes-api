<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Link;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use App\Repository\LodgingTranslationRepository;
use App\State\LodgingTranslationProcessor;
use App\State\LodgingTranslationsProvider;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: LodgingTranslationRepository::class)]
#[ORM\UniqueConstraint(name: 'UNIQ_LODGING_TRANSLATION_LOCALE', fields: ['lodging', 'locale'])]
#[ApiResource(
    operations: [
        new GetCollection(
            uriTemplate: '/lodgings/{lodgingId}/translations',
            uriVariables: ['lodgingId' => new Link(fromClass: Lodging::class, toProperty: 'lodging')],
            provider: LodgingTranslationsProvider::class,
        ),
        new Post(
            uriTemplate: '/lodgings/{lodgingId}/translations',
            uriVariables: ['lodgingId' => new Link(fromClass: Lodging::class, toProperty: 'lodging')],
            security: "is_granted('ROLE_HOST')",
            processor: LodgingTranslationProcessor::class,
            read: false,
        ),
        new Put(
            uriTemplate: '/lodging-translations/{id}',
            security: "is_granted('ROLE_HOST')",
            processor: LodgingTranslationProcessor::class,
        ),
    ],
    normalizationContext: ['groups' => ['translation:read']],
    denormalizationContext: ['groups' => ['translation:write']],
)]
class LodgingTranslation
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: 'doctrine.uuid_generator')]
    #[Groups(['translation:read'])]
    private ?Uuid $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?Lodging $lodging = null;

    #[ORM\Column(length: 5)]
    #[Groups(['translation:read', 'translation:write'])]
    #[Assert\NotBlank]
    #[Assert\Length(max: 5)]
    private ?string $locale = null;

    #[ORM\Column(length: 150)]
    #[Groups(['translation:read', 'translation:write'])]
    #[Assert\NotBlank]
    private ?string $name = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(['translation:read', 'translation:write'])]
    private ?string $description = null;

    public function getId(): ?Uuid
    {
        return $this->id;
    }

    public function getLodging(): ?Lodging
    {
        return $this->lodging;
    }

    public function setLodging(?Lodging $lodging): static
    {
        $this->lodging = $lodging;

        return $this;
    }

    public function getLocale(): ?string
    {
        return $this->locale;
    }

    public function setLocale(string $locale): static
    {
        $this->locale = $locale;

        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;

        return $this;
    }
}
