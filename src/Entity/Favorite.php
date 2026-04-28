<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use App\Repository\FavoriteRepository;
use App\State\FavoriteProcessor;
use App\State\MyFavoritesProvider;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: FavoriteRepository::class)]
#[ORM\UniqueConstraint(name: 'uniq_favorite_user_lodging', columns: ['user_id', 'lodging_id'])]
#[ApiResource(
    operations: [
        new Post(
            uriTemplate: '/me/favorites',
            security: "is_granted('ROLE_CUSTOMER')",
            processor: FavoriteProcessor::class,
            denormalizationContext: ['groups' => ['favorite:write']],
        ),
        new GetCollection(
            uriTemplate: '/me/favorites',
            security: "is_granted('ROLE_CUSTOMER')",
            provider: MyFavoritesProvider::class,
        ),
        new Delete(
            uriTemplate: '/favorites/{id}',
            security: "is_granted('ROLE_CUSTOMER') and object.getUser() == user",
        ),
    ],
    normalizationContext: ['groups' => ['favorite:read']],
)]
class Favorite
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: 'doctrine.uuid_generator')]
    #[Groups(['favorite:read'])]
    private ?Uuid $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['favorite:read'])]
    private ?User $user = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['favorite:read'])]
    private ?Lodging $lodging = null;

    #[Groups(['favorite:write'])]
    #[Assert\NotNull]
    private ?Uuid $lodgingId = null;

    #[ORM\Column(type: Types::DATETIMETZ_IMMUTABLE)]
    #[Groups(['favorite:read'])]
    private ?\DateTimeImmutable $createdAt = null;

    public function getId(): ?Uuid
    {
        return $this->id;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;

        return $this;
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

    public function getLodgingId(): ?Uuid
    {
        return $this->lodgingId;
    }

    public function setLodgingId(?Uuid $lodgingId): static
    {
        $this->lodgingId = $lodgingId;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }
}
