<?php
$CONFIG = [
  'redirects' => [
    // Request path without /index.php/ maps to a controller path in the form
    // <app name>.<controller name>.<handler>.

    // - For a FooController.php the controller name is "foo" (lowercase)
    // - A handler would be a method in FooController that was annotated with
    //   - either #[FrontpageRoute] attribute
    //   - or configured in routes.php
    '^\/settings(\/.*)?' => 'simplesettings.page.index'
  ],
];
