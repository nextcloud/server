# Contributing to the OpenStack SDK

- [Setting up your git workspace](#setting-up-your-git-workspace)
- [Unit tests](#unit-tests)
- [Integration tests](#integration-tests)
- [Style guide](#style-guide)
- [Documentation](#documentation)
- [5 ways to get involved](#5-ways-to-get-involved)

## Setting up your git workspace

As a contributor you will need to setup your workspace in a slightly different
way than just downloading it. Here are the basic installation instructions:

1. Install Composer. There are installation guides for 
[Linux/Unix/OSX](https://getcomposer.org/doc/00-intro.md#installation-linux-unix-osx) and
[Windows](https://getcomposer.org/doc/00-intro.md#installation-windows) users.

2. Navigate to the repository in Github and [fork it](https://help.github.com/articles/fork-a-repo/). You should now have a local version 
of the repository in `<yourusername>/openstack`, where `<yourusername>` is your Github username.

3. Clone your fork repository to your local workspace:

```bash
git clone git@github.com:<yourusername>/openstack.git
```

Note: in order to clone with SSH, you must first save your public key into Github. If you 
do not know how to do this, please follow [these instructions](https://help.github.com/articles/generating-ssh-keys/).

4. Install all the development dependencies with Composer:

```bash
composer install
```

5. Once everything is installed, you're ready to go! If you are working on a new feature, check out a new
feature brach:

```bash
git checkout -b my-new-feature
```

or if you're fixing a bug, the usual convention is to reference the Github issue:

```bash
git checkout -b issue-XXX
```

Where `XXX` is the unique Github issue ID.

## Tests

When working on a new or existing feature, testing will be the backbone of your
work since it helps uncover and prevent regressions in the codebase. There are
two types of test we use in php-opencloud: unit tests and integration tests, which
are both described below.

### Unit tests

Unit tests are the fine-grained tests that establish and ensure the behaviour
of individual units of functionality. We usually test on an
operation-by-operation basis (an operation typically being an API action) with
the use of mocking to set up explicit expectations. We use [Prophecy](https://github.com/phpspec/prophecy) as our 
underlying mocking framework, which we'll cover in more detail below.

#### Mocks

Mocks are fake representations of normal objects which allow you to test and examine the contracts in your code. In a 
nutshell, they provide two benefits:

* they allow you to test how your *exactly* how your objects communicate
* they replace slow dependencies with fast, deterministic behaviour

Say, for example, you're working with and testing the `OpenStack\Compute\v2\Models\Server` class. You'd need to mock 
the underlying HTTP client which each resource is injected with at instantiation, because otherwise your tests would 
run the execute real HTTP transactions over the wire. This would be painfully slow and unhelpful. So instead, we mock it:

```php
use OpenStack\Test\TestCase;

class ServerTest extends TestCase
{
    private $server;

    public function setUp()
    {
        parent::setUp();
        
        $this->server = new Server($this->client->reveal());
    }
}
```

You will notice that we have a helper parent class which handles some of the common tasks. In the `parent::setUp` call, 
a `GuzzleHttp\Client` object is mocked and set as an instance variable (`$this->client`). This object is of the type
`\Prophecy\Prophecy\ObjectProphecy` - which is a common type used in Prophecy for mock objects. But this will not 
satisfy our type hints, so we ask the mock object to expose the primitive type it's mocking through `reveal` - which in 
our case is the Guzzle client. This satisfies our type hints _and_ give us access to a mock object.

So we've set up our mock, how can we use it? Well, we can now set up _expectations_. For example, we want to test
that when `$server->update` is called, a HTTP request is built and sent to the remote API. We also want the remote API 
to send us back a HTTP response, which then populates our model object. With mocking, we control the whole life cycle:

```php
public function test_it_updates()
{
    // Based on the name, this is what we expect the JSON structure to be.
    $expectedJson = ['server' => ['name' => 'foo']];
    
    // We'll pretend as if the server sets this as the new "updated" time
    $updatedTime = date('z');

    // Set up our expectations
    $expectedRequest = new Request('PUT', 'servers/serverId', [], Stream::factory(json_encode($expectedJson)));
    $expectedResponse = new Response(202, [], Stream::factory(json_encode(
        ['server' => ['name' => 'foo', 'updated' => $updatedTime]]
    )));

    // Mock the request being created by the Guzzle client
    $this->client
        ->createRequest('PUT', 'servers/serverId', ['json' => $expectedJson])
        ->shouldBeCalled()
        ->willReturn($expectedRequest);

    // Mock the request being sent by the Guzzle client
    $this->client
        ->send($expectedRequest)
        ->shouldBeCalled()
        ->willReturn($expectedResponse);

    // Execute the call like a user would normally do
    $this->server->name = 'foo';
    $this->server->update();
    
    // We can also check the response has populated the server
    $this->assertEquals($updatedTime, $this->server->updated);
}
```

We're testing a few different things here:

1. We're ensuring that the Guzzle client is creating a request _exactly_ how we think it should. We have certain 
expectations of what the HTTP method should be, along with the path, JSON body and even which headers should be used 
for this update operation.

2. Once this request is created, we expect the Guzzle client to send it and return us a HTTP response. Our expectation
is that the response should have a `202` response code, and a JSON body which then populates the server model.

3. Once the population has happened, we expect the server's `updated` attribute to match what was returned from the API 
(i.e. our mocked response).

Now, the above example is deliberately verbose to show all the details. In reality, you can use our helper functions 
to clean up the code a bit:

```php
public function test_it_updates()
{
    // Updatable attributes
    $this->server->name = 'foo';
    $this->server->ipv4 = '0.0.0.0';
    $this->server->ipv6 = '0:0:0:0:0:ffff:0:0';
    
    // This is the JSON we expect being sent to the API
    $expectedJson = ['server' => [
        'name'       => 'foo',
        'accessIPv4' => '0.0.0.0',
        'accessIPv6' => '0:0:0:0:0:ffff:0:0',
    ]];
    
    // First we mock the request being created
    $request = $this->setupMockRequest('PUT', 'servers/serverId', $expectedJson);
    
    // Then mock the response being sent back
    $this->setupMockResponse($request, 'server-put');
        
    $this->assertInstanceOf(Server::class, $this->server->update());
}
```

The second argument to `setupMockResponse` is an external file, storing a string HTTP message.

#### Running the unit tests

We use phpunit, so you run this at the project root:

```bash
phpunit
```

### Integration tests

As we've already mentioned, unit tests have a very narrow and confined focus -
they test small units of behaviour. Integration tests on the other hand have a
far larger scope: they are fully functional tests that test the entire API of a
service in one fell swoop. They don't care about unit isolation or mocking
expectations, they instead do a full run-through and consequently test how the
entire system _integrates_ together. When an API satisfies expectations, it
proves by default that the requirements for a contract have been met.

Please be aware that acceptance tests will hit a live API - and may incur
service charges from your provider. Although most tests handle their own
teardown procedures, it is always worth manually checking that resources are
deleted after the test suite finishes.

We use all of our sample files as live integration tests, achieving the dual aim of reducing code duplication and 
ensuring that our samples actually work.

### Setting up environment variables

Rename `env_test.sh.dist` as `env_test.sh` and replace values according to your OpenStack instance configuration.
Completed file may look as following.


```bash
#!/usr/bin/env bash
export OS_AUTH_URL="http://1.2.3.4:5000/v3"       
export OS_REGION="RegionOne"
export OS_REGION_NAME="RegionOne"
export OS_USER_ID="536068bcb1b946ff8e2f10eff6543f9c"
export OS_USERNAME="admin"
export OS_PASSWORD="2251639ecaea442b"
export OS_PROJECT_ID="b62b3bebf9e84e4eb11aafcd8c58db3f"
export OS_PROJECT_NAME="admin"
export OS_RESIZE_FLAVOR=2                                 #Must be a valid flavor ID
export OS_FLAVOR=1                                        #Must be a valid flavor ID
export OS_DOMAIN_ID="default"
```

To export environment variables, run
 ```bash
 $ . env_test.sh
 ```

Additionally, integration tests require image called `cirros` exists.

### Running integration tests

You interact with integration tests through a runner script:

```bash
php ./tests/integration/run.php [-s=BlockStorage|Compute|Identity|Images|Networking|ObjectStore] [--debug=1|2]
```

It supports these command-line flags:

| Flag | Description | Example |
| ---- | ----------- | ------- |
| `-s` `--service` | Allows you to refine tests by a particular service. A service corresponds to top-level directories in the `./integration` directory, meaning that `compute` and `identity` are services because they exist as sub-directories there. If omitted, all services are run.|Run compute service: `php ./tests/integration/run.php -s compute` Run all tests: `php ./tests/integration/run.php`|
| `-v` `--version` | Allows you to refine by a particular service version. A version corresponds to the sub-directories inside a service directory, meaning that `v2` is a supported version of `compute` because it exists as a sub-directory inside the `compute` directory. If omitted, all versions are run.|Run v2 Compute tests: `php ./tests/integration/run.php -s compute -v v2` Run all compute tests: `php ./tests/integration/run.php -s compute`|
| `-t` `--test` | Allows you to refine by a particular test. Tests are defined in classes like `integration\OpenStack\Compute\v2`. Each test method manually references a sample file. To refine which tests are run, list the name of the method in this class. If omitted, all tests are run.|Run create server test: `php ./tests/integration/run.php -s compute -v v2 -t createServer` Run all compute v2 tests: `php ./tests/integration/run.php -s compute -v v2`|
| `--debug` |||
| `--help` | A help screen is returned and no tests run | `php ./tests/integration/run.php --help`


## Style guide

We follow [PSR-2](https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-2-coding-style-guide.md) for our 
style guide, so please ensure your code abides by this standard. You can use popular 
[source code fixers](https://github.com/FriendsOfPHP/PHP-CS-Fixer) to reformat your code automatically.

## Documentation

Clear, accurate and concise documentation is incredibly important to our project. Every new method will need an 
accompanying [phpdoc docblock](http://phpdoc.org/docs/latest/index.html) which outlines what the method does, as well 
as param or return types.

We use [reStructuredText](http://docutils.sourceforge.net/docs/user/rst/quickref.html) and [Sphinx](http://sphinx-doc.org/) 
to structure our user documentation, and [Read the Docs](https://readthedocs.org/) for hosting and post-commit rebuilding.

## 5 ways to get involved

There are five main ways you can get involved in our open-source project, and
each is described briefly below. Once you've made up your mind and decided on
your fix, you will need to follow the same basic steps that all submissions are
required to adhere to:

1. [fork](https://help.github.com/articles/fork-a-repo/) the `php-opencloud/openstack` repository
2. checkout a [new branch](https://github.com/Kunena/Kunena-Forum/wiki/Create-a-new-branch-with-git-and-manage-branches)
3. submit your branch as a [pull request](https://help.github.com/articles/creating-a-pull-request/)

### 1. Providing feedback

On of the easiest ways to get readily involved in our project is to let us know
about your experiences using our SDK. Feedback like this is incredibly useful
to us, because it allows us to refine and change features based on what our
users want and expect of us. There are a bunch of ways to get in contact! You
can [ping us](https://developer.rackspace.com/support/) via e-mail, talk to us on irc
(#rackspace-dev on freenode), [tweet us](https://twitter.com/rackspace), or
submit an issue on our [bug tracker](/issues). Things you might like to tell us
are:

* how easy was it to start using our SDK?
* did it meet your expectations? If not, why not?
* did our documentation help or hinder you?
* what could we improve in general?

### 2. Fixing bugs

If you want to start fixing open bugs, we'd really appreciate that! Bug fixing
is central to any project. The best way to get started is by heading to our
[bug tracker](https://github.com/php-opencloud/openstack/issues) and finding open
bugs that you think nobody is working on. It might be useful to comment on the
thread to see the current state of the issue and if anybody has made any
breakthroughs on it so far.

### 3. Improving documentation

We have three forms of documentation:

* short README documents that briefly introduce a topic
* reference documentation
* user documentation on http://docs.php-opencloud.com that includes
getting started guides, installation guides and code samples

If you feel that a certain section could be improved - whether it's to clarify
ambiguity, correct a technical mistake, or to fix a grammatical error - please
feel entitled to do so! We welcome doc pull requests with the same childlike
enthusiasm as any other contribution!

### 4. Optimizing existing features

If you would like to improve or optimize an existing feature, please be aware
that we adhere to [semantic versioning](http://semver.org) - which means that
we cannot introduce breaking changes to the API without a major version change
(v1.x -> v2.x). Making that leap is a big step, so we encourage contributors to
refactor rather than rewrite. Running tests will prevent regression and avoid
the possibility of breaking somebody's current implementation.

Another tip is to keep the focus of your work as small as possible - try not to
introduce a change that affects lots and lots of files because it introduces
added risk and increases the cognitive load on the reviewers checking your
work. Change-sets which are easily understood and will not negatively impact
users are more likely to be integrated quickly.

### 5. Working on a new feature

If you've found something we've left out, definitely feel free to start work on
introducing that feature. It's always useful to open an issue or submit a pull
request early on to indicate your intent to a core contributor - this enables
quick/early feedback and can help steer you in the right direction by avoiding
known issues. It might also help you avoid losing time implementing something
that might not ever work. One tip is to prefix your Pull Request issue title
with `[wip]` - then people know it's a work in progress.

You must ensure that all of your work is well tested - both in terms of unit
and acceptance tests. Untested code will not be merged because it introduces
too much of a risk to end-users.

Happy hacking!
