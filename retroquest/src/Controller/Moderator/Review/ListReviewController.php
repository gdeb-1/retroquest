<?php

namespace App\Controller\Moderator\Review;

use App\Repository\ReviewRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_MODERATOR')]
class ListReviewController extends AbstractController
{
    #[Route('/moderator/review', name: 'app_moderator_reviews', options: ['expose' => true])]
    public function reviews(ReviewRepository $reviewRepository): Response
    {
        $reviews = $reviewRepository->findBy([], ['createdAt' => 'DESC']);

        return $this->render('moderator/reviews.html.twig', [
            'reviews' => $reviews,
        ]);
    }
}
