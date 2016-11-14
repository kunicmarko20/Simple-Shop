<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace AppBundle\Service;

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
}
