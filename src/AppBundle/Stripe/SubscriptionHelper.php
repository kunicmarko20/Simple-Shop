<?php

namespace AppBundle\Stripe;

use AppBundle\Entity\Subscription;
use AppBundle\Entity\User;
use Doctrine\ORM\EntityManager;
use Stripe;

class SubscriptionHelper
{
    /** @var SubscriptionPlan[] */
    private $plans = [];
    private $user;
    private $em;
    public function __construct(User $user, EntityManager $em)
    {
        $this->user = $user;
        $this->em = $em;
        
        $this->plans[] = new SubscriptionPlan(
            'mini_pack_monthly',
            'Mini Pack',
            99
        );
        
        $this->plans[] = new SubscriptionPlan(
            'mega_pack_monthly',
            'Mega Pack',
            199
        );
    }

    /**
     * @param $planId
     * @return SubscriptionPlan|null
     */
    public function findPlan($planId)
    {
        foreach ($this->plans as $plan) {
            if ($plan->getPlanId() == $planId) {
                return $plan;
            }
        }
    }
    
    public function addSubscriptionToUser(Stripe\Subscription $stripe){
        $subscription = $this->user->getSubscription();
        
        if(!$subscription){
            $subscription = new Subscription();
            $subscription->setUser($this->user);
        }
        $periodEnd = \DateTime::createFromFormat('U', $stripe->current_period_end);
        
        $subscription->activateSubscription($stripe->plan->id, $stripe->id, $periodEnd);
        $this->em->persist($subscription);
        $this->em->flush();
    }
    
    public function updateCardDetails(\Stripe\Customer $customer){
        
        $cardDetails = $customer->sources->data[0];
        $this->user->setCardBrand($cardDetails->brand);
        $this->user->setCardLast4($cardDetails->last4);
        
        $this->em->persist($this->user);
        $this->em->flush();
    }
    
    public function fullyCancelSubscription(Subscription $subscription)
    {
        $subscription->cancel();
        $this->em->persist($subscription);
        $this->em->flush($subscription);
    }
}
