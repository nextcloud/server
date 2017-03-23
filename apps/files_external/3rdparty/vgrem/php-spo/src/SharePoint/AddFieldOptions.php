<?php


namespace Office365\PHP\Client\SharePoint;


use Office365\PHP\Client\Runtime\Utilities\EnumType;

class AddFieldOptions extends EnumType
{
    const DefaultValue = 0;
    const AddToDefaultContentType = 1;
    const AddToNoContentType = 2;
    const AddToAllContentTypes = 4;
    const AddFieldInternalNameHint = 8;
    const AddFieldToDefaultView = 16;
    const AddFieldCheckDisplayName = 32;
}