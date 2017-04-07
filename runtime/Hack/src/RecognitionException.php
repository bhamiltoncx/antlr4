<?hh // strict

/*
 * Copyright (c) 2012-2017 The ANTLR Project. All rights reserved.
 * Use of this file is governed by the BSD 3-clause license that
 * can be found in the LICENSE.txt file in the project root.
 */

namespace ANTLR;

use ANTLR\Misc\IntervalSet;

/** The root of the ANTLR exception hierarchy. In general, ANTLR tracks just
 *  3 kinds of errors: prediction errors, failed predicate errors, and
 *  mismatched input errors. In each case, the parser knows where it is
 *  in the input, where it is in the ATN, the rule invocation stack,
 *  and what kind of problem occurred.
 */
class RecognitionException extends Exception {
  /** The {@link Recognizer} where this exception originated. */
  private ?Recognizer $recognizer;

  private ?RuleContext $ctx;

  private ?IntStream $input;

  /**
   * The current {@link Token} when an error occurred. Since not all streams
   * support accessing symbols by index, we have to track the {@link Token}
   * instance itself.
   */
  private Token $offendingToken;

  private int $offendingState = -1;

  public RecognitionException(
    ?Recognizer $recognizer,
    ?IntStream $input,
    ?ParserRuleContext $ctx,
  )
  {
    $this->recognizer = $recognizer;
    $this->input = $input;
    $this->ctx = $ctx;
    if ($recognizer !== null) {
      $this->offendingState = $recognizer->getState();
    }
  }

  public function __construct(
    string $message,
    ?Recognizer $recognizer,
    ?IntStream $input,
    ?ParserRuleContext $ctx,
  )
  {
    parent::__construct($message);
    $this->recognizer = $recognizer;
    $this->input = $input;
    $this->ctx = $ctx;
    if ($recognizer !== null) {
      $this->offendingState = $recognizer->getState();
    }
  }

  /**
   * Get the ATN state number the parser was in at the time the error
   * occurred. For {@link NoViableAltException} and
   * {@link LexerNoViableAltException} exceptions, this is the
   * {@link DecisionState} number. For others, it is the state whose outgoing
   * edge we couldn't match.
   *
   * <p>If the state number is not known, this method returns -1.</p>
   */
  public function getOffendingState(): int {
    return $this->offendingState;
  }

  protected final function setOffendingState($offendingState): void {
    $this->offendingState = $offendingState;
  }

  /**
   * Gets the set of input symbols which could potentially follow the
   * previously matched symbol at the time this exception was thrown.
   *
   * <p>If the set of expected tokens is not known and could not be computed,
   * this method returns {@code null}.</p>
   *
   * @return The set of token types that could potentially follow the current
   * state in the ATN, or {@code null} if the information is not available.
   */
  public function getExpectedTokens(): ?IntervalSet {
    if ($this->recognizer != null) {
      return $this->recognizer->getATN()->getExpectedTokens(
        $this->offendingState,
        $this->ctx,
      );
    }

    return null;
  }

  /**
   * Gets the {@link RuleContext} at the time this exception was thrown.
   *
   * <p>If the context is not available, this method returns {@code null}.</p>
   *
   * @return The {@link RuleContext} at the time this exception was thrown.
   * If the context is not available, this method returns {@code null}.
   */
  public function getCtx(): ?RuleContext {
    return $this->ctx;
  }

  /**
   * Gets the input stream which is the symbol source for the recognizer where
   * this exception was thrown.
   *
   * <p>If the input stream is not available, this method returns {@code null}.</p>
   *
   * @return The input stream which is the symbol source for the recognizer
   * where this exception was thrown, or {@code null} if the stream is not
   * available.
   */
  public function getInputStream(): ?IntStream {
    return $this->input;
  }


  public function getOffendingToken(): ?Token {
    return $this->offendingToken;
  }

  protected final function setOffendingToken(Token $offendingToken): void {
    $this->offendingToken = $offendingToken;
  }

  /**
   * Gets the {@link Recognizer} where this exception occurred.
   *
   * <p>If the recognizer is not available, this method returns {@code null}.</p>
   *
   * @return The recognizer where this exception occurred, or {@code null} if
   * the recognizer is not available.
   */
  public function getRecognizer(): ?Recognizer {
    return $this->recognizer;
  }
}
