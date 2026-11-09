<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\DAV\Command;

use Sabre\DAV\Server as SabreServer;

/**
 * Reads the listener registrations out of a Sabre server via reflection so
 * they can be displayed for debugging (see the dav:show-listeners command).
 *
 * This intentionally reaches into sabre/event's WildcardEmitterTrait internals
 * and into the query-monitoring side maps of OCA\DAV\Connector\Sabre\Server;
 * when a property is missing (e.g. after a library upgrade) the affected
 * information is silently omitted rather than failing.
 */
class ListenerIntrospector {
	/** Sabre's default listener priority (see Sabre\Event\EmitterInterface::on). */
	private const DEFAULT_PRIORITY = 100;

	/**
	 * Reads the Sabre event emitter's internal listener maps via reflection and
	 * returns one entry per registration, sorted by event name then priority.
	 *
	 * @return list<array{event: string, priority: int, listener: string}>
	 */
	public function collectListeners(SabreServer $server): array {
		$reflection = new \ReflectionObject($server);

		// Query-monitoring wrappers keep the original callbacks in a side map on
		// the Nextcloud server subclass. Read them so wrapped listeners can be
		// resolved back to the real plugin method rather than the wrapper.
		$wrappedListeners = $this->readListenerMap($reflection, $server, 'wrappedListeners');
		$originalListeners = $this->readListenerMap($reflection, $server, 'originalListeners');

		$rows = [];
		foreach (['listeners' => false, 'wildcardListeners' => true] as $property => $isWildcard) {
			if (!$reflection->hasProperty($property)) {
				continue;
			}
			$value = $reflection->getProperty($property)->getValue($server);
			foreach ($value as $eventName => $registrations) {
				foreach ($registrations as $registration) {
					// Each registration is a [priority, callable] pair.
					[$priority, $callBack] = $registration;
					// Array keys degrade numeric strings to int, so cast for strict_types.
					[$callBack, $monitored] = $this->unwrapCallback((string)$eventName, $callBack, $wrappedListeners, $originalListeners);

					$rows[] = [
						'event' => $eventName . ($isWildcard ? '*' : ''),
						'priority' => (int)$priority,
						'listener' => $this->describeCallable($callBack) . ($monitored ? ' (query-monitored)' : ''),
					];
				}
			}
		}

		usort($rows, static function (array $a, array $b): int {
			return [$a['event'], $a['priority']] <=> [$b['event'], $b['priority']];
		});

		return $rows;
	}

	/**
	 * Resolves the firing order for a single concrete event name into printable
	 * rows (priority, registration origin and resolved listener), in the exact
	 * order Sabre fires them.
	 *
	 * @return list<array{order: int, priority: int, registeredOn: string, listener: string}>
	 */
	public function resolveFiringOrder(SabreServer $server, string $eventName): array {
		$reflection = new \ReflectionObject($server);
		$wrappedListeners = $this->readListenerMap($reflection, $server, 'wrappedListeners');
		$originalListeners = $this->readListenerMap($reflection, $server, 'originalListeners');

		$rows = [];
		foreach ($this->resolveFiringSequence($server, $eventName) as $i => $registration) {
			[$priority, $callBack, $registeredOn] = $registration;
			[$callBack, $monitored] = $this->unwrapCallback($eventName, $callBack, $wrappedListeners, $originalListeners);
			$rows[] = [
				'order' => $i + 1,
				'priority' => (int)$priority,
				'registeredOn' => $registeredOn,
				'listener' => $this->describeCallable($callBack) . ($monitored ? ' (query-monitored)' : ''),
			];
		}

		return $rows;
	}

	/**
	 * Returns the [priority, callable] registrations for a concrete event name in
	 * the exact order Sabre fires them.
	 *
	 * The order is taken directly from $server->listeners($eventName) — the very
	 * list Sabre\DAV\Server::emit() iterates at request time. The priority is
	 * then looked up per callback from the raw registration maps for display only.
	 *
	 * @return list<array{0: int, 1: callable, 2: string}> [priority, callable, event name the listener was registered on]
	 */
	public function resolveFiringSequence(SabreServer $server, string $eventName): array {
		$ordered = $server->listeners($eventName);
		$registrations = $this->rawRegistrationsFor($server, $eventName);

		$result = [];
		foreach ($ordered as $callBack) {
			$priority = self::DEFAULT_PRIORITY;
			$registeredOn = $eventName;
			foreach ($registrations as $key => [$prio, $cb, $origin]) {
				if ($cb === $callBack) {
					$priority = $prio;
					$registeredOn = $origin;
					// Consume the match so duplicate registrations of the same
					// callback map to distinct priorities.
					unset($registrations[$key]);
					break;
				}
			}
			$result[] = [(int)$priority, $callBack, $registeredOn];
		}

		return $result;
	}

	/**
	 * Finds the WebDAV server's deferred "beforeMethod:*" handlers (defined in
	 * $sourceFile) and returns them so they can be invoked to register the
	 * plugins they add lazily.
	 *
	 * @return list<\Closure>
	 */
	public function findDeferredHandlers(SabreServer $server, string $sourceFile): array {
		$reflection = new \ReflectionObject($server);
		if (!$reflection->hasProperty('wildcardListeners')) {
			return [];
		}

		// WildcardEmitterTrait stores "beforeMethod:*" under the key without the
		// trailing wildcard character.
		$wildcard = $reflection->getProperty('wildcardListeners')->getValue($server);
		$handlers = [];
		foreach ($wildcard['beforeMethod:'] ?? [] as [, $callBack]) {
			if (!$callBack instanceof \Closure) {
				continue;
			}
			$function = new \ReflectionFunction($callBack);
			if ($function->getFileName() === $sourceFile) {
				$handlers[] = $callBack;
			}
		}

		return $handlers;
	}

	/**
	 * Collects the raw registrations that apply to a concrete event name: the
	 * exact listeners plus every wildcard whose (star-stripped) key is a prefix
	 * of the event name, each annotated with the event name it was registered
	 * on. Order/priority here is not authoritative — it is only used as a
	 * lookup table in resolveFiringSequence().
	 *
	 * @return list<array{0: int, 1: callable, 2: string}> [priority, callable, event name the listener was registered on]
	 */
	private function rawRegistrationsFor(SabreServer $server, string $eventName): array {
		$reflection = new \ReflectionObject($server);
		$exactAll = $reflection->hasProperty('listeners')
			? $reflection->getProperty('listeners')->getValue($server)
			: [];
		$wildcardAll = $reflection->hasProperty('wildcardListeners')
			? $reflection->getProperty('wildcardListeners')->getValue($server)
			: [];

		$merged = [];
		foreach ($exactAll[$eventName] ?? [] as [$priority, $callBack]) {
			$merged[] = [$priority, $callBack, $eventName];
		}
		foreach ($wildcardAll as $wcEvent => $wcListeners) {
			if (str_starts_with($eventName, (string)$wcEvent)) {
				foreach ($wcListeners as [$priority, $callBack]) {
					$merged[] = [$priority, $callBack, $wcEvent . '*'];
				}
			}
		}

		return $merged;
	}

	/**
	 * Resolves a possibly query-monitored callback back to the original listener.
	 *
	 * @param array<string, list<callable>> $wrappedListeners
	 * @param array<string, list<callable>> $originalListeners
	 * @return array{0: callable, 1: bool} the real callback and whether it was wrapped
	 */
	private function unwrapCallback(string $eventName, callable $callBack, array $wrappedListeners, array $originalListeners): array {
		if (isset($wrappedListeners[$eventName])) {
			$index = array_search($callBack, $wrappedListeners[$eventName], true);
			if ($index !== false && isset($originalListeners[$eventName][$index])) {
				return [$originalListeners[$eventName][$index], true];
			}
		}

		return [$callBack, false];
	}

	/**
	 * @return array<string, list<callable>>
	 */
	private function readListenerMap(\ReflectionObject $reflection, SabreServer $server, string $property): array {
		if (!$reflection->hasProperty($property)) {
			return [];
		}
		$value = $reflection->getProperty($property)->getValue($server);
		return \is_array($value) ? $value : [];
	}

	/**
	 * Produces a human-readable description of a callable so it can be traced
	 * back to the plugin that registered it.
	 */
	private function describeCallable(callable $callBack): string {
		if (\is_string($callBack)) {
			return $callBack;
		}

		if (\is_array($callBack)) {
			$class = \is_object($callBack[0]) ? $callBack[0]::class : $callBack[0];
			return $class . '::' . $callBack[1];
		}

		if ($callBack instanceof \Closure) {
			$function = new \ReflectionFunction($callBack);
			$name = $function->getName();
			$scope = $function->getClosureScopeClass()?->getName();
			$file = $function->getFileName();
			$line = $function->getStartLine();
			$location = ($file !== false && $line !== false) ? $file . ':' . $line : null;

			$suffix = $location !== null ? ' (' . $location . ')' : '';

			// A first-class callable (e.g. "$this->afterDownload(...)") reports a
			// bare function/method reference rather than a "{closure}" marker.
			// Wrap it so it is clearly marked as a closure and not confused with
			// a plain method callable.
			if (!str_contains($name, '{closure')) {
				$target = ($scope !== null && !str_contains($name, '::')) ? $scope . '::' . $name : $name;
				return '{closure:' . $target . '}';
			}

			// PHP 8.4+ returns a descriptive name that already starts with
			// "{closure:" and includes the enclosing method and line, e.g.
			// "{closure:OCA\DAV\Server::__construct():289}". Drop the duplicate
			// trailing line and append the file so the full location is visible.
			if (str_starts_with($name, '{closure:')) {
				return (preg_replace('/:\d+}$/', '}', $name) ?? $name) . $suffix;
			}

			// Older PHP returns "{closure}", prefixed with the namespace when
			// defined inside one (e.g. "OCA\DAV\{closure}"); replace it with the
			// class the closure was defined in (usually the plugin).
			if ($scope !== null) {
				return '{closure:' . $scope . '}' . $suffix;
			}

			return '{closure}' . $suffix;
		}

		// After ruling out strings, arrays and closures, the only remaining
		// callable form is an invokable object.
		return $callBack::class . '::__invoke';
	}
}
