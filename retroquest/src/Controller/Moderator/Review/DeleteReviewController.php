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
class DeleteReviewController extends AbstractController
{
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
