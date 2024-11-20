<?php

namespace App\Controller;

use App\Entity\Campus;
use App\Entity\CsvUserData;
use App\Entity\Participant;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Doctrine\ORM\EntityManagerInterface;
use League\Csv\Reader;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;


 #[Route('/import')]

class AdminController extends AbstractController
{
     private $logger;

     public function __construct(LoggerInterface $logger)
     {
         $this->logger = $logger;
     }
     #[Route('/', name: 'admin_import_users')]
    public function importUsers(
        Request $request,
        ValidatorInterface $validator,
        EntityManagerInterface $entityManager,
         LoggerInterface $logger
    ) {
        $form = $this->createFormBuilder()
            ->add('csvFile', FileType::class, [
                'label' => 'Fichier CSV'
            ])
            ->add('import', SubmitType::class,
                ['label' => 'Importer',
                    'attr' => ['class' => 'btn']
                ])
            ->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $csvFile = $form->get('csvFile')->getData();

            // Cas où aucun fichier n'a été téléchargé
            if (!$csvFile) {
                $this->addFlash('error', 'Aucun fichier CSV n\'a été téléchargé.');
                return $this->redirectToRoute('app_sortie_accueil');
            }

            try {
                $csv = Reader::createFromPath($csvFile->getPathname(), 'r');
                $csv->setHeaderOffset(0);

                foreach ($csv as $row) {
                    $csvUserData = new CsvUserData();
                    $csvUserData->setNom($row['nom']);
                    $csvUserData->setPrenom($row['prenom']);
                    $csvUserData->setTelephone($row['telephone']);
                    $csvUserData->setEmail($row['email']);
                    $csvUserData->setPseudo($row['pseudo']);
                    $csvUserData->setCampus($row['campus']);;
                    $csvUserData->setRoles(json_decode($row['roles'], true));
                    $csvUserData->setActif($row['actif']);
                    $csvUserData->setPhoto($row['photo']);

                    $errors = $validator->validate($csvUserData);

                    if (count($errors) === 0) {
                        // Recherche l'entité Campus correspondante en fonction du nom du campus
                        $campusName = $csvUserData->getCampus();
                        $campusEntity = $entityManager->getRepository(Campus::class)->findOneBy(['nom' => $campusName]);

                        if ($campusEntity) {
                            $participant = new Participant();
                            $participant->setNom($csvUserData->getNom());
                            $participant->setPrenom($csvUserData->getPrenom());
                            $participant->setTelephone($csvUserData->getTelephone());
                            $participant->setEmail($csvUserData->getEmail());
                            $participant->setPseudo($csvUserData->getPseudo());
                            $participant->setCampus($campusEntity);
                            $participant->setRoles($csvUserData->getRoles());
                            $participant->setActif($csvUserData->isActif());
                            $participant->setPhoto($csvUserData->getPhoto());

                            // Génére un mot de passe aléatoire
                            $password = password_hash('motdepasse', PASSWORD_DEFAULT);
                            $participant->setPassword($password);

                            $entityManager->persist($participant);
                        } else {
                            $this->addFlash('error', 'Aucun campus correspondant trouvé pour : ' . $campusName);
                        }
                    }
                }

                $entityManager->flush();

                $this->addFlash('success', 'Utilisateurs importés avec succès.');
                return $this->redirectToRoute('app_sortie_accueil');
            } catch (\Exception $e) {
                $this->addFlash('error', 'Une erreur s\'est produite lors de l\'importation du fichier CSV : ' . $e->getMessage());
                // Exception pour avoir plus de détails sur l'erreur.
                $this->logger->error('Erreur d\'importation CSV : ' . $e->getMessage());
                return $this->redirectToRoute('app_sortie_accueil');
            }
        }


        return $this->render('admin/import_users.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}
