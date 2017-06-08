<?php


namespace Office365\PHP\Client\Runtime\Utilities;


class Guid
{

    /**
     * @var array
     */
    private static $Hexcode = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9', 'a', 'b', 'c', 'd', 'e', 'f'];


    /**
     * @var string
     */
    private $guidString;


    public function __construct($guidText)
    {
        $this->guidString = $this->normalizeGuidString($guidText);
    }

    public function toString($format=null)
    {
        if($format == "N")
            $value = str_replace("-","",$this->guidString);
        else if($format == "B")
            $value = '{' . $this->guidString . '}';
        else
            $value = $this->guidString;
        return $value;
    }

    /**
     * @return Guid
     */
    public static function newGuid()
    {
        $result = '';
        for ($index = 0; $index < 32; $index++) {
            $value = floor(rand(0,15));

            switch ($index) {
                case 8:
                    $result .= '-';
                    break;
                case 12:
                    $value = 4;
                    $result .= '-';
                    break;
                case 16:
                    $value = $value & 3 | 8;
                    $result .= '-';
                    break;
                case 20:
                    $result .= '-';
                    break;
            }
            $result .= self::$Hexcode[$value];
        }
        $uuidOut = new Guid($result);
        return $uuidOut;
    }

    private function normalizeGuidString($guidText)
    {
        $newUuidText = str_replace(' ', '', $guidText);
        $newUuidText = str_replace('{', '', $newUuidText);
        $newUuidText = str_replace('}', '', $newUuidText);
        $newUuidText = strtolower($newUuidText);
        return $newUuidText;
    }

}