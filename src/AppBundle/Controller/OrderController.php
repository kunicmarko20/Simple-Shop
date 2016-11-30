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
    
    public function chargeCustomer($token)
    {
        if (!$token && $this->get('shopping_cart')->getTotalWithDiscount() > 0) {
            throw new \Exception('Somehow the order is non-free, but we have no token!?');
        }
        
        $user = $this->getUser();
        $stripeClient = $this->get('stripe.client');
        $cart = $this->get('shopping_cart');
        $helper = $this->get('subscription_helper');
        if(!$user->getStripeCustomerId()){
            $stripeCustomer = $stripeClient->createCustomer($user, $token);
        } else {
            // don't need to update it if the order is free
            if ($token) {
                $stripeCustomer = $stripeClient->updateCustomerCard($user, $token);
            } else {
                $stripeCustomer = $stripeClient->findCustomer($user);
            }
        }

        $helper->updateCardDetails($user, $stripeCustomer);

        if ($cart->getCouponCodeValue()) {
            $stripeCustomer->coupon = $cart->getCouponCode();
            $stripeCustomer->save();
        }
        
        foreach($cart->getProducts() as $product){
               $stripeClient->createInvoiceItem(
                    $product->getPrice()*100,
                    $user,
                    $product->getName()
               );
        }

       if($cart->getSubscriptionPlan()){
            $subscription = $stripeClient->createSubscription(
              $user,
              $cart->getSubscriptionPlan()
            );

            $helper->addSubscriptionToUser(
                $subscription,
                $user
            );
       }else {
          $stripeClient->createInvoice($user, true);               
       }
       
    }
    
    /**
     * @Route("/checkout/coupon", name="order_add_coupon")
     * @Method("POST")
     */
    public function addCouponAction(Request $request)
    {
        $code = $request->request->get('code');
        if (!$code) {
            $this->addFlash('error', 'Missing coupon code!');
            return $this->redirectToRoute('order_checkout');
        }
        
        try {
            $stripeCoupon = $this->get('stripe.client')
                ->findCoupon($code);
        } catch (\Stripe\Error\InvalidRequest $e) {
            $this->addFlash('error', 'Invalid coupon code!');
            return $this->redirectToRoute('order_checkout');
        }
        
        if (!$stripeCoupon->valid) {
            $this->addFlash('error', 'Coupon expired');
            return $this->redirectToRoute('order_checkout');
        }
        
        $this->get('shopping_cart')
            ->setCouponCode($code, $stripeCoupon->amount_off / 100);
        
        $this->addFlash('success', 'Coupon applied!');
        
        return $this->redirectToRoute('order_checkout');
        
    }
}