/*
 * Copyright (c) 2012-2017 The ANTLR Project. All rights reserved.
 * Use of this file is governed by the BSD 3-clause license that
 * can be found in the LICENSE.txt file in the project root.
 */

package org.antlr.v4.runtime.atn;

import org.antlr.v4.runtime.misc.IntervalSet;

public abstract class CodePointTransitions {
	public static Transition createWithCodePoint(ATNState target, int codePoint) {
		if (Character.isSupplementaryCodePoint(codePoint)) {
			return new SetTransition(target, IntervalSet.of(codePoint));
		} else {
			return new AtomTransition(target, codePoint);
		}
	}

	public static Transition createWithCodePointRange(
			ATNState target,
			int codePointFrom,
			int codePointTo) {
		if (Character.isSupplementaryCodePoint(codePointFrom) ||
		    Character.isSupplementaryCodePoint(codePointTo)) {
			return new SetTransition(target, IntervalSet.of(codePointFrom, codePointTo));
		} else {
			return new RangeTransition(target, codePointFrom, codePointTo);
		}
	}
}
