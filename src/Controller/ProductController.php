<?php

namespace App\Controller;

use App\Entity\Product;
use App\Form\ProductType;
use App\Form\SearchingType;
use App\Repository\ProductRepository;
use App\Repository\CategoryRepository;
use Doctrine\ORM\EntityManagerInterface;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[Route('/product')]
class ProductController extends AbstractController
{
    #[Route('/', name: 'app_product_index')]
    public function index(
        ProductRepository $productRepository,
        CategoryRepository $categoryRepository,
        Request $request
    ): Response {



        // on change la methode du formulaire en GET car le 
        // formulaire de base sur symfony est par defaut en methode POST
        //le changement de la methode est necessaire car on dois pouvoir concerver 
        // le contenue de la recherche dans l'url
        $form = $this->createForm(SearchingType::class, null, ['method' => 'GET']);
        $form->handleRequest($request);

        $products = $productRepository->findAll();
        $categories = $categoryRepository->findAll();

        if ($form->isSubmitted() && $form->isValid()) {
            $searchText = $form->get('text')->getData();
            $products = $productRepository->search($searchText);
        }

        if ($request->query->get('id') !== null) {
            $categoryId = $request->query->get('id');
            $products = $productRepository->findBy(['category' => $categoryId], ['id' => 'DESC']);
        }







        // if ($request->query->has('id')) {
        //     $categoryId = $request->query->get('id');
        //     $products = $productRepository->findByCategory($categoryId);
        // }


        return $this->render('product/index.html.twig', [
            'form' => $form,
            'products' => $products,
            'categories' => $categories
        ]);
    }


    #[Route('/new', name: 'app_product_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, CategoryRepository $categoryRepository): Response
    {

        if ($categoryRepository->findAll() == []) {
            $this->addFlash('warning', "Il n'y a pas de categorie,veuillez en ajouter une avant de creer un produit");
            return $this->redirectToRoute('app_category_new');
        }



        $product = new Product();
        $product->setReference(uniqid());


        $form = $this->createForm(ProductType::class, $product);
        $form->handleRequest($request);



        if ($form->isSubmitted() && $form->isValid()) {


            $image = $form->get('image')->getData();
            if ($image != null) {
                $imageName = uniqid() . '.' . $image->guessExtension();
                $product->setImage($imageName);;

                $image->move($this->getParameter('product_image_directory'), $imageName);
            }


            $entityManager->persist($product);
            $entityManager->flush();

            return $this->redirectToRoute('app_product_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('product/new.html.twig', [
            'product' => $product,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_product_show', methods: ['GET'])]
    public function show(Product $product): Response
    {
        return $this->render('product/show.html.twig', [
            'product' => $product,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_product_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Product $product, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(ProductType::class, $product);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $image = $form->get('image')->getData();
            // je verifie qu'une nouvelle image a ete envoyé avec le formulaire 
            if ($image != null) {
                // je verifie l'existance d'une encienne image au produit 
                // si c'est le cas je supprime l'ancienne image 
                if ($product->getImage() != null && file_exists($this->getParameter('product_image_directory') . $product->getImage())) {
                    unlink($this->getParameter('product_image_directory') . $product->getImage());
                }

                // puis je telechager la nouvelle image et change le nom de l'image en base de donnees

                $imgName = uniqid() . '.' . $image->guessExtension();
                $product->setImage($imgName);
                $image->move($this->getParameter('product_image_directory'), $imgName);
            }





            $entityManager->persist($product);
            $entityManager->flush();

            return $this->redirectToRoute('app_product_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('product/edit.html.twig', [
            'product' => $product,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_product_delete', methods: ['POST'])]
    public function delete(Request $request, Product $product, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete' . $product->getId(), $request->getPayload()->get('_token'))) {

            if ($product->getImage() != null && file_exists($this->getParameter('product_image_directory') . $product->getImage())) {
                unlink($this->getParameter('product_image_directory') . $product->getImage());
            }
            $entityManager->remove($product);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_product_index', [], Response::HTTP_SEE_OTHER);
    }
}
