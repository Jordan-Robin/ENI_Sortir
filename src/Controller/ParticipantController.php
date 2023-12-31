<?php

namespace App\Controller;

use App\Entity\Participant;
use App\Form\ParticipantType;
use Doctrine\ORM\EntityManagerInterface;
use App\Repository\ParticipantRepository;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class ParticipantController extends AbstractController
{
    #[Route('/participant/edit/{id}', name: 'participant.edit', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_USER')]
    public function edit(Request $request, EntityManagerInterface $manager, UserPasswordHasherInterface $hasher, ParticipantRepository $participantRepository, int $id): Response
    {
        $participant = $participantRepository->find($id);
        $campusNames = ['SAINT-HERBLAIN', 'CHARTRES DE BRETAGNE', 'LA ROCHE SUR YON'];
        if (!$this->getUser()) {
            return $this->redirectToRoute('app_login');
        }

        if ($this->getUser() !== $participant) {
            return $this->redirectToRoute('home');
        }

        $form = $this->createForm(ParticipantType::class, $participant);
        $participant->setImageFile(null);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $nouveauMotPasse = $form->get('MotPasse')->getData();
            if ($nouveauMotPasse !== null) {
                $hashedPassword = $hasher->hashPassword($participant, $nouveauMotPasse);
                $participant->setMotPasse($hashedPassword);
            }

            $manager->persist($participant);
            $manager->flush();

            $this->addFlash(
                'success',
                'Vos modifications ont bien été enregistrées.'
            );
        }

        return $this->render('participant/edit.html.twig', [
            'form' => $form->createView(),
            'participant' => $participant,
            'campusNames' => $campusNames,
        ]);
    }


    #[Route('/participant/show', name: 'participant.show')]
    /**
     * @IsGranted({"ROLE_ADMIN", "ROLE_USER"})
     */
    public function show(ParticipantRepository $participantRepository): Response
    {
        $utilisateurConnecte = $this->getUser();
        $participant = $participantRepository->find($utilisateurConnecte->getId());

        return $this->render('participant/show.html.twig', [
            'participant' => $participant,
        ]);
    }
}
