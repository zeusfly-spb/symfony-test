<?php

namespace App\Entity;

use App\Repository\GoodRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use App\Entity\User;


#[ORM\Entity(repositoryClass: GoodRepository::class)]
class Good
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['good:read', 'good:detail', 'good:write'])]

    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups(['good:read', 'good:detail', 'good:write'])]

    private ?string $name = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['good:read', 'good:detail', 'good:write'])]

    private ?string $comment = null;

    #[ORM\Column]
    #[Groups(['good:read', 'good:detail', 'good:write'])]
    private ?int $count = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['good:detail'])]
    private ?User $user = null;

    public function getId(): ?int
    {
        return $this->id;
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

    public function getComment(): ?string
    {
        return $this->comment;
    }

    public function setComment(?string $comment): static
    {
        $this->comment = $comment;

        return $this;
    }

    public function getCount(): ?int
    {
        return $this->count;
    }

    public function setCount(int $count): static
    {
        $this->count = $count;

        return $this;
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
}
