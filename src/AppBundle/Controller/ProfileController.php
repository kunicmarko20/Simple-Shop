<?php
namespace AppBundle\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;


class ProfileController extends Controller
{
    /**
     * @Route("/profile", name="profile_account")
     */
    public function accountAction()
    {
        $currentPlan = null;
        $otherPlan = null;
        $otherDurationPlan = null;
        
        if ($this->getUser()->hasActiveSubscription()) {
            $currentPlan = $this->get('subscription_helper')
                ->findPlan($this->getUser()->getSubscription()->getStripePlanId());
            $otherPlan = $this->get('subscription_helper')
                ->findPlanToChangeTo($currentPlan->getPlanId());
            $otherDurationPlan = $this->get('subscription_helper')
                ->findPlanForOtherDuration($currentPlan->getPlanId());
        }
        $invoices = $this->get('stripe.client')
            ->findPaidInvoices($this->getUser());
        
        return $this->render('profile/account.html.twig', [
            'error' => null,
            'currentPlan' => $currentPlan,
            'otherPlan' => $otherPlan,
            'invoices' => $invoices,
            'otherDurationPlan' => $otherDurationPlan,
        ]);
    }
    
    /**
     * @Route("/profile/invoices/{invoiceId}", name="account_invoice_show")
     */
    public function showInvoiceAction($invoiceId)
    {
        $stripeInvoice = $this->get('stripe.client')
            ->findInvoice($invoiceId);
        return $this->render('profile/invoice.html.twig', array(
            'invoice' => $stripeInvoice
        ));
    }
    
    /**
     * @Route("/profile/subscription/cancel", name="account_subscription_cancel")
     * @Method("POST")
     */
    public function cancelSubscriptionAction()
    {
        $stripeClient = $this->get('stripe.client');
        $stripeSubscription = $stripeClient->cancelSubscription($this->getUser());
        $subscription = $this->getUser()->getSubscription();
        if ($stripeSubscription->status == 'canceled') {
            // the subscription was cancelled immediately
            $subscription->cancel();
        } else {
            $subscription->deactivateSubscription();
        }
        $em = $this->getDoctrine()->getManager();
        $em->persist($subscription);
        $em->flush();
        $this->addFlash('success', 'Subscription Canceled :(');
        return $this->redirectToRoute('profile_account');
    }
    /**
     * @Route("/profile/subscription/reactivate", name="account_subscription_reactivate")
     */
    public function reactivateSubscriptionAction()
    {
        $stripeClient = $this->get('stripe.client');
        $stripeSubscription = $stripeClient->reactivateSubscription($this->getUser());
        $this->get('subscription_helper')
            ->addSubscriptionToUser($stripeSubscription, $this->getUser());
        $this->addFlash('success', 'Welcome back!');
        return $this->redirectToRoute('profile_account');
    }
    /**
     * @Route("/profile/card/update", name="account_update_credit_card")
     * @Method("POST")
     */
    public function updateCreditCardAction(Request $request)
    {
        $token = $request->request->get('stripeToken');
        $user = $this->getUser();
        try {
            $stripeClient = $this->get('stripe.client');
            $stripeCustomer = $stripeClient->updateCustomerCard(
                $user,
                $token
            );
        } catch (\Stripe\Error\Card $e) {
            $error = 'There was a problem charging your card: '.$e->getMessage();
            $this->addFlash('error', $error);
            return $this->redirectToRoute('profile_account');
        }
        // save card details!
        $this->get('subscription_helper')
            ->updateCardDetails($user, $stripeCustomer);
        $this->addFlash('success', 'Card updated!');
        return $this->redirectToRoute('profile_account');
    }
    /**
     * @Route("/profile/plan/change/preview/{planId}", name="account_preview_plan_change")
     */
    public function previewPlanChangeAction($planId)
    {
        $plan = $this->get('subscription_helper')
            ->findPlan($planId);
        $stripeInvoice = $this->get('stripe.client')
            ->getUpcomingInvoiceForChangedSubscription(
                $this->getUser(),
                $plan
            );
        
        $currentUserPlan = $this->get('subscription_helper')
            ->findPlan($this->getUser()->getSubscription()->getStripePlanId());
        
        // contains the pro-rations *plus* the next cycle's amount
        $total = $stripeInvoice->amount_due;
        
        // subtract plan price to *remove* next the next cycle's total
        
        if ($plan->getDuration() == $currentUserPlan->getDuration()) {
            // subtract plan price to *remove* next the next cycle's total
            $total -= $plan->getPrice() * 100;
        }
        return new JsonResponse(['total' => $total/100]);
    }
    /**
     * @Route("/profile/plan/change/execute/{planId}", name="account_execute_plan_change")
     * @Method("POST")
     */
    public function changePlanAction($planId)
    {
        $plan = $this->get('subscription_helper')
            ->findPlan($planId);
        $stripeClient = $this->get('stripe.client');
        try {
            $stripeSubscription = $stripeClient->changePlan($this->getUser(), $plan);
        } catch (\Stripe\Error\Card $e) {
            return new JsonResponse([
                'message' => $e->getMessage()
            ], 400);
        }
        // causes the planId to be updated on the user's subscription
        $this->get('subscription_helper')
            ->addSubscriptionToUser($stripeSubscription, $this->getUser());
        return new Response(null, 204);
    }
}