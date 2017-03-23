<?php
/**
 * Represents base permissions for a role definition.
 */

namespace Office365\PHP\Client\SharePoint;


use Office365\PHP\Client\Runtime\ClientValueObject;


class BasePermissions extends ClientValueObject
{


    /**
     * The bitwise high-order boundary (higher 32 bits) of the permission.
     * @var  int
     */
    public $High;


    /**
     * The bitwise low-order boundary (lower 32 bits) of the permission.
     * @var int
     */
    public $Low;


    /**
     * @param int $perm
     * @return bool
     */
    public function has($perm)
    {
        if ($perm == PermissionKind::EmptyMask)
            return true;
        if ($perm == PermissionKind::FullMask) {
            if (((int)$this->High & (int)32767) == (int)32767)
                return (int)$this->Low == (int)65535;
            return false;
        }
        $high = (int)($perm - 1);
        $low = 1;
        if ($high >= 0 && $high < 32)
            return 0 != ((int)$this->Low & (int)($low << $high));
        if ($high >= 32 && $high < 64)
            return 0 != ((int)$this->High & (int)($low << $high - 32));
        return false;
    }

    /**
     *
     */
    public function clearAll()
    {
        $this->Low = 0;
        $this->High = 0;
    }


    /*function convertToEntity(ODataPayload $payload, ODataFormat $format)
    {
        if($format->MetadataLevel == ODataMetadataLevel::Verbose)
            $payload = $payload->Value->GetUserEffectivePermissions;
        parent::convertToEntity($payload, $format);
    }*/

}