<?php
/*
 * Copyright 2013 Google Inc.
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

/**
 * Implementation of levels 1-3 of the URI Template spec. 
 * @see http://tools.ietf.org/html/rfc6570
 */
class Google_Utils_URITemplate
{
  const TYPE_MAP = "1";
  const TYPE_LIST = "2";
  const TYPE_SCALAR = "4";

  /**
   * @var $operators array 
   * These are valid at the start of a template block to
   * modify the way in which the variables inside are
   * processed.
   */
  private $operators = array(
      "+" => "reserved",
      "/" => "segments",
      "." => "dotprefix",
      "#" => "fragment",
      ";" => "semicolon",
      "?" => "form",
      "&" => "continuation"
  );

  /**
   * @var reserved array
   * These are the characters which should not be URL encoded in reserved
   * strings.
   */
  private $reserved = array(
      "=", ",", "!", "@", "|", ":", "/", "?", "#",
      "[", "]","$", "&", "'", "(", ")", "*", "+", ";"
  );
  private $reservedEncoded = array(
    "%3D", "%2C", "%21", "%40", "%7C", "%3A", "%2F", "%3F",
    "%23", "%5B", "%5D", "%24", "%26", "%27", "%28", "%29",
    "%2A", "%2B", "%3B"
  );

  public function parse($string, array $parameters)
  {
    return $this->resolveNextSection($string, $parameters);
  }

  /**
   * This function finds the first matching {...} block and
   * executes the replacement. It then calls itself to find
   * subsequent blocks, if any. 
   */
  private function resolveNextSection($string, $parameters)
  {
    $start = strpos($string, "{");
    if ($start === false) {
      return $string;
    }
    $end = strpos($string, "}");
    if ($end === false) {
      return $string;
    }
    $string = $this->replace($string, $start, $end, $parameters);
    return $this->resolveNextSection($string, $parameters);
  }

  private function replace($string, $start, $end, $parameters)
  {
    // We know a data block will have {} round it, so we can strip that.
    $data = substr($string, $start + 1, $end - $start - 1);

    // If the first character is one of the reserved operators, it effects
    // the processing of the stream.
    if (isset($this->operators[$data[0]])) {
      $op = $this->operators[$data[0]];
      $data = substr($data, 1);
      $prefix = "";
      $prefix_on_missing = false;

      switch ($op) {
        case "reserved":
          // Reserved means certain characters should not be URL encoded
          $data = $this->replaceVars($data, $parameters, ",", null, true);
          break;
        case "fragment":
          // Comma separated with fragment prefix. Bare values only.
          $prefix = "#";
          $prefix_on_missing = true;
          $data = $this->replaceVars($data, $parameters, ",", null, true);
          break;
        case "segments":
          // Slash separated data. Bare values only.
          $prefix = "/";
          $data =$this->replaceVars($data, $parameters, "/");
          break;
        case "dotprefix":
          // Dot separated data. Bare values only.
          $prefix = ".";
          $prefix_on_missing = true;
          $data = $this->replaceVars($data, $parameters, ".");
          break;
        case "semicolon":
          // Semicolon prefixed and separated. Uses the key name
          $prefix = ";";
          $data = $this->replaceVars($data, $parameters, ";", "=", false, true, false);
          break;
        case "form":
          // Standard URL format. Uses the key name
          $prefix = "?";
          $data = $this->replaceVars($data, $parameters, "&", "=");
          break;
        case "continuation":
          // Standard URL, but with leading ampersand. Uses key name.
          $prefix = "&";
          $data = $this->replaceVars($data, $parameters, "&", "=");
          break;
      }

      // Add the initial prefix character if data is valid.
      if ($data || ($data !== false && $prefix_on_missing)) {
        $data = $prefix . $data;
      }

    } else {
      // If no operator we replace with the defaults.
      $data = $this->replaceVars($data, $parameters);
    }
    // This is chops out the {...} and replaces with the new section.
    return substr($string, 0, $start) . $data . substr($string, $end + 1);
  }

  private function replaceVars(
      $section,
      $parameters,
      $sep = ",",
      $combine = null,
      $reserved = false,
      $tag_empty = false,
      $combine_on_empty = true
  ) {
    if (strpos($section, ",") === false) {
      // If we only have a single value, we can immediately process.
      return $this->combine(
          $section,
          $parameters,
          $sep,
          $combine,
          $reserved,
          $tag_empty,
          $combine_on_empty
      );
    } else {
      // If we have multiple values, we need to split and loop over them.
      // Each is treated individually, then glued together with the
      // separator character.
      $vars = explode(",", $section);
      return $this->combineList(
          $vars,
          $sep,
          $parameters,
          $combine,
          $reserved,
          false, // Never emit empty strings in multi-param replacements
          $combine_on_empty
      );
    }
  }
 
  public function combine(
      $key,
      $parameters,
      $sep,
      $combine,
      $reserved,
      $tag_empty,
      $combine_on_empty
  ) {
    $length = false;
    $explode = false;
    $skip_final_combine = false;
    $value = false;

    // Check for length restriction.
    if (strpos($key, ":") !== false) {
      list($key, $length) = explode(":", $key);
    }
    
    // Check for explode parameter.
    if ($key[strlen($key) - 1] == "*") {
      $explode = true;
      $key = substr($key, 0, -1);
      $skip_final_combine = true;
    }
    
    // Define the list separator.
    $list_sep = $explode ? $sep : ",";
    
    if (isset($parameters[$key])) {
      $data_type = $this->getDataType($parameters[$key]);
      switch($data_type) {
        case self::TYPE_SCALAR:
          $value = $this->getValue($parameters[$key], $length);
          break;
        case self::TYPE_LIST:
          $values = array();
          foreach ($parameters[$key] as $pkey => $pvalue) {
            $pvalue = $this->getValue($pvalue, $length);
            if ($combine && $explode) {
              $values[$pkey] = $key . $combine . $pvalue;
            } else {
              $values[$pkey] = $pvalue;
            }
          }
          $value = implode($list_sep, $values);
          if ($value == '') {
            return '';
          }
          break;
        case self::TYPE_MAP:
          $values = array();
          foreach ($parameters[$key] as $pkey => $pvalue) {
            $pvalue = $this->getValue($pvalue, $length);
            if ($explode) {
              $pkey = $this->getValue($pkey, $length);
              $values[] = $pkey . "=" . $pvalue; // Explode triggers = combine.
            } else {
              $values[] = $pkey;
              $values[] = $pvalue;
            }
          }
          $value = implode($list_sep, $values);
          if ($value == '') {
            return false;
          }
          break;
      }
    } else if ($tag_empty) {
      // If we are just indicating empty values with their key name, return that.
      return $key;
    } else {
      // Otherwise we can skip this variable due to not being defined.
      return false;
    }

    if ($reserved) {
      $value = str_replace($this->reservedEncoded, $this->reserved, $value);
    }

    // If we do not need to include the key name, we just return the raw
    // value.
    if (!$combine || $skip_final_combine) {
      return $value;
    }
        
    // Else we combine the key name: foo=bar, if value is not the empty string.
    return $key . ($value != '' || $combine_on_empty ? $combine . $value : '');
  }
  
  /**
   * Return the type of a passed in value
   */
  private function getDataType($data)
  {
    if (is_array($data)) {
      reset($data);
      if (key($data) !== 0) {
        return self::TYPE_MAP;
      }
      return self::TYPE_LIST;
    }
    return self::TYPE_SCALAR;
  }
  
  /**
   * Utility function that merges multiple combine calls
   * for multi-key templates.
   */
  private function combineList(
      $vars,
      $sep,
      $parameters,
      $combine,
      $reserved,
      $tag_empty,
      $combine_on_empty
  ) {
    $ret = array();
    foreach ($vars as $var) {
      $response = $this->combine(
          $var,
          $parameters,
          $sep,
          $combine,
          $reserved,
          $tag_empty,
          $combine_on_empty
      );
      if ($response === false) {
        continue;
      }
      $ret[] = $response;
    }
    return implode($sep, $ret);
  }
  
  /**
   * Utility function to encode and trim values
   */
  private function getValue($value, $length)
  {
    if ($length) {
      $value = substr($value, 0, $length);
    }
    $value = rawurlencode($value);
    return $value;
  }
}
