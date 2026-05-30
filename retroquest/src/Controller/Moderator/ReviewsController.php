<?php

namespace App\Controller\Moderator;

use App\Entity\Review;
use App\Repository\ReviewRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_MODERATOR')]
class ReviewsController extends AbstractController
{
    #[Route('/moderator/review', name: 'app_moderator_reviews', options: ['expose' => true])]
    public function reviews(ReviewRepository $reviewRepository): Response
    {
        $reviews = $reviewRepository->findBy([], ['createdAt' => 'DESC']);

        return $this->render('moderator/reviews.html.twig', [
            'reviews' => $reviews,
        ]);
    }

    #[Route('/moderator/review/validate/{id}', name: 'app_moderator_validate_review', methods: ['POST'], options: ['expose' => true])]
    public function validateReview(
        Review $review,
        Request $request,
        EntityManagerInterface $entityManager
    ): Response {
        $token = $request->request->get('_token');
        if (!$this->isCsrfTokenValid('validate_review_' . $review->getId(), $token)) {
            $this->addFlash('error', 'Le jeton de sécurité est invalide. Veuillez réessayer.');
            return $this->redirectToRoute('app_moderator_reviews');
        }
        $review->setIsValid(true);
        $entityManager->flush();
        $this->addFlash('success', 'L\'avis a été validé avec succès.');
        return $this->redirectToRoute('app_moderator_reviews');
    }

    #[Route('/moderator/review/delete/{id}', name: 'app_moderator_delete_review', methods: ['POST'], options: ['expose' => true])]
    public function deleteReview(
        Review $review,
        Request $request,
        EntityManagerInterface $entityManager
    ): Response {
        $token = $request->request->get('_token');
        if (!$this->isCsrfTokenValid('delete_review_' . $review->getId(), $token)) {
            $this->addFlash('error', 'Le jeton de sécurité est invalide. Veuillez réessayer.');
            return $this->redirectToRoute('app_moderator_reviews');
        }
        $entityManager->remove($review);
        $entityManager->flush();
        $this->addFlash('success', 'L\'avis a été supprimé avec succès.');
        return $this->redirectToRoute('app_moderator_reviews');
    }
}
