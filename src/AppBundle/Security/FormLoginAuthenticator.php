<?php

namespace AppBundle\Security;

use AppBundle\Form\LoginForm;
use Doctrine\ORM\EntityManager;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoder;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Guard\Authenticator\AbstractFormLoginAuthenticator;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;

class FormLoginAuthenticator extends AbstractFormLoginAuthenticator
{
    private $formFactory;
    private $em;
    private $router;
    private $passwordEncoder;

    public function __construct(FormFactoryInterface $formFactory, EntityManager $em, RouterInterface $router, UserPasswordEncoder $passwordEncoder)
    {
        $this->formFactory = $formFactory;
        $this->em = $em;
        $this->router = $router;
        $this->passwordEncoder = $passwordEncoder;
    }

    public function getCredentials(Request $request)
    {
        if ($request->getPathInfo() != '/login' || $request->getMethod() != 'POST') {
            return;
        }
        dump($request->headers->get('referer'));
        $form = $this->formFactory->create(LoginForm::class);
        $form->handleRequest($request);

        $data = $form->getData();
        $request->getSession()->set(
            Security::LAST_USERNAME,
            $data['_username']
        );

        return $data;
    }
    public function getUser($credentials, UserProviderInterface $userProvider)
    {
        $username = $credentials['_username'];
        $userRepo = $this->em
            ->getRepository('AppBundle:User');
        $user = $userRepo->findByUsernameOrEmail($username);
        if(!$user){
            throw new CustomUserMessageAuthenticationException(
                'Username or Email cound not be found.'
            );
        }
        return $user;
    }
    public function checkCredentials($credentials, UserInterface $user)
    {
        $password = $credentials['_password'];
        
        if ($this->passwordEncoder->isPasswordValid($user, $password)) {
            return true;
        }
        
        return false;
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception) {
        $request->getSession()->set(Security::AUTHENTICATION_ERROR,$exception);
        
        $url = $this->router->generate('security_login');
        
        return new RedirectResponse($url);
    }

    public function getDefaultSuccessRedirectUrl() {
        return $this->router->generate('profile_account');
    }


    public function supportsRememberMe() {
        
    }

    protected function getLoginUrl() {
        $url = $this->router->generate('security_login');
        
        return new RedirectResponse($url);
    }

}
