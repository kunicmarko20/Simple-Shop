<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace AppBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use AppBundle\Form\LoginForm;

class SecurityController extends Controller
{
    /**
     * @Route("/login", name="security_login")
     */
    public function loginAction()
    {
        $helper = $this->get('security.authentication_utils');
        
        $form = $this->createForm(LoginForm::class,array(
            '_username' => $helper->getLastUsername()
        ));
        return $this->render('security/login.html.twig', array(
            // last username entered by the user (if any)
            'form' => $form->createView(),
            // last authentication error (if any)
            'error' => $helper->getLastAuthenticationError(),
        ));
    }
    
    /**
     * @Route("/logout", name="security_logout")
     */
    public function logoutAction()
    {
        
    }
}