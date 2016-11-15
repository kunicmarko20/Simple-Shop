<?php


namespace AppBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;

class WebhookController {
    
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
        
        $stripeEvent = $this->get('stripe_client')
            ->findEvent($eventId);
        
        $subscriptionHelper = $this->get('subscription_helper');
        
        switch ($stripeEvent->type) {
            case 'customer.subscription.deleted':
                $stripeSubscriptionId = $stripeEvent->data->object->id;
                $subscription = $this->findSubscription($stripeSubscriptionId);
                $subscriptionHelper->fullyCancelSubscription($subscription);
                break;
            default:
                throw new \Exception('Unexpected webhook type form Stripe! '.$stripeEvent->type);
        }
        
    }
    
    
     /**
     * @param $stripeSubscriptionId
     * @return \AppBundle\Entity\Subscription
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
}
