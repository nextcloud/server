# This file is licensed under the Affero General Public License version 3 or
# later. See the COPYING file.
# @author Christoph Wurst <christoph@winzerhof-wurst.at>
# @copyright Christoph Wurst 2017

default: svg-sprites

svg-sprites: clean
	cd build && \
	npm install && \
	pwd && \
	grunt

clean:
	rm -f core/css/images/actions-*.svg