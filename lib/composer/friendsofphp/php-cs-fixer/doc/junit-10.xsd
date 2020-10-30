<?xml version="1.0" encoding="UTF-8" ?>
<!--
The MIT License (MIT)

Copyright (c) 2014, Gregory Boissinot

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in
all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
THE SOFTWARE.
-->
<xs:schema xmlns:xs="http://www.w3.org/2001/XMLSchema">

    <xs:element name="failure">
        <xs:complexType mixed="true">
            <xs:attribute name="type" type="xs:string" use="optional"/>
            <xs:attribute name="message" type="xs:string" use="optional"/>
        </xs:complexType>
    </xs:element>

    <xs:element name="error">
        <xs:complexType mixed="true">
            <xs:attribute name="type" type="xs:string" use="optional"/>
            <xs:attribute name="message" type="xs:string" use="optional"/>
        </xs:complexType>
    </xs:element>

    <xs:element name="skipped">
        <xs:complexType mixed="true">
            <xs:attribute name="type" type="xs:string" use="optional"/>
            <xs:attribute name="message" type="xs:string" use="optional"/>
        </xs:complexType>
    </xs:element>

    <xs:element name="properties">
        <xs:complexType>
            <xs:sequence>
                <xs:element ref="property" minOccurs="0" maxOccurs="unbounded"/>
            </xs:sequence>
        </xs:complexType>
    </xs:element>

    <xs:element name="property">
        <xs:complexType>
            <xs:attribute name="name" type="xs:string" use="required"/>
            <xs:attribute name="value" type="xs:string" use="required"/>
        </xs:complexType>
    </xs:element>

    <xs:element name="system-err" type="xs:string"/>
    <xs:element name="system-out" type="xs:string"/>

    <xs:element name="testcase">
        <xs:complexType>
            <xs:sequence>
                <xs:choice minOccurs="0" maxOccurs="unbounded">
                    <xs:element ref="skipped"/>
                    <xs:element ref="error"/>
                    <xs:element ref="failure"/>
                    <xs:element ref="system-out"/>
                    <xs:element ref="system-err"/>
                </xs:choice>
            </xs:sequence>
            <xs:attribute name="name" type="xs:string" use="required"/>
            <xs:attribute name="assertions" type="xs:string" use="optional"/>
            <xs:attribute name="time" type="xs:string" use="optional"/>
            <xs:attribute name="timestamp" type="xs:string" use="optional"/>
            <xs:attribute name="classname" type="xs:string" use="optional"/>
            <xs:attribute name="status" type="xs:string" use="optional"/>
            <xs:attribute name="class" type="xs:string" use="optional"/>
            <xs:attribute name="file" type="xs:string" use="optional"/>
            <xs:attribute name="line" type="xs:string" use="optional"/>
            <xs:attribute name="log" type="xs:string" use="optional"/>
            <xs:attribute name="group" type="xs:string" use="optional"/>
            <xs:attribute name="url" type="xs:string" use="optional"/>
        </xs:complexType>
    </xs:element>

    <xs:element name="testsuite">
        <xs:complexType>
            <xs:choice minOccurs="0" maxOccurs="unbounded">
                <xs:element ref="testsuite"/>
                <xs:element ref="properties"/>
                <xs:element ref="testcase"/>
                <xs:element ref="system-out"/>
                <xs:element ref="system-err"/>
            </xs:choice>
            <xs:attribute name="name" type="xs:string" use="optional"/>
            <xs:attribute name="tests" type="xs:string" use="required"/>
            <xs:attribute name="failures" type="xs:string" use="optional"/>
            <xs:attribute name="errors" type="xs:string" use="optional"/>
            <xs:attribute name="time" type="xs:string" use="optional"/>
            <xs:attribute name="disabled" type="xs:string" use="optional"/>
            <xs:attribute name="skipped" type="xs:string" use="optional"/>
            <xs:attribute name="skips" type="xs:string" use="optional"/>
            <xs:attribute name="timestamp" type="xs:string" use="optional"/>
            <xs:attribute name="hostname" type="xs:string" use="optional"/>
            <xs:attribute name="id" type="xs:string" use="optional"/>
            <xs:attribute name="package" type="xs:string" use="optional"/>
            <xs:attribute name="assertions" type="xs:string" use="optional"/>
            <xs:attribute name="file" type="xs:string" use="optional"/>
            <xs:attribute name="skip" type="xs:string" use="optional"/>
            <xs:attribute name="log" type="xs:string" use="optional"/>
            <xs:attribute name="url" type="xs:string" use="optional"/>
        </xs:complexType>
    </xs:element>

    <xs:element name="testsuites">
        <xs:complexType>
            <xs:sequence>
                <xs:element ref="testsuite" minOccurs="0" maxOccurs="unbounded"/>
            </xs:sequence>
            <xs:attribute name="name" type="xs:string" use="optional"/>
            <xs:attribute name="time" type="xs:string" use="optional"/>
            <xs:attribute name="tests" type="xs:string" use="optional"/>
            <xs:attribute name="failures" type="xs:string" use="optional"/>
            <xs:attribute name="disabled" type="xs:string" use="optional"/>
            <xs:attribute name="errors" type="xs:string" use="optional"/>
        </xs:complexType>
    </xs:element>

</xs:schema>
