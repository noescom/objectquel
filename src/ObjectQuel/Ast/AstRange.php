<?php
	
	namespace Services\ObjectQuel\Ast;
	
	use Services\ObjectQuel\AstInterface;
	use Services\ObjectQuel\AstVisitorInterface;
	
	/**
	 * Class AstRange
	 * AstRange klasse is verantwoordelijk voor het definiëren van een bereik in de AST (Abstract Syntax Tree).
	 */
	class AstRange extends Ast {
		
		// Alias voor het bereik
		private string $name;
		
		// Entiteit geassocieerd met het bereik
		private AstEntity $entity;
		
		// De via string geeft aan op welk veld gejoined moet worden (LEFT JOIN etc)
		private ?AstInterface $joinProperty;
		
		// True als de relatie optioneel is. E.g. of het om een LEFT JOIN gaat.
		private bool $required;
		
		/**
		 * AstRange constructor.
		 * @param string $name De naam voor dit bereik.
		 * @param AstEntity $entity De entiteit die is geassocieerd met dit bereik.
		 * @param AstInterface|null $joinProperty
		 * @param bool $required True als de relatie verplicht is. E.g. het gaat om een INNER JOIN. False voor LEFT JOIN.
		 */
		public function __construct(string $name, AstEntity $entity, ?AstInterface $joinProperty=null, bool $required=false) {
			$this->name = $name;
			$this->entity = $entity;
			$this->joinProperty = $joinProperty;
			$this->required = $required;
			
			$this->entity->setRange($this);
		}
		
		/**
		 * Accepteer een bezoeker om de AST te verwerken.
		 * @param AstVisitorInterface $visitor Bezoeker object voor AST-manipulatie.
		 */
		public function accept(AstVisitorInterface $visitor) {
			parent::accept($visitor);  // Accepteer eerst de bezoeker op ouderklasse
			$this->entity->accept($visitor);  // Accepteer vervolgens de bezoeker op de entiteit
			
			if (!is_null($this->joinProperty)) {
				$this->joinProperty->accept($visitor); // En accepteer de 'via' property
			}
		}
		
		/**
		 * Haal de alias voor dit bereik op.
		 * @return string De alias van dit bereik.
		 */
		public function getName(): string {
			return $this->name;
		}
		
		/**
		 * Haal de AST van de entiteit op die is geassocieerd met dit bereik.
		 * @return AstEntity De naam van de entiteit.
		 */
		public function getEntity(): AstEntity {
			return $this->entity;
		}
		
		/**
		 * De via expressie geeft aan op welk velden gejoined moet worden
		 * @return AstInterface|null
		 */
		public function getJoinProperty(): ?AstInterface {
			return $this->joinProperty;
		}
		
		/**
		 * De via expressie geeft aan op welk velden gejoined moet worden
		 * @param AstInterface|null $joinExpression
		 * @return void
		 */
		public function setJoinProperty(?AstInterface $joinExpression): void {
			$this->joinProperty = $joinExpression;
		}

		/**
		 * Maakt de relatie verplicht
		 * @var bool $required
		 * @return void
		 */
		public function setRequired(bool $required=true): void {
			$this->required = $required;
		}
		
		/**
		 * True als de relatie verplicht is. E.g. het gaat om een INNER JOIN.
		 * @return bool
		 */
		public function isRequired(): bool {
			return $this->required;
		}
	}