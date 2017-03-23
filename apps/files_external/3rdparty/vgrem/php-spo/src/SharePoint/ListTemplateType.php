<?php


namespace Office365\PHP\Client\SharePoint;
use Office365\PHP\Client\Runtime\Utilities\EnumType;

/**
 * Specifies the type of a list definition or a list template and assigns each an underlying Int32 value that corresponds to
 * the list type’s ID number.
 */
abstract class ListTemplateType extends EnumType
{
    const InvalidType = -1;
    const NoListTemplate = 0;
    const GenericList = 100;
    const DocumentLibrary = 101;
    const Survey = 102;
    const Links = 103;
    const Announcements = 104;
    const Contacts = 105;
    const Events = 106;
    const Tasks = 107;
    const DiscussionBoard = 108;
    const PictureLibrary = 109;
    const DataSources = 110;
    const WebTemplateCatalog = 111;
    const UserInformation = 112;
    const WebPartCatalog = 113;
    const ListTemplateCatalog = 114;
    const XMLForm = 115;
    const MasterPageCatalog = 116;
    const NoCodeWorkflows = 117;
    const WorkflowProcess = 118;
    const WebPageLibrary = 119;
    const CustomGrid = 120;
    const SolutionCatalog = 121;
    const NoCodePublic = 122;
    const ThemeCatalog = 123;
    const DesignCatalog = 124;
    const AppDataCatalog = 125;
    const DataConnectionLibrary = 130;
    const WorkflowHistory = 140;
    const GanttTasks = 150;
    const HelpLibrary = 151;
    const AccessRequest = 160;
    const TasksWithTimelineAndHierarchy = 171;
    const MaintenanceLogs = 175;
    const Meetings = 200;
    const Agenda = 201;
    const MeetingUser = 202;
    const Decision = 204;
    const MeetingObjective = 207;
    const TextBox = 210;
    const ThingsToBring = 211;
    const HomePageLibrary = 212;
    const Posts = 301;
    const Comments = 302;
    const Categories = 303;
    const Facility = 402;
    const Whereabouts = 403;
    const CallTrack = 404;
    const Circulation = 405;
    const Timecard = 420;
    const Holidays = 421;
    const IMEDic = 499;
}