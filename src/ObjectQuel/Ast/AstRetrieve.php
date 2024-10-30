<?php
	
	namespace Services\ObjectQuel\Ast;
	
	use Services\ObjectQuel\AstInterface;
	use Services\ObjectQuel\AstVisitorInterface;
	
	/**
	 * Class AstRetrieve
	 *
	 * Represents a retrieve operation in the AST (Abstract Syntax Tree).
	 */
	class AstRetrieve extends Ast {
		
		/**
		 * @var array The values to be retrieved.
		 */
		protected array $values;
		
		/**
		 * @var array Macros
		 */
		protected array $macros;
		
		/**
		 * @var AstRange[] The ranges used in the retrieve statement
		 */
		protected array $ranges;
		
		/**
		 * @var AstInterface|null The conditions for the retrieve operation, if any.
		 */
		protected ?AstInterface $conditions;
		
		/**
		 * @var AstInterface[] $sort;
		 */
		protected array $sort;
		
		/**
		 * @var bool True if the query is unique (e.g. DISTINCT in MySQL)
		 */
		protected bool $unique;
		
		/**
		 * AstRetrieve constructor.
		 * Initializes an empty array of values and sets conditions to null.
		 * @param AstRange[] $ranges
		 * @param bool $unique True if the results are unique (DISTINCT), false if not
		 */
		public function __construct(array $ranges, bool $unique) {
			$this->values = [];
			$this->macros = [];
			$this->conditions = null;
			$this->ranges = $ranges;
			$this->unique = $unique;
			$this->sort = [];
		}
		
		/**
		 * Accepteert een bezoeker (visitor) om bewerkingen uit te voeren op deze node.
		 * Deze methode laat de visitor eerst bewerkingen uitvoeren op de node zelf en
		 * vervolgens op elk van de ranges die bij deze node horen.
		 * @param AstVisitorInterface $visitor De visitor die geaccepteerd wordt.
         * @return void
		 */
		public function accept(AstVisitorInterface $visitor): void {
			// Laat de visitor bewerkingen uitvoeren op elk range-object in de node.
			foreach($this->ranges as $value) {
				$value->accept($visitor);
			}
			
			// Laat de visitor bewerkingen uitvoeren op de node, exclusief de ranges.
			$this->acceptWithoutRanges($visitor);
		}
		
		/**
		 * Accepteert een bezoeker om bewerkingen uit te voeren op deze node, maar
		 * zonder de ranges te betrekken. Dit is nuttig wanneer bewerkingen op alleen
		 * de node zelf vereist zijn, zonder de ranges te beïnvloeden.
		 * @param AstVisitorInterface $visitor De visitor die geaccepteerd wordt.
         * @return void
		 */
		public function acceptWithoutRanges(AstVisitorInterface $visitor): void {
			// Laat de parent class de visitor accepteren en bewerkingen uitvoeren.
			parent::accept($visitor);
			
			// Laat de visitor bewerkingen uitvoeren op elk waarde-object in de node.
			foreach($this->values as $value) {
				$value->accept($visitor);
			}

			// Als er condities zijn, laat deze ook door de visitor bewerkt worden.
			if ($this->conditions !== null) {
				$this->conditions->accept($visitor);
			}
			
			// Als er een sort clausule is, laat deze ook door de visitor bewerkt worden.
            foreach($this->sort as $s) {
				$s['ast']->accept($visitor);
			}

			// En ook de macros
			foreach($this->macros as $macro) {
				$macro->accept($visitor);
			}
		}
		
		/**
		 * Add a value to be retrieved.
		 * @param AstInterface $ast The value to add.
		 */
		public function addValue(AstInterface $ast): void {
			$this->values[] = $ast;
		}
		
		/**
		 * Get the values to be retrieved.
		 * @return AstInterface[] The array of values.
		 */
		public function getValues(): array {
			return $this->values;
		}
		
		/**
		 * Get the conditions for this retrieve operation, if any.
		 * @return AstInterface|null The conditions or null.
		 */
		public function getConditions(): ?AstInterface {
			return $this->conditions;
		}
		
		/**
		 * Set the conditions for this retrieve operation.
		 *
		 * @param AstInterface $ast The conditions.
		 */
		public function setConditions(AstInterface $ast): void {
			$this->conditions = $ast;
		}
		
		/**
		 * Returns the ranges used in the retrieve statement
		 * @return array
		 */
		public function getRanges(): array {
			return $this->ranges;
		}
		
		/**
		 * Adds a new range to the range list
		 * @return void
		 */
		public function addRange(AstRange $range): void {
			$this->ranges[] = $range;
		}
		
		/**
		 * Returns true if the query is unique
		 * @return bool
		 */
		public function isUnique(): bool {
			return $this->unique;
		}
		
		/**
		 * Returns all defined macros
		 * @return array
		 */
		public function getMacros(): array {
			return $this->macros;
		}
		
		/**
		 * Adds a new macro
		 * @param string $name
		 * @param AstInterface|null $ast
		 * @return void
		 */
		public function addMacro(string $name, ?AstInterface $ast): void {
			$this->macros[$name] = $ast;
		}
		
		/**
		 * Returns true if the macro exists, false if not
		 * @param string $name
		 * @return bool
		 */
		public function macroExists(string $name): bool {
			return isset($this->macros[$name]);
		}
        
        /**
         * Sets sort clause
         * @param array $sortArray
         * @return void
         */
		public function setSort(array $sortArray): void {
			$this->sort = $sortArray;
		}
		
		/**
		 * Returns the sort clause
		 * @return array|AstInterface[]
         */
		public function getSort(): array {
			return $this->sort;
		}
	}