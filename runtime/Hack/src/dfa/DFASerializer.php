<?hh // strict

/*
 * Copyright (c) 2012-2017 The ANTLR Project. All rights reserved.
 * Use of this file is governed by the BSD 3-clause license that
 * can be found in the LICENSE.txt file in the project root.
 */

namespace ANTLR\DFA;

use ANTLR\Vocabulary;
use ANTLR\VocabularyImpl;

/** A DFA walker that knows how to dump them to serialized strings. */
class DFASerializer {

  private DFA $dfa;

  private Vocabulary $vocabulary;

  /**
   * @deprecated Use {@link #DFASerializer(DFA, Vocabulary)} instead.
   */
  public function __construct(DFA $dfa, array<string> $tokenNames) {
    $this->dfa = $dfa;
    $this->vocabulary = VocabularyInfo::fromTokenNames($tokenNames);
  }

  public function __construct(DFA $dfa, Vocabulary $vocabulary) {
    $this->dfa = $dfa;
    $this->vocabulary = $vocabulary;
  }

  public function __toString(): string {
    if ( $this->dfa->s0===null ) {
      return '';
    }
    $buf = '';
    foreach ($this->dfa->getStates() as $i => $s) {
      if ( $s->edges!==null ) {
        foreach ($s->edges as $t) {
          if ( $t!==null && $t->stateNumber !== PHP_INT_MAX ) {
            $buf .= $this->getStateString($s);
            $label = $this->getEdgeLabel($i);
            $buf .= '-'.$label.'->'.$this->getStateString($t)."\n";
          }
        }
      }
    }
    return $buf;
  }

  protected function getEdgeLabel(int $i): string {
    return $this->vocabulary->getDisplayName($i - 1);
  }


  protected function getStateString(DFAState $s): string {
    $n = $s->stateNumber;
    $baseStateStr = ($s->isAcceptState ? ':' : '') . 's' . (string)$n . ($s->requiresFullContext ? '^' : '');
    if ( $s->isAcceptState ) {
            if ( $s->predicates!==null ) {
              return $baseStateStr . '=>' . (string) $s->predicates;
            }
            else {
                return $baseStateStr . '=>' . $s->prediction;
            }
    }
    else {
      return $baseStateStr;
    }
  }
}
