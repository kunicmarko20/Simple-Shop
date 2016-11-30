<?php
namespace AppBundle\Stripe;
use AppBundle\Entity\Subscription;
use AppBundle\Entity\User;
use Doctrine\ORM\EntityManager;

class SubscriptionHelper
{
    private $plans = [];
    private $em;
    public function __construct(EntityManager $em)
    {
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
        $this->plans[] = new SubscriptionPlan(
            'mini_pack_yearly',
            'Mini Pack',
            990,
            SubscriptionPlan::DURATION_YEARLY
        );
        $this->plans[] = new SubscriptionPlan(
            'mega_pack_yearly',
            'Mega Pack',
            1990,
            SubscriptionPlan::DURATION_YEARLY
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
    /**
     * @param $currentPlanId
     * @return SubscriptionPlan
     */
    public function findPlanToChangeTo($currentPlanId)
    {
        if (strpos($currentPlanId, 'mini_pack') !== false) {
            $newPlanId = str_replace('mini_pack', 'mega_pack', $currentPlanId);
        } else {
            $newPlanId = str_replace('mega_pack', 'mini_pack', $currentPlanId);
        }
        return $this->findPlan($newPlanId);
    }
    public function findPlanForOtherDuration($currentPlanId)
    {
        if (strpos($currentPlanId, 'monthly') !== false) {
            $newPlanId = str_replace('monthly', 'yearly', $currentPlanId);
        } else {
            $newPlanId = str_replace('yearly', 'monthly', $currentPlanId);
        }
        return $this->findPlan($newPlanId);
    }
    public function addSubscriptionToUser(\Stripe\Subscription $stripeSubscription, User $user)
    {
        $subscription = $user->getSubscription();
        if (!$subscription) {
            $subscription = new Subscription();
            $subscription->setUser($user);
        }
        $periodEnd = \DateTime::createFromFormat('U', $stripeSubscription->current_period_end);
        $subscription->activateSubscription(
            $stripeSubscription->plan->id,
            $stripeSubscription->id,
            $periodEnd
        );
        $this->em->persist($subscription);
        $this->em->flush($subscription);
    }
    public function updateCardDetails(User $user, \Stripe\Customer $stripeCustomer)
    {
        if (!$stripeCustomer->sources->data) {
            // the customer may not have a card on file
            return;
        }
        $cardDetails = $stripeCustomer->sources->data[0];
        $user->setCardBrand($cardDetails->brand);
        $user->setCardLast4($cardDetails->last4);
        $this->em->persist($user);
        $this->em->flush($user);
    }
    public function fullyCancelSubscription(Subscription $subscription)
    {
        $subscription->cancel();
        $this->em->persist($subscription);
        $this->em->flush($subscription);
    }
    public function handleSubscriptionPaid(Subscription $subscription, \Stripe\Subscription $stripeSubscription)
    {
        $newPeriodEnd = \DateTime::createFromFormat('U', $stripeSubscription->current_period_end);
        
        // you can use this to send emails to new or renewal customers
        $isRenewal = $newPeriodEnd > $subscription->getBillingPeriodEndsAt();
        
        $subscription->setBillingPeriodEndsAt($newPeriodEnd);
        $this->em->persist($subscription);
        $this->em->flush($subscription);
    }
}