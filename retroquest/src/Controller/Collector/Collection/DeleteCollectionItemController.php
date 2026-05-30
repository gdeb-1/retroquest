<?php

namespace App\Controller\Collector\Collection;

use App\Entity\CollectionItem;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_COLLECTOR')]
class DeleteCollectionItemController extends AbstractController
{
    #[Route('/collector/deleteCollectionItem/{id}', name: 'app_collector_delete_collection_item', methods: ['POST'], options: ['expose' => true])]
    public function deleteCollectionItem(
        CollectionItem $collectionItem,
        Request $request,
        EntityManagerInterface $entityManager
    ): Response {
        /** @var User $user */
        $user = $this->getUser();
        if ($collectionItem->getCollector() !== $user) {
            throw $this->createAccessDeniedException("Vous n'êtes pas autorisé à supprimer ce jeu.");
        }

        $token = $request->request->get('_token');
        if (!$this->isCsrfTokenValid('delete_collection_item', $token)) {
            $this->addFlash('error', 'Le jeton de sécurité est invalide. Veuillez réessayer.');
            return $this->redirectToRoute('app_collector_my_collection');
        }

        $entityManager->remove($collectionItem);
        $entityManager->flush();

        $this->addFlash('success', 'Le jeu a été retiré de votre collection.');

        return $this->redirectToRoute('app_collector_my_collection');
    }
}
