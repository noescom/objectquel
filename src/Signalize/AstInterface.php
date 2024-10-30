<?php
	
	namespace Services\Signalize;
	
	interface AstInterface {
		
		/**
		 * Valideer de AST
		 * @param AstVisitorInterface $visitor
		 * @return mixed
		 */
		public function accept(AstVisitorInterface $visitor);
	}