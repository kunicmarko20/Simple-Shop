<?php

namespace AppBundle\Controller;

use AppBundle\Entity\Product;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use \Stripe;

class OrderController extends Controller
{
    
    /**
     * @Route("/cart/product/{slug}", name="order_add_product_to_cart")
     * @Method("POST")
     */
    public function addProductToCartAction(Product $product)
    {
        $this->get('shopping_cart')
            ->addProduct($product);
        
        if ($this->get('security.authorization_checker')->isGranted('ROLE_ADMIN')) {
            $this->addFlash('success', 'Product added!');
        }


        return $this->redirectToRoute('order_checkout');
    }
    /**
     * @Route("/cart/subscription/{planId}", name="order_add_subscription_to_cart")
     */
    public function addSubscriptionToCartAction($planId)
    {
        $subscriptionHelper = $this->get('subscription_helper');
        $plan = $subscriptionHelper->findPlan($planId);
        
        if(!$plan){
            throw $this->createNotFoundException('Plan not found.');
        }
        
        $this->get('shopping_cart')->addSubscription($planId);
        
        return $this->redirectToRoute('order_checkout');
    }
    
    /**
     * @Route("/checkout", name="order_checkout")
     */
    public function checkoutAction(Request $request)
    {
        $products = $this->get('shopping_cart')->getProducts();
        $error = false;
        
        if($request->isMethod('POST')){
            $token = $request->get('stripeToken');
            
            try {
                $this->chargeCustomer($token);
            } catch (Stripe\Error\Card $e) {
                $error = "There was a problem charging your card: ".$e->getMessage();
            }
            if(!$error){
                $this->get('shopping_cart')->emptyCart();
                $this->addFlash('success', 'Order Complete'); 
                return $this->redirectToRoute('homepage');
            }

        }
        
        return $this->render('order/checkout.html.twig', array(
            'products' => $products,
            'cart' => $this->get('shopping_cart'),
            'error' => $error
        ));

    }
    
    public function chargeCustomer($token){
        
        $user = $this->getUser();
        $stripeClient = $this->get('stripe.client');
        $cart = $this->get('shopping_cart');
        $helper = $this->get('subscription_helper');
        if(!$user->getStripeCustomerId()){
            $stripeCustomer = $stripeClient->createCustomer($token);
        } else {
            $stripeCustomer = $stripeClient->updateCustomerCard($token);
        }

        $helper->updateCardDetails($stripeCustomer);
        
        foreach($cart->getProducts() as $product){
               $stripeClient->createInvoiceItem(
                    $product->getPrice()*100,
                    $product->getName()
               );
        }

       if($cart->getSubscriptionPlan()){
            $subscription = $stripeClient->createSubscription(
              $cart->getSubscriptionPlan()
            );

            $helper->addSubscriptionToUser(
                 $subscription
              );
       }else {
          $stripeClient->createInvoice(true);               
       }
       
    }
}