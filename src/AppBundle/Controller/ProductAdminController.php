<?php

namespace AppBundle\Controller;

use AppBundle\Entity\Product;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;
use AppBundle\Form\ProductCreateForm;

class ProductAdminController extends Controller
{
    /**
     * @Route("/admin/products", name="product_list")
     */
    public function listAction()
    {
        $products = $this->getDoctrine()
            ->getRepository('AppBundle:Product')
            ->findAll();

        return $this->render('product/list.html.twig', [
            'products' => $products
        ]);
    }

    /**
     * @Route("/admin/products/new", name="product_new")
     */
    public function newAction(Request $request)
    {
  
        $form = $this->createForm(ProductCreateForm::class);
        
        $form->handleRequest($request);

        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $item = $form->getData();
            $em->persist($item);
            $em->flush();
            $this->addFlash('success', 'Product created');
            
            return $this->redirectToRoute('product_list');
        }
        
        return $this->render('product/new.html.twig',[
            'form' => $form->createView()
        ]);
    }

    /**
     * @Route("/admin/products/delete/{id}", name="product_delete")
     * @Method("POST")
     */
    public function deleteAction(Product $product)
    {
        $em = $this->getDoctrine()->getManager();
        $em->remove($product);
        $em->flush();

        $this->addFlash('success', 'The product was deleted');

        return $this->redirectToRoute('product_list');
    }
}
