<?php

namespace App\Controller\Moderator\Game;

use App\Entity\Game;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_MODERATOR')]
class GameToggleVisibilityController extends AbstractController
{
    #[Route('/moderator/game/toggle-visibility/{id}', name: 'app_moderator_toggle_game_visibility', methods: ['POST'], options: ['expose' => true])]
    public function toggleGameVisibility(
        Game $game,
        Request $request,
        EntityManagerInterface $entityManager
    ): Response {
        $token = $request->request->get('_token');
        if (!$this->isCsrfTokenValid('toggle_visibility_' . $game->getId(), $token)) {
            $this->addFlash('error', 'Le jeton de sécurité est invalide. Veuillez réessayer.');
            return $this->redirectToRoute('app_moderator_games');
        }

        $game->setIsHidden(!$game->isHidden());
        $entityManager->flush();

        $status = $game->isHidden() ? 'masquée' : 'visible';
        $this->addFlash('success', sprintf('La fiche du jeu "%s" est maintenant %s.', $game->getTitle(), $status));

        return $this->redirectToRoute('app_moderator_games');
    }
}
