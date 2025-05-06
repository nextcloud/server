

# AsyncProcess

Using `IAsyncProcess` allow the execution of code on a separated process in order to improve the quality of the user experience.


## Concept 

To shorten the hanging time on heavy process that reflect on the user experience and to avoid delay between 
instruction and execution, this API allows to prepare instructions to be executed on a parallel thread.

This is obtained by creating a loopback HTTP request, initiating a fresh PHP process that will execute the 
prepared instruction after emulating a connection termination, freeing the main process.  

#### Technology

The logic is to:

- store a serialized version of the code to be executed on another process into database.
- start a new process as soon as possible that will retrieve the code from database and execute it.

#### Setup

The feature require setting a loopback url.  
This is done automatically by a background job. The automatic process uses `'overwrite.cli.url'` and `'trusted_domains'` from _config/config.php_ to find a list of domain name to test.   
It can be initiated via `occ`:

>     ./occ async:setup --discover

Or manually set with:

>     ./occ async:setup --loopback https://cloud.example.net/


 
## Blocks & Sessions

- We will define as _Block_ complete part of unsplittable code.
- A list of _Blocks_ can be grouped in _Sessions_.
- While _Sessions_ are independent of each other, interactions can be set between _Blocks_ of the same _Session_.

**Interactions**

- _Blocks_ are executed in the order they have been created.
- It is possible for a _Block_ to get results from a previous process from the session.
- A _Block_ defined as blocker will stop further process from that session on failure.
- A _Block_ can require a previous process to be successful before being executed.

**Replayability**

- A block can be set as _replayable_, meaning that in case of failure it can be run multiple time until it end properly.   

**Quick example**

```php

// define all part of the code that can be async
$this->asyncProcess->invoke($myInvoke1)->id('block1')->replayable();       // block1 can be replayed until successful
$this->asyncProcess->invoke($myInvoke2)->id('block2')->require('block1');  // block2 will not be executed until block1 has not been successful
$this->asyncProcess->invoke($myInvoke3)->id('block3')->blocker();          // block3 will run whatever happened to block1 and block2 and its suc1cess is mandatory to continue with the session
$this->asyncProcess->invoke($myInvoke4)->id('block4');

$this->asyncProcess->async(); // close the session and initiate async execution
```


## ProcessExecutionTime

Code is to be executed as soon as defined, with alternative fallback solutions in that order:

- `::NOW` - main process will fork and execute the code in parallel (instantly)
- `::ASAP` - process will be executed by an optional live service (a second later)
- `::LATER` - process will be executed at the next execution of the cron tasks (within the next 10 minutes)
- `::ON_REQUEST` - process needs to be executed manually


## IAsyncProcess

`IAsyncProcess` is the public interface that contains few methods to prepare the code to be processed on a parallel process.


#### Closure

The code can be directly written in a closure by calling `exec()`:

```php
$this->asyncProcess->exec(function (int $value, string $line, MyObject $obj): void {
	// long process
},
	random_int(10000, 99999),
	'this is a line',
	$myObj
)->async();
```


#### Invokable

Within the magic method `__invoke()` in a configured objects

```php
class MyInvoke {
	public function __construct(
		private array $data = []
	) {
	}

	public function __invoke(int $n): void {
		// do long process
	}
}


$myInvoke = new MyInvoke(['123']);
$this->asyncProcess->invoke($myInvoke, random_int(10000, 99999))->async();
```


#### PHP Class

Via the method `async()` from a class

```php
<?php

namespace OCA\MyApp;

class MyObj {
	public function __construct(
	) {
	}

	public function async(int $n): void {
		// run heavy stuff
	}
}
```

```php
$this->asyncProcess->call(\OCA\MyApp\MyObj::class, random_int(10000, 99999))->async();
```



## IBlockInterface

When storing a new _Block_ via `IAsyncProcess::call()`,`IAsyncProcess::invoke()` or `IAsyncProcess::async()`, will be returned a `IBlockInterface` to provided details about the _Block_.


### name(string)

Identification and/or description of the _Block_ for better understanding when debugging

```php
$this->asyncProcess->call(\OCA\MyApp\MyObj::class)->name('my process');
```

### id(string)

Identification of the _Block_ for future interaction between _Blocks_ within the same _Session_

```php
$this->asyncProcess->call(\OCA\MyApp\MyObj::class)->id('my_process');
```

As an example, `id` are to be used to obtain `IBlockInterface` from a specific _Block_:
```php
ISessionInterface::byId('my_process'); // returns IBlockInterface
```


### blocker()

Set current _Block_ as _Blocker_, meaning that further _Blocks_ of the _Session_ are lock until this process does not run successfully

```php
$this->asyncProcess->call(\OCA\MyApp\MyObj::class)->blocker();
```


### require(string)

Define that the _Block_ can only be executed if set _Block_, identified by its `id`, ran successfully.  
Multiple _Blocks_ can be set a required.

```php
$this->asyncProcess->call(\OCA\MyApp\MyObj::class)->require('other_block_1')->require('other_block_2');
```

### replayable()

The _Block_ is configured as replayable, meaning that it will be restarted until it runs correctly

```php
$this->asyncProcess->call(\OCA\MyApp\MyObj::class)->replayable();
```

The delay is calculated using 6 (six) exponent current retry, capped at 6:

- 1st retry after few seconds,
- 2nd retry after 30 seconds,
- 3rd retry after 3 minutes,
- 4th retry after 20 minutes,
- 5th retry after 2 hours,
- 6th retry after 12 hours,
- other retries every 12 hours.

### delay(int)

Only try to initiate the process n seconds after current time.

### dataset(array)

It is possible to set a list of arguments to be applied to the same _Block_.  
The _Block_ will be executed for each defined set of data

```php
$this->asyncProcess->call(\OCA\MyApp\MyObj::class)->dataset(
    [
        ['this is a string', 1], 
        ['this is another string', 12],
        ['and another value as first parameter', 42],
    ]
);
```


### post-execution

Post execution of a _Block_, its `IBlockInterface` can be used to get details about it:

- **getExecutionTime()** returns the `ProcessExecutionTime` that initiated the process,
- **getResult()** returns the array returned by the process in case of success,
- **getError()** returns the error in case of failure



## ISessionInterface

`ISessionInterface` is available to your code via `ABlockWrapper` and helps interaction between all the _Blocks_ of the same _Session_

###	getAll()

returns all `IBlockInterface` from the _Session_

### byToken(string)

returns a `IBockInterface` using its token

### byId(string)

returns a `IBockInterface` using its `id`

### getGlobalStatus()

return a `BlockStatus` (Enum) based on every status of all _Blocks_ of the _Session_:

- returns `::PREP` if one block is still at prep stage,
- return `::BLOCKER` if at least one block is set as `blocker` and is failing,
- returns `::SUCCESS` if all blocks are successful,
- returns `::ERROR` if all process have failed,
- returns `::STANDBY` or `::RUNNING` if none of the previous condition are met. `::RUNNING` if at least one `Block` is currently processed.




## ABlockWrapper

This abstract class helps to interface with other _Blocks_ from the same _Session_.  
It will be generated and passed as argument to the defined block if the first parameter is an `AprocessWrapper` is expected as first parameter:


As a _Closure_
```php
$this->asyncProcess->exec(function (ABlockWrapper $wrapper, array $data): array {
	$resultFromProc1 = $wrapper->getSessionInterface()->byId('block1')?->getResult(); // can be null if 'block1' is not success, unless using require()
	$wrapper->activity(BlockActivity::NOTICE, 'result from previous process: ' . json_encode($resultFromProc1))
},
	['mydata' => true]
	)->id('block2');
```

When using `invoke()`
```php
class MyInvoke {
	public function __construct(
	) {
	}

	public function __invoke(ABlockWrapper $wrapper, int $n): void {
		$data = $wrapper->getSessionInterface()->byId('block1')?->getResult(); // can be null if 'block1' is not success
	}
}

$myInvoke = new MyInvoke();
$this->asyncProcess->invoke($myInvoke, random_int(10000, 99999))->requipe('block1'); // require ensure block1 has run successfully before this one
```

Syntax is the same with `call()`, when defining the `async()` method

```php
class MyObj {
	public function __construct(
	) {
	}
	
	public function async(ABlockWrapper $wrapper): void {
	}
}
```

#### Abstract methods

`ABlockWrapper` is an abstract class with a list of interfaced methods that have different behavior on the _BlockWrapper_ sent by the framework.  

- **DummyBlockWrapper** will do nothing,
- **CliBlockWrapper** will generate/manage console output,
- **LoggerBlockWrapper** will only create new nextcloud logs entry.

List of usefull methods:

- **activity(BlockActivity $activity, string $line = '');** can be used to update details about current part of your code during its process
- **getSessionInterface()** return the `ISessionInterface` for the session
- **getReplayCount()** returns the number of retry


## Other tools

#### Live Service

This will cycle every few seconds to check for any session in stand-by mode and will execute its blocks
>      ./occ async:live

 
#### Manage sessions and blocks

Get resume about current session still in database.

>     `./occ async:manage`

Get details about a session.

>     `./occ async:manage --session <sessionId>

Get details about a block

>     `./occ async:manage --details <blockId>

Get excessive details about a block

>     `./occ async:manage --details <blockId> --full-details

Replay a not successful block

>     `./occ async:manage --replay <blockId>

#### Mocking process

`./occ async:setup` allow an admin to generate fake processes to emulate the feature:

- **--mock-session _int_**       create _n_ sessions
- **--mock-block _int_**         create _n_ blocks
- **--fail-process _string_**    create failing process 


# Work in Progress

missing element of the feature:

- [ ] discussing the need of signing the PHP code stored in database to ensure its authenticity before execution,
- [ ] ability to overwrite the code or arguments of a process to fix a failing process,
- [ ] Check and confirm value type compatibility between parameters and arguments when storing new block
- [ ] implementing dataset(),
- [ ] implementing delay(),
- [ ] full documentation,
- [ ] tests, tests, tests.


