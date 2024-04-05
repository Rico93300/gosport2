<?php

namespace App\Controller;

use App\Repository\ProductRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/cart')]
class CartController extends AbstractController
{
    public function __construct(private RequestStack $requestStack)
    {
    }

    #[Route('/', name: 'app_cart_index')]
    public function index(ProductRepository $productRepository): Response
    {

        $cart =  $this->getCart();
        $cartProducts = [];
        foreach ($cart as $productId => $quantity) {
            $product = $productRepository->find($productId);
            $cartProducts[] = [
                "product" => $product,
                "quantity" => $quantity
            ];
        }


        // dd($cartProducts);

        return $this->render('cart/index.html.twig', [
            'cart' => $cartProducts
        ]);
    }

    /**
     * method to increase quantity of product in cart
     * @return Response
     */
    #[Route('/increase/{id}', name: 'app_cart_increase')]
    public function increaseQuantity(int $id): Response
    {
        $cart = $this->getCart();
        // rajoute le produit en session si il n'existe pas , ou ajoute +1 a sa quantité si il existe
        $cart[$id] = isset($cart[$id]) ? $cart[$id] + 1 : 1;

        $this->setCart($cart);

        return $this->redirectToRoute('app_cart_index');
    }

    /**
     * method to decrease quantity of product in cart ,
     * @return Response
     */
    #[Route('/decrease/{id}', name: 'app_cart_decrease')]
    public function decreaseQuantity(int $id): Response
    {
        $cart = $this->getCart();

        if (isset($cart[$id])) {
            // si le produit existe en panier on reduit la quantité de 1
            $cart[$id] -= 1;
            // si la quantite est a zero on supprime le produit de la session
            if ($cart[$id] === 0) {
                unset($cart[$id]);
            }
        }

        $this->setCart($cart);

        return $this->redirectToRoute('app_cart_index');
    }

    /**
     * method to remove a product in cart
     * @return Response
     */
    #[Route('/remove/{id}', name: 'app_cart_remove')]
    public function remove(int $id): Response
    {
        // on recupere le tableau du panier
        $cart = $this->getCart();
        // on supprime l'element du tableau
        unset($cart[$id]);
        //on revois le panier mis a jour en session
        $this->setCart($cart);

        return $this->redirectToRoute('app_cart_index');
    }

    /**
     * method to flush the cart
     *
     * @return Response
     */
    #[Route('/flush', name: 'app_cart_flush')]
    public function flush(): Response
    {
        $this->setCart([]);

        return $this->redirectToRoute('app_cart_index');
    }

    /**
     * method to get the cart from the session
     *
     * @return array
     */
    private function getCart(): array
    {
        $session = $this->requestStack->getSession();

        $cart = $session->get('cart', []);

        return $cart;
    }

    /**
     * method to set the cart in the session
     *
     * @param array $cart
     * @return void
     */
    private function setCart(array $cart): void
    {
        $session = $this->requestStack->getSession();

        $session->set('cart', $cart);
    }
}
