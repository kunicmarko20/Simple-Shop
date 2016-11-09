<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity
 * @ORM\Table(name="product")
 * @ORM\HasLifecycleCallbacks() 
 */
class Product
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string")
     * @Assert\NotBlank()
     */
    private $name;

    /**
     * @ORM\Column(unique=true)
     * @Gedmo\Slug(fields={"name"})
     */
    private $slug;

    /**
     * @ORM\Column(type="integer")
     * @Assert\Range(
     *      min = 0,
     *      minMessage = "You can't enter negative number."
     * )
     * @Assert\NotBlank()
     */
    private $price;

    /**
     * @ORM\Column(type="text")
     * @Assert\NotBlank()
     */
    private $description;
    /**
     * @ORM\Column(type="string")
     */
    private $imageFilename;
    
    public function getId()
    {
        return $this->id;
    }

    public function getName()
    {
        return $this->name;
    }

    public function setName($name)
    {
        $this->name = $name;
    }

    public function getPrice()
    {
        return $this->price;
    }

    public function setPrice($price)
    {
        $this->price = $price;
    }

    public function getDescription()
    {
        return $this->description;
    }

    public function setDescription($description)
    {
        $this->description = $description;
    }

    public function getImageFilename()
    {
        return $this->imageFilename;
    }

    public function setImageFilename($imageFilename)
    {
        $this->imageFilename = $imageFilename;
    }

    public function getSlug()
    {
        return $this->slug;
    }

    public function setSlug($slug)
    {
        $this->slug = $slug;
    }
    
     /**
     * @ORM\PrePersist()
     */
    function onPrePersist() {
        //just so I don't have to upload images
        $this->imageFilename = "product_".rand(1,6).".jpg";
    }
}
