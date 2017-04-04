<?hh // strict

namespace ANTLR\\Misc;

use ANTLR\\Lexer;
use ANTLR\\Token;

/**
 * This class implements the {@link IntSet} backed by a sorted array of
 * non-overlapping intervals. It is particularly efficient for representing
 * large collections of numbers, where the majority of elements appear as part
 * of a sequential range of numbers that are all part of the set. For example,
 * the set { 1, 2, 3, 4, 7, 8 } may be represented as { [1, 4], [7, 8] }.
 *
 * <p>
 * This class is able to represent sets containing any combination of values in
 * the range {@link Integer#MIN_VALUE} to {@link Integer#MAX_VALUE}
 * (inclusive).</p>
 */
final class IntervalSet implements IntSet {
  public static IntervalSet $COMPLETE_CHAR_SET =
    static::of(Lexer::$MIN_CHAR_VALUE, Lexer::$MAX_CHAR_VALUE)
      ->setReadonly(true);

  public static IntervalSet $EMPTY_SET = new IntervalSet()->setReadonly(true);

  /** The list of sorted, disjoint intervals. */
  protected vec<Interval> intervals;

  protected bool readonly;

  public IntervalSet() {
    $this->intervals = vec[];
  }

  public IntervalSet(vec<Interval> $intervals) {
    $this->intervals = $intervals;
  }

  public IntervalSet(IntervalSet set) {
    $this->intervals = vec[];
    $this->addAll($set);
  }

  public IntervalSet(int $el, /* HH_FIXME[4033] typehint varargs */...$els) {
    $this->intervals = vec[];
    $this->add($el);
    foreach (array_filter($els, $x ==> is_int($x)) as $x) {
      $this->add($x);
    }
  }

  /** Create a set with a single element, el. */

  public static function of(int $a): IntervalSet {
    IntervalSet $s = new IntervalSet();
    $s->add($a);
    return $s;
  }

  /** Create a set with all ints within range [a..b] (inclusive) */
  public static function of(int $a, int $b): IntervalSet {
    IntervalSet $s = new IntervalSet();
    $s->add($a,$b);
    return $s;
  }

  public function clear(): void {
    if ( $this->readonly ) {
      throw new IllegalStateException("can't alter readonly IntervalSet");
    }
    $this->intervals = vec[];
  }

  /** Add a single element to the set.  An isolated element is stored
   *  as a range el..el.
   */
  <<__Override>>
  public function add(int $el): void {
    if ( $this->readonly ) {
      throw new IllegalStateException("can't alter readonly IntervalSet");
    }
    $this->add($el,$el);
  }

  /** Add interval; i.e., add all integers from a to b to set.
   *  If b&lt;a, do nothing.
   *  Keep list in sorted order (by left range value).
   *  If overlap, combine ranges.  For example,
   *  If this is {1..5, 10..20}, adding 6..7 yields
   *  {1..5, 6..7, 10..20}.  Adding 4..8 yields {1..8, 10..20}.
   */
  public function add(int $a, int $b): void {
    $this->add(Interval::of($a, $b));
  }

  private function add(Interval $addition): void {
    if ( $this->readonly ) {
      throw new IllegalStateException("can't alter readonly IntervalSet");
    }
    //System.out.println("add "+addition+" to "+intervals.toString());
    if ( $addition->b<$addition->a ) {
      return;
    }

    // find position in list
    for ( $i = 0; $i < count($this->intervals); $i++ ) {
      $r = $this->intervals[$i];
      if ( $addition->equals($r) ) {
        return;
      }
      if ( $addition->adjacent($r) || !$addition->disjoint($r) ) {
        // next to each other, make a single larger interval
        $bigger = $addition->union($r);
        $this->intervals[$i] = $bigger;
        $i++;
        // make sure we didn't just create an interval that
        // should be merged with next interval in list
        while ( $i < count($this->intervals) ) {
          $next = $this->intervals[$i];
          if ( !$bigger->adjacent($next) && $bigger->disjoint($next) ) {
            break;
          }

          // if we bump up against or overlap next, merge
          $new_intervals = vec[];
          for ( $j = 0; $j < $i-1; $j++ ) {
            $new_intervals[] = $this->intervals[$j];
          }
          $new_intervals[] = $bigger->union($next);
          for ( $j = $i+1; $j < count($this->intervals); $j++ ) {
            $new_intervals[] = $this->intervals[$j];
          }
          $this->intervals = $new_intervals;
          $i++;
        }
        return;
      }
      if ( $addition->startsBeforeDisjoint($r) ) {
        $new_intervals = vec[];
        for ($j = 0; $j < $i-1; $j++) {
          $new_intervals[] = $this->intervals[$j];
        }
        $new_intervals[$i-1] = $addition;
        for ($j = $i; $j < count($this->intervals); $j++) {
          $new_intervals[] = $this->intervals[$j];
        }
        $this->intervals = $new_intervals;
        return;
      }
      // if disjoint and after r, a future iteration will handle it
    }
    // ok, must be after last interval (and disjoint from last interval)
    // just add it
    $this->intervals[] = $addition;
  }

  /** combine all sets in the array returned the or'd value */
  public static function or(Traversable<IntervalSet> $sets): IntervalSet {
    IntervalSet r = new IntervalSet();
    foreach ($sets as $s) { $r->addAll($s); }
    return r;
  }

  <<__Override>>
  public function addAll(IntSet $set): IntervalSet {
    if ($set instanceof IntervalSet) {
      $other = (IntervalSet)$set;
      // walk set and add each interval
      $n = count($other->intervals);
      for ($i = 0; $i < $n; $i++) {
        $this->add($other->intervals[$i]);
      }
    }
    else {
      for ($value : $set->toList()) {
        $this->add($value);
      }
    }

    return $this;
  }

  public function complement(int $minElement, int $maxElement): ?IntervalSet {
    return $this->complement(IntervalSet::of($minElement,$maxElement));
  }

  /** {@inheritDoc} */
  <<__Override>>
  public function complement(IntSet $vocabulary): IntervalSet {
    if (vocabulary instanceof IntervalSet) {
      $vocabularyIS = (IntervalSet)$vocabulary;
    }
    else {
      $vocabularyIS = new IntervalSet();
      $vocabularyIS->addAll($vocabulary);
    }

    return $vocabularyIS->subtract($this);
  }

  <<__Override>>
  public function subtract(IntSet a): IntervalSet {
    if ($a->isNil()) {
      return new IntervalSet($this);
    }

    if ($a instanceof IntervalSet) {
      return static::subtract($this, (IntervalSet)$a);
    }

    IntervalSet $other = new IntervalSet();
    other->addAll($a);
    return static::subtract($this, $other);
  }

  /**
   * Compute the set difference between two interval sets. The specific
   * operation is {@code left - right}. If either of the input sets is
   * {@code null}, it is treated as though it was an empty set.
   */

  public static IntervalSet subtract(IntervalSet left, IntervalSet right) {
    if ($left->isNil()) {
      return new IntervalSet();
    }

    $result = new IntervalSet($left);
    if ($right->isNil()) {
      // right set has no elements; just return the copy of the current set
      return $result;
    }

    $resultI = 0;
    $rightI = 0;
    while ($resultI < count($result->intervals) && $rightI < count($right->intervals)) {
      $resultInterval = $result->intervals[$resultI];
      $rightInterval = $right->intervals[$rightI];

      // operation: (resultInterval - rightInterval) and update indexes

      if ($rightInterval->b < $resultInterval->a) {
        rightI++;
        continue;
      }

      if ($rightInterval->a > $resultInterval->b) {
        resultI++;
        continue;
      }

      if ($rightInterval->a > $resultInterval->a) {
        $beforeCurrent = new Interval($resultInterval->a, $rightInterval->a - 1);
      } else {
        $beforeCurrent = null;
      }

      if ($rightInterval->b < $resultInterval->b) {
        $afterCurrent = new Interval($rightInterval->b + 1, $resultInterval->b);
      } else {
        $afterCurrent = null;
      }

      if ($beforeCurrent !== null) {
        if ($afterCurrent !== null) {
          // split the current interval into two
          $new_intervals = vec[];
          for ($i = 0; $i < $resultI; $i++) {
            $new_intervals[] = $result->intervals[$i];
          }
          $new_intervals[] = $beforeCurrent;
          for ($i = $resultI; $i < count($result->intervals); $i++) {
            $new_intervals[] = $result->intervals[$i];
          }
          $result->intervals = $new_intervals;
          $resultI++;
          $rightI++;
          continue;
        }
        else {
          // replace the current interval
          $result->intervals[$resultI] = $beforeCurrent;
          $resultI++;
          continue;
        }
      }
      else {
        if ($afterCurrent !== null) {
          // replace the current interval
          $result->intervals[$resultI] = $afterCurrent;
          $rightI++;
          continue;
        }
        else {
          // remove the current interval (thus no need to increment resultI)
          $new_intervals = vec[];
          for ($i = 0; $i < count($result->intervals); $i++) {
            if ($i == $resultI) {
              continue;
            }
            $new_intervals[] = $result->intervals[$i];
          }
          $result->intervals = $new_intervals;
          continue;
        }
      }
    }

    // If rightI reached right.intervals.size(), no more intervals to subtract from result.
    // If resultI reached result.intervals.size(), we would be subtracting from an empty set.
    // Either way, we are done.
    return $result;
  }

  <<__Override>>
  public function or(IntSet a): IntervalSet {
    IntervalSet $o = new IntervalSet();
    $o->addAll($this);
    $o->addAll($a);
    return $o;
  }

  /** {@inheritDoc} */
  <<__Override>>
  public function and(IntSet other): InteralSet {
    $myIntervals = this->intervals;
    $theirIntervals = ((IntervalSet)$other)->intervals;
    $intersection = new IntervalSet();
    $mySize = count($myIntervals);
    $theirSize = count($theirIntervals);
    $i = 0;
    $j = 0;
    // iterate down both interval lists looking for nondisjoint intervals
    while ( $i<$mySize && $j<$theirSize ) {
      $mine = $myIntervals[i];
      $theirs = $theirIntervals[j];
      //System.out.println("mine="+mine+" and theirs="+theirs);
      if ( $mine->startsBeforeDisjoint($theirs) ) {
        // move this iterator looking for interval that might overlap
        $i++;
      }
      elseif ( $theirs->startsBeforeDisjoint($mine) ) {
        // move other iterator looking for interval that might overlap
        $j++;
      }
      elseif ( $mine->properlyContains($theirs) ) {
        // overlap, add intersection, get next theirs
        $intersection->add($mine->intersection($theirs));
        $j++;
      }
      elseif ( $theirs->properlyContains($mine) ) {
        // overlap, add intersection, get next mine
        $intersection->add($mine->intersection($theirs));
        $i++;
      }
      elseif ( !$mine->disjoint($theirs) ) {
        // overlap, add intersection
        $intersection->add($mine->intersection($theirs));
        // Move the iterator of lower range [a..b], but not
        // the upper range as it may contain elements that will collide
        // with the next iterator. So, if mine=[0..115] and
        // theirs=[115..200], then intersection is 115 and move mine
        // but not theirs as theirs may collide with the next range
        // in thisIter.
        // move both iterators to next ranges
        if ( $mine->startsAfterNonDisjoint($theirs) ) {
          $j++;
        }
        elseif ( $theirs->startsAfterNonDisjoint($mine) ) {
          $i++;
        }
      }
    }
    return $intersection;
  }


  /** {@inheritDoc} */
  <<__Override>>
  public function contains(int el): bool {
    return false;
  }

  /** {@inheritDoc} */
  <<__Override>>
  public function isNil(): bool {
    return $this->intervals===null || $this->intervals->isEmpty();
  }

  /**
   * Returns the maximum value contained in the set if not isNil().
   *
   * @return the maximum value contained in the set.
   * @throws RuntimeException if set is empty
   */
  public function getMaxElement(): int {
    if ( $this->isNil() ) {
      throw new Exception("set is empty");
    }
    $last = $this->intervals[count($this->intervals)-1];
    return $last->b;
  }

  /**
   * Returns the minimum value contained in the set if not isNil().
   *
   * @return the minimum value contained in the set.
   * @throws RuntimeException if set is empty
   */
  public function getMinElement(): int {
    if ( $this->isNil() ) {
      throw new Exception("set is empty");
    }

    return $this->intervals[0]->a;
  }

  /** Return a list of Interval objects. */
  public function getIntervals(): vec<Interval> {
    return $this->intervals;
  }

  /** Are two IntervalSets equal?  Because all intervals are sorted
   *  and disjoint, equals is a simple linear walk over both lists
   *  to make sure they are the same.  Interval.equals() is used
   *  by the List.equals() method to check the ranges.
   */
  public function equals(IntervalSet other): bool {
    return this->intervals == $other->intervals;
  }

  public function __toString(): string { return $this->toString(false); }

  public function toString(bool elemAreChar): string {
    if ( $this->isNil() ) {
      return '{}';
    }
    $buf = '';
    if ( $this->size()>1 ) {
      $buf .= '{';
    }
    $first = true;
    foreach ($this->intervals as $I) {
      if ($first) {
        $first = false;
      } else {
        $buf .= ', ';
      }
      $a = $I->a;
      $b = $I->b;
      if ( $a==$b ) {
        if ( $a==Token::EOF ) {
          $buf .= '<EOF>';
        }
        elseif ( $elemAreChar ) {
          $buf .= "'" . IntlChar::chr($a) . "'";
        }
        else {
          $buf .= (string)$a;
        }
      }
      else {
        if ( $elemAreChar ) {
          $buf .= "'" . IntlChar::chr($a) . "'..'" . IntlChar::chr($b) . "'";
        }
        else {
          $buf .= (string)$a . '..' . (string)$b;
        }
      }
    }
    if ( $this->size()>1 ) {
      $buf .= '}';
    }
    return $buf;
    }
    public function toString(Vocabulary vocabulary): string {
    StringBuilder buf = new StringBuilder();
    if ( this.intervals==null || this.intervals.isEmpty() ) {
      return "{}";
    }
    if ( this.size()>1 ) {
      buf.append("{");
    }
    Iterator<Interval> iter = this.intervals.iterator();
    while (iter.hasNext()) {
      Interval I = iter.next();
      int a = I.a;
      int b = I.b;
      if ( a==b ) {
        buf.append(elementName(vocabulary, a));
      }
      else {
        for (int i=a; i<=b; i++) {
          if ( i>a ) buf.append(", ");
          buf.append(elementName(vocabulary, i));
        }
      }
      if ( iter.hasNext() ) {
        buf.append(", ");
      }
    }
    if ( this.size()>1 ) {
      buf.append("}");
    }
    return buf.toString();
  }

  private function elementName(Vocabulary $vocabulary, int $a): string {
    if ($a == Token::EOF) {
      return '<EOF>';
    }
    elseif (a == Token::EPSILON) {
      return '<EPSILON>';
    }
    else {
      return $vocabulary->getDisplayName($a);
    }
  }

  <<__Override>>
  public function size(): int {
    $numIntervals = count($this->intervals);
    if ( $numIntervals==1 ) {
      $firstInterval = $this->intervals[0];
      return $firstInterval->b-$firstInterval->a+1;
    }
    for ( $i = 0; $i < numIntervals; $i++ ) {
      $I = $intervals[$i];
      $n += ($I->b-$I->a+1);
    }
    return $n;
  }

  public function toIntegerList(): IntegerList {
    $values = new IntegerList($this->size());
    foreach ($this->intervals as $I) {
      $a = $I->a;
      $b = $I->b;
      for ($v=$a; $v<=$b; $v++) {
        $values->add($v);
      }
    }
    return $values;
  }

  <<__Override>>
  public function toList(): vec<int> {
    $values = vec[];
    foreach ($this->intervals as $I) {
      $a = $I->a;
      $b = $I->b;
      for ($v=$a; $v<=$b; $v++) {
        $values[] = $v;
      }
    }
    return $values;
  }

  public function toSet(): keyset<int> {
    $s = keyset[];
    foreach ($this->intervals as $I) {
      $a = $I->a;
      $b = $I->b;
      for ($v=$a; $v<=$b; $v++) {
        $s[] = $v;
      }
    }
    return $s;
  }

  public function toArray(): vec<int> {
    return $this->toIntegerList()->toVec();
  }

  <<__Override>>
  public function remove(int $el): void {
    if ( $this->readonly ) { throw new Exception("can't alter readonly IntervalSet"); }
    $new_intervals = vec[];
    foreach ($this->intervals as $I) {
      $a = $I->a;
      $b = $I->b;
      if ( $el<$a ) {
        // list is sorted and el is before this interval; not here
        return;
      }
      if ( $el==$a && $el==$b ) {
        // if whole interval x..x, rm
      }
      elseif ( $el==$a ) {
        // if on left edge x..b, adjust left
        $new_intervals[] = new Interval($a+1, $b);
      }
      elseif ( $el==$b ) {
        // if on right edge a..x, adjust right
        $new_intervals[] = new Interval($a, $b-1);
      }
      elseif ( el>a && el<b ) { // found in this interval
        $new_intervals[] = new Interval($a, $el-1);
        $new_intervals[] = new Interval($el+1, $b);
      }
    }
    $this->intervals = $new_intervals;
  }

  public function isReadonly(): bool {
    return $this->readonly;
  }

  public function setReadonly(bool readonly): IntervalSet {
    if ( $this->readonly && !$readonly ) {
      throw new Exception("can't alter readonly IntervalSet");
    }
    $this->readonly = $readonly;
    return $this;
  }
}
