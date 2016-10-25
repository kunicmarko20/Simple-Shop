<?php

namespace AppBundle\Util;

use AppBundle\Entity\User;
use Doctrine\ORM\EntityManager;
use InvalidArgumentException;

class UserUtil
{
    private $em;
    
    public function __construct(EntityManager $em) {
        $this->em = $em;
    }
    
    public function create($username, $password, $email, $role)
    {
        $user = new User();
        $user->setUsername($username);
        $user->setEmail($email);
        $user->setPlainPassword($password);
        $user->setRoles($role != false ? [$role] : ['ROLE_USER']);
        $this->em->persist($user);
        $this->em->flush();
    }

    public function promote($username,$role)
    {
        $user = $this->em->getRepository('AppBundle:User')->findByUsernameOrEmail($username);

        if (!$user) {
            throw new InvalidArgumentException(sprintf('User identified by "%s" username does not exist.', $username));
        }
        $roles = $user->getRoles();
        if(!in_array($role, $roles)){
           $roles[] = $role;
           $user->setRoles($roles);
           $this->em->persist($user);
           $this->em->flush();
           
           return true;
        }  
        
        return false;
    }
}
