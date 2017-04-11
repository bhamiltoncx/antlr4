<?hh // strict

/*
 * Copyright (c) 2012-2017 The ANTLR Project. All rights reserved.
 * Use of this file is governed by the BSD 3-clause license that
 * can be found in the LICENSE.txt file in the project root.
 */

namespace ANTRL\DFA;

use ANTLR\VocabularyImpl;

class LexerDFASerializer extends DFASerializer {
  public function __construct(DFA $dfa) {
    parent::__construct($dfa, VocabularyImpl::EMPTY_VOCABULARY);
  }

  <<__Override>>
  protected function getEdgeLabel(int $i): string {
    return "'" . IntlChar::chr($i) . "'";
  }
}
