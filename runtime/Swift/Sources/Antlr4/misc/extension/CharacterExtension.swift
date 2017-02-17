/// Copyright (c) 2012-2016 The ANTLR Project. All rights reserved.
/// Use of this file is governed by the BSD 3-clause license that
/// can be found in the LICENSE.txt file in the project root.

//
//  CharacterEextension.swift
//  Antlr.swift
//
//  Created by janyou on 15/9/4.
//

import Foundation

extension Character {

    //"1" -> 1 "2"  -> 2
    var integerValue: Int {
        return Int(String(self)) ?? 0
    }
    public init(integerLiteral value: IntegerLiteralType) {
        self = Character(UnicodeScalar(value)!)
    }

    //char ->  int

    public static var MAX_VALUE: Int {
        return 0x10FFFF;
    }
    public static var MIN_VALUE: Int {
        return 0x0000;
    }
}
