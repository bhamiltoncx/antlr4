<?hh // strict

/*
 * Copyright (c) 2012-2017 The ANTLR Project. All rights reserved.
 * Use of this file is governed by the BSD 3-clause license that
 * can be found in the LICENSE.txt file in the project root.
 */

namespace ANTLR\Misc;

class IntegerList {

  private array<int> data;

  public function __construct() {
    $this->data = array();
  }

  public function __construct(IntegerList $list) {
    $this->data = $list->data;
  }

  public function __construct(Traversable<int> $list) {
    $this->data = array($list);
  }

  public final function add(int $value): void {
    $this->data[] = $value;
  }

  public final function addAll(array<int> $array): void {
    array_splice($this->data, count($this->data), 0, $array);
  }

  public final function addAll(IntegerList $list): void {
    $this->addAll($list->data);
  }

  public final function get(int $index): int {
    if ($index < 0 || $index >= count($this->data)) {
      throw new Exception();
    }

    return $this->data[$index];
  }

  public final function contains(int $value): bool {
    return in_array($value, $this->data);
  }

  public final function set(int $index, int $value): int {
    if ($index < 0 || $index >= count($this->data)) {
      throw new Exception();
    }

    $previous = $this->data[$index];
    $this->data[$index] = $value;
    return $previous;
  }

  public final function removeAt(int $index): int {
    $value = $this->get($index);
    array_splice($this->data, $index, 1);
    return $value;
  }

  public final function removeRange(int $fromIndex, int $toIndex): void {
    if ($fromIndex < 0 ||
        $toIndex < 0 ||
        $fromIndex > count($this->data) ||
        $toIndex > count($this->data)) {
      throw new Exception();
    }
    if ($fromIndex > $toIndex) {
      throw new Exception();
    }
    $range_size = $toIndex - $fromIndex;
    array_splice($this->data, $fromIndex, $range_size);
  }

  public final function isEmpty(): bool {
    return count($this->data) == 0;
  }

  public final function size(): int {
    return count($this->data);
  }

  public final function clear(): void {
    $this->data = array_fill(0, $this->size, 0);
    $this->size = 0;
  }

  public function toArray(): array<int> {
    return $this->data;
  }

  public final function sort(): void {
    sort($this->data);
  }

  /**
   * Compares the specified object with this list for equality.  Returns
   * {@code true} if and only if the specified object is also an {@link IntegerList},
   * both lists have the same size, and all corresponding pairs of elements in
   * the two lists are equal.  In other words, two lists are defined to be
   * equal if they contain the same elements in the same order.
   * <p>
   * This implementation first checks if the specified object is this
   * list. If so, it returns {@code true}; if not, it checks if the
   * specified object is an {@link IntegerList}. If not, it returns {@code false};
   * if so, it checks the size of both lists. If the lists are not the same size,
   * it returns {@code false}; otherwise it iterates over both lists, comparing
   * corresponding pairs of elements.  If any comparison returns {@code false},
   * this method returns {@code false}.
   *
   * @param o the object to be compared for equality with this list
   * @return {@code true} if the specified object is equal to this list
   */
  public function equals(IntegerList $other): bool {
    return $this->data == $other->data;
  }

  /**
   * Returns a string representation of this list.
   */
  public function toString(): string {
    return implode(', ', $this->data);
  }

  public final function toSerialized(): string {
    return json_encode($this->data);
  }
}
