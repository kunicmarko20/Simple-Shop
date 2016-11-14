<?php

namespace AppBundle\Entity;

use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\Role\Role;
use Symfony\Component\Security\Core\User\UserInterface;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity
 * @ORM\Table(name="user")
 * @ORM\Entity(repositoryClass="AppBundle\Repository\UserRepository")
 * @UniqueEntity("email", message="Email in use.")
 * @UniqueEntity("username", message="Username already exists.")
 */
class User implements UserInterface
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @Assert\NotBlank()
     * @Assert\Email()
     * @ORM\Column(type="string", unique=true)
     */
    private $email;
    
    /**
     * @Assert\NotBlank()
     * @ORM\Column(type="string", unique=true)
     */
    private $username;

    /**
     * The encoded password
     *
     * @ORM\Column(type="string")
     */
    private $password;

    /**
     * A non-persisted field that's used to create the encoded password.
     * @Assert\NotBlank(groups={"Registration"})
     * @Assert\Length(
     *      min = 6,
     *      minMessage = "Your password must be at least {{ limit }} characters long.",
     * )
     * @var string
     */
    private $plainPassword;

    /**
     * @ORM\Column(type="json_array")
     */
    private $roles = [];
    
    /**
      * @ORM\Column(type="string", unique=true, nullable=true)
      */
     private $stripeCustomerId;
    
     
    /**
     * @ORM\Column(type="string", nullable=true)
     */
    private $cardBrand;

    /**
     * @ORM\Column(type="string", length=4, nullable=true)
     */
    private $cardLast4;

    /**
     * @ORM\OneToOne(targetEntity="Subscription", mappedBy="user")
     */
    private $subscription;
    
     function getStripeCustomerId() {
         return $this->stripeCustomerId;
     }
 
     function setStripeCustomerId($stripeCustomerId) {
         $this->stripeCustomerId = $stripeCustomerId;
     }
    // needed by the security system
    public function getUsername()
    {
        return $this->username;
    }
    
    public function setUsername($username) 
    {
        $this->username = $username;
    }
    
    public function getRoles()
    {
        $roles = $this->roles;

        // give everyone ROLE_USER!
        if (!in_array('ROLE_USER', $roles)) {
            $roles[] = 'ROLE_USER';
        }

        return $roles;
    }

    public function setRoles(array $roles)
    {
        $this->roles = $roles;
    }

    public function getPassword()
    {
        return $this->password;
    }

    public function getSalt()
    {
    }

    public function eraseCredentials()
    {
        $this->plainPassword = null;
    }

    public function getEmail()
    {
        return $this->email;
    }

    public function setEmail($email)
    {
        $this->email = $email;
    }

    public function setPassword($password)
    {
        $this->password = $password;
    }

    public function getPlainPassword()
    {
        return $this->plainPassword;
    }

    public function setPlainPassword($plainPassword)
    {
        $this->plainPassword = $plainPassword;
        $this->password = null;
    }
    /**
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }


    /**
     * Set cardBrand
     *
     * @param string $cardBrand
     *
     * @return User
     */
    public function setCardBrand($cardBrand)
    {
        $this->cardBrand = $cardBrand;

        return $this;
    }

    /**
     * Get cardBrand
     *
     * @return string
     */
    public function getCardBrand()
    {
        return $this->cardBrand;
    }

    /**
     * Set cardLast4
     *
     * @param string $cardLast4
     *
     * @return User
     */
    public function setCardLast4($cardLast4)
    {
        $this->cardLast4 = $cardLast4;

        return $this;
    }

    /**
     * Get cardLast4
     *
     * @return string
     */
    public function getCardLast4()
    {
        return $this->cardLast4;
    }

    /**
     * Set subscription
     *
     * @param \AppBundle\Entity\Subscription $subscription
     *
     * @return User
     */
    public function setSubscription(\AppBundle\Entity\Subscription $subscription = null)
    {
        $this->subscription = $subscription;

        return $this;
    }

    /**
     * Get subscription
     *
     * @return \AppBundle\Entity\Subscription
     */
    public function getSubscription()
    {
        return $this->subscription;
    }
    
    public function hasActiveSubscription(){
        return $this->getSubscription() && $this->getSubscription()->isActive();
    }
    
    public function hasActiveNonCancelledSubscription(){
        return $this->hasActiveSubscription() && !$this->subscription->isCancelled();
    }
}
