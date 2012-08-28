#!/usr/bin/python -Qwarnall
# -*- coding: utf-8 -*-
#
# This script checks if all files have licenses and fixes them if needed.
# The script should be run from the root directory of the webodf project.
import os, os.path

# read the license text from the source file
# the license starts at the line ' * @licstart' and ends at ' */'
def readLicense(path):
	file = open(path, "rU")
	licensetext = []
	started = False
	for line in file:
		if line.rstrip() == ' * @licstart':
			started = True
		if started:
			licensetext.append(line)
		if line.rstrip() == ' */':
			break
	return licensetext

def writeLicense(file, license, defaultcopyright):
	if defaultcopyright:
		file.write(defaultcopyright)
	file.writelines(license)

def fixLicense(path, license, defaultcopyright):
	# read the file
	file = open(path, "rU")
	lines = file.readlines()
	file.close()
	# does the file have any copyright statement already?
	hasLicense = False
	hasCopyright = False
	for line in lines:
		if line.rstrip() == ' * @licstart':
			hasLicense = True
		if line[:17] == ' * Copyright (C) ':
			hasCopyright = True
	if hasCopyright:
		defaultcopyright = None
	wroteLicense = False
	skip = False
	# write the file with the new slice
	file = open(path, "w")
	for line in lines:
		if not wroteLicense:
			if not hasLicense:
				file.write("/**\n")
				writeLicense(file, license, defaultcopyright)
				wroteLicense = True
			elif line.rstrip() == ' * @licstart':
				writeLicense(file, license, defaultcopyright)
				wroteLicense = True
				skip = True
		if skip:
			if line.rstrip() == ' */':
				skip = False
		else:
			file.write(line)
	file.close()

# get list of *.js files
jsfiles = []
for root, directories, files in os.walk("."):
	while "extjs" in directories:
		directories.remove("extjs")
	for f in files:
		if f[-3:] == ".js":
			jsfiles.append(os.path.abspath(os.path.join(root, f)))

# remove webodf/lib/packackages.js since it is the source for the licenses
sourcefilepath = os.path.join(os.getcwd(), "webodf/lib/packages.js")
jsfiles.remove(sourcefilepath)

licensetext = readLicense(sourcefilepath)
defaultcopyright = " * Copyright (C) 2011 KO GmbH <jos.van.den.oever@kogmbh.com>\n"

for f in jsfiles:
	fixLicense(f, licensetext, defaultcopyright)
