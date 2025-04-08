
## AsyncProcess

using `AsyncProcess` it is possible to easily separate part of 
your code on a separated process, to improve the quality of the 
user experience.

There is multiple ways to do so: 

#### Closure

```php
AsyncProcess::exec(function (int $value, string $line, MyObject $obj): void {
	// long process
},
	random_int(10000, 99999),
	'this is a variable',
	$myObj
)->async();
```



#### Invokable



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
AsyncProcess::invoke($myInvoke, random_int(10000, 99999))->async();
```


#### PHP Class

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
AsyncProcess::call(\OCA\MyApp\MyObj::class, random_int(10000, 99999))->async();
```

## Execution delay

when calling `async()` a time can be set:
- `ProcessExecutionTime::NOW` - right after preping the processes of the session, a process will be initiated aside.   
_When running from web process, a request is made to a local endpoint that will close the socket while keep running_
_When running from command line, if `posix` is available, a real fork is expected; if not, on main process unless set to use the web endpoint_


- `ProcessExecutionTime::ASAP` - while nothing is initiated from the main process, the defined process can be run few seconds later by a local service.


- `ProcessExecutionTime::LATER` - will be run by a cycling background job. Includes `::ASAP` if no local service.


- `ProcessExecutionTime::ON_REQUEST` -  


## Sessions

  Multiple process can be executed in a queue with optional dependencies. All process defined in a session are executed on the same process, or at least in the same order they were created:
  
```php

// define all part of the code that can be async
AsyncProcess::invoke($myInvoke1)->id('proc1')->replayable();      // proc1 can be replayed until successful
AsyncProcess::invoke($myInvoke2)->id('proc2')->require('proc1');  // proc2 will not be executed until proc1 has not been successful
AsyncProcess::invoke($myInvoke3)->id('proc3')->blocker();         // proc3 will run whatever happened to proc1 and proc2 and its success is mandatory to continue with the session
AsyncProcess::invoke($myInvoke4)->id('proc4');

AsyncProcess::async(ProcessExecutionTime::NOW, true); // close the session and make prep it for async execution
```

## AProcessWrapper

the `abstract` helps to interface with the main process, but with other process from the same session.

When defining the function/method to be called once on a different process, add a `AprocessWrapper` as first parameter:

```php
AsyncProcess::exec(function (AProcessWrapper $wrapper, array $data): array {
	$resultFromProc1 = $wrapper->getSessionInterface()->byId('proc1')?->getResult(); // can be null if 'proc1' is not success, unless using require()
	$wrapper->activity(ProcessActivity::NOTICE, 'result from previous process: ' . json_encode($resultFromProc1))
},
	['mydata' => true]
	)->require('proc1'); // so $resultFromProc1 is not null if 'proc1' returned an array...

```
  
## dataset

process will be executed for each defined set of data
```php
AsyncProcess::call(\OCA\MyApp\MyObj::class)->dataset([['oui', 1], ['yes', 12]]);
```

