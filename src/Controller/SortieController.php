<?php

namespace App\Controller;

use App\Repository\VilleRepository;
use App\Entity\Campus;
use App\Entity\Etat;
use App\Entity\Lieu;
use App\Entity\Sortie;
use App\Form\AnnulationType;
use App\Form\LieuType;
use App\Form\SortieType;
use App\Repository\CampusRepository;
use App\Repository\SortieRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Constraints\Json;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/sortie')]
class SortieController extends AbstractController
{
    #[Route('/', name: 'app_sortie_accueil', methods: ['GET'])]
    public function acceuil(Request $request, SortieRepository $sortieRepository, CampusRepository $campusRepository, Security $security): Response
    {
        $searchTerm = $request->query->get('search');
        $dateDebut = $request->query->get('dateDebut');
        $dateFin = $request->query->get('dateFin');

        $selectedCampusId = $request->query->get('campus', 0);

        $organisateur = $request->query->get('organisateur');
        $user = $security->getUser();
        $inscrit = $request->query->get('inscrit');
        $nonInscrit = $request->query->get('non_inscrit');
        $passees = $request->query->get('passees');

        $sorties = $sortieRepository->findSortiesBySearchWithFilters(
            $searchTerm,
            $dateDebut,
            $dateFin,
            $selectedCampusId,
            $organisateur,
            $inscrit,
            $nonInscrit,
            $passees,
            $user
        );

        //CRÉER une instance de 'DateTime' représentant la date et l'heure actuelles
        $dateUnMoisAvant = new \DateTime();

        //SOUSTRAIT une période d'un mois à la date actuelle (calcule la date d'il y a un mois).
        $dateUnMoisAvant->sub(new \DateInterval('P1M'));

        //FILTRE le tableau des sorties pour exclure celles dont la date de début est antérieure à la date calculée, (c'est-à-dire, les sorties du dernier mois).
        $sorties = array_filter($sorties, function ($sortie) use ($dateUnMoisAvant) {
            return $sortie->getDateHeureDebut() > $dateUnMoisAvant;
        });

        $campuses = $campusRepository->findAll();

        return $this->render('sortie/accueil.html.twig', [
            'sorties' => $sorties,
            'searchTerm' => $searchTerm,
            'dateDebut' => $dateDebut,
            'dateFin' => $dateFin,
            'selectedCampusId' => $selectedCampusId,
            'campus' => $campuses,
        ]);
    }

    #[Route('/creer', name: 'app_sortie_creersortie', methods: ['GET', 'POST'])]
    public function creerSortie(Request $request, EntityManagerInterface $entityManager): Response
    {
        $participant = $this->getUser();
        $sortie = new Sortie();
        $organisateur = $this->getUser();
        $sortie->setOrganisateur($organisateur);

        // Récupérez le campus de l'utilisateur connecté
        $campus = $participant->getCampus();
        $sortie->setCampusSorties($campus);

        $form = $this->createForm(SortieType::class, $sortie);
        $form->handleRequest($request);

        // Créez le formulaire de lieu
        $lieu = new Lieu();
        $lieuForm = $this->createForm(LieuType::class, $lieu);

        if ($form->isSubmitted() && $form->isValid()) {
            $action = $request->request->get('action');

            if ($action === 'enregistrer') {
                // Etat sur "Créée" si le bouton "Enregistrer" est cliqué (faudra demander confirmation à Hervé)
                $etatCreee = $entityManager->getRepository(Etat::class)->findOneBy(['libelle' => 'Créée']);
                $sortie->setSortieEtat($etatCreee);
            } elseif ($action === 'publier') {
                // Etat sur "Ouverte" si le bouton "Publier la sortie" est cliqué
                $etatOuverte = $entityManager->getRepository(Etat::class)->findOneBy(['libelle' => 'Ouverte']);
                $sortie->setSortieEtat($etatOuverte);
            }

            $entityManager->persist($sortie);
            $entityManager->flush();

            return $this->redirectToRoute('app_sortie_accueil', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('sortie/creer_sortie.html.twig', [
            'sortie' => $sortie,
            'form' => $form->createView(),
            'lieuForm' => $lieuForm->createView(),
            'participant' => $participant,
        ]);
    }


    #[Route('/details/{id}', name: 'app_sortie_details', methods: ['GET'])]
    public function details(Sortie $sortie): Response
    {
        return $this->render('sortie/details.html.twig', [
            'sortie' => $sortie,
        ]);
    }

    #[Route('/{id}/modification', name: 'app_sortie_modification', methods: ['GET', 'POST'])]
    public function modification(Request $request, Sortie $sortie, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(SortieType::class, $sortie);
        $form->handleRequest($request);

        //VÉRIFICATION de l'organisateur
        $organisateur = $this->getUser();
        if ($sortie->getOrganisateur() !== $organisateur && !$this->isGranted("ROLE_ADMIN")) {
            $this->addFlash('warning','Vous n\'êtes pas autorisé à modifier cette sortie.');
            return $this->redirectToRoute('app_sortie_accueil', [], Response::HTTP_SEE_OTHER);
        }

        //VÉRIFICATION de la date de début de la sortie
        $dateHeureDebut = $sortie->getDateHeureDebut();
        $heureActuelle = new \DateTime('now');
        if ($dateHeureDebut <= $heureActuelle) {
            $this->addFlash('warning','La sortie a déjà commencé ou passée');
            return $this->redirectToRoute('app_sortie_accueil', [], Response::HTTP_SEE_OTHER);
        }

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_sortie_accueil', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('sortie/modification.html.twig', [
            'sortie' => $sortie,
            'form' => $form,
        ]);
    }

    #[Route('/annulation/{id}', name: 'app_sortie_annulation', methods: ['GET', 'POST'])]
    public function annulation(Request $request, Sortie $sortie, EntityManagerInterface $entityManager): Response
    {
        //VÉRIFICATION de l'organisateur
        $organisateur = $this->getUser();
        if ($sortie->getOrganisateur() !== $organisateur && !$this->isGranted("ROLE_ADMIN")) {
            $this->addFlash('warning', 'Vous n\'êtes pas autorisé à annuler cette sortie.');
            return $this->redirectToRoute('app_sortie_accueil', [], Response::HTTP_SEE_OTHER);
        }

        //VÉRIFICATION de la date de début de la sortie
        $dateHeureDebut = $sortie->getDateHeureDebut();
        $heureActuelle = new \DateTime('now');
        if ($dateHeureDebut <= $heureActuelle) {
            $this->addFlash('warning', 'La sortie a déjà commencé ou passée');
            return $this->redirectToRoute('app_sortie_accueil', [], Response::HTTP_SEE_OTHER);
        }

        //DÉFINIT par défaut l'état sur "Annulée"
        $etatAnnulee = $entityManager->getRepository(Etat::class)->findOneBy(['libelle' => 'Annulée']);
        $sortie->setSortieEtat($etatAnnulee);


        //CRÉER le formulaire d'annulation
        $form = $this->createForm(AnnulationType::class, $sortie);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $motifAnnulation = $form->get('motifAnnulation')->getData();

            $sortie->setMotifAnnulation($motifAnnulation);

            $entityManager->flush();

            return $this->redirectToRoute('app_sortie_accueil', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('sortie/annulation.html.twig', [
            'sortie' => $sortie,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_sortie_supprimer', methods: ['POST'])]
    public function supprimer(Request $request, Sortie $sortie, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete' . $sortie->getId(), $request->request->get('_token'))) {
            $entityManager->remove($sortie);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_sortie_accueil', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/inscription/{id}', name: 'app_sortie_inscription', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function inscription(Sortie $sortie, EntityManagerInterface $entityManager): Response
    {
        //RÉCUPÈRE l'utilisateur actuellement connecté
        $participant = $this->getUser();

        //VÉRIFIE si l'utilisateur est bel et bien connecté
        if ($participant !== null) {
            $sortieEtat = $sortie->getSortieEtat();

            $dateActuelle = new \DateTime();

            if ($sortieEtat !== null &&
                $sortieEtat->getLibelle() === 'Ouverte' &&
                $sortie->getDateHeureDebut() > $dateActuelle &&
                $sortie->getDateLimiteInscription() > $dateActuelle) {

                //VÉRIFIE si l'utilisateur n'est pas déjà inscrit à la sortie
                if (!$sortie->getParticipants()->contains($participant)) {

                    //AJOUTE l'utilisateur à la liste des participants de la sortie
                    $sortie->addParticipant($participant);

                    //ENREGISTRE et APPLIQUE les modifications de l'entité 'Participant' dans la base de données
                    $entityManager->persist($participant);
                    $entityManager->flush();

                    $this->addFlash('success', 'Inscription réussie!');
                } else {
                    $this->addFlash('warning', 'Vous êtes déjà inscrit à cette sortie.');
                }
            } else {
                $this->addFlash('warning', 'La sortie n\'est pas ouverte aux inscriptions.');
            }
        }

        return $this->redirectToRoute('app_sortie_accueil', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/desister/{id}', name: 'app_sortie_desister', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function desister(Sortie $sortie, EntityManagerInterface $entityManager): Response
    {
        //RÉCUPÈRE l'utilisateur actuellement connecté
        $participant = $this->getUser();

        //VÉRIFIE si l'utilisateur est bel et bien connecté
        if ($participant !== null) {

            //VÉRIFIE si l'utilisateur est inscrit à la sortie
            if ($sortie->getParticipants()->contains($participant)) {

                //SUPPRIME l'utilisateur à la liste des participants de la sortie
                $sortie->removeParticipant($participant);

                $entityManager->flush();

                $this->addFlash('success', 'Vous vous êtes désisté de cette sortie avec succès.');
            } else {
                $this->addFlash('warning', 'Vous n\'êtes pas inscrit à cette sortie.');
            }
        }

        return $this->redirectToRoute('app_sortie_accueil', [], Response::HTTP_SEE_OTHER);
    }
    #[Route('/lieux-par-ville/{id}', name: 'app_lieux_par_ville', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function lieuxParVille(Request $request, EntityManagerInterface $entityManager): JsonResponse
    {

        $villeId = $request->attributes->get('id');

        $lieuxRepository = $entityManager->getRepository(Lieu::class);
        $lieux = $lieuxRepository->findBy(['ville' => $villeId]);

        $lieuxData=[];
        foreach ($lieux as $lieu) {
            $lieuxData[] = [
                'id' => $lieu->getId(),
                'nom' => $lieu->getNom(),
            ];
        }

        // Retournez la liste des lieux au format JSON
        return $this->json($lieuxData);
        //return new JsonResponse($lieuxData);
    }

    #[Route('/lieu-details/{id}', name: 'app_lieu_details', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function lieuDetails(Request $request, EntityManagerInterface $entityManager): JsonResponse
    {
        $lieuId = $request->attributes->get('id');

        $lieuRepository = $entityManager->getRepository(Lieu::class);
        $lieu = $lieuRepository->find($lieuId);

        if (!$lieu) {
            return new JsonResponse(['error' => 'Lieu non trouvé'], Response::HTTP_NOT_FOUND);
        }

        $lieuData = [
            'rue' => $lieu->getRue(),
            'latitude' => $lieu->getLatitude(),
            'longitude' => $lieu->getLongitude(),
            'code_postal' => $lieu->getCodePostal(),
            //'campus' => $lieu->getCampus(),
        ];

        // Retournez les détails du lieu au format JSON
        return $this->json($lieuData);
    }
    #[Route('/lieu/creer', name: 'app_lieu_creer', methods: ['POST'])]
    public function creerLieu(Request $request, EntityManagerInterface $entityManager, ValidatorInterface $validator): JsonResponse
    {
        $lieu = new Lieu();
        $lieuForm = $this->createForm(LieuType::class, $lieu);
        // Gérez la soumission du formulaire
        $lieuForm->handleRequest($request);
        if ($lieuForm->isSubmitted() && $lieuForm->isValid()) {
            // Si le formulaire est valide, persistez le lieu en base de données
            $entityManager->persist($lieu);
            $entityManager->flush();
            // Retournez une réponse JSON indiquant le succès de l'opération
            return $this->json(['success' => true], Response::HTTP_CREATED);
        } else {
            // Si le formulaire n'est pas valide, récupérez les erreurs de validation
            $errors = [];

            foreach ($validator->validate($lieu) as $error) {
                $errors[] = $error->getMessage();
            }
            // Retournez une réponse JSON avec les erreurs de validation
            return $this->json(['errors' => $errors], Response::HTTP_BAD_REQUEST);
        }
    }

}

