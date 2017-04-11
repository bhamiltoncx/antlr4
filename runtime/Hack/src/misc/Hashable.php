<?hh // strict
// Copyright 2004-present Facebook. All Rights Reserved.

interface Hashable<T> {
  public function hash(): int;
  public function equals(T $other): bool;
}
