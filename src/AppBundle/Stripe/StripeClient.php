<?php
namespace AppBundle\Stripe;

use AppBundle\Entity\User;
use AppBundle\Stripe\SubscriptionPlan;
use Doctrine\ORM\EntityManager;

class StripeClient
{
    private $em;
    public function __construct($secretKey, EntityManager $em)
    {
        $this->em = $em;
        \Stripe\Stripe::setApiKey($secretKey);
    }
    public function createCustomer(User $user, $paymentToken)
    {
        $customer = \Stripe\Customer::create([
            'email' => $user->getEmail(),
            'source' => $paymentToken,
        ]);
        $user->setStripeCustomerId($customer->id);
        $this->em->persist($user);
        $this->em->flush($user);
        return $customer;
    }
    public function updateCustomerCard(User $user, $paymentToken)
    {
        $customer = \Stripe\Customer::retrieve($user->getStripeCustomerId());
        $customer->source = $paymentToken;
        $customer->save();
        return $customer;
    }
    public function createInvoiceItem($amount, User $user, $description)
    {
        return \Stripe\InvoiceItem::create(array(
            "amount" => $amount,
            "currency" => "usd",
            "customer" => $user->getStripeCustomerId(),
            "description" => $description
        ));
    }
    public function createInvoice(User $user, $payImmediately = true)
    {
        $invoice = \Stripe\Invoice::create(array(
            "customer" => $user->getStripeCustomerId()
        ));
        if ($payImmediately) {
            // guarantee it charges *right* now
            try {
                $invoice->pay();
            } catch (\Stripe\Error\Card $e) {
                // paying failed, close this invoice so we don't
                // keep trying to pay it
                $invoice->closed = true;
                $invoice->save();
                throw $e;
            }
        }
        return $invoice;
    }
    public function createSubscription(User $user, SubscriptionPlan $plan)
    {
        $subscription = \Stripe\Subscription::create(array(
            'customer' => $user->getStripeCustomerId(),
            'plan' => $plan->getPlanId()
        ));
        return $subscription;
    }
    public function cancelSubscription(User $user)
    {
        $sub = \Stripe\Subscription::retrieve(
            $user->getSubscription()->getStripeSubscriptionId()
        );
        $currentPeriodEnd = new \DateTime('@'.$sub->current_period_end);
        $cancelAtPeriodEnd = true;
        if ($sub->status == 'past_due') {
            // past due? Cancel immediately, don't try charging again
            $cancelAtPeriodEnd = false;
        } elseif ($currentPeriodEnd < new \DateTime('+1 hour')) {
            // within 1 hour of the end? Cancel so the invoice isn't charged
            $cancelAtPeriodEnd = false;
        }
        $sub->cancel([
            'at_period_end' => $cancelAtPeriodEnd,
        ]);
        return $sub;
    }
    public function reactivateSubscription(User $user)
    {
        if (!$user->hasActiveSubscription()) {
            throw new \LogicException('Subscriptions can only be reactivated if the subscription has not actually ended yet');
        }
        $subscription = \Stripe\Subscription::retrieve(
            $user->getSubscription()->getStripeSubscriptionId()
        );
        // this triggers the refresh of the subscription!
        $subscription->plan = $user->getSubscription()->getStripePlanId();
        $subscription->save();
        return $subscription;
    }
    /**
     * @param $eventId
     * @return \Stripe\Event
     */
    public function findEvent($eventId)
    {
        return \Stripe\Event::retrieve($eventId);
    }
    /**
     * @param $stripeSubscriptionId
     * @return \Stripe\Subscription
     */
    public function findSubscription($stripeSubscriptionId)
    {
        return \Stripe\Subscription::retrieve($stripeSubscriptionId);
    }
    public function getUpcomingInvoiceForChangedSubscription(User $user, SubscriptionPlan $newPlan)
    {
        return \Stripe\Invoice::upcoming([
            'customer' => $user->getStripeCustomerId(),
            'subscription' => $user->getSubscription()->getStripeSubscriptionId(),
            'subscription_plan' => $newPlan->getPlanId(),
        ]);
    }
    public function changePlan(User $user, SubscriptionPlan $newPlan)
    {
        $stripeSubscription = $this->findSubscription($user->getSubscription()->getStripeSubscriptionId());
        $currentPeriodStart = $stripeSubscription->current_period_start;
        $originalPlanId = $stripeSubscription->plan->id;
        $stripeSubscription->plan = $newPlan->getPlanId();
        $stripeSubscription->save();
        // if the duration did not change, Stripe will not charge them immediately
        // but we *do* want them to be charged immediately
        // if the duration changed, an invoice was already created and paid
        if ($stripeSubscription->current_period_start == $currentPeriodStart) {
            try {
                // immediately invoice them
                $this->createInvoice($user);
            } catch (\Stripe\Error\Card $e) {
                $stripeSubscription->plan = $originalPlanId;
                // prevent prorations discounts/charges from changing back
                $stripeSubscription->prorate = false;
                $stripeSubscription->save();
                throw $e;
            }
        }
        return $stripeSubscription;
    }
}