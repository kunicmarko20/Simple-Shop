<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace AppBundle\Stripe;

use AppBundle\Entity\User;
use Doctrine\ORM\EntityManager;
use Stripe;


class StripeClient {
    
    private $em;
    private $user;
    public function __construct($secretKey,User $user, EntityManager $em) {
        $this->em = $em;
        $this->user = $user;
        Stripe\Stripe::setApiKey($secretKey);
    }
    
    public function createCustomer($token){
                $customer =  Stripe\Customer::create(array(
              "email" => $this->user->getEmail(),
              "source" => $token
                   ));
                $this->user->setStripeCustomerId($customer->id);
                $this->em->persist($this->user);
                $this->em->flush();
                
                return $customer;
    }
    public function createSubscription($plan){
        $subscription = Stripe\Subscription::create(array(
            "customer" => $this->user->getStripeCustomerId(),
            "plan" => $plan->getPlanId()
          ));
        
        return $subscription;
    }
    public function updateCustomerCard($token){
                $customer = Stripe\Customer::retrieve($this->user->getStripeCustomerId());
                $customer->source = $token;
                $customer->save();
                
                return $customer;
    }
    
    public function createInvoiceItem($amount, $description){
        return Stripe\InvoiceItem::create(array(
                   "amount" => $amount,
                   "currency" => "usd",
                   "customer" => $this->user->getStripeCustomerId(),
                   "description" => $description
                 ));
    }
    
    public function createInvoice($payImmediately = true ){
            $invoice = Stripe\Invoice::create([
                 'customer'=> $this->user->getStripeCustomerId()
             ]);
            
            if($payImmediately){
                 $invoice->pay();                
            }
            
            return $invoice;

    }
    
    public function cancelSubscription(){
        $subscription = \Stripe\Subscription::retrieve(
                $this->user->getSubscription()->getStripeSubscriptionId()
        );
        
        $cancelAtPeriodEnd = true;
        $currentPeriodEnd = new \DateTime('@'.$subscription->current_period_end);
        
        if($subscription->status == 'past_due'){
            $cancelAtPeriodEnd = false;
        }elseif ($currentPeriodEnd < new \DateTime('+5 hours')){ //timezone of server needs to be UTC if we want to workaround 1 hour Stripe bug, or we can set time to more than 1 hour
            $cancelAtPeriodEnd = false;
        }
        
        $subscription->cancel(array('at_period_end' => $cancelAtPeriodEnd));
        
        return $subscription;
    }
    
    public function reactivateSubscription(){
        
        if(!$this->user->hasActiveSubscription()){
            throw new \LogicException('Subscription can only be reactivated if billing period did not end.');
        }
        
        $subscription = Stripe\Subscription::retrieve(
                $this->user->getSubscription()->getStripeSubscriptionId()
        );
        $subscription->plan =  $this->user->getSubscription()->getStripePlanId();
        $subscription->save();
        
        return $subscription;
    }
    
    /**
     * @param $eventId
     * @return \Stripe\Event
     */
    public function findEvent($eventId)
    {
        return Stripe\Event::retrieve($eventId);
    }
}
