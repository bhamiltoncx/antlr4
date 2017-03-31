/*
 * Copyright (c) 2012-2017 The ANTLR Project. All rights reserved.
 * Use of this file is governed by the BSD 3-clause license that
 * can be found in the LICENSE.txt file in the project root.
 */

package org.antlr.v4.codegen.target;

import org.antlr.v4.codegen.CodeGenerator;
import org.antlr.v4.codegen.Target;
import org.antlr.v4.codegen.UnicodeEscapes;
import org.antlr.v4.tool.ast.GrammarAST;

import java.util.Arrays;
import java.util.HashSet;
import java.util.Locale;
import java.util.Set;

/**
 * Codegen target for the Hack language (http://hacklang.org/).
 */
public final class HackTarget extends Target {
	private static final String[] hackKeywords =
	{
			"__construct",
			"__destruct",
			"abstract",
			"and",
			"array",
			"as",
			"async",
			"attribute",
			"await",
			"bool",
			"break",
			"case",
			"catch",
			"category",
			"children",
			"class",
			"clone",
			"const",
			"continue",
			"darray",
			"default",
			"dict",
			"do",
			"double",
			"echo",
			"else",
			"elseif",
			"enum",
			"extends",
			"false",
			"final",
			"finally",
			"float",
			"for",
			"foreach",
			"function",
			"if",
			"implements",
			"include",
			"include_once",
			"instanceof",
			"int",
			"interface",
			"keyset",
			"list",
			"namespace",
			"new",
			"newtype",
			"noreturn",
			"null",
			"object",
			"or",
			"print",
			"private",
			"protected",
			"public",
			"require",
			"require_once",
			"return",
			"shape",
			"static",
			"string",
			"super",
			"switch",
			"throw",
			"trait",
			"true",
			"try",
			"type",
			"unset",
			"use",
			"varray",
			"vec",
			"where",
			"while",
			"xor",
			"yield"
	};

	public HackTarget(CodeGenerator gen) {
		super(gen, "Hack");
	}

	@Override
	public int getSerializedATNSegmentLimit() {
		return Integer.MAX_VALUE;
	}

	@Override
	protected boolean visibleGrammarSymbolCausesIssueInGeneratedCode(GrammarAST idNode) {
		return getBadWords().contains(idNode.getText());
	}

	@Override
	public String getVersion() {
		return "4.7";
	}

	/** Avoid grammar symbols in this set to prevent conflicts in gen'd code. */
	protected final Set<String> badWords = new HashSet<String>();

	public Set<String> getBadWords() {
		if (badWords.isEmpty())
		{
			addBadWords();
		}

		return badWords;
	}

	protected void addBadWords() {
		badWords.addAll(Arrays.asList(hackKeywords));
		badWords.add("rule");
		badWords.add("parserRule");
	}

	@Override
	protected void appendUnicodeEscapedCodePoint(int codePoint, StringBuilder sb) {
		// Hack and Swift share the same escaping style.
		UnicodeEscapes.appendSwiftStyleEscapedCodePoint(codePoint, sb);
	}
}
