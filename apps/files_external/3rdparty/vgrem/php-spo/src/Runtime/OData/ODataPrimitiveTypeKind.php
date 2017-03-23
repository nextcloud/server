<?php


namespace Office365\PHP\Client\Runtime\OData;
use Office365\PHP\Client\Runtime\Utilities\EnumType;


/**
 * The Abstract Type System used to define the primitive types supported by OData
 * Ref: http://www.odata.org/documentation/odata-version-2-0/overview/#AbstractTypeSystem
 */
class ODataPrimitiveTypeKind extends EnumType
{


    public static function getPrimitiveCollectionNames(){
        $primitiveNames = self::getValues();
        return array_map(function($name) { return "Collection(" . $name . ")";} , $primitiveNames);
    }

    /**
     * Represent fixed- or variable- length binary data
     */
    const Binary = "Edm.Binary";


    /**
     * Represents the mathematical concept of binary-valued logic
     */
    const Boolean = "Edm.Boolean";


    /**
     * Unsigned 8-bit integer value
     */
    const Byte = "Edm.Byte";


    /**
     * Represents date and time with values ranging
     * from 12:00:00 midnight, January 1, 1753 A.D. through 11:59:59 P.M, December 9999 A.D.
     */
    const DateTime = "Edm.DateTime";


    /**
     * Represents numeric values with fixed precision and scale.
     * This type can describe a numeric value ranging from negative 10^255 + 1 to positive 10^255 -1
     */
    const Decimal = "Edm.Decimal";

    /**
     * Represents a floating point number with 15 digits precision
     * that can represent values with approximate range of Â± 2.23e -308 through Â± 1.79e +308
     */
    const Double = "Edm.Double";


    /**
     * Represents a floating point number with 7 digits precision that can represent values
     * with approximate range of Â± 1.18e -38 through Â± 3.40e +38
     */
    const Single = "Edm.Single";

    /**
     * Represents a 16-byte (128-bit) unique identifier value
     */
    const Guid = "Edm.Guid";


    /**
     *
     */
    const Int16 = "Edm.Int16";


    /**
     *
     */
    const Int32 = "Edm.Int32";


    /**
     *
     */
    const Int64 = "Edm.Int64";


    /**
     *
     */
    const SByte = "Edm.SByte";


    /**
     * Represents fixed- or variable-length character data
     */
    const String = "Edm.String";


    /**
     * Represents the time of day with values ranging from 0:00:00.x to 23:59:59.y,
     * where x and y depend upon the precision
     */
    const Time = "Edm.Time";


    /**
     * Represents date and time as an Offset in minutes from GMT, with values ranging from 12:00:00 midnight,
     * January 1, 1753 A.D. through 11:59:59 P.M, December 9999 A.D
     */
    const DateTimeOffset = "Edm.DateTimeOffset";

}