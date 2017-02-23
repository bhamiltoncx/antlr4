/*
 * Copyright (c) 2012-2017 The ANTLR Project. All rights reserved.
 * Use of this file is governed by the BSD 3-clause license that
 * can be found in the LICENSE.txt file in the project root.
 */

package org.antlr.v4.test.tool;

import org.antlr.v4.misc.EscapeSequenceParsing;

import org.junit.Test;

import static org.antlr.v4.misc.EscapeSequenceParsing.Result;
import static org.junit.Assert.assertEquals;
import static org.junit.Assert.assertNull;

public class TestEscapeSequenceParsing {
	@Test
	public void testParseEmpty() {
		assertNull(EscapeSequenceParsing.parseEscape("", 0));
	}

	@Test
	public void testParseJustBackslash() {
		assertNull(EscapeSequenceParsing.parseEscape("\\", 0));
	}

	@Test
	public void testParseInvalidEscape() {
		assertNull(EscapeSequenceParsing.parseEscape("\\z", 0));
	}

	@Test
	public void testParseNewline() {
		assertEquals(
			new Result(Result.Type.UNICODE_CODE_POINT, '\n', null, 2),
			EscapeSequenceParsing.parseEscape("\\n", 0));
	}

	@Test
	public void testParseUnicodeTooShort() {
		assertNull(EscapeSequenceParsing.parseEscape("\\uABC", 0));
	}

	@Test
	public void testParseUnicodeBMP() {
		assertEquals(
			new Result(Result.Type.UNICODE_CODE_POINT, 0xABCD, null, 6),
			EscapeSequenceParsing.parseEscape("\\uABCD", 0));
	}

	@Test
	public void testParseUnicodeSMPTooShort() {
		assertNull(EscapeSequenceParsing.parseEscape("\\u{}", 0));
	}

	@Test
	public void testParseUnicodeSMP() {
		assertEquals(
			new Result(Result.Type.UNICODE_CODE_POINT, 0x10ABCD, null, 10),
			EscapeSequenceParsing.parseEscape("\\u{10ABCD}", 0));
	}

	@Test
	public void testParseUnicodePropertyTooShort() {
		assertNull(EscapeSequenceParsing.parseEscape("\\p{}", 0));
	}

	@Test
	public void testParseUnicodeProperty() {
		assertEquals(
			new Result(Result.Type.UNICODE_PROPERTY_NAME, -1, "Lu", 6),
			EscapeSequenceParsing.parseEscape("\\p{Lu}", 0));
	}

	@Test
	public void testParseUnicodePropertyInvertedTooShort() {
		assertNull(EscapeSequenceParsing.parseEscape("\\P{}", 0));
	}

	@Test
	public void testParseUnicodePropertyInverted() {
		assertEquals(
			new Result(Result.Type.UNICODE_PROPERTY_NAME_INVERTED, -1, "Lu", 6),
			EscapeSequenceParsing.parseEscape("\\P{Lu}", 0));
	}
}
