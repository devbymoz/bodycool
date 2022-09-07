<?php

namespace App\Entity;

use App\Repository\FranchiseRepository;
use DateTimeImmutable;
use DateTimeZone;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use App\Entity\Permission;

#[ORM\Entity(repositoryClass: FranchiseRepository::class)]
class Franchise
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 50, unique: true)]
    private ?string $name = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createAt = null;

    #[ORM\Column]
    private ?bool $active = null;

    #[ORM\OneToOne(inversedBy: 'franchise', cascade: ['persist', 'remove'])]
    private ?User $userOwner = null;

    #[ORM\ManyToMany(targetEntity: Permission::class)]
    #[ORM\JoinTable(name: 'global_permissions')]
    private Collection $globalPermissions;

    /**
     * À l'instanciation d'une nouvelle franchise, on initialise :
     * - la date de création
     * - on active la franchise
     * - les permissions globales auxquelles la franchise à accès
     */
    public function __construct()
    {
        $this->createAt = new DateTimeImmutable('now', new DateTimeZone('Europe/Paris'));
        $this->active = 1;
        $this->globalPermissions = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getCreateAt(): ?\DateTimeImmutable
    {
        return $this->createAt;
    }

    public function setCreateAt(\DateTimeImmutable $createAt): self
    {
        $this->createAt = $createAt;

        return $this;
    }

    public function isActive(): ?bool
    {
        return $this->active;
    }

    public function setActive(bool $active): self
    {
        $this->active = $active;

        return $this;
    }

    public function getUserOwner(): ?User
    {
        return $this->userOwner;
    }

    public function setUserOwner(?User $userOwner): self
    {
        $this->userOwner = $userOwner;

        return $this;
    }

    /**
     * @return Collection<int, Permission>
     */
    public function getGlobalPermissions(): Collection
    {
        return $this->globalPermissions;
    }

    public function addGlobalPermission(Permission $globalPermission): self
    {
        if (!$this->globalPermissions->contains($globalPermission)) {
            $this->globalPermissions->add($globalPermission);
        }

        return $this;
    }

    public function removeGlobalPermission(Permission $globalPermission): self
    {
        $this->globalPermissions->removeElement($globalPermission);

        return $this;
    }
}
