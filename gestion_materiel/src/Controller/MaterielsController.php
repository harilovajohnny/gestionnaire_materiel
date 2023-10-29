<?php

namespace App\Controller;

use App\Entity\Category;
use App\Entity\Materiel;
use App\Form\MaterielType;
use App\Repository\MaterielRepository;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/materiels')]
class MaterielsController extends AbstractController
{
    #[Route('/', name: 'app_materiels_index', methods: ['GET'])]
    public function index(Request $request, MaterielRepository $materielRepository, PaginatorInterface $paginator): Response
    {
        $entityManager = $this->getDoctrine()->getManager();
        $categoryRepository = $entityManager->getRepository(Category::class);
        $category_value = $categoryRepository->findAll();

        $materiels = $materielRepository->findAll();

        return $this->render('materiels/index.html.twig', [
            'categories' => $category_value,
            'materiels' => $materiels,
        ]);
    }

    // Function pour le filtre de la liste l'equipement par category
    #[Route('/materials-by-category', name: 'api_materials_by_category', methods: ['GET'])]
    public function getMaterialsByCategory(Request $request, MaterielRepository $materielRepository): JsonResponse
    {
        $categoryId = $request->query->get('category_id');

        if ($categoryId) {
            $materiels = $materielRepository->findByCategory($categoryId);
        } else {
            $materiels = $materielRepository->findAll();
        }

        // Convertir les matériaux en un tableau de données JSON
        $materialData = [];

        foreach ($materiels as $materiel) {
            $updatedAt = $materiel->getUpdatedAt();

            // Vérifiez si $updatedAt est null, sinon utilisez sa valeur
            $updatedAtFormatted = null !== $updatedAt ? $updatedAt->format('Y-m-d H:i:s') : null;

            $materialData[] = [
                'id' => $materiel->getId(),
                'name' => $materiel->getName(),
                'category' => $materiel->getCategory(),
                'number' => $materiel->getNumber(),
                'description' => $materiel->getDescription(),
                'createdAt' => $materiel->getCreatedAt()->format('Y-m-d H:i:s'),
                'updatedAt' => $updatedAtFormatted,
            ];
        }

        return $this->json($materialData);
    }

    #[Route('/new', name: 'app_materiels_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $materiel = new Materiel();

        $materiel->setCreatedAt(new \DateTimeImmutable());
        $form = $this->createForm(MaterielType::class, $materiel);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($materiel);
            $entityManager->flush();

            $this->addFlash('success', 'Insertion Réussie');

            return $this->redirectToRoute('app_materiels_index', [], Response::HTTP_SEE_OTHER);
        }

        // Requête pour la Recuperation des données category
        $entityManager = $this->getDoctrine()->getManager();
        $categoryRepository = $entityManager->getRepository(Category::class);
        $category_value = $categoryRepository->findAll();

        return $this->renderForm('materiels/new.html.twig', [
            'materiel' => $materiel,
            'form' => $form,
            'categories' => $category_value,
        ]);
    }

    #[Route('/{id}', name: 'app_materiels_show', methods: ['GET'])]
    public function show(Materiel $materiel): Response
    {
        return $this->render('materiels/show.html.twig', [
            'materiel' => $materiel,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_materiels_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Materiel $materiel, EntityManagerInterface $entityManager): Response
    {
        $materiel->setUpdatedAt(new \DateTimeImmutable());
        $form = $this->createForm(MaterielType::class, $materiel);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            $this->addFlash('success-update', 'modification Réussie');

            return $this->redirectToRoute('app_materiels_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('materiels/edit.html.twig', [
            'materiel' => $materiel,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_materiels_delete', methods: ['POST'])]
    public function delete(Request $request, Materiel $materiel, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$materiel->getId(), $request->request->get('_token'))) {
            $entityManager->remove($materiel);
            $entityManager->flush();
            $this->addFlash('success-delete', 'Suppresion Réussie');
        }

        return $this->redirectToRoute('app_materiels_index', [], Response::HTTP_SEE_OTHER);
    }
}
