<?php

namespace AppBundle\Controller;

use AppBundle\Entity\Product;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class ProductController extends Controller
{
    /**
     * @Route("/products", name="product_show_all")
     */
    public function showAllAction()
    {
        $products = $this->getDoctrine()
            ->getRepository('AppBundle:Product')
            ->findAll();
        return $this->render('product/show_all.html.twig', array(
            'products' => $products
        ));
    }
    /**
     * @Route("/products/{slug}", name="product_show")
     */
    public function showAction(Product $product)
    {
        return $this->render('product/show.html.twig', array(
            'product' => $product
        ));
    }

    /**
     * @Route("/pricing", name="pricing_show")
     */
    public function pricingAction()
    {
        return $this->render('product/pricing.html.twig', array(
        ));
    }
}
