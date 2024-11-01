<?php
	
	namespace Services\ObjectQuel\Rules;
	
	use Services\ObjectQuel\Ast\AstAlias;
	use Services\ObjectQuel\Ast\AstRange;
	use Services\ObjectQuel\Ast\AstRegExp;
	use Services\ObjectQuel\Ast\AstRetrieve;
	use Services\ObjectQuel\Lexer;
	use Services\ObjectQuel\LexerException;
	use Services\ObjectQuel\ParserException;
	use Services\ObjectQuel\Token;
	
	class Retrieve {

		private Lexer $lexer;
		private GeneralExpression $expressionRule;
		private LogicalExpression $logicalExpressionRule;
		
		/**
		 * Range parser
		 * @param Lexer $lexer
		 */
		public function __construct(Lexer $lexer) {
			$this->lexer = $lexer;
			$this->expressionRule = new GeneralExpression($this->lexer);
			$this->logicalExpressionRule = new LogicalExpression($this->lexer);
		}
		
		/**
		 * Parses the values retrieved by lexer within a given AST structure.
		 * @param AstRetrieve $retrieve The AST retrieval instance.
		 * @return array An array of parsed values.
		 * @throws LexerException|ParserException
		 */
		protected function parseValues(AstRetrieve $retrieve): array {
			$values = [];
			
			do {
				// Bewaar de startpositie van de lexer
				$startPos = $this->lexer->getPos();
				
				// Bepaal of de huidige token een alias vertegenwoordigt
				$aliasToken = $this->lexer->peekNext() == Token::Equals ? $this->lexer->match(Token::Identifier) : null;
				
				if ($aliasToken) {
					$this->lexer->match(Token::Equals);
				}
				
				// Parse de volgende expressie
				$expression = $this->expressionRule->parse();
				
				// Reguliere expressie niet toegestaan in field lijst
				if ($expression instanceof AstRegExp) {
					throw new ParserException("Regular expressions are not allowed in the value list. Please remove the regular expression.");
				}
				
				// Haal de broncode slice op
				$sourceSlice = $this->lexer->getSourceSlice($startPos, $this->lexer->getPos() - $startPos);
				
				// Bepaal en verwerk de alias voor de huidige expressie
				if ($aliasToken === null || !$retrieve->macroExists($aliasToken->getValue())) {
					if ($aliasToken !== null) {
						$retrieve->addMacro($aliasToken->getValue(), $expression);
					}
					
					$aliasName = $aliasToken ? $aliasToken->getValue() : $sourceSlice;
					$values[] = new AstAlias(trim($aliasName), $expression);
				} else {
					throw new ParserException("Duplicate variable name detected: '{$aliasToken->getValue()}'. Please use unique names.");
				}
				
			} while ($this->lexer->optionalMatch(Token::Comma));
			
			return $values;
		}
		
		/**
		 * Parse the 'retrieve' statement of the ObjectQuel language.
		 * @param AstRange[] $ranges
		 * @return AstRetrieve
		 * @throws LexerException|ParserException
		 */
		public function parse(array $ranges): AstRetrieve {
			// Match and consume the 'retrieve' token
			$this->lexer->match(Token::Retrieve);
			
			// Create a new AST node for the 'retrieve' operation
			$retrieve = new AstRetrieve($ranges, $this->lexer->optionalMatch(Token::Unique));
			
			// Match and consume the opening parenthesis
			$this->lexer->match(Token::ParenthesesOpen);
			
			// Parse all values inside the parenthesis and add them to the AstRetrieve node
			foreach($this->parseValues($retrieve) as $value) {
				$retrieve->addValue($value);
			}
			
			// Match and consume the closing parenthesis
			$this->lexer->match(Token::ParenthesesClose);
			
			// Check for an optional 'where' clause and parse its conditions if present
			if ($this->lexer->optionalMatch(Token::Where)) {
				$retrieve->setConditions($this->logicalExpressionRule->parse());
			}
			
			// Sort by
			if ($this->lexer->optionalMatch(Token::Sort)) {
                $this->lexer->match(Token::By);
                
                $sortArray = [];
                
                do {
                    $sortResult = $this->expressionRule->parse();
                    $order = ''; // Standaard order is leeg.
                    
                    if ($this->lexer->optionalMatch(Token::Asc)) {
                        $order = 'asc';
                    } elseif ($this->lexer->optionalMatch(Token::Desc)) {
                        $order = 'desc';
                    }
                    
                    $sortArray[] = ['ast' => $sortResult, 'order' => $order];
                } while ($this->lexer->optionalMatch(Token::Comma));
                
                $retrieve->setSort($sortArray);
            }
			
			// Optionele puntkomma
			if ($this->lexer->lookahead() == Token::Semicolon) {
				$this->lexer->match(Token::Semicolon);
			}
			
			// Return the retrieve node
			return $retrieve;
		}
	}