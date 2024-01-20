<?php

namespace App\Controller;

use App\Entity\Product;
use App\Entity\User;
use App\Repository\ClientRepository;
use App\Repository\ProductRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class ProductController extends AbstractController
{
    #[Route(
        path: '/api/products',
        name: 'api_products',
        methods: ['GET']
    )]
    public function getAllProducts(ProductRepository $productRepository, SerializerInterface $serializer, Request $request): JsonResponse
    {
        $page = $request->get('page', 1);
        $limit = $request->get('limit', 3);

        $productList = $productRepository->findAllWithPagination($page, $limit);
        $jsonProductList = $serializer->serialize($productList, 'json', ['groups' => 'getProducts']);
        return new JsonResponse($jsonProductList, Response::HTTP_OK, [], true);
    }

    #[Route(
        path: '/api/products/{id}',
        name: 'api_detailProduct',
        methods: ['GET']
    )]
    public function getDetailProduct(Product $product, SerializerInterface $serializer): JsonResponse
    {
        $jsonProduct = $serializer->serialize($product, 'json', ['groups' => 'getProducts']);
        return new JsonResponse($jsonProduct, Response::HTTP_OK, [], true);
    }


    #[Route(
        path: '/api/products',
        name: 'api_create_product',
        methods: ['POST']
    )]
    #[IsGranted('ROLE_ADMIN', message: 'Vous n\'avez pas les droits suffisants pour créer un produit')]
    public function createProduct(
        Request $request,
        SerializerInterface $serializer,
        EntityManagerInterface $manager,
        UrlGeneratorInterface $urlGenerator,
        ValidatorInterface $validator
    ): JsonResponse
    {
        $product = $serializer->deserialize($request->getContent(), Product::class, 'json');

        //On vérifie les erreurs
        $errors = $validator->validate($product);
        if ($errors->count() > 0) {
            return new JsonResponse($serializer->serialize($errors, 'json'), JsonResponse::HTTP_BAD_REQUEST, [], true);
        }

        $manager->persist($product);
        $manager->flush();

        $jsonProduct = $serializer->serialize($product, 'json', ['groups' => 'getProducts']);

        $location = $urlGenerator->generate('api_detailProduct', ['id' => $product->getId()], UrlGeneratorInterface::ABSOLUTE_URL);

        return new JsonResponse($jsonProduct, Response::HTTP_CREATED, ["Location" => $location], true);
    }

}
