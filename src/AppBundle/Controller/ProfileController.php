<?php

namespace AppBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
class ProfileController extends Controller
{
    /**
     * @Route("/profile", name="profile_account")
     */
    public function accountAction()
    {
        return $this->render('profile/account.html.twig',[
            'error' => null
        ]);
    }
    
    /**
     * @Route("/profile/subscription/cancel", name="account_account_cancel")
     * @Method("POST")
     */
    public function cancelSubscriptionAction()
    {
        $stripeClient = $this->get('stripe.client');
        $stripeSubscription = $stripeClient->cancelSubscription();
        
        $subscription = $this->getUser()->getSubscription();
        if($stripeSubscription->status == 'canceled'){
            $subscription->cancel();
        }else {
            $subscription->deactivateSubscription();            
        }

        $em = $this->getDoctrine()->getManager();
        $em->persist($subscription);
        $em->flush();
        
        $this->addFlash('success','Subscription Cancelled');
        
        return $this->redirectToRoute('profile_account');
    }
     /**
     * @Route("/profile/subscription/reactivate", name="account_account_reactivate")
     * @Method("POST")
     */
    public function reactivateSubscriptionAction(){
        
       $stripeClient = $this->get('stripe.client');
       
       $stripeSubscription = $stripeClient->reactivateSubscription();
       
       $this->get('subscription_helper')->addSubscriptionToUser($stripeSubscription);
       
       $this->addFlash('success','Reactivated subscription');
       
       return $this->redirectToRoute('profile_account');
    }
    
    /**
     * @Route("/profile/card/update", name="account_update_credit_card")
     * @Method("POST")
     */
    public function updateCreditCardAction(Request $request)
    {
        $token = $request->request->get('stripeToken');
        
        try {
            $stripeClient = $this->get('stripe.client');

            $customer = $stripeClient->updateCustomerCard($token);
        } catch (\Stripe\Error\Card $e) {
            $error = 'There was a problem charging your card: '.$e->getMessage();
            
            $this->addFlash('error', $error);
            
            return $this->redirectToRoute('profile_account');
        }
        $this->get('subscription_helper')->updateCardDetails($customer);
        
        $this->addFlash('success', 'Card updated!');
        return $this->redirectToRoute('profile_account');
    }
}
