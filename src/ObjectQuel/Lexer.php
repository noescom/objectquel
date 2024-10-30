<?php
    
    namespace Services\ObjectQuel;

    use Services\Kernel\BasicEnum;
    use Throwable;
    
    /**
     * Simple lexer to dissect doc blocks
     * @package Services\AnnotationsReader
     */
    class Lexer {
	    protected string $string;
	    protected int $pos;
	    protected int $previousPos;
	    protected int $previousPreviousPos;
	    protected int $lineNumber;
	    protected int $length;
	    protected array $keywords;
	    protected array $single_tokens;
	    protected array $two_char_tokens;
	    protected Token $next_token;
	    protected Token $lookahead;
        
        /**
         * Lexer constructor.
         * @param string $string
         * @throws LexerException
         */
        public function __construct(string $string) {
            $this->string = $string;
            $this->pos = 0;
			$this->previousPos = 0;
			$this->lineNumber = 1;
            $this->length = strlen($string);
            $this->keywords = [
                'retrieve' => Token::Retrieve,
                'where'    => Token::Where,
                'and'      => Token::And,
                'or'       => Token::Or,
                'range'    => Token::Range,
                'of'       => Token::Of,
                'is'       => Token::Is,
                'in'       => Token::In,
                'via'      => Token::Via,
                'unique'   => Token::Unique,
                'sort'     => Token::Sort,
                'by'       => Token::By,
                'not'      => Token::Not,
                'asc'      => Token::Asc,
                'desc'     => Token::Desc,
            ];
			
			$this->single_tokens = [
				'.'  => Token::Dot,
				','  => Token::Comma,
				'='  => Token::Equals,
				'>'  => Token::LargerThan,
				'<'  => Token::SmallerThan,
				'('  => Token::ParenthesesOpen,
				')'  => Token::ParenthesesClose,
				'+'  => Token::Plus,
				'-'  => Token::Minus,
				'*'  => Token::Star,
				':'  => Token::Colon,
				';'  => Token::Semicolon,
				'/'  => Token::Slash,
				'\\' => Token::Backslash,
				'%'  => Token::Percentage,
				'#'  => Token::Hash,
				'&'  => Token::Ampersand,
				'^'  => Token::Hat,
				'!'  => Token::Exclamation,
				'?'  => Token::Question,
			];
			
			$this->two_char_tokens = [
				'==' => Token::Equal,
				'!=' => Token::Unequal,
				'<>' => Token::Unequal,
				'>=' => Token::LargerThanOrEqualTo,
				'<=' => Token::SmallerThanOrEqualTo,
				'<<' => Token::BinaryShiftLeft,
				'>>' => Token::BinaryShiftRight,
				'->' => Token::Arrow
			];
            
            $this->next_token = $this->nextToken();
            $this->lookahead = $this->nextToken();
        }
        
        /**
         * Advance $this->pos to the start of the next token
         */
		protected function advance(): void {
			while ($this->pos < $this->length) {
				$currentChar = $this->string[$this->pos];
				
				if ($currentChar == "\n") {
					++$this->lineNumber;
				}
				
				if (!in_array($currentChar, [" ", "\n", "\r", "\t"])) {
					break;
				}
				
				++$this->pos;
			}
		}
		
		/**
		 * Fetches a numeric value (integer or float) from a string starting at the current position.
		 * If the number is malformed (e.g., contains multiple decimal points), an exception is thrown.
		 * @return float|int The numeric value extracted from the string.
		 * @throws LexerException If the number is malformed or if no number is found.
		 */
		protected function fetchNumber(): float|int {
			$string = "";
			$decimalFound = false;
			
			while ($this->pos < $this->length) {
				$char = $this->string[$this->pos];

				if (!ctype_digit($char) && $char !== '.') {
					break; // Geen deel van een getal; stop de iteratie.
				}
				
				if ($char === '.') {
					if ($decimalFound) {
						// Als er al een decimaal punt is gevonden, is het getal foutief gevormd.
						throw new LexerException("Malformed floating point number");
					}

					$decimalFound = true;
				}
				
				$string .= $char;
				$this->pos++;
			}
			
			// Gebruik een early return om direct het type getal te retourneren.
			return $decimalFound ? floatval($string) : intval($string);
		}
        
        /**
         * Fetches the next token
         * @return Token
         * @throws LexerException
         */
        protected function nextToken(): Token {
			// copy pos to previousPos for accurate positioning
			$this->previousPreviousPos = $this->previousPos;
			$this->previousPos = $this->pos;
			
			// advance pos to next token
            $this->advance();
			
            // end of file
            if ($this->pos == $this->length) {
                return new Token(Token::Eof);
            }
            
            // starts with number, so must be number
            if (ctype_digit($this->string[$this->pos])) {
                return new Token(Token::Number, $this->fetchNumber(), $this->lineNumber);
            }
    
            // negative number
            if ($this->pos + 1 < $this->length) {
                if (($this->string[$this->pos] == '-') && ctype_digit($this->string[$this->pos + 1])) {
                    ++$this->pos;
                    return new Token(Token::Number, $this->fetchNumber() * -1, $this->lineNumber);
                }
            }
    
            // double quote = string
            if (($this->string[$this->pos] == '"') || ($this->string[$this->pos] == '\'')) {
				$firstChar = $this->string[$this->pos];
                $string = "";
    
                while ($this->string[++$this->pos] !== $firstChar) {
                    if ($this->pos == $this->length) {
                        throw new LexerException("Unexpected end of data");
                    }
                    
                    if ($this->string[$this->pos] == "\n") {
                        throw new LexerException("Unterminated string");
                    }
                    
                    $string .= $this->string[$this->pos];
                }
    
                ++$this->pos;
                return new Token(Token::String, $string, $this->lineNumber, ['char' => $firstChar]);
            }
			
            // backtick string means regular expression
            if ($this->string[$this->pos] === '`') {
                $string = "";
    
                while ($this->string[++$this->pos] !== '`') {
                    if ($this->pos == $this->length) {
                        throw new LexerException("Unexpected end of data");
                    }
                    
                    if ($this->string[$this->pos] == "\n") {
                        throw new LexerException("Unterminated string");
                    }
                    
                    $string .= $this->string[$this->pos];
                }
    
                ++$this->pos;
                return new Token(Token::RegExp, $string, $this->lineNumber);
            }
			
			// parameter
			if ($this->string[$this->pos] == ":") {
				++$this->pos;
				
				$string = "";
				while (($this->pos < $this->length) && (ctype_alnum($this->string[$this->pos]) || ($this->string[$this->pos] == '_'))) {
					$string .= $this->string[$this->pos++];
				}
				
				return new Token(Token::Parameter, $string, $this->lineNumber);
			}

            // starts with letter, so must be a identifier
            if (ctype_alpha($this->string[$this->pos])) {
				$string = "";
				
                while (($this->pos < $this->length) && (ctype_alnum($this->string[$this->pos]) || ($this->string[$this->pos] == '_'))) {
                    $string .= $this->string[$this->pos++];
                }
                
                if (strcasecmp($string, "true") == 0) {
                    return new Token(Token::True, null, $this->lineNumber);
                } elseif (strcasecmp($string, "false") == 0) {
                    return new Token(Token::False, null, $this->lineNumber);
                } elseif (strcasecmp($string, "null") == 0) {
                    return new Token(Token::Null, null, $this->lineNumber);
                } elseif (isset($this->keywords[strtolower($string)])) {
					return new Token($this->keywords[strtolower($string)], $string, $this->lineNumber);
				} else {
                    return new Token(Token::Identifier, $string, $this->lineNumber);
                }
            }
			
			// two character tokens
			if ($this->pos + 1 < $this->length) {
				$stringToCompare = substr($this->string, $this->pos, 2);
				
				if (isset($this->two_char_tokens[$stringToCompare])) {
					$this->pos += 2;
					return new Token($this->two_char_tokens[$stringToCompare], $stringToCompare, $this->lineNumber);
				}
			}
			
			// single character tokens
			if (isset($this->single_tokens[$this->string[$this->pos]])) {
				$charToCompare = $this->string[$this->pos];
				$token = new Token($this->single_tokens[$charToCompare], $charToCompare, $this->lineNumber);
				++$this->pos;
				return $token;
			}
			
			// Unidentified token
			++$this->pos;
			return new Token(Token::None, null, $this->lineNumber);
        }
        
        /**
         * Match the next token
         * @param int $token
         * @return Token
         * @throws LexerException
         */
        public function match(int $token): Token {
            if ($this->next_token->getType() == $token) {
                $currentToken = $this->next_token;
                $this->next_token = $this->lookahead;
                $this->lookahead = $this->nextToken();
                return $currentToken;
            }
            
            throw new LexerException("Unexpected token");
        }
        
        /**
         * Match the next token
         * @param int $token
         * @param Token|null $result
         * @return bool
         * @throws LexerException
         */
        public function optionalMatch(int $token, Token &$result = null): bool {
            if ($this->next_token->getType() == $token) {
				$result = $this->match($token);
                return true;
            }
            
            return false;
        }
		
		/**
		 * Returns the position of the next token in the source text
		 * @return int
		 */
		public function getPos(): int {
			return $this->previousPreviousPos;
		}
        
        /**
         * Returns the next token without advancing the token counter
         * @return Token
         */
        public function peek(): Token {
            return $this->next_token;
        }

        /**
         * Returns the type of the lookahead
         * @return int
         */
        public function lookahead(): int {
            return $this->next_token->getType();
        }
		
		/**
		 * Returns the token after the current token without advancing the token counter
		 * @return int
		 */
		public function peekNext(): int {
			return $this->lookahead->getType();
		}
		
		/**
		 * Returns the source code
		 * @return string
		 */
		public function getSource(): string {
			return $this->string;
		}

		/**
		 * Returns a part of the source code
		 * @param int $offset
		 * @param int $length
		 * @return false|string
		 */
		public function getSourceSlice(int $offset, int $length): false|string {
			return substr($this->string, $offset, $length);
		}
		
		/**
		 * Returns the line number the token was found on
		 * @return int
		 */
		public function getLineNumber(): int {
			return $this->lineNumber;
		}
	}