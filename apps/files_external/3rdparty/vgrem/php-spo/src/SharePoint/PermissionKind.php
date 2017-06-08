<?php


namespace Office365\PHP\Client\SharePoint;


use Office365\PHP\Client\Runtime\Utilities\EnumType;

class PermissionKind extends EnumType
{
    const EmptyMask = 0;
    const ViewListItems = 1;
    const AddListItems = 2;
    const EditListItems = 3;
    const DeleteListItems = 4;
    const ApproveItems = 5;
    const OpenItems = 6;
    const ViewVersions = 7;
    const DeleteVersions = 8;
    const CancelCheckout = 9;
    const ManagePersonalViews = 10;
    const ManageLists = 12;
    const ViewFormPages = 13;
    const AnonymousSearchAccessList = 14;
    const Open = 17;
    const ViewPages = 18;
    const AddAndCustomizePages = 19;
    const ApplyThemeAndBorder = 20;
    const ApplyStyleSheets = 21;
    const ViewUsageData = 22;
    const CreateSSCSite = 23;
    const ManageSubwebs = 24;
    const CreateGroups = 25;
    const ManagePermissions = 26;
    const BrowseDirectories = 27;
    const BrowseUserInfo = 28;
    const AddDelPrivateWebParts = 29;
    const UpdatePersonalWebParts = 30;
    const ManageWeb = 31;
    const AnonymousSearchAccessWebLists = 32;
    const UseClientIntegration = 37;
    const UseRemoteAPIs = 38;
    const ManageAlerts = 39;
    const CreateAlerts = 40;
    const EditMyUserInfo = 41;
    const EnumeratePermissions = 63;
    const FullMask = 65;
}