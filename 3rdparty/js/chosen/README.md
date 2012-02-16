# Chosen

Chosen is a library for making long, unwieldy select boxes more user friendly.

- jQuery support: 1.4+
- Prototype support: 1.7+

For documentation, usage, and examples, see:  
http://harvesthq.github.com/chosen

### Contributing to Chosen

Contributions and pull requests are very welcome. Please follow these guidelines when submitting new code.

1. Make all changes in Coffeescript files, **not** JavaScript files.
2. For feature changes, update both jQuery *and* Prototype versions
3. Use 'cake build' to generate Chosen's JavaScript file and minified version.
4. Don't touch the VERSION file
5. Submit a Pull Request using GitHub.

### Using CoffeeScript & Cake

First, make sure you have the proper CoffeeScript / Cake set-up in place.

1. Install Coffeescript: the [CoffeeScript documentation](http://jashkenas.github.com/coffee-script/) provides easy-to-follow instructions.
2. Install UglifyJS: <code>npm -g install uglify-js</code>
3. Verify that your $NODE_PATH is properly configured using <code>echo $NODE_PATH</code>

Once you're configured, building the JavasScript from the command line is easy:

    cake build                # build Chosen from source
    cake watch                # watch coffee/ for changes and build Chosen
    
If you're interested, you can find the recipes in Cakefile.


### Chosen Credits

- Built by [Harvest](http://www.getharvest.com/)
- Concept and development by [Patrick Filler](http://www.patrickfiller.com/)
- Design and CSS by [Matthew Lettini](http://matthewlettini.com/)

### Notable Forks

- [Chosen for MooTools](https://github.com/julesjanssen/chosen), by Jules Janssen
- [Chosen Drupal 7 Module](https://github.com/Polzme/chosen), by Pol Dell'Aiera