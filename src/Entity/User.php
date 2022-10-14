<?php

namespace App\Entity;

use App\Repository\UserRepository;
use DateTimeImmutable;
use DateTimeZone;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Serializer\Annotation\Ignore;


#[ORM\Entity(repositoryClass: UserRepository::class)]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private int $id;

    #[ORM\Column(length: 255, unique: true)]
    private string $email;

    #[ORM\Column]
    private ?array $roles = [];

    /**
     * @var string The hashed password
     */
    #[ORM\Column(nullable: true)]
    #[Ignore]
    private ?string $password = null;

    private ?string $plainPassword = null;

    #[ORM\Column(length: 50)]
    private string $lastname;

    #[ORM\Column(length: 50)]
    private string $firstname;

    #[ORM\Column(length: 20)]
    private string $phone;

    #[ORM\Column(length: 255)]
    private string $avatar;

    #[ORM\Column]
    private \DateTimeImmutable $createAt;

    #[ORM\Column]
    private ?bool $active = null;

    #[ORM\Column(length: 255, nullable: true, unique: true)]
    private ?string $activationToken = null;

    #[ORM\OneToOne(mappedBy: 'userOwner', cascade: ['persist', 'remove'])]
    private ?Franchise $franchise = null;

    #[ORM\OneToOne(mappedBy: 'userAdmin', cascade: ['persist', 'remove'])]
    private ?Structure $structure = null;

    /**
     * À l'instanciation d'un nouvel utilisateur, on initialise : 
     * - La date de création
     * - l'activation du compte à false
     * - une photo de profil par defaut
     * - le token pour la création du mot de passe
     */
    public function __construct()
    {
        $this->createAt = new DateTimeImmutable('now', new DateTimeZone('Europe/Paris'));
        $this->active = 0;
        $this->avatar= 'avatar-defaut.jpg';
        $this->activationToken = md5(uniqid());
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): self
    {
        $this->email = $email;

        return $this;
    }

    /**
     * A visual identifier that represents this user.
     *
     * @see UserInterface
     */
    public function getUserIdentifier(): string
    {
        return (string) $this->email;
    }

    /**
     * @see UserInterface
     */
    public function getRoles(): array
    {
        $roles = $this->roles;
        // guarantee every user at least has ROLE_USER
        $roles[] = 'ROLE_USER';
        
        return array_unique($roles);
    }

    public function setRoles(array $roles): self
    {
        $this->roles = $roles;

        return $this;
    }

    /**
     * @see PasswordAuthenticatedUserInterface
     */
    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password): self
    {
        $this->password = $password;

        return $this;
    }

    /**
     * @see UserInterface
     */
    public function eraseCredentials()
    {
        // If you store any temporary, sensitive data on the user, clear it here
        // $this->plainPassword = null;
    }

    public function getLastname(): ?string
    {
        return $this->lastname;
    }

    public function setLastname(string $lastname): self
    {
        $this->lastname = $lastname;

        return $this;
    }

    public function getFirstname(): ?string
    {
        return $this->firstname;
    }

    public function setFirstname(string $firstname): self
    {
        $this->firstname = $firstname;

        return $this;
    }

    public function getPhone(): ?string
    {
        return $this->phone;
    }

    public function setPhone(?string $phone): self
    {
        $this->phone = $phone;

        return $this;
    }

    public function getAvatar(): ?string
    {
        return $this->avatar;
    }

    public function setAvatar(?string $avatar): self
    {
        $this->avatar = $avatar;

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

    public function getActivationToken(): ?string
    {
        return $this->activationToken;
    }

    public function setActivationToken(?string $activationToken): self
    {
        $this->activationToken = $activationToken;

        return $this;
    }

    /**
     * Get the value of plainPassword
     */ 
    public function getPlainPassword()
    {
        return $this->plainPassword;
    }

    /**
     * Set the value of plainPassword
     *
     * @return  self
     */ 
    public function setPlainPassword($plainPassword): \App\Entity\User
    {
        $this->plainPassword = $plainPassword;

        return $this;
    }

    public function getFranchise(): ?Franchise
    {
        return $this->franchise;
    }

    public function setFranchise(?Franchise $franchise): self
    {
        // unset the owning side of the relation if necessary
        if ($franchise === null && $this->franchise !== null) {
            $this->franchise->setUserOwner(null);
        }

        // set the owning side of the relation if necessary
        if ($franchise !== null && $franchise->getUserOwner() !== $this) {
            $franchise->setUserOwner($this);
        }

        $this->franchise = $franchise;

        return $this;
    }

    public function getStructure(): ?Structure
    {
        return $this->structure;
    }

    public function setStructure(Structure $structure): self
    {
        // set the owning side of the relation if necessary
        if ($structure->getUserAdmin() !== $this) {
            $structure->setUserAdmin($this);
        }

        $this->structure = $structure;

        return $this;
    }
}
