/*
 * Copyright (c) 2012-2017 The ANTLR Project. All rights reserved.
 * Use of this file is governed by the BSD 3-clause license that
 * can be found in the LICENSE.txt file in the project root.
 */

package org.antlr.v4.misc;

import java.util.Objects;

/**
 * Utility class to parse escapes like:
 *   \\n
 *   \\uABCD
 *   \\u{10ABCD}
 *   \\p{Foo}
 *   \\P{Bar}
 */
public abstract class EscapeSequenceParsing {
	public static class Result {
		public enum Type {
			UNICODE_CODE_POINT,
			UNICODE_PROPERTY_NAME,
			UNICODE_PROPERTY_NAME_INVERTED
		};

		public final Type type;
		public final int codePoint;
		public final String propertyName;
		public final int codeUnitLength;

		public Result(Type type, int codePoint, String propertyName, int codeUnitLength) {
			this.type = type;
			this.codePoint = codePoint;
			this.propertyName = propertyName;
			this.codeUnitLength = codeUnitLength;
		}

		@Override
		public String toString() {
			return String.format(
					"%s type=%s codePoint=0x%04X propertyName=%s codeUnitLength=%d",
					super.toString(),
					type,
					codePoint,
					propertyName,
					codeUnitLength);
		}

		@Override
		public boolean equals(Object other) {
			if (!(other instanceof Result)) {
				return false;
			}
			Result that = (Result) other;
			return Objects.equals(this.type, that.type) &&
				Objects.equals(this.codePoint, that.codePoint) &&
				Objects.equals(this.propertyName, that.propertyName) &&
				Objects.equals(this.codeUnitLength, that.codeUnitLength);
		}

		@Override
		public int hashCode() {
			return Objects.hash(type, codePoint, propertyName, codeUnitLength);
		}
	}

	/**
	 * Parses a single escape sequence starting at {@code startOff}.
	 *
	 * Returns null if no valid escape sequence was found, a Result otherwise.
	 */
	public static Result parseEscape(String s, int startOff) {
		int offset = startOff;
		if (offset + 2 > s.length() || s.codePointAt(offset) != '\\') {
			return null;
		}
		// Move past backslash
		offset++;
		int escaped = s.codePointAt(offset);
		// Move past escaped code point
		offset += Character.charCount(escaped);
		if (escaped == 'u') {
			// \\u{1} is the shortest we support
			if (offset + 3 > s.length()) {
				return null;
			}
			int hexStartOffset;
			int hexEndOffset;
			if (s.codePointAt(offset) == '{') {
				hexStartOffset = offset + 1;
				hexEndOffset = s.indexOf('}', hexStartOffset);
				offset = hexEndOffset + 1;
			} else {
				if (offset + 4 > s.length()) {
					return null;
				}
				hexStartOffset = offset;
				hexEndOffset = offset + 4;
				offset = hexEndOffset;
			}
			int codePointValue = CharSupport.parseHexValue(s, hexStartOffset, hexEndOffset);
			if (codePointValue == -1) {
				return null;
			}
			return new Result(
				Result.Type.UNICODE_CODE_POINT,
				codePointValue,
				null,
				offset - startOff);
		} else if (escaped == 'p' || escaped == 'P') {
			// \p{L} is the shortest we support
			if (offset + 3 > s.length() || s.codePointAt(offset) != '{') {
				return null;
			}
			int openBraceOffset = offset;
			int closeBraceOffset = s.indexOf('}', openBraceOffset);
			String propertyName = s.substring(openBraceOffset + 1, closeBraceOffset);
			offset = closeBraceOffset + 1;
			Result.Type type = escaped == 'p' ?
					Result.Type.UNICODE_PROPERTY_NAME :
					Result.Type.UNICODE_PROPERTY_NAME_INVERTED;
			return new Result(
				type,
				-1,
				propertyName,
				offset - startOff);
		} else if (escaped < CharSupport.ANTLRLiteralEscapedCharValue.length) {
			int codePoint = CharSupport.ANTLRLiteralEscapedCharValue[escaped];
			if (codePoint == 0) {
				return null;
			}
			return new Result(
				Result.Type.UNICODE_CODE_POINT,
				codePoint,
				null,
				offset - startOff);
		} else {
			return null;
		}
	}
}
