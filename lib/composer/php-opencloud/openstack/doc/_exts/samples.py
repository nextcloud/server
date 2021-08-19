from sphinx.directives import LiteralInclude
from docutils import nodes
from sphinx.addnodes import download_reference
from sphinx.writers.html import HTMLTranslator
import re

class Sample(LiteralInclude):

  def run(self):
    self.arguments[0] = "/../samples/" + self.arguments[0]
    self.options['language'] = 'php'

    pattern = "[\s+]?(\<\?php.*?]\);)"

    code_block = super(Sample, self).run()[0]
    string = str(code_block[0])

    match = re.match(pattern, string, re.S)
    if match is None:
      return [code_block]

    auth_str = match.group(1).strip()
    main_str = re.sub(pattern, "", string, 0, re.S).strip()

    show_hide_btn = download_reference(reftarget=self.arguments[0])

    return [
        show_hide_btn, 
        nodes.literal_block(auth_str, auth_str, language="php"), 
        nodes.literal_block(main_str, main_str, language="php")]

def visit_download_reference(self, node):
  self.context.append('<a href="javascript:void(0);" class="toggle btn">Show auth code</a>')

def depart_download_reference(self, node):
  self.body.append(self.context.pop())

def setup(app):
  app.add_node(download_reference, html=(visit_download_reference, depart_download_reference))
  app.add_directive('sample', Sample)
  return {'version': '0.1'}
