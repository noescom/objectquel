<?php
	
	namespace Services\Controller;
	
	use Services\AnnotationsReader\Annotations\Route;
	use Services\Entity\ProductsEntity;
	use Services\EntityManager\EntityManager;
	use Symfony\Component\HttpFoundation\Response;
	
	class TestController {
		
		private EntityManager $entityManager;
		
		/**
		 * TestController controller
		 * @param EntityManager $entityManager
		 */
		public function __construct(EntityManager $entityManager) {
			$this->entityManager = $entityManager;
		}
		
		/**
		 * @Route("/hallo/{name}")
		 * @Validation\Type(property="name", type="string")
		 * @param string $name
		 * @return Response
		 */
		public function index(string $name): Response {
			$entity = $this->entityManager->executeQuery("
				range of x is ProductsEntity;
				range of y is ProductsDescriptionEntity via y.productsId=x.productsId;
				retrieve (x,y) where x.productsId=1537
			");
			
			
			return new Response('Hello ' . $name . '!');
		}
	}