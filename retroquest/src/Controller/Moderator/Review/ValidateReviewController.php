<?php

namespace App\Controller\Moderator\Review;

use App\Entity\Review;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_MODERATOR')]
class ValidateReviewController extends AbstractController
{
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
}
