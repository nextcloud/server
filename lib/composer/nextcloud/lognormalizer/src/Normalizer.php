<?php
/**
 * interfaSys - lognormalizer
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Olivier Paroz <dev-lognormalizer@interfasys.ch>
 * @author Jordi Boggiano <j.boggiano@seld.be>
 *
 * @copyright Olivier Paroz 2015
 * @copyright Jordi Boggiano 2014-2015
 */

namespace Nextcloud\LogNormalizer;

use Throwable;

/**
 * Converts any variable to a String
 *
 * @package Nextcloud\LogNormalizer
 */
class Normalizer {

	/**
	 * @type string
	 */
	const SIMPLE_DATE = "Y-m-d H:i:s";

	/**
	 * @type int
	 */
	private $maxRecursionDepth;

	/**
	 * @type int
	 */
	private $maxArrayItems;

	/**
	 * @type string
	 */
	private $dateFormat;

	/**
	 * @param int $maxRecursionDepth The maximum depth at which to go when inspecting objects
	 * @param int $maxArrayItems The maximum number of Array elements you want to show, when
	 *     parsing an array
	 * @param null|string $dateFormat The format to apply to dates
	 */
	public function __construct($maxRecursionDepth = 4, $maxArrayItems = 20, $dateFormat = null) {
		$this->maxRecursionDepth = $maxRecursionDepth;
		$this->maxArrayItems = $maxArrayItems;
		if ($dateFormat !== null) {
			$this->dateFormat = $dateFormat;
		} else {
			$this->dateFormat = static::SIMPLE_DATE;
		}
	}

	/**
	 * Normalises the variable, JSON encodes it if needed and cleans up the result
	 *
	 * @param mixed $data
	 *
	 * @return string|null
	 */
	public function format(&$data) {
		$data = $this->normalize($data);
		$data = $this->convertToString($data);

		return $data;
	}

	/**
	 * Converts Objects, Arrays, Dates and Exceptions to a String or an Array
	 *
	 * @uses Nextcloud\LogNormalizer\Normalizer::normalizeTraversable
	 * @uses Nextcloud\LogNormalizer\Normalizer::normalizeDate
	 * @uses Nextcloud\LogNormalizer\Normalizer::normalizeObject
	 * @uses Nextcloud\LogNormalizer\Normalizer::normalizeResource
	 *
	 * @param $data
	 * @param int $depth
	 *
	 * @return string|array
	 */
	public function normalize($data, $depth = 0) {
		$scalar = $this->normalizeScalar($data);
		if (!is_array($scalar)) {
			return $scalar;
		}
		$decisionArray = [
			'normalizeTraversable' => [$data, $depth],
			'normalizeDate'        => [$data],
			'normalizeObject'      => [$data, $depth],
			'normalizeResource'    => [$data],
		];

		foreach ($decisionArray as $functionName => $arguments) {
			$dataType = call_user_func_array([$this, $functionName], $arguments);
			if ($dataType !== null) {
				return $dataType;
			}
		}

		return '[unknown(' . gettype($data) . ')]';
	}

	/**
	 * JSON encodes data which isn't already a string and cleans up the result
	 *
	 * @todo: could maybe do a better job removing slashes
	 *
	 * @param mixed $data
	 *
	 * @return string|null
	 */
	public function convertToString($data) {
		if (!is_string($data)) {
			$data = @json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
			// Removing null byte and double slashes from object properties
			$data = str_replace(['\\u0000', '\\\\'], ["", "\\"], $data);
		}

		return $data;
	}

	/**
	 * Returns various, filtered, scalar elements
	 *
	 * We're returning an array here to detect failure because null is a scalar and so is false
	 *
	 * @param $data
	 *
	 * @return mixed
	 */
	private function normalizeScalar($data) {
		if (null === $data || is_scalar($data)) {
			/*// utf8_encode only works for Latin1 so we rely on mbstring
			if (is_string($data)) {
				$data = mb_convert_encoding($data, "UTF-8");
			}*/

			if (is_float($data)) {
				$data = $this->normalizeFloat($data);
			}

			return $data;
		}

		return [];
	}

	/**
	 * Normalises infinite and trigonometric floats
	 *
	 * @param float $data
	 *
	 * @return string|double
	 */
	private function normalizeFloat($data) {
		if (is_infinite($data)) {
			$postfix = 'INF';
			if ($data < 0) {
				$postfix = '-' . $postfix;
			}
			$data = $postfix;
		} else {
			if (is_nan($data)) {
				$data = 'NaN';
			}
		}

		return $data;
	}

	/**
	 * Returns an array containing normalized elements
	 *
	 * @used-by Nextcloud\LogNormalizer\Normalizer::normalize
	 *
	 * @param $data
	 * @param int $depth
	 *
	 * @return array|null
	 */
	private function normalizeTraversable($data, $depth = 0) {
		if (is_array($data) || $data instanceof \Traversable) {
			return $this->normalizeTraversableElement($data, $depth);
		}

		return null;
	}

	/**
	 * Converts each element of a traversable variable to String
	 *
	 * @param $data
	 * @param int $depth
	 *
	 * @return array
	 */
	private function normalizeTraversableElement($data, $depth) {
		$maxObjectRecursion = $this->maxRecursionDepth;
		$maxArrayItems = $this->maxArrayItems;
		$count = 1;
		$normalized = [];
		$nextDepth = $depth + 1;
		foreach ($data as $key => $value) {
			if ($count >= $maxArrayItems) {
				$normalized['...'] =
					'Over ' . $maxArrayItems . ' items, aborting normalization';
				break;
			}
			$count++;
			if ($depth < $maxObjectRecursion) {
				$normalized[$key] = $this->normalize($value, $nextDepth);
			}
		}

		return $normalized;
	}

	/**
	 * Converts a date to String
	 *
	 * @used-by Nextcloud\LogNormalizer\Normalizer::normalize
	 *
	 * @param mixed $data
	 *
	 * @return null|string
	 */
	private function normalizeDate($data) {
		if ($data instanceof \DateTime) {
			return $data->format($this->dateFormat);
		}

		return null;
	}

	/**
	 * Converts an Object to an Array
	 *
	 * We don't convert to json here as we would double encode them
	 *
	 * @used-by Nextcloud\LogNormalizer\Normalizer::normalize
	 *
	 * @param mixed $data
	 * @param int $depth
	 *
	 * @return array|null
	 */
	private function normalizeObject($data, $depth) {
		if (is_object($data)) {
			if ($data instanceof Throwable) {
				return $this->normalizeException($data);
			}
			// We don't need to go too deep in the recursion
			$maxObjectRecursion = $this->maxRecursionDepth;
			$arrayObject = new \ArrayObject($data);
			$serializedObject = $arrayObject->getArrayCopy();
			if ($depth < $maxObjectRecursion) {
				$depth++;
				$response = $this->normalize($serializedObject, $depth);

				return [$this->getObjetName($data) => $response];
			}

			return $this->getObjetName($data);
		}

		return null;
	}

	/**
	 * Converts an Exception to String
	 *
	 * @param Throwable $exception
	 *
	 * @return string[]
	 */
	private function normalizeException(Throwable $exception) {
		$data = [
			'class'   => get_class($exception),
			'message' => $exception->getMessage(),
			'code'    => $exception->getCode(),
			'file'    => $exception->getFile() . ':' . $exception->getLine(),
		];
		$trace = $exception->getTraceAsString();
		$data['trace'] = $trace;

		$previous = $exception->getPrevious();
		if ($previous) {
			$data['previous'] = $this->normalizeException($previous);
		}

		return $data;
	}

	/**
	 * Formats the output of the object parsing
	 *
	 * @param $object
	 *
	 * @return string
	 */
	private function getObjetName($object) {
		return sprintf('[object] (%s)', get_class($object));
	}

	/**
	 * Converts a resource to a String
	 *
	 * @used-by Nextcloud\LogNormalizer\Normalizer::normalize
	 *
	 * @param $data
	 *
	 * @return string|null
	 */
	private function normalizeResource($data) {
		if (is_resource($data)) {
			return '[resource] ' . substr((string)$data, 0, 40);
		}

		return null;
	}

}
