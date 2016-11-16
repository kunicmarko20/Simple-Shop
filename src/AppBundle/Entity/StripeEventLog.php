<?php
namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="stripe_event_log")
 */
class StripeEventLog
{   
    /**
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @ORM\Column(type="integer")
     */
    private $id;
    /**
     * @ORM\Column(type="string", unique=true)
     */
    private $stripeEventId;
    /**
     * @ORM\Column(type="datetime")
     */
    private $handledAt;
    public function __construct($stripeEventId)
    {
        $this->stripeEventId = $stripeEventId;
        $this->handledAt = new \DateTime();
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
     * Set stripeEventId
     *
     * @param string $stripeEventId
     *
     * @return StripeEventLog
     */
    public function setStripeEventId($stripeEventId)
    {
        $this->stripeEventId = $stripeEventId;

        return $this;
    }

    /**
     * Get stripeEventId
     *
     * @return string
     */
    public function getStripeEventId()
    {
        return $this->stripeEventId;
    }

    /**
     * Set handledAt
     *
     * @param \DateTime $handledAt
     *
     * @return StripeEventLog
     */
    public function setHandledAt($handledAt)
    {
        $this->handledAt = $handledAt;

        return $this;
    }

    /**
     * Get handledAt
     *
     * @return \DateTime
     */
    public function getHandledAt()
    {
        return $this->handledAt;
    }
}
