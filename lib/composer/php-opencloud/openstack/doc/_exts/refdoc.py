from docutils.parsers.rst import Directive
from docutils import nodes

REFDOC_URL = 'http://refdocs.os.php-opencloud.com/'

class RefDoc(Directive):

  required_arguments = 1
  has_content = True

  def run(self):
    full_url = REFDOC_URL + self.arguments[0]
    title = "reference documentation"

    text = []
    text.extend([
      nodes.Text('To see all the required and optional parameters for this operation, along with their types and descriptions, view the '),
      nodes.reference(title, title, internal=False, refuri=full_url),
      nodes.Text('.')
      ])

    return [nodes.paragraph('', '', *text)]

def setup(app):
    app.add_directive('refdoc', RefDoc)
    return {'version': '0.1'}
