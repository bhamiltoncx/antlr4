<?hh // strict

/*
 * Copyright (c) 2012-2017 The ANTLR Project. All rights reserved.
 * Use of this file is governed by the BSD 3-clause license that
 * can be found in the LICENSE.txt file in the project root.
 */

namespace ANTLR;

use ANTLR\ATN\ATN;
use ANTLR\ATN\ATNSimulator;
use ANTLR\ATN\ParseInfo;

abstract class Recognizer<Symbol, ATNInterpreter as ATNSimulator> {
  public const int EOF=-1;

  // private static final Map<Vocabulary, Map<String, Integer>> tokenTypeMapCache =
  //    new WeakHashMap<Vocabulary, Map<String, Integer>>();
  // private static final Map<String[], Map<String, Integer>> ruleIndexMapCache =
  //    new WeakHashMap<String[], Map<String, Integer>>();

  private array<ANTLRErrorListener> $listeners = array(ConsoleErrorListener::$INSTANCE);

  protected ?ATNInterpreter $interp;

  private int $stateNumber = -1;

  public abstract function getTokenNames(): array<string>;
  public abstract function getRuleNames(): array<string>;

  /**
   * Get the vocabulary used by the recognizer.
   *
   * @return A {@link Vocabulary} instance providing information about the
   * vocabulary used by the grammar.
   */
  public function getVocabulary(): Vocabulary {
    return VocabularyImpl::fromTokenNames($this->getTokenNames());
  }

  /**
   * Get a map from token names to token types.
   *
   * <p>Used for XPath and tree pattern compilation.</p>
   */
  public function getTokenTypeMap(): array<string, int> {
    $vocabulary = $this->getVocabulary();
    // TODO: tokenTypeMapCache
    $result = array();
    for ($i = 0; $i <= $this->getATN()->maxTokenType; $i++) {
      $literalName = $vocabulary->getLiteralName($i);
      if ($literalName !== null) {
        $result[$literalName] = $i;
      }

      $symbolicName = $vocabulary->getSymbolicName($i);
      if ($symbolicName !== null) {
        $result[$symbolicName] = $i;
      }
    }

    $result['EOF'] = Token::EOF;

    //tokenTypeMapCache.put(vocabulary, result);

    return $result;
  }

  /**
   * Get a map from rule names to rule indexes.
   *
   * <p>Used for XPath and tree pattern compilation.</p>
   */
  public function getRuleIndexMap(): array<String, Integer> {
    return array_flip($this->getRuleNames());
  }

  public function getTokenType(string $tokenName): int {
    return HH\idx($this->getTokenTypeMap(), $tokenName, Token::INVALID_TYPE);
  }

  /**
   * If this recognizer was generated, it will have a serialized ATN
   * representation of the grammar.
   *
   * <p>For interpreters, we don't know their serialized ATN despite having
   * created the interpreter from it.</p>
   */
  public function getSerializedATN(): string {
    throw new Exception("there is no serialized ATN");
  }

  /** For debugging and other purposes, might want the grammar name.
   *  Have ANTLR generate an implementation for this method.
   */
  public abstract function getGrammarFileName(): string;

  /**
   * Get the {@link ATN} used by the recognizer for prediction.
   *
   * @return The {@link ATN} used by the recognizer for prediction.
   */
  public abstract function getATN(): ATN;

  /**
   * Get the ATN interpreter used by the recognizer for prediction.
   *
   * @return The ATN interpreter used by the recognizer for prediction.
   */
  public function getInterpreter(): ?ATNInterpreter {
    return $this->interp;
  }

  /** If profiling during the parse/lex, this will return DecisionInfo records
   *  for each decision in recognizer in a ParseInfo object.
   *
   * @since 4.3
   */
  public function getParseInfo(): ?ParseInfo {
    return null;
  }

  /**
   * Set the ATN interpreter used by the recognizer for prediction.
   *
   * @param interpreter The ATN interpreter used by the recognizer for
   * prediction.
   */
  public function setInterpreter(ATNInterpreter ?$interpreter): void {
    $this->interp = $interpreter;
  }

  /** What is the error header, normally line/character position information? */
  public function getErrorHeader(RecognitionException e): String {
    $line = $e->getOffendingToken()->getLine();
    $charPositionInLine = $e->getOffendingToken()->getCharPositionInLine();
    return 'line '.$line.':'.$charPositionInLine;
  }

  public function addErrorListener(ANTLRErrorListener $listener): void {
    $this->listeners[] = $listener;
  }

  public function removeErrorListener(ANTLRErrorListener $listener): void {
    $idx = array_search($listener, $this->listeners);
    if ($idx !== false) {
      array_splice($this->listeners, $idx, 1);
    }
  }

  public function removeErrorListeners(): void {
    $this->listeners = array();
  }


  public function getErrorListeners(): array<ANTLRErrorListener> {
    return $this->listeners;
  }

  public function getErrorListenerDispatch(): ANTLRErrorListener {
    return new ProxyErrorListener($this->getErrorListeners());
  }

  // subclass needs to override these if there are sempreds or actions
  // that the ATN interp needs to execute
  public function sempred(RuleContext $localctx, int $ruleIndex, int $actionIndex): bool {
    return true;
  }

  public function precpred(RuleContext $localctx, int $precedence): bool {
    return true;
  }

  public function action(RuleContext $localctx, int $ruleIndex, int $actionIndex): void {
  }

  public final function getState(): int {
    return $this->stateNumber;
  }

  /** Indicate that the recognizer has changed internal state that is
   *  consistent with the ATN state passed in.  This way we always know
   *  where we are in the ATN as the parser goes along. The rule
   *  context objects form a stack that lets us see the stack of
   *  invoking rules. Combine this and we have complete ATN
   *  configuration information.
   */
  public final function setState(int $atnState): void {
//    System.err.println("setState "+atnState);
    $this->stateNumber = $atnState;
//    if ( traceATNStates ) _ctx.trace(atnState);
  }

  public abstract function getInputStream(): IntStream;

  public abstract function setInputStream(IntStream $input): void;


  public abstract function getTokenFactory(): TokenFactory;

  public abstract function setTokenFactory(TokenFactory $input): void;
}
