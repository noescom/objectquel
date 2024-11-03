<?php
	
	namespace Services\Signalize\Rules;
	
	use Services\Signalize\Ast\AstBindValue;
	use Services\Signalize\Lexer;
	use Services\Signalize\LexerException;
	use Services\Signalize\ParserException;
	use Services\Signalize\Token;
	
	class BindValue {
		
		protected Lexer $lexer;
		
		/**
		 * BindText constructor
		 * @param Lexer $lexer
		 */
		public function __construct(Lexer $lexer) {
			$this->lexer = $lexer;
		}
		
		/**
		 * Parse a keyup bind
		 * @return AstBindValue The resulting AST node representing the parsed expression.
		 * @throws LexerException
		 * @throws ParserException
		 */
		public function parse(): AstBindValue {
			// Maak een nieuw ArithmeticExpression object aan met de lexer
			$generalExpression = new ArithmeticExpression($this->lexer);
			
			// Match een identifier en een dubbele punt ':'
			$this->lexer->match(Token::Identifier);
			$this->lexer->match(Token::Colon);

			// Match het openen van een accolades '{'
			$this->lexer->match(Token::CurlyBraceOpen);
			
			// Parse de tokenstream binnen de accolades
			$ast = $generalExpression->parse();
			
			// Match het sluiten van een accolades '}'
			$this->lexer->match(Token::CurlyBraceClose);
			
			// Als alles goed gaat, retourneer een nieuw AstBindKeyUp object met de geparste AST
			return new AstBindValue($ast);
		}
	}