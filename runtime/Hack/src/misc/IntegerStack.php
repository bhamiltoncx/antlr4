<?hh // strict

/*
 * Copyright (c) 2012-2017 The ANTLR Project. All rights reserved.
 * Use of this file is governed by the BSD 3-clause license that
 * can be found in the LICENSE.txt file in the project root.
 */
package org.antlr.v4.runtime.misc;

public class IntegerStack extends IntegerList {

  public final function push(int $value): void {
    $this->add($value);
  }

  public final function pop(): int {
    return $this->removeAt($this->size() - 1);
  }

  public final function peek(): int {
    return $this->get($this->size() - 1);
  }

}
