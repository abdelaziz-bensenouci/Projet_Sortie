<?php

namespace App\Controller;

use App\Entity\Participant;
use App\Form\ParticipantType;
use App\Repository\CampusRepository;
use App\Repository\ParticipantRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;


class ParticipantController extends AbstractController
{
    #[Route('/participant', name: 'app_participant_index', methods: ['GET'])]
    public function index(ParticipantRepository $participantRepository): Response
    {
        return $this->render('participant/index.html.twig', [
            'participants' => $participantRepository->findAll(),
        ]);
    }

    #[Route(path: '/', name: 'app_login')]
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        // get the login error if there is one
        $error = $authenticationUtils->getLastAuthenticationError();
        // last username entered by the user
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('security/login.html.twig', ['last_username' => $lastUsername, 'error' => $error]);
    }

    #[Route(path: '/logout', name: 'app_logout')]
    public function logout(): void
    {
        throw new \LogicException('This method can be blank - it will be intercepted by the logout key on your firewall.');
    }

    #[Route('/participant/action_groupe', name: 'app_participant_actiongroupe', methods: ['POST'])]
    public function actionGroupe(Request $request, EntityManagerInterface $entityManager): Response
    {
        $action = $request->request->get('action');
        $participantsIds = $_POST['participants'];


        if ($action === 'delete') {

            $message = 'Participants supprimés : ';
            foreach ($participantsIds as $participantId) {
                $participant = $entityManager->getRepository(Participant::class)->find($participantId);
                if ($participant) {
                    $entityManager->remove($participant);
                    $message.=$participant->getPseudo() .', ';
                }
            }
            $entityManager->flush();
            $this->addFlash('danger', $message);

        } elseif ($action === 'deactivate') {

            $message = 'Participants désactivés : ';
            foreach ($participantsIds as $participantId) {
                $participant = $entityManager->getRepository(Participant::class)->find($participantId);
                if ($participant) {
                    $participant->setActif(0);
                    $entityManager->persist($participant);
                    $message.=$participant->getPseudo() .', ';
                }
            }
            $entityManager->flush();
            $this->addFlash('danger', $message);

        }

        return $this->redirectToRoute('app_participant_index');
    }

    #[Route('/participant/new', name: 'app_participant_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, UserPasswordHasherInterface $passwordHasher): Response
    {
        $participant = new Participant();
        $form = $this->createForm(ParticipantType::class, $participant);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // hydrate les propriétés absentes du formulaires
            // roles - actif - sorties

            // Vérifier que le mdp == confirmation de mdp
            $password = $form->get('password')->getData();
            $confirmPassword = $form->get('password')['second']->getData();

            if ($password === $confirmPassword) {
                // Hash le mdp
                $hashedPassword = $passwordHasher->hashPassword($participant, $participant->getPassword());
                $participant->setPassword($hashedPassword);

                // Ajout ROLE par défaut
                $participant->setRoles(["ROLE_USER"]);

                // Ajout actif par défaut
                $participant->setActif(1);

                // Gérer le téléchargement de la photo
                $photoFile = $form->get('photo')->getData();
                if ($photoFile instanceof UploadedFile) {
                    $newFilename = uniqid() . '.' . $photoFile->guessExtension();
                    try {
                        $photoFile->move(
                            $this->getParameter('img_directory'),
                            $newFilename
                        );
                    } catch (FileException $e) {
                        // mess d'erreur
                    }
                    $participant->setPhoto($newFilename);
                }

                $entityManager->persist($participant);
                $entityManager->flush();
                $this->addFlash('success', 'Participant créé!');

                return $this->redirectToRoute('app_participant_show', ['id' => $participant->getId()],
                    Response::HTTP_SEE_OTHER);
            } else {
                // Les mots de passe ne correspondent pas, affichez une erreur
                $form->get('confirmPassword')->addError(new FormError('Les mots-de-passe ne sont pas identiques!'));
            }
        }

        return $this->render('participant/new.html.twig', [
            'participant' => $participant,
            'form' => $form,
        ]);
    }

    #[Route('/participant/{id}/edit', name: 'app_participant_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Participant $participant, EntityManagerInterface $entityManager, UserPasswordHasherInterface $passwordHasher): Response
    {
        if ($participant->getId() != $this->getUser()->getId() && !$this->isGranted("ROLE_ADMIN")) {
            $this->addFlash('warning', 'Vous ne pouvez modifier que votre compte !');
            return $this->redirectToRoute('app_participant_show', ['id' => $this->getUser()->getId()],
                Response::HTTP_SEE_OTHER);
        }

        $form = $this->createForm(ParticipantType::class, $participant);
        $form->handleRequest($request);

        // penser à hash le mdp avant envoi
        if ($form->isSubmitted() && $form->isValid()) {
            // Vérifier que le mdp == confirmation de mdp
            $password = $form->get('password')->getData();
            $confirmPassword = $form->get('password')['second']->getData();

            if ($password === $confirmPassword) {
                // Hash le mdp
                $hashedPassword = $passwordHasher->hashPassword($participant, $participant->getPassword());
                $participant->setPassword($hashedPassword);

                // Gérer le téléchargement de la photo
                $photoFile = $form->get('photo')->getData();
                if ($photoFile instanceof UploadedFile) {
                    $newFilename = uniqid() . '.' . $photoFile->guessExtension();
                    try {
                        $photoFile->move(
                            $this->getParameter('img_directory'),
                            $newFilename
                        );
                    } catch (FileException $e) {
                        // mess d'erreur
                    }
                    $participant->setPhoto($newFilename);
                }
                $entityManager->flush();
                $this->addFlash('success', 'Modification prise en compte!');
                return $this->redirectToRoute('app_participant_show', ['id' => $participant->getId()],
                    Response::HTTP_SEE_OTHER);
            } else {
                // Les mots de passe ne correspondent pas, affichez une erreur
                $form->get('confirmPassword')->addError(new FormError('Les mots-de-passe ne sont pas identiques!'));
            }
        }

        return $this->render('participant/edit.html.twig', [
            'participant' => $participant,
            'form' => $form,
        ]);
    }

    #[Route('/participant/{id}/delete', name: 'app_participant_delete', methods: ['POST'])]
    public function delete(Request $request, Participant $participant, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$participant->getId(), $request->request->get('_token'))) {
            $entityManager->remove($participant);
            $entityManager->flush();
            $this->addFlash('success', 'Participant supprimé!');
        }

        return $this->redirectToRoute('app_participant_index', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/participant/{id}', name: 'app_participant_show', methods: ['GET'])]
    public function show(Request $request, Participant $participant, CampusRepository $campusRepository): Response
    {
        $searchTerm = $request->query->get('search');

        if ($searchTerm) {
            $campus = $campusRepository->findByNom($searchTerm);
        } else {
            $campus = $campusRepository->findAll();
        }

        return $this->render('participant/show.html.twig', [
            'participant' => $participant,
            'campus' => $campus,
            'searchTerm' => $searchTerm,
        ]);
}

}



