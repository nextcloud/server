<?php


namespace Office365\PHP\Client\SharePoint;


use Office365\PHP\Client\Runtime\Utilities\EnumType;

class PrincipalType extends EnumType
{
    const None = 0;
    const User = 1;
    const DistributionList = 2;
    const SecurityGroup = 4;
    const SharePointGroup = 8;
    const All = 15;
}