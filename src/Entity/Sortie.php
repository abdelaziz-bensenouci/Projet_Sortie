<?php

namespace App\Entity;

use App\Repository\SortieRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: SortieRepository::class)]
class Sortie
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $nom = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $dateHeureDebut = null;

    #[ORM\Column(nullable: true)]
    private ?int $duree = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $dateLimiteInscription = null;

    #[ORM\Column]
    private ?int $nbInscriptionsMax = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $infosSortie = null;

    #[ORM\ManyToOne(inversedBy: 'lieu_sorties')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Lieu $lieu = null;

    #[ORM\ManyToOne(inversedBy: 'sorties')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Campus $campus_sorties = null;

    #[ORM\ManyToOne(inversedBy: 'organisateur')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Participant $organisateur = null;

    #[ORM\ManyToMany(targetEntity: Participant::class, mappedBy: 'sorties')]
    private Collection $participants;

    #[ORM\ManyToOne(inversedBy: 'sorties')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Etat $sortie_etat = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $MotifAnnulation = null;

    public function __construct()
    {
        $this->participants = new ArrayCollection();
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

    public function getDateHeureDebut(): ?\DateTimeInterface
    {
        return $this->dateHeureDebut;
    }

    public function setDateHeureDebut(\DateTimeInterface $dateHeureDebut): static
    {
        $this->dateHeureDebut = $dateHeureDebut;

        return $this;
    }

    public function getDuree(): ?int
    {
        return $this->duree;
    }

    public function setDuree(?int $duree): static
    {
        $this->duree = $duree;

        return $this;
    }

    public function getDateLimiteInscription(): ?\DateTimeInterface
    {
        return $this->dateLimiteInscription;
    }

    public function setDateLimiteInscription(\DateTimeInterface $dateLimiteInscription): static
    {
        $this->dateLimiteInscription = $dateLimiteInscription;

        return $this;
    }

    public function getNbInscriptionsMax(): ?int
    {
        return $this->nbInscriptionsMax;
    }

    public function setNbInscriptionsMax(int $nbInscriptionsMax): static
    {
        $this->nbInscriptionsMax = $nbInscriptionsMax;

        return $this;
    }

    public function getInfosSortie(): ?string
    {
        return $this->infosSortie;
    }

    public function setInfosSortie(?string $infosSortie): static
    {
        $this->infosSortie = $infosSortie;

        return $this;
    }

    public function getOrganisateur(): ?Participant
    {
        return $this->organisateur;
    }

    public function setOrganisateur(?Participant $organisateur): static
    {
        $this->organisateur = $organisateur;

        return $this;
    }

    /**
     * @return Collection<int, Participant>
     */
    public function getParticipants(): Collection
    {
        return $this->participants;
    }

    public function addParticipant(Participant $participant): static
    {
        if (!$this->participants->contains($participant)) {
            $this->participants->add($participant);
            $participant->addSorty($this);
        }

        return $this;
    }

    public function removeParticipant(Participant $participant): static
    {
        if ($this->participants->removeElement($participant)) {
            $participant->removeSorty($this);
        }

        return $this;
    }

    public function getLieu(): ?Lieu
    {
        return $this->lieu;
    }

    public function setLieu(?Lieu $lieu): static
    {
        $this->lieu = $lieu;

        return $this;
    }

    public function getCampusSorties(): ?Campus
    {
        return $this->campus_sorties;
    }

    public function setCampusSorties(?Campus $campus_sorties): static
    {
        $this->campus_sorties = $campus_sorties;

        return $this;
    }

    public function getSortieEtat(): ?Etat
    {
        return $this->sortie_etat;
    }

    public function setSortieEtat(?Etat $sortie_etat): static
    {
        $this->sortie_etat = $sortie_etat;

        return $this;
    }

    public function getMotifAnnulation(): ?string
    {
        return $this->MotifAnnulation;
    }

    public function setMotifAnnulation(?string $MotifAnnulation): static
    {
        $this->MotifAnnulation = $MotifAnnulation;

        return $this;
    }
}

