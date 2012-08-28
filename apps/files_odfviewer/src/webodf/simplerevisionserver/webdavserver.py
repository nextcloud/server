#! /usr/bin/env python

import BaseHTTPServer, SimpleHTTPServer, re, os, sys, xml.etree.ElementTree
from xml.etree.ElementTree import Element
from xml.etree.ElementTree import ElementTree
from xml.etree.ElementTree import QName
from xml.etree.ElementTree import SubElement

class WebDAVRequestHandler(SimpleHTTPServer.SimpleHTTPRequestHandler):
	def do_GET(self):
		# handle headers like Range: bytes=0-1023
		if 'Range' in self.headers:
			m = re.match('\s*bytes\s*=\s*(\d+)\s*-\s*(\d+)\s*',
					self.headers['Range'])
			if m:
				start = int(m.group(1))
				end = int(m.group(2))
				f = self.send_range_head(start, end)
				if f:
					self.copyfilerange(f, self.wfile, start,
							end)
					f.close()
				return
		return SimpleHTTPServer.SimpleHTTPRequestHandler.do_GET(self)

	def do_PROPFIND(self):
		print 'PROPFIND ' + self.path
		for key in self.headers.keys():
			print key + '\t' + self.headers[key]
		req = self.parseinputxml()
		req = ElementTree(req)
		res = ElementTree(Element(QName("DAV:", 'multistatus')))
		self.addresponse('/', res.getroot(), 0)

		self.writeresponse(res)

	def do_OPTIONS(self):
		req = self.parseinputxml()
		print req
        	self.send_response(200)
		self.send_header("DAV", "1");
		self.end_headers()
		self.wfile.close()

	def parseinputxml(self):
		try:
			contentlength = int(self.headers['content-length'])
		except:
			return None
		data = self.rfile.read(contentlength)
		print data
		return xml.etree.ElementTree.fromstring(data)
		
	def writeresponse(self, response):
		self.send_response(200)
		self.send_header("Content-Type", 'text/xml; charset="utf-8"')
        	self.end_headers()
		response.write(self.wfile, 'utf-8')
#		response.write(sys.stdout, 'utf-8')
#		sys.stdout.flush()
		d = xml.etree.ElementTree.tostring(response.getroot(), 'utf-8')
		print d
		self.wfile.close()

	def addresponse(self, path, root, depth):
		e = SubElement(root, QName("DAV:", 'response'))
		href = SubElement(e, QName("DAV:", 'href'))
		href.text = path
		propstat = SubElement(e, QName("DAV:", 'propstat'))
		prop = SubElement(propstat, QName("DAV:", 'resourcetype'))
		if os.path.isdir(path):
			SubElement(prop, QName("DAV:", 'collection'))

if __name__ == '__main__':
	server_address = ('', 8080)
	httpd = BaseHTTPServer.HTTPServer(server_address, WebDAVRequestHandler)
	httpd.serve_forever()
