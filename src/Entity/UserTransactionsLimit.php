<?php
namespace App\Entity;

use App\Repository\UserTransactionsLimitRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: UserTransactionsLimitRepository::class)]
class UserTransactionsLimit
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['limit_read', 'limit_write'])]
    private ?int $id = null;

    #[ORM\OneToOne(inversedBy: 'userTransactionsLimit', cascade: ['persist', 'remove'])]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['limit_read', 'limit_write'])]
    private ?User $user = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    #[Groups(['limit_read', 'limit_write'])]
    private ?string $daily_limit = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    #[Groups(['limit_read', 'limit_write'])]
    private ?string $monthly_limit = null;

    #[ORM\Column]
    #[Groups(['limit_read'])]
    private ?\DateTimeImmutable $created_at = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    #[Groups(['limit_read', 'limit_write'])]
    private ?\DateTimeInterface $updated_at = null;

    public function getId(): ?int
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

    public function getDailyLimit(): ?string
    {
        return $this->daily_limit;
    }

    public function setDailyLimit(string $daily_limit): static
    {
        $this->daily_limit = $daily_limit;

        return $this;
    }

    public function getMonthlyLimit(): ?string
    {
        return $this->monthly_limit;
    }

    public function setMonthlyLimit(string $monthly_limit): static
    {
        $this->monthly_limit = $monthly_limit;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->created_at;
    }

    public function setCreatedAt(\DateTimeImmutable $created_at): static
    {
        $this->created_at = $created_at;

        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeInterface
    {
        return $this->updated_at;
    }

    public function setUpdatedAt(\DateTimeInterface $updated_at): static
    {
        $this->updated_at = $updated_at;

        return $this;
    }
}
