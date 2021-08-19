Server states
-------------

Servers contain a status attribute that indicates the current server state. You can filter on the server status when
you complete a list servers request. The server status is returned in the response body. The server status is one of
the following values:

+---------------+------------------------------------------------------------------------------------------------------+
| State         | Description                                                                                          |
+===============+======================================================================================================+
| ACTIVE        | The server is active.                                                                                |
+---------------+------------------------------------------------------------------------------------------------------+
| BUILDING      | The server has not finished the original build process.                                              |
+---------------+------------------------------------------------------------------------------------------------------+
| DELETED       | The server is permanently deleted.                                                                   |
+---------------+------------------------------------------------------------------------------------------------------+
| ERROR         | The server is in error.                                                                              |
+---------------+------------------------------------------------------------------------------------------------------+
| HARD_REBOOT   | The server is hard rebooting. This is equivalent to pulling the power plug on a physical server,     |
|               | plugging it back in, and rebooting it.                                                               |
+---------------+------------------------------------------------------------------------------------------------------+
| PASSWORD      | The password is being reset on the server.                                                           |
+---------------+------------------------------------------------------------------------------------------------------+
| PAUSED        | In a paused state, the state of the server is stored in RAM. A paused server continues to run in     |
|               | frozen state.                                                                                        |
+---------------+------------------------------------------------------------------------------------------------------+
| REBOOT        | The server is in a soft reboot state. A reboot command was passed to the operating system.           |
+---------------+------------------------------------------------------------------------------------------------------+
| REBUILD       | The server is currently being rebuilt from an image.                                                 |
+---------------+------------------------------------------------------------------------------------------------------+
| RESCUED       | The server is in rescue mode. A rescue image is running with the original server image attached.     |
+---------------+------------------------------------------------------------------------------------------------------+
| RESIZED       | Server is performing the differential copy of data that changed during its initial copy. Server is   |
|               | down for this stage.                                                                                 |
+---------------+------------------------------------------------------------------------------------------------------+
| REVERT_RESIZE | The resize or migration of a server failed for some reason. The destination server is being cleaned  |
|               | up and the original source server is restarting.                                                     |
+---------------+------------------------------------------------------------------------------------------------------+
| SOFT_DELETED  | The server is marked as deleted but the disk images are still available to restore.                  |
+---------------+------------------------------------------------------------------------------------------------------+
| STOPPED       | The server is powered off and the disk image still persists.                                         |
+---------------+------------------------------------------------------------------------------------------------------+
| SUSPENDED     | The server is suspended, either by request or necessity. This status appears for only the following  |
|               | hypervisors: XenServer/XCP, KVM, and ESXi. Administrative users may suspend an instance if it is     |
|               | infrequently used or to perform system maintenance. When you suspend an instance, its VM state is    |
|               | stored on disk, all memory is written to disk, and the virtual machine is stopped. Suspending an     |
|               | instance is similar to placing a device in hibernation; memory and vCPUs become available to create  |
|               | other instances.                                                                                     |
+---------------+------------------------------------------------------------------------------------------------------+
| UNKNOWN       | The state of the server is unknown. Contact your cloud provider.                                     |
+---------------+------------------------------------------------------------------------------------------------------+
| VERIFY_RESIZE | System is awaiting confirmation that the server is operational after a move or resize.               |
+---------------+------------------------------------------------------------------------------------------------------+
