<?php

namespace Safe;

use Safe\Exceptions\SemException;

/**
 * Checks whether the message queue key exists.
 *
 * @param int $key Queue key.
 * @throws SemException
 *
 */
function msg_queue_exists(int $key): void
{
    error_clear_last();
    $result = \msg_queue_exists($key);
    if ($result === false) {
        throw SemException::createFromPhpError();
    }
}


/**
 * msg_receive will receive the first message from the
 * specified queue of the type specified by
 * desiredmsgtype.
 *
 * @param resource $queue Message queue resource handle
 * @param int $desiredmsgtype If desiredmsgtype is 0, the message from the front
 * of the queue is returned. If desiredmsgtype is
 * greater than 0, then the first message of that type is returned.
 * If desiredmsgtype is less than 0, the first
 * message on the queue with a type less than or equal to the
 * absolute value of desiredmsgtype will be read.
 * If no messages match the criteria, your script will wait until a suitable
 * message arrives on the queue.  You can prevent the script from blocking
 * by specifying MSG_IPC_NOWAIT in the
 * flags parameter.
 * @param int|null $msgtype The type of the message that was received will be stored in this
 * parameter.
 * @param int $maxsize The maximum size of message to be accepted is specified by the
 * maxsize; if the message in the queue is larger
 * than this size the function will fail (unless you set
 * flags as described below).
 * @param mixed $message The received message will be stored in message,
 * unless there were errors receiving the message.
 * @param bool $unserialize If set to
 * TRUE, the message is treated as though it was serialized using the
 * same mechanism as the session module. The message will be unserialized
 * and then returned to your script. This allows you to easily receive
 * arrays or complex object structures from other PHP scripts, or if you
 * are using the WDDX serializer, from any WDDX compatible source.
 *
 * If unserialize is FALSE, the message will be
 * returned as a binary-safe string.
 * @param int $flags The optional flags allows you to pass flags to the
 * low-level msgrcv system call.  It defaults to 0, but you may specify one
 * or more of the following values (by adding or ORing them together).
 *
 * Flag values for msg_receive
 *
 *
 *
 * MSG_IPC_NOWAIT
 * If there are no messages of the
 * desiredmsgtype, return immediately and do not
 * wait.  The function will fail and return an integer value
 * corresponding to MSG_ENOMSG.
 *
 *
 *
 * MSG_EXCEPT
 * Using this flag in combination with a
 * desiredmsgtype greater than 0 will cause the
 * function to receive the first message that is not equal to
 * desiredmsgtype.
 *
 *
 * MSG_NOERROR
 *
 * If the message is longer than maxsize,
 * setting this flag will truncate the message to
 * maxsize and will not signal an error.
 *
 *
 *
 *
 *
 * @param int|null $errorcode If the function fails, the optional errorcode
 * will be set to the value of the system errno variable.
 * @throws SemException
 *
 */
function msg_receive($queue, int $desiredmsgtype, ?int &$msgtype, int $maxsize, &$message, bool $unserialize = true, int $flags = 0, ?int &$errorcode = null): void
{
    error_clear_last();
    $result = \msg_receive($queue, $desiredmsgtype, $msgtype, $maxsize, $message, $unserialize, $flags, $errorcode);
    if ($result === false) {
        throw SemException::createFromPhpError();
    }
}


/**
 * msg_remove_queue destroys the message queue specified
 * by the queue.  Only use this function when all
 * processes have finished working with the message queue and you need to
 * release the system resources held by it.
 *
 * @param resource $queue Message queue resource handle
 * @throws SemException
 *
 */
function msg_remove_queue($queue): void
{
    error_clear_last();
    $result = \msg_remove_queue($queue);
    if ($result === false) {
        throw SemException::createFromPhpError();
    }
}


/**
 * msg_send sends a message of type
 * msgtype (which MUST be greater than 0) to
 * the message queue specified by queue.
 *
 * @param resource $queue Message queue resource handle
 * @param int $msgtype The type of the message (MUST be greater than 0)
 * @param mixed $message The body of the message.
 *
 * If serialize set to FALSE is supplied,
 * MUST be of type: string, integer, float
 * or bool. In other case a warning will be issued.
 * @param bool $serialize The optional serialize controls how the
 * message is sent.  serialize
 * defaults to TRUE which means that the message is
 * serialized using the same mechanism as the session module before being
 * sent to the queue.  This allows complex arrays and objects to be sent to
 * other PHP scripts, or if you are using the WDDX serializer, to any WDDX
 * compatible client.
 * @param bool $blocking If the message is too large to fit in the queue, your script will wait
 * until another process reads messages from the queue and frees enough
 * space for your message to be sent.
 * This is called blocking; you can prevent blocking by setting the
 * optional blocking parameter to FALSE, in which
 * case msg_send will immediately return FALSE if the
 * message is too big for the queue, and set the optional
 * errorcode to MSG_EAGAIN,
 * indicating that you should try to send your message again a little
 * later on.
 * @param int|null $errorcode If the function fails, the optional errorcode will be set to the value of the system errno variable.
 * @throws SemException
 *
 */
function msg_send($queue, int $msgtype, $message, bool $serialize = true, bool $blocking = true, ?int &$errorcode = null): void
{
    error_clear_last();
    $result = \msg_send($queue, $msgtype, $message, $serialize, $blocking, $errorcode);
    if ($result === false) {
        throw SemException::createFromPhpError();
    }
}


/**
 * msg_set_queue allows you to change the values of the
 * msg_perm.uid, msg_perm.gid, msg_perm.mode and msg_qbytes fields of the
 * underlying message queue data structure.
 *
 * Changing the data structure will require that PHP be running as the same
 * user that created the queue, owns the queue (as determined by the
 * existing msg_perm.xxx fields), or be running with root privileges.
 * root privileges are required to raise the msg_qbytes values above the
 * system defined limit.
 *
 * @param resource $queue Message queue resource handle
 * @param array $data You specify the values you require by setting the value of the keys
 * that you require in the data array.
 * @throws SemException
 *
 */
function msg_set_queue($queue, array $data): void
{
    error_clear_last();
    $result = \msg_set_queue($queue, $data);
    if ($result === false) {
        throw SemException::createFromPhpError();
    }
}


/**
 * sem_acquire by default blocks (if necessary) until the
 * semaphore can be acquired.  A process attempting to acquire a semaphore which
 * it has already acquired will block forever if acquiring the semaphore would
 * cause its maximum number of semaphore to be exceeded.
 *
 * After processing a request, any semaphores acquired by the process but not
 * explicitly released will be released automatically and a warning will be
 * generated.
 *
 * @param resource $sem_identifier sem_identifier is a semaphore resource,
 * obtained from sem_get.
 * @param bool $nowait Specifies if the process shouldn't wait for the semaphore to be acquired.
 * If set to true, the call will return
 * false immediately if a semaphore cannot be immediately
 * acquired.
 * @throws SemException
 *
 */
function sem_acquire($sem_identifier, bool $nowait = false): void
{
    error_clear_last();
    $result = \sem_acquire($sem_identifier, $nowait);
    if ($result === false) {
        throw SemException::createFromPhpError();
    }
}


/**
 * sem_get returns an id that can be used to
 * access the System V semaphore with the given key.
 *
 * A second call to sem_get for the same key
 * will return a different semaphore identifier, but both
 * identifiers access the same underlying semaphore.
 *
 * If key is 0, a new private semaphore
 * is created for each call to sem_get.
 *
 * @param int $key
 * @param int $max_acquire The number of processes that can acquire the semaphore simultaneously
 * is set to max_acquire.
 * @param int $perm The semaphore permissions. Actually this value is
 * set only if the process finds it is the only process currently
 * attached to the semaphore.
 * @param int $auto_release Specifies if the semaphore should be automatically released on request
 * shutdown.
 * @return resource Returns a positive semaphore identifier on success.
 * @throws SemException
 *
 */
function sem_get(int $key, int $max_acquire = 1, int $perm = 0666, int $auto_release = 1)
{
    error_clear_last();
    $result = \sem_get($key, $max_acquire, $perm, $auto_release);
    if ($result === false) {
        throw SemException::createFromPhpError();
    }
    return $result;
}


/**
 * sem_release releases the semaphore if it
 * is currently acquired by the calling process, otherwise
 * a warning is generated.
 *
 * After releasing the semaphore, sem_acquire
 * may be called to re-acquire it.
 *
 * @param resource $sem_identifier A Semaphore resource handle as returned by
 * sem_get.
 * @throws SemException
 *
 */
function sem_release($sem_identifier): void
{
    error_clear_last();
    $result = \sem_release($sem_identifier);
    if ($result === false) {
        throw SemException::createFromPhpError();
    }
}


/**
 * sem_remove removes the given semaphore.
 *
 * After removing the semaphore, it is no longer accessible.
 *
 * @param resource $sem_identifier A semaphore resource identifier as returned
 * by sem_get.
 * @throws SemException
 *
 */
function sem_remove($sem_identifier): void
{
    error_clear_last();
    $result = \sem_remove($sem_identifier);
    if ($result === false) {
        throw SemException::createFromPhpError();
    }
}


/**
 * shm_put_var inserts or updates the
 * variable with the given
 * variable_key.
 *
 * Warnings (E_WARNING level) will be issued if
 * shm_identifier is not a valid SysV shared memory
 * index or if there was not enough shared memory remaining to complete your
 * request.
 *
 * @param resource $shm_identifier A shared memory resource handle as returned by
 * shm_attach
 * @param int $variable_key The variable key.
 * @param mixed $variable The variable. All variable types
 * that serialize supports may be used: generally
 * this means all types except for resources and some internal objects
 * that cannot be serialized.
 * @throws SemException
 *
 */
function shm_put_var($shm_identifier, int $variable_key, $variable): void
{
    error_clear_last();
    $result = \shm_put_var($shm_identifier, $variable_key, $variable);
    if ($result === false) {
        throw SemException::createFromPhpError();
    }
}


/**
 * Removes a variable with a given variable_key
 * and frees the occupied memory.
 *
 * @param resource $shm_identifier The shared memory identifier as returned by
 * shm_attach
 * @param int $variable_key The variable key.
 * @throws SemException
 *
 */
function shm_remove_var($shm_identifier, int $variable_key): void
{
    error_clear_last();
    $result = \shm_remove_var($shm_identifier, $variable_key);
    if ($result === false) {
        throw SemException::createFromPhpError();
    }
}


/**
 * shm_remove removes the shared memory
 * shm_identifier. All data will be destroyed.
 *
 * @param resource $shm_identifier The shared memory identifier as returned by
 * shm_attach
 * @throws SemException
 *
 */
function shm_remove($shm_identifier): void
{
    error_clear_last();
    $result = \shm_remove($shm_identifier);
    if ($result === false) {
        throw SemException::createFromPhpError();
    }
}
