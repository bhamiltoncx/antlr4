<?hh // strict

/*
 * Copyright (c) 2012-2017 The ANTLR Project. All rights reserved.
 * Use of this file is governed by the BSD 3-clause license that
 * can be found in the LICENSE.txt file in the project root.
 */

namespace ANTLR\Misc;

/**
 *
 * @author Sam Harwell
 */
abstract class MurmurHash {

  private const int DEFAULT_SEED = 0;

  /**
   * Initialize the hash using the default seed value.
   *
   * @return the intermediate hash value
   */
  public static function initialize(): int {
    return static::initialize(static::DEFAULT_SEED);
  }

  /**
   * Initialize the hash using the specified {@code seed}.
   *
   * @param seed the seed
   * @return the intermediate hash value
   */
  public static function initialize(int $seed): int {
    return $seed;
  }

  // Mimic Java's >>> operator. From http://stackoverflow.com/a/14428473
  private static function unsignedRightShift(int $value, int $shift): int {
    if ($shift === 0) {
      return $value;
    }
    return ($value >> $shift) & ~(1<<(8*PHP_INT_SIZE-1)>>($shift-1));
  }

  /**
   * Update the intermediate hash value for the next input {@code value}.
   *
   * @param hash the intermediate hash value
   * @param value the value to add to the current hash
   * @return the updated intermediate hash value
   */
  public static function update(int $hash, int $value): int {
    $c1 = 0xCC9E2D51;
    $c2 = 0x1B873593;
    $r1 = 15;
    $r2 = 13;
    $m = 5;
    $n = 0xE6546B64;

    $k = $value;
    $k = $k * $c1;
    $k = ($k << $r1) | (static::unsignedRightShift($k, 32 - $r1));
    $k = $k * $c2;

    $hash = $hash ^ $k;
    $hash = ($hash << $r2) | (static::unsignedRightShift($hash, 32 - $r2));
    $hash = $hash * $m + $n;

    return $hash;
  }

  /**
   * Update the intermediate hash value for the next input {@code value}.
   *
   * @param hash the intermediate hash value
   * @param value the value to add to the current hash
   * @return the updated intermediate hash value
   */
  public static function update(int $hash, Hashable $value): int {
    return static::update($hash, $value->hashCode());
  }

  /**
   * Apply the final computation steps to the intermediate value {@code hash}
   * to form the final result of the MurmurHash 3 hash function.
   *
   * @param hash the intermediate hash value
   * @param numberOfWords the number of integer values added to the hash
   * @return the final hash result
   */
  public static function finish(int $hash, int $numberOfWords): int {
    $hash = $hash ^ ($numberOfWords * 4);
    $hash = $hash ^ (static::unsignedRightShift($hash, 16));
    $hash = $hash * 0x85EBCA6B;
    $hash = $hash ^ (static::unsignedRightShift($hash, 13));
    $hash = $hash * 0xC2B2AE35;
    $hash = $hash ^ (static::unsignedRightShift($hash, 16));
    return $hash;
  }

  /**
   * Utility function to compute the hash code of an array using the
   * MurmurHash algorithm.
   *
   * @param <T> the array element type
   * @param data the array data
   * @param seed the seed for the MurmurHash algorithm
   * @return the hash code of the data
   */
  public static function hashCode<T>(Traversable<T> $data, int $seed) {
    $hash = static::initialize($seed);
    $length = 0;
    foreach ($data as $value) {
      $hash = static::update($hash, $value);
      $length++;
    }

    $hash = static::finish($hash, $length);
    return $hash;
  }
}
