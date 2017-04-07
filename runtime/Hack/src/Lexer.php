<?hh // strict

/*
 * Copyright (c) 2012-2017 The ANTLR Project. All rights reserved.
 * Use of this file is governed by the BSD 3-clause license that
 * can be found in the LICENSE.txt file in the project root.
 */

namespace ANTLR;

use ANTLR\ATN\LexerATNSimulator;
use ANTLR\Misc\IntegerStack;
use ANTLR\Misc\Interval;

newtype TokenFactorySourcePair = (TokenSource, CharStream);

/** A lexer is a recognizer that draws input symbols from a character stream.
 *  lexer grammars result in a subclass of this object. A Lexer object
 *  uses simplified match() and error recovery mechanisms in the interest
 *  of speed.
 */
abstract class Lexer extends Recognizer<int, LexerATNSimulator>
  implements TokenSource
{
  const int DEFAULT_MODE = 0;
  const int MORE = -2;
  const int SKIP = -3;

  const int DEFAULT_TOKEN_CHANNEL = Token::DEFAULT_CHANNEL;
  const int HIDDEN = Token::HIDDEN_CHANNEL;
  const int MIN_CHAR_VALUE = 0x0000;
  const int MAX_CHAR_VALUE = 0x10FFFF;

  public ?CharStream $input;
  protected ?TokenFactorySourcePair $tokenFactorySourcePair;

  /** How to create token objects */
  protected TokenFactory<CommonToken> $factory = CommonTokenFactory::DEFAULT;

  /** The goal of all lexer rules/methods is to create a token object.
   *  This is an instance variable as multiple rules may collaborate to
   *  create a single token.  nextToken will return this object after
   *  matching lexer rule(s).  If you subclass to allow multiple token
   *  emissions, then set this to the last token to be matched or
   *  something nonnull so that the auto token emit mechanism will not
   *  emit another token.
   */
  public ?Token $token;

  /** What character index in the stream did the current token start at?
   *  Needed, for example, to get the text for current token.  Set at
   *  the start of nextToken.
   */
  public int $tokenStartCharIndex = -1;

  /** The line on which the first character of the token resides */
  public int $tokenStartLine;

  /** The character position of first character within the line */
  public int $tokenStartCharPositionInLine;

  /** Once we see EOF on char stream, next token will be EOF.
   *  If you have DONE : EOF ; then you see DONE EOF.
   */
  public bool $hitEOF;

  /** The channel number for the current token */
  public int $channel;

  /** The token type for the current token */
  public int $type;

  public IntegerStack $modeStack = new IntegerStack();
  public int $mode = Lexer::DEFAULT_MODE;

  /** You can set the text for the current token to override what is in
   *  the input char buffer.  Use setText() or can set this instance var.
   */
  public ?string $text;

  public __construct() { }

  public __construct(CharStream $input) {
    $this->input = $input;
    $this->tokenFactorySourcePair = tuple($this, $input);
  }

  public function reset(): void {
    // wack Lexer state variables
    if ( $this->input !== null ) {
      $this->input->seek(0); // rewind the input
    }
    $this->token = null;
    $this->type = Token::INVALID_TYPE;
    $this->channel = Token::DEFAULT_CHANNEL;
    $this->tokenStartCharIndex = -1;
    $this->tokenStartCharPositionInLine = -1;
    $this->tokenStartLine = -1;
    $this->text = null;

    $this->hitEOF = false;
    $this->mode = Lexer::DEFAULT_MODE;
    $this->modeStack->clear();

    $this->getInterpreter()->reset();
  }

  /** Return a token from this source; i.e., match a token on the char
   *  stream.
   */
  <<__Override>>
  public function nextToken(): Token {
    if ($this->input == null) {
      throw new Exception("nextToken requires a non-null input stream.");
    }

    // Mark start location in char stream so unbuffered streams are
    // guaranteed at least have text of current token
    $tokenStartMarker = $this->input->mark();
    try{
      outer:
      while (true) {
        if ($this->hitEOF) {
          $this->emitEOF();
          return $this->token;
        }

        $this->token = null;
        $this->channel = Token::DEFAULT_CHANNEL;
        $this->tokenStartCharIndex = $this->input->index();
        $this->tokenStartCharPositionInLine = $this->getInterpreter()->getCharPositionInLine();
        $this->tokenStartLine = $this->getInterpreter()->getLine();
        $this->text = null;
        do {
          $this->type = Token::INVALID_TYPE;
//        System.out.println("nextToken line "+tokenStartLine+" at "+((char)input.LA(1))+
//                   " in mode "+mode+
//                   " at index "+input.index());
          try {
            $ttype = $this->getInterpreter()->match($this->input, $this->mode);
          }
          catch (LexerNoViableAltException $e) {
            $this->notifyListeners($e);     // report error
            $this->recover($e);
            $ttype = static::SKIP;
          }
          if ( $this->input->LA(1)==IntStream::EOF ) {
            $this->hitEOF = true;
          }
          if ( $this->type == Token::INVALID_TYPE ) {
            $this->type = $ttype;
          }
          if ( $this->type == static::SKIP ) {
            continue outer;
          }
        } while ( $this->type == static::MORE );
        if ( $this->token == null ) {
          $this->emit();
        }
        return $this->token;
      }
    }
    finally {
      // make sure we release marker after match or
      // unbuffered char stream will keep buffering
      $this->input->release($tokenStartMarker);
    }
  }

  /** Instruct the lexer to skip creating a token for current lexer rule
   *  and look for another token.  nextToken() knows to keep looking when
   *  a lexer rule finishes with token set to SKIP_TOKEN.  Recall that
   *  if token==null at end of any token rule, it creates one for you
   *  and emits it.
   */
  public function skip(): void {
    $this->type = static::SKIP;
  }

  public function more(): void {
    $this->type = MORE;
  }

  public function mode(int $m): void {
    $this->mode = $m;
  }

  public function pushMode(int $m): void {
    //if ( LexerATNSimulator::debug ) System.out.println("pushMode "+m);
    $this->modeStack->push($this->mode);
    $this->mode($m);
  }

  public function popMode(): int {
    if ( $this->modeStack->isEmpty() ) {
      throw new EmptyStackException();
    }
    //if ( LexerATNSimulator.debug ) System.out.println("popMode back to "+ _modeStack.peek());
    $this->mode( $this->modeStack->pop() );
    return $this->mode;
  }

  <<__Override>>
  public function setTokenFactory(TokenFactory $factory): void {
    $this->factory = $factory;
  }

  <<__Override>>
  public function getTokenFactory(): TokenFactory {
    return $this->factory;
  }

  /** Set the char stream and reset the lexer */
  <<__Override>>
  public function setInputStream(IntStream $input): void {
    $this->input = null;
    $this->tokenFactorySourcePair = tuple($this, $input);
    $this->reset();
    $this->input = (CharStream)$input;
    $this->tokenFactorySourcePair = tuple($this, $input);
  }

  <<__Override>>
  public function getSourceName(): string {
    return $this->input->getSourceName();
  }

  <<__Override>>
  public function getInputStream(): CharStream {
    return $this->input;
  }

  /** By default does not support multiple emits per nextToken invocation
   *  for efficiency reasons.  Subclass and override this method, nextToken,
   *  and getToken (to push tokens into a list and pull from that list
   *  rather than a single variable as this implementation does).
   */
  public function emit(Token $token): void {
    //System.err.println("emit "+token);
    $this->token = $token;
  }

  /** The standard method called to automatically emit a token at the
   *  outermost lexical rule.  The token object should point into the
   *  char buffer start..stop.  If there is a text override in 'text',
   *  use that to set the token's text.  Override this method to emit
   *  custom Token objects or provide a new factory.
   */
  public function emit(): Token {
    $t = $this->factory->create($this->tokenFactorySourcePair, $this->type, $this->text, $this->channel, $this->tokenStartCharIndex, $this->getCharIndex()-1,
                  $this->tokenStartLine, $this->tokenStartCharPositionInLine);
    $this->emit($t);
    return $t;
  }

  public function emitEOF(): Token {
    $cpos = $this->getCharPositionInLine();
    $line = $this->getLine();
    $eof = $this->factory->create($this->tokenFactorySourcePair, Token::EOF, null, Token::DEFAULT_CHANNEL, $this->input->index(), $this->input->index()-1,
                                  $line, $cpos);
    $this->emit($eof);
    return $eof;
  }

  <<__Override>>
  public function getLine(): int {
    return $this->getInterpreter()->getLine();
  }

  <<__Override>>
  public function getCharPositionInLine(): int {
    return $this->getInterpreter()->getCharPositionInLine();
  }

  public function setLine(int $line): void {
    $this->getInterpreter()->setLine($line);
  }

  public function setCharPositionInLine(int $charPositionInLine): void {
    $this->getInterpreter()->setCharPositionInLine($charPositionInLine);
  }

  /** What is the index of the current character of lookahead? */
  public function getCharIndex(): int {
    return $this->input->index();
  }

  /** Return the text matched so far for the current token or any
   *  text override.
   */
  public function getText(): string {
    if ( $this->text !== null ) {
      return $this->text;
    }
    return $this->getInterpreter()->getText($this->input);
  }

  /** Set the complete text of this token; it wipes any previous
   *  changes to the text.
   */
  public function setText(string $text): void {
    $this->text = $text;
  }

  /** Override if emitting multiple tokens. */
  public function getToken(): Token {
    return $this->token;
  }

  public function setToken(Token $token): void {
    $this->token = $token;
  }

  public function setType(int $ttype): void {
    $this->type = $ttype;
  }

  public function getType(): int {
    return $this->type;
  }

  public function setChannel(int $channel): void {
    $this->channel = $channel;
  }

  public function getChannel(): int {
    return $this->channel;
  }

  public function getChannelNames(): ?array<string> { return null; }

  public function getModeNames(): ?array<string> {
    return null;
  }

  /** Used to print out token names like ID during debugging and
   *  error reporting.  The generated parsers implement a method
   *  that overrides this to point to their String[] tokenNames.
   */
  <<__Override>>
  public function getTokenNames(): ?array<string> {
    return null;
  }

  /** Return a list of all Token objects in input char stream.
   *  Forces load of all tokens. Does not include EOF token.
   */
  public function getAllTokens(): array<Token> {
    $tokens = array();
    $t = $this->nextToken();
    while ( $t->getType()!=Token::EOF ) {
      $tokens[] = $t;
      $t = $this->nextToken();
    }
    return $tokens;
  }

  public function recover(LexerNoViableAltException $e): void {
    if ($this->input->LA(1) != IntStream::EOF) {
      // skip a char and try again
      $this->getInterpreter()->consume($this->input);
    }
  }

  public function notifyListeners(LexerNoViableAltException $e): void {
    $text = $this->input->getText(Interval::of($this->tokenStartCharIndex, $this->input->index()));
    $msg = "token recognition error at: '". $this->getErrorDisplay($text) . "'";

    $listener = $this->getErrorListenerDispatch();
    $listener->syntaxError($this, null, $this->tokenStartLine, $this->tokenStartCharPositionInLine, $msg, $e);
  }

  public function getErrorDisplay(string $s): string {
    $buf = '';
    $code_point_iter = IntlBreakIterator::createCodePointInstance();
    $code_point_iter->setText($s);
    while (true) {
      $current = $code_point_iter->current();
      $next = $code_point_iter->next();
      if (!$code_point_iter->valid() || !is_int($next)) {
        break;
      }
      $buf .= $this->getErrorDisplay($code_point_iter->getLastCodePoint());
    }
    return $buf;
  }

  public function getErrorDisplay(int $c): string {
    switch ( $c ) {
      case Token::EOF :
        return '<EOF>';
      case 0x0A :
        return '\n';
      case 0x09 :
        return '\t';
      case 0x0d :
        return '\r';
      default:
        return IntlChar::chr($c);
    }
  }

  public function getCharErrorDisplay(int $c): string {
    $s = $this->getErrorDisplay($c);
    return "'"+$s+"'";
  }

  /** Lexers can normally match any char in it's vocabulary after matching
   *  a token, so do the easy thing and just kill a character and hope
   *  it all works out.  You can instead use the rule invocation stack
   *  to do sophisticated error recovery if you are in a fragment rule.
   */
  public function recover(RecognitionException $re): void {
    //System.out.println("consuming char "+(char)input.LA(1)+" during recovery");
    //re.printStackTrace();
    // TODO: Do we lose character or line position information?
    $this->input->consume();
  }
}
