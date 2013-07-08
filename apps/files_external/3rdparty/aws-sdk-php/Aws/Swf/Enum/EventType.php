<?php
/**
 * Copyright 2010-2013 Amazon.com, Inc. or its affiliates. All Rights Reserved.
 *
 * Licensed under the Apache License, Version 2.0 (the "License").
 * You may not use this file except in compliance with the License.
 * A copy of the License is located at
 *
 * http://aws.amazon.com/apache2.0
 *
 * or in the "license" file accompanying this file. This file is distributed
 * on an "AS IS" BASIS, WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either
 * express or implied. See the License for the specific language governing
 * permissions and limitations under the License.
 */

namespace Aws\Swf\Enum;

use Aws\Common\Enum;

/**
 * Contains enumerable EventType values
 */
class EventType extends Enum
{
    const WORKFLOW_EXECUTION_STARTED = 'WorkflowExecutionStarted';
    const WORKFLOW_EXECUTION_CANCEL_REQUESTED = 'WorkflowExecutionCancelRequested';
    const WORKFLOW_EXECUTION_COMPLETED = 'WorkflowExecutionCompleted';
    const COMPLETE_WORKFLOW_EXECUTION_FAILED = 'CompleteWorkflowExecutionFailed';
    const WORKFLOW_EXECUTION_FAILED = 'WorkflowExecutionFailed';
    const FAIL_WORKFLOW_EXECUTION_FAILED = 'FailWorkflowExecutionFailed';
    const WORKFLOW_EXECUTION_TIMED_OUT = 'WorkflowExecutionTimedOut';
    const WORKFLOW_EXECUTION_CANCELED = 'WorkflowExecutionCanceled';
    const CANCEL_WORKFLOW_EXECUTION_FAILED = 'CancelWorkflowExecutionFailed';
    const WORKFLOW_EXECUTION_CONTINUED_AS_NEW = 'WorkflowExecutionContinuedAsNew';
    const CONTINUE_AS_NEW_WORKFLOW_EXECUTION_FAILED = 'ContinueAsNewWorkflowExecutionFailed';
    const WORKFLOW_EXECUTION_TERMINATED = 'WorkflowExecutionTerminated';
    const DECISION_TASK_SCHEDULED = 'DecisionTaskScheduled';
    const DECISION_TASK_STARTED = 'DecisionTaskStarted';
    const DECISION_TASK_COMPLETED = 'DecisionTaskCompleted';
    const DECISION_TASK_TIMED_OUT = 'DecisionTaskTimedOut';
    const ACTIVITY_TASK_SCHEDULED = 'ActivityTaskScheduled';
    const SCHEDULE_ACTIVITY_TASK_FAILED = 'ScheduleActivityTaskFailed';
    const ACTIVITY_TASK_STARTED = 'ActivityTaskStarted';
    const ACTIVITY_TASK_COMPLETED = 'ActivityTaskCompleted';
    const ACTIVITY_TASK_FAILED = 'ActivityTaskFailed';
    const ACTIVITY_TASK_TIMED_OUT = 'ActivityTaskTimedOut';
    const ACTIVITY_TASK_CANCELED = 'ActivityTaskCanceled';
    const ACTIVITY_TASK_CANCEL_REQUESTED = 'ActivityTaskCancelRequested';
    const REQUEST_CANCEL_ACTIVITY_TASK_FAILED = 'RequestCancelActivityTaskFailed';
    const WORKFLOW_EXECUTION_SIGNALED = 'WorkflowExecutionSignaled';
    const MARKER_RECORDED = 'MarkerRecorded';
    const RECORD_MARKER_FAILED = 'RecordMarkerFailed';
    const TIMER_STARTED = 'TimerStarted';
    const START_TIMER_FAILED = 'StartTimerFailed';
    const TIMER_FIRED = 'TimerFired';
    const TIMER_CANCELED = 'TimerCanceled';
    const CANCEL_TIMER_FAILED = 'CancelTimerFailed';
    const START_CHILD_WORKFLOW_EXECUTION_INITIATED = 'StartChildWorkflowExecutionInitiated';
    const START_CHILD_WORKFLOW_EXECUTION_FAILED = 'StartChildWorkflowExecutionFailed';
    const CHILD_WORKFLOW_EXECUTION_STARTED = 'ChildWorkflowExecutionStarted';
    const CHILD_WORKFLOW_EXECUTION_COMPLETED = 'ChildWorkflowExecutionCompleted';
    const CHILD_WORKFLOW_EXECUTION_FAILED = 'ChildWorkflowExecutionFailed';
    const CHILD_WORKFLOW_EXECUTION_TIMED_OUT = 'ChildWorkflowExecutionTimedOut';
    const CHILD_WORKFLOW_EXECUTION_CANCELED = 'ChildWorkflowExecutionCanceled';
    const CHILD_WORKFLOW_EXECUTION_TERMINATED = 'ChildWorkflowExecutionTerminated';
    const SIGNAL_EXTERNAL_WORKFLOW_EXECUTION_INITIATED = 'SignalExternalWorkflowExecutionInitiated';
    const SIGNAL_EXTERNAL_WORKFLOW_EXECUTION_FAILED = 'SignalExternalWorkflowExecutionFailed';
    const EXTERNAL_WORKFLOW_EXECUTION_SIGNALED = 'ExternalWorkflowExecutionSignaled';
    const REQUEST_CANCEL_EXTERNAL_WORKFLOW_EXECUTION_INITIATED = 'RequestCancelExternalWorkflowExecutionInitiated';
    const REQUEST_CANCEL_EXTERNAL_WORKFLOW_EXECUTION_FAILED = 'RequestCancelExternalWorkflowExecutionFailed';
    const EXTERNAL_WORKFLOW_EXECUTION_CANCEL_REQUESTED = 'ExternalWorkflowExecutionCancelRequested';
}
