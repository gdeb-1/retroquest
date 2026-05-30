<?php

namespace App\Controller\Collector\Collection;

use App\Entity\CollectionItem;
use App\Form\CollectionItemType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_COLLECTOR')]
class AddCollectionItemController extends AbstractController
{
    #[Route('/collector/addCollectionItem', name: 'app_collector_add_collection_item', options: ['expose' => true])]
    public function addCollectionItem(
        Request $request,
        EntityManagerInterface $entityManager
    ): Response {
        $collectionItem = new CollectionItem();
        $collectionItem->setCollector($this->getUser());
        $collectionItem->setAcquisitionDate(new \DateTime());

        $form = $this->createForm(CollectionItemType::class, $collectionItem);
        try {
            $form->handleRequest($request);
        } catch (\Exception $e) {
            $this->addFlash('error', 'Une erreur est survenue lors de l\'ajout du jeu à votre collection.');
            return $this->redirectToRoute('app_collector_add_collection_item');
        }

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($collectionItem);
            $entityManager->flush();

            $this->addFlash('success', 'Le jeu a été ajouté à votre collection.');

            return $this->redirectToRoute('app_collector_my_collection');
        }

        return $this->render('collector/add_collection_item.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}
