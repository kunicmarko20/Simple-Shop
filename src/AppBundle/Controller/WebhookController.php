<?php
namespace AppBundle\Controller;

use AppBundle\Entity\StripeEventLog;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class WebhookController extends Controller
{
    private $em;

    /**
     * @Route("/webhooks/stripe", name="webhook_stripe")
     */
    public function stripeWebhookAction(Request $request)
    {
        $data = json_decode($request->getContent(), true);
        if ($data === null) {
            throw new \Exception('Bad JSON body from Stripe!');
        }
        $eventId = $data['id'];
        
        $this->em = $this->getDoctrine()->getManager();
        
        if ($this->checkLogs($eventId)) {
            return new Response('Event previously handled');
        }
        $this->updateLogs($eventId);
        
        $stripeEvent = $this->get('stripe_client')
            ->findEvent($eventId);
        
        $this->handleEvent($stripeEvent);
        
        return new Response('Event Handled: '.$stripeEvent->type);
    }
    /**
     * @param $stripeSubscriptionId
     * @return Subscription
     * @throws \Exception
     */
    private function findSubscription($stripeSubscriptionId)
    {
        $subscription = $this->getDoctrine()
            ->getRepository('AppBundle:Subscription')
            ->findOneBy([
                'stripeSubscriptionId' => $stripeSubscriptionId
            ]);
        if (!$subscription) {
            throw new \Exception('Somehow we have no subscription id ' . $stripeSubscriptionId);
        }
        return $subscription;
    }
    
    private function checkLogs($eventId){
        return $this->em->getRepository('AppBundle:StripeEventLog')
            ->findOneBy(['stripeEventId' => $eventId]);
    }
    
    private function updateLogs($eventId){
        $log = new StripeEventLog($eventId);
        $this->em->persist($log);
        $this->em->flush($log);
    }
    
    private function handleEvent($stripeEvent){
        $subscriptionHelper = $this->get('subscription_helper');
        
        switch ($stripeEvent->type) {
            case 'customer.subscription.deleted':
                $stripeSubscriptionId = $stripeEvent->data->object->id;
                $subscription = $this->findSubscription($stripeSubscriptionId);
                $subscriptionHelper->fullyCancelSubscription($subscription);
                break;
            case 'invoice.payment_failed':
                $stripeSubscriptionId = $stripeEvent->data->object->subscription;
                if ($stripeSubscriptionId) {
                    $subscription = $this->findSubscription($stripeSubscriptionId);
                    if ($stripeEvent->data->object->attempt_count == 1) {
                        $user = $subscription->getUser();
                        $stripeCustomer = $this->get('stripe_client')
                            ->findCustomer($user->getStripeCustomerId());
                        
                        $hasCardOnFile = count($stripeCustomer->sources->data) > 0;
                        
                        // todo - send the user an email about the problem
                        // use hasCardOnFile to customize this
                    }
                }
            break;
            case 'invoice.payment_succeeded':
                $stripeSubscriptionId = $stripeEvent->data->object->subscription;
                
                if ($stripeSubscriptionId) {
                    
                    $subscription = $this->findSubscription($stripeSubscriptionId);
                    
                    $stripeSubscription = $this->get('stripe_client')
                        ->findSubscription($stripeSubscriptionId);
                    
                    $subscriptionHelper->handleSubscriptionPaid($subscription, $stripeSubscription);
                }
            break;
            default:
                // allow this - we'll have Stripe send us everything
                // throw new \Exception('Unexpected webhook type form Stripe! '.$stripeEvent->type);       
        }
    }
}