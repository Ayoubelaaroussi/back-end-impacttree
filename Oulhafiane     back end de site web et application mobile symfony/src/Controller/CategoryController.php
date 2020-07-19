<?php

namespace App\Controller;

use App\Entity\Category;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Response;
use Doctrine\ORM\EntityManagerInterface;
use JMS\Serializer\SerializerInterface;
use JMS\Serializer\SerializationContext;

class CategoryController extends AbstractController
{
    private $em;
    private $serializer;

    public function __construct(EntityManagerInterface $em, SerializerInterface $serializer)
    {
        $this->em = $em;
        $this->serializer = $serializer;
    }

    /**
     * @Route("/api/categories", name="categories", methods={"GET"})
     */
    public function index()
    {
        $categories = $this->em->getRepository(Category::class)->findAll();
        if (null === $categories)
			throw new HttpException(404, "No category found.");
		$data = $this->serializer->serialize($categories, 'json', SerializationContext::create()->setGroups(array('list-categories')));
		$response = new Response($data);
		$response->headers->set('Content-Type', 'application/json');

		return $response;
    }
}
