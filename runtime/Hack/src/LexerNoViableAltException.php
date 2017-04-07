<?hh // strict

/*
 * Copyright (c) 2012-2017 The ANTLR Project. All rights reserved.
 * Use of this file is governed by the BSD 3-clause license that
 * can be found in the LICENSE.txt file in the project root.
 */

namespace ANTLR;

use ATN\ATNConfigSet;
use Misc\Interval;
use Misc\Utils;

public class LexerNoViableAltException extends RecognitionException {
  /** Matching attempted at what input index? */
  private int $startIndex;

  /** Which configurations did we try at input.index() that couldn't match input.LA(1)? */
  private ATNConfigSet $deadEndConfigs;

  public function __construct(Lexer $lexer,
                              CharStream $input,
                              int $startIndex,
                              ATNConfigSet $deadEndConfigs) {
    parent::__construct($lexer, $input, null);
    $this->startIndex = $startIndex;
    $this->deadEndConfigs = $deadEndConfigs;
  }

  public function getStartIndex(): int {
    return $this->startIndex;
  }


  public function getDeadEndConfigs(): ATNConfigSet {
    return $this->deadEndConfigs;
  }

  public function toString(): string {
    $symbol = '';
    if ($this->startIndex >= 0 && $this->startIndex < $this->getInputStream()->size()) {
      $symbol = $this->getInputStream()->getText(Interval::of($startIndex,$startIndex));
      $symbol = Utils::escapeWhitespace($symbol, false);
    }

    return sprintf("%s('%s')", get_class($this), $symbol);
  }
}
