<?php

namespace App\Entity;

use App\Repository\LieuRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: LieuRepository::class)]
class Lieu
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 50)]
    private ?string $nom = null;

    #[ORM\Column(length: 255)]
    private ?string $rue = null;

    #[ORM\Column]
    private ?float $latitude = null;

    #[ORM\Column]
    private ?float $longitude = null;

    #[ORM\ManyToOne(inversedBy: 'lieux')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Ville $ville = null;

    #[ORM\OneToMany(mappedBy: 'lieu', targetEntity: Sortie::class)]
    private Collection $lieu_sorties;

    public function __construct()
    {
        $this->lieu_sorties = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNom(): ?string
    {
        return $this->nom;
    }

    public function setNom(string $nom): static
    {
        $this->nom = $nom;

        return $this;
    }

    public function getRue(): ?string
    {
        return $this->rue;
    }

    public function setRue(string $rue): static
    {
        $this->rue = $rue;

        return $this;
    }

    public function getLatitude(): ?float
    {
        return $this->latitude;
    }

    public function setLatitude(float $latitude): static
    {
        $this->latitude = $latitude;

        return $this;
    }

    public function getLongitude(): ?float
    {
        return $this->longitude;
    }

    public function setLongitude(float $longitude): static
    {
        $this->longitude = $longitude;

        return $this;
    }

    public function getVille(): ?Ville
    {
        return $this->ville;
    }

    public function setVille(?Ville $ville): static
    {
        $this->ville = $ville;

        return $this;
    }

    public function getCodePostal(): ?string
    {
        // Récupérez le code postal à partir de l'objet Ville associé
        return $this->ville->getCodePostal();
    }

    /**
     * @return Collection<int, Sortie>
     */
    public function getLieuSorties(): Collection
    {
        return $this->lieu_sorties;
    }

    public function addLieuSorty(Sortie $lieuSorty): static
    {
        if (!$this->lieu_sorties->contains($lieuSorty)) {
            $this->lieu_sorties->add($lieuSorty);
            $lieuSorty->setLieu($this);
        }

        return $this;
    }

    public function removeLieuSorty(Sortie $lieuSorty): static
    {
        if ($this->lieu_sorties->removeElement($lieuSorty)) {
            // set the owning side to null (unless already changed)
            if ($lieuSorty->getLieu() === $this) {
                $lieuSorty->setLieu(null);
            }
        }

        return $this;
    }
}

