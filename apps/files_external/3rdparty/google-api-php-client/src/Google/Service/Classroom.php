<?php
/*
 * Copyright 2010 Google Inc.
 *
 * Licensed under the Apache License, Version 2.0 (the "License"); you may not
 * use this file except in compliance with the License. You may obtain a copy of
 * the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS, WITHOUT
 * WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied. See the
 * License for the specific language governing permissions and limitations under
 * the License.
 */

/**
 * Service definition for Classroom (v1).
 *
 * <p>
 * Google Classroom API</p>
 *
 * <p>
 * For more information about this service, see the API
 * <a href="https://developers.google.com/classroom/" target="_blank">Documentation</a>
 * </p>
 *
 * @author Google, Inc.
 */
class Google_Service_Classroom extends Google_Service
{
  /** Manage your Google Classroom classes. */
  const CLASSROOM_COURSES =
      "https://www.googleapis.com/auth/classroom.courses";
  /** View your Google Classroom classes. */
  const CLASSROOM_COURSES_READONLY =
      "https://www.googleapis.com/auth/classroom.courses.readonly";
  /** View the email addresses of people in your classes. */
  const CLASSROOM_PROFILE_EMAILS =
      "https://www.googleapis.com/auth/classroom.profile.emails";
  /** View the profile photos of people in your classes. */
  const CLASSROOM_PROFILE_PHOTOS =
      "https://www.googleapis.com/auth/classroom.profile.photos";
  /** Manage your Google Classroom class rosters. */
  const CLASSROOM_ROSTERS =
      "https://www.googleapis.com/auth/classroom.rosters";
  /** View your Google Classroom class rosters. */
  const CLASSROOM_ROSTERS_READONLY =
      "https://www.googleapis.com/auth/classroom.rosters.readonly";

  public $courses;
  public $courses_aliases;
  public $courses_students;
  public $courses_teachers;
  public $invitations;
  public $userProfiles;
  

  /**
   * Constructs the internal representation of the Classroom service.
   *
   * @param Google_Client $client
   */
  public function __construct(Google_Client $client)
  {
    parent::__construct($client);
    $this->rootUrl = 'https://classroom.googleapis.com/';
    $this->servicePath = '';
    $this->version = 'v1';
    $this->serviceName = 'classroom';

    $this->courses = new Google_Service_Classroom_Courses_Resource(
        $this,
        $this->serviceName,
        'courses',
        array(
          'methods' => array(
            'create' => array(
              'path' => 'v1/courses',
              'httpMethod' => 'POST',
              'parameters' => array(),
            ),'delete' => array(
              'path' => 'v1/courses/{id}',
              'httpMethod' => 'DELETE',
              'parameters' => array(
                'id' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),'get' => array(
              'path' => 'v1/courses/{id}',
              'httpMethod' => 'GET',
              'parameters' => array(
                'id' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),'list' => array(
              'path' => 'v1/courses',
              'httpMethod' => 'GET',
              'parameters' => array(
                'teacherId' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'pageToken' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'studentId' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'pageSize' => array(
                  'location' => 'query',
                  'type' => 'integer',
                ),
              ),
            ),'patch' => array(
              'path' => 'v1/courses/{id}',
              'httpMethod' => 'PATCH',
              'parameters' => array(
                'id' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'updateMask' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
              ),
            ),'update' => array(
              'path' => 'v1/courses/{id}',
              'httpMethod' => 'PUT',
              'parameters' => array(
                'id' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),
          )
        )
    );
    $this->courses_aliases = new Google_Service_Classroom_CoursesAliases_Resource(
        $this,
        $this->serviceName,
        'aliases',
        array(
          'methods' => array(
            'create' => array(
              'path' => 'v1/courses/{courseId}/aliases',
              'httpMethod' => 'POST',
              'parameters' => array(
                'courseId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),'delete' => array(
              'path' => 'v1/courses/{courseId}/aliases/{alias}',
              'httpMethod' => 'DELETE',
              'parameters' => array(
                'courseId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'alias' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),'list' => array(
              'path' => 'v1/courses/{courseId}/aliases',
              'httpMethod' => 'GET',
              'parameters' => array(
                'courseId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'pageToken' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'pageSize' => array(
                  'location' => 'query',
                  'type' => 'integer',
                ),
              ),
            ),
          )
        )
    );
    $this->courses_students = new Google_Service_Classroom_CoursesStudents_Resource(
        $this,
        $this->serviceName,
        'students',
        array(
          'methods' => array(
            'create' => array(
              'path' => 'v1/courses/{courseId}/students',
              'httpMethod' => 'POST',
              'parameters' => array(
                'courseId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'enrollmentCode' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
              ),
            ),'delete' => array(
              'path' => 'v1/courses/{courseId}/students/{userId}',
              'httpMethod' => 'DELETE',
              'parameters' => array(
                'courseId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'userId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),'get' => array(
              'path' => 'v1/courses/{courseId}/students/{userId}',
              'httpMethod' => 'GET',
              'parameters' => array(
                'courseId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'userId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),'list' => array(
              'path' => 'v1/courses/{courseId}/students',
              'httpMethod' => 'GET',
              'parameters' => array(
                'courseId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'pageToken' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'pageSize' => array(
                  'location' => 'query',
                  'type' => 'integer',
                ),
              ),
            ),
          )
        )
    );
    $this->courses_teachers = new Google_Service_Classroom_CoursesTeachers_Resource(
        $this,
        $this->serviceName,
        'teachers',
        array(
          'methods' => array(
            'create' => array(
              'path' => 'v1/courses/{courseId}/teachers',
              'httpMethod' => 'POST',
              'parameters' => array(
                'courseId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),'delete' => array(
              'path' => 'v1/courses/{courseId}/teachers/{userId}',
              'httpMethod' => 'DELETE',
              'parameters' => array(
                'courseId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'userId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),'get' => array(
              'path' => 'v1/courses/{courseId}/teachers/{userId}',
              'httpMethod' => 'GET',
              'parameters' => array(
                'courseId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'userId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),'list' => array(
              'path' => 'v1/courses/{courseId}/teachers',
              'httpMethod' => 'GET',
              'parameters' => array(
                'courseId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'pageToken' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'pageSize' => array(
                  'location' => 'query',
                  'type' => 'integer',
                ),
              ),
            ),
          )
        )
    );
    $this->invitations = new Google_Service_Classroom_Invitations_Resource(
        $this,
        $this->serviceName,
        'invitations',
        array(
          'methods' => array(
            'accept' => array(
              'path' => 'v1/invitations/{id}:accept',
              'httpMethod' => 'POST',
              'parameters' => array(
                'id' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),'create' => array(
              'path' => 'v1/invitations',
              'httpMethod' => 'POST',
              'parameters' => array(),
            ),'delete' => array(
              'path' => 'v1/invitations/{id}',
              'httpMethod' => 'DELETE',
              'parameters' => array(
                'id' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),'get' => array(
              'path' => 'v1/invitations/{id}',
              'httpMethod' => 'GET',
              'parameters' => array(
                'id' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),'list' => array(
              'path' => 'v1/invitations',
              'httpMethod' => 'GET',
              'parameters' => array(
                'courseId' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'pageToken' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'userId' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'pageSize' => array(
                  'location' => 'query',
                  'type' => 'integer',
                ),
              ),
            ),
          )
        )
    );
    $this->userProfiles = new Google_Service_Classroom_UserProfiles_Resource(
        $this,
        $this->serviceName,
        'userProfiles',
        array(
          'methods' => array(
            'get' => array(
              'path' => 'v1/userProfiles/{userId}',
              'httpMethod' => 'GET',
              'parameters' => array(
                'userId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),
          )
        )
    );
  }
}


/**
 * The "courses" collection of methods.
 * Typical usage is:
 *  <code>
 *   $classroomService = new Google_Service_Classroom(...);
 *   $courses = $classroomService->courses;
 *  </code>
 */
class Google_Service_Classroom_Courses_Resource extends Google_Service_Resource
{

  /**
   * Creates a course. The user specified in `ownerId` is the owner of the created
   * course and added as a teacher. This method returns the following error codes:
   * * `PERMISSION_DENIED` if the requesting user is not permitted to create
   * courses or for access errors. * `NOT_FOUND` if the primary teacher is not a
   * valid user. * `FAILED_PRECONDITION` if the course owner's account is disabled
   * or for the following request errors: * UserGroupsMembershipLimitReached *
   * `ALREADY_EXISTS` if an alias was specified in the `id` and already exists.
   * (courses.create)
   *
   * @param Google_Course $postBody
   * @param array $optParams Optional parameters.
   * @return Google_Service_Classroom_Course
   */
  public function create(Google_Service_Classroom_Course $postBody, $optParams = array())
  {
    $params = array('postBody' => $postBody);
    $params = array_merge($params, $optParams);
    return $this->call('create', array($params), "Google_Service_Classroom_Course");
  }

  /**
   * Deletes a course. This method returns the following error codes: *
   * `PERMISSION_DENIED` if the requesting user is not permitted to delete the
   * requested course or for access errors. * `NOT_FOUND` if no course exists with
   * the requested ID. (courses.delete)
   *
   * @param string $id Identifier of the course to delete. This identifier can be
   * either the Classroom-assigned identifier or an alias.
   * @param array $optParams Optional parameters.
   * @return Google_Service_Classroom_Empty
   */
  public function delete($id, $optParams = array())
  {
    $params = array('id' => $id);
    $params = array_merge($params, $optParams);
    return $this->call('delete', array($params), "Google_Service_Classroom_Empty");
  }

  /**
   * Returns a course. This method returns the following error codes: *
   * `PERMISSION_DENIED` if the requesting user is not permitted to access the
   * requested course or for access errors. * `NOT_FOUND` if no course exists with
   * the requested ID. (courses.get)
   *
   * @param string $id Identifier of the course to return. This identifier can be
   * either the Classroom-assigned identifier or an alias.
   * @param array $optParams Optional parameters.
   * @return Google_Service_Classroom_Course
   */
  public function get($id, $optParams = array())
  {
    $params = array('id' => $id);
    $params = array_merge($params, $optParams);
    return $this->call('get', array($params), "Google_Service_Classroom_Course");
  }

  /**
   * Returns a list of courses that the requesting user is permitted to view,
   * restricted to those that match the request. This method returns the following
   * error codes: * `PERMISSION_DENIED` for access errors. * `INVALID_ARGUMENT` if
   * the query argument is malformed. * `NOT_FOUND` if any users specified in the
   * query arguments do not exist. (courses.listCourses)
   *
   * @param array $optParams Optional parameters.
   *
   * @opt_param string teacherId Restricts returned courses to those having a
   * teacher with the specified identifier. The identifier can be one of the
   * following: * the numeric identifier for the user * the email address of the
   * user * the string literal `"me"`, indicating the requesting user
   * @opt_param string pageToken nextPageToken value returned from a previous list
   * call, indicating that the subsequent page of results should be returned. The
   * list request must be otherwise identical to the one that resulted in this
   * token.
   * @opt_param string studentId Restricts returned courses to those having a
   * student with the specified identifier. The identifier can be one of the
   * following: * the numeric identifier for the user * the email address of the
   * user * the string literal `"me"`, indicating the requesting user
   * @opt_param int pageSize Maximum number of items to return. Zero or
   * unspecified indicates that the server may assign a maximum. The server may
   * return fewer than the specified number of results.
   * @return Google_Service_Classroom_ListCoursesResponse
   */
  public function listCourses($optParams = array())
  {
    $params = array();
    $params = array_merge($params, $optParams);
    return $this->call('list', array($params), "Google_Service_Classroom_ListCoursesResponse");
  }

  /**
   * Updates one or more fields in a course. This method returns the following
   * error codes: * `PERMISSION_DENIED` if the requesting user is not permitted to
   * modify the requested course or for access errors. * `NOT_FOUND` if no course
   * exists with the requested ID. * `INVALID_ARGUMENT` if invalid fields are
   * specified in the update mask or if no update mask is supplied. *
   * `FAILED_PRECONDITION` for the following request errors: * CourseNotModifiable
   * (courses.patch)
   *
   * @param string $id Identifier of the course to update. This identifier can be
   * either the Classroom-assigned identifier or an alias.
   * @param Google_Course $postBody
   * @param array $optParams Optional parameters.
   *
   * @opt_param string updateMask Mask that identifies which fields on the course
   * to update. This field is required to do an update. The update will fail if
   * invalid fields are specified. The following fields are valid: * `name` *
   * `section` * `descriptionHeading` * `description` * `room` * `courseState`
   * When set in a query parameter, this field should be specified as
   * `updateMask=,,...`
   * @return Google_Service_Classroom_Course
   */
  public function patch($id, Google_Service_Classroom_Course $postBody, $optParams = array())
  {
    $params = array('id' => $id, 'postBody' => $postBody);
    $params = array_merge($params, $optParams);
    return $this->call('patch', array($params), "Google_Service_Classroom_Course");
  }

  /**
   * Updates a course. This method returns the following error codes: *
   * `PERMISSION_DENIED` if the requesting user is not permitted to modify the
   * requested course or for access errors. * `NOT_FOUND` if no course exists with
   * the requested ID. * `FAILED_PRECONDITION` for the following request errors: *
   * CourseNotModifiable (courses.update)
   *
   * @param string $id Identifier of the course to update. This identifier can be
   * either the Classroom-assigned identifier or an alias.
   * @param Google_Course $postBody
   * @param array $optParams Optional parameters.
   * @return Google_Service_Classroom_Course
   */
  public function update($id, Google_Service_Classroom_Course $postBody, $optParams = array())
  {
    $params = array('id' => $id, 'postBody' => $postBody);
    $params = array_merge($params, $optParams);
    return $this->call('update', array($params), "Google_Service_Classroom_Course");
  }
}

/**
 * The "aliases" collection of methods.
 * Typical usage is:
 *  <code>
 *   $classroomService = new Google_Service_Classroom(...);
 *   $aliases = $classroomService->aliases;
 *  </code>
 */
class Google_Service_Classroom_CoursesAliases_Resource extends Google_Service_Resource
{

  /**
   * Creates an alias for a course. This method returns the following error codes:
   * * `PERMISSION_DENIED` if the requesting user is not permitted to create the
   * alias or for access errors. * `NOT_FOUND` if the course does not exist. *
   * `ALREADY_EXISTS` if the alias already exists. (aliases.create)
   *
   * @param string $courseId Identifier of the course to alias. This identifier
   * can be either the Classroom-assigned identifier or an alias.
   * @param Google_CourseAlias $postBody
   * @param array $optParams Optional parameters.
   * @return Google_Service_Classroom_CourseAlias
   */
  public function create($courseId, Google_Service_Classroom_CourseAlias $postBody, $optParams = array())
  {
    $params = array('courseId' => $courseId, 'postBody' => $postBody);
    $params = array_merge($params, $optParams);
    return $this->call('create', array($params), "Google_Service_Classroom_CourseAlias");
  }

  /**
   * Deletes an alias of a course. This method returns the following error codes:
   * * `PERMISSION_DENIED` if the requesting user is not permitted to remove the
   * alias or for access errors. * `NOT_FOUND` if the alias does not exist.
   * (aliases.delete)
   *
   * @param string $courseId Identifier of the course whose alias should be
   * deleted. This identifier can be either the Classroom-assigned identifier or
   * an alias.
   * @param string $alias Alias to delete. This may not be the Classroom-assigned
   * identifier.
   * @param array $optParams Optional parameters.
   * @return Google_Service_Classroom_Empty
   */
  public function delete($courseId, $alias, $optParams = array())
  {
    $params = array('courseId' => $courseId, 'alias' => $alias);
    $params = array_merge($params, $optParams);
    return $this->call('delete', array($params), "Google_Service_Classroom_Empty");
  }

  /**
   * Returns a list of aliases for a course. This method returns the following
   * error codes: * `PERMISSION_DENIED` if the requesting user is not permitted to
   * access the course or for access errors. * `NOT_FOUND` if the course does not
   * exist. (aliases.listCoursesAliases)
   *
   * @param string $courseId The identifier of the course. This identifier can be
   * either the Classroom-assigned identifier or an alias.
   * @param array $optParams Optional parameters.
   *
   * @opt_param string pageToken nextPageToken value returned from a previous list
   * call, indicating that the subsequent page of results should be returned. The
   * list request must be otherwise identical to the one that resulted in this
   * token.
   * @opt_param int pageSize Maximum number of items to return. Zero or
   * unspecified indicates that the server may assign a maximum. The server may
   * return fewer than the specified number of results.
   * @return Google_Service_Classroom_ListCourseAliasesResponse
   */
  public function listCoursesAliases($courseId, $optParams = array())
  {
    $params = array('courseId' => $courseId);
    $params = array_merge($params, $optParams);
    return $this->call('list', array($params), "Google_Service_Classroom_ListCourseAliasesResponse");
  }
}
/**
 * The "students" collection of methods.
 * Typical usage is:
 *  <code>
 *   $classroomService = new Google_Service_Classroom(...);
 *   $students = $classroomService->students;
 *  </code>
 */
class Google_Service_Classroom_CoursesStudents_Resource extends Google_Service_Resource
{

  /**
   * Adds a user as a student of a course. This method returns the following error
   * codes: * `PERMISSION_DENIED` if the requesting user is not permitted to
   * create students in this course or for access errors. * `NOT_FOUND` if the
   * requested course ID does not exist. * `FAILED_PRECONDITION` if the requested
   * user's account is disabled, for the following request errors: *
   * CourseMemberLimitReached * CourseNotModifiable *
   * UserGroupsMembershipLimitReached * `ALREADY_EXISTS` if the user is already a
   * student or teacher in the course. (students.create)
   *
   * @param string $courseId Identifier of the course to create the student in.
   * This identifier can be either the Classroom-assigned identifier or an alias.
   * @param Google_Student $postBody
   * @param array $optParams Optional parameters.
   *
   * @opt_param string enrollmentCode Enrollment code of the course to create the
   * student in. This code is required if userId corresponds to the requesting
   * user; it may be omitted if the requesting user has administrative permissions
   * to create students for any user.
   * @return Google_Service_Classroom_Student
   */
  public function create($courseId, Google_Service_Classroom_Student $postBody, $optParams = array())
  {
    $params = array('courseId' => $courseId, 'postBody' => $postBody);
    $params = array_merge($params, $optParams);
    return $this->call('create', array($params), "Google_Service_Classroom_Student");
  }

  /**
   * Deletes a student of a course. This method returns the following error codes:
   * * `PERMISSION_DENIED` if the requesting user is not permitted to delete
   * students of this course or for access errors. * `NOT_FOUND` if no student of
   * this course has the requested ID or if the course does not exist.
   * (students.delete)
   *
   * @param string $courseId Identifier of the course. This identifier can be
   * either the Classroom-assigned identifier or an alias.
   * @param string $userId Identifier of the student to delete. The identifier can
   * be one of the following: * the numeric identifier for the user * the email
   * address of the user * the string literal `"me"`, indicating the requesting
   * user
   * @param array $optParams Optional parameters.
   * @return Google_Service_Classroom_Empty
   */
  public function delete($courseId, $userId, $optParams = array())
  {
    $params = array('courseId' => $courseId, 'userId' => $userId);
    $params = array_merge($params, $optParams);
    return $this->call('delete', array($params), "Google_Service_Classroom_Empty");
  }

  /**
   * Returns a student of a course. This method returns the following error codes:
   * * `PERMISSION_DENIED` if the requesting user is not permitted to view
   * students of this course or for access errors. * `NOT_FOUND` if no student of
   * this course has the requested ID or if the course does not exist.
   * (students.get)
   *
   * @param string $courseId Identifier of the course. This identifier can be
   * either the Classroom-assigned identifier or an alias.
   * @param string $userId Identifier of the student to return. The identifier can
   * be one of the following: * the numeric identifier for the user * the email
   * address of the user * the string literal `"me"`, indicating the requesting
   * user
   * @param array $optParams Optional parameters.
   * @return Google_Service_Classroom_Student
   */
  public function get($courseId, $userId, $optParams = array())
  {
    $params = array('courseId' => $courseId, 'userId' => $userId);
    $params = array_merge($params, $optParams);
    return $this->call('get', array($params), "Google_Service_Classroom_Student");
  }

  /**
   * Returns a list of students of this course that the requester is permitted to
   * view. This method returns the following error codes: * `NOT_FOUND` if the
   * course does not exist. * `PERMISSION_DENIED` for access errors.
   * (students.listCoursesStudents)
   *
   * @param string $courseId Identifier of the course. This identifier can be
   * either the Classroom-assigned identifier or an alias.
   * @param array $optParams Optional parameters.
   *
   * @opt_param string pageToken nextPageToken value returned from a previous list
   * call, indicating that the subsequent page of results should be returned. The
   * list request must be otherwise identical to the one that resulted in this
   * token.
   * @opt_param int pageSize Maximum number of items to return. Zero means no
   * maximum. The server may return fewer than the specified number of results.
   * @return Google_Service_Classroom_ListStudentsResponse
   */
  public function listCoursesStudents($courseId, $optParams = array())
  {
    $params = array('courseId' => $courseId);
    $params = array_merge($params, $optParams);
    return $this->call('list', array($params), "Google_Service_Classroom_ListStudentsResponse");
  }
}
/**
 * The "teachers" collection of methods.
 * Typical usage is:
 *  <code>
 *   $classroomService = new Google_Service_Classroom(...);
 *   $teachers = $classroomService->teachers;
 *  </code>
 */
class Google_Service_Classroom_CoursesTeachers_Resource extends Google_Service_Resource
{

  /**
   * Creates a teacher of a course. This method returns the following error codes:
   * * `PERMISSION_DENIED` if the requesting user is not permitted to create
   * teachers in this course or for access errors. * `NOT_FOUND` if the requested
   * course ID does not exist. * `FAILED_PRECONDITION` if the requested user's
   * account is disabled, for the following request errors: *
   * CourseMemberLimitReached * CourseNotModifiable * CourseTeacherLimitReached *
   * UserGroupsMembershipLimitReached * `ALREADY_EXISTS` if the user is already a
   * teacher or student in the course. (teachers.create)
   *
   * @param string $courseId Identifier of the course. This identifier can be
   * either the Classroom-assigned identifier or an alias.
   * @param Google_Teacher $postBody
   * @param array $optParams Optional parameters.
   * @return Google_Service_Classroom_Teacher
   */
  public function create($courseId, Google_Service_Classroom_Teacher $postBody, $optParams = array())
  {
    $params = array('courseId' => $courseId, 'postBody' => $postBody);
    $params = array_merge($params, $optParams);
    return $this->call('create', array($params), "Google_Service_Classroom_Teacher");
  }

  /**
   * Deletes a teacher of a course. This method returns the following error codes:
   * * `PERMISSION_DENIED` if the requesting user is not permitted to delete
   * teachers of this course or for access errors. * `NOT_FOUND` if no teacher of
   * this course has the requested ID or if the course does not exist. *
   * `FAILED_PRECONDITION` if the requested ID belongs to the primary teacher of
   * this course. (teachers.delete)
   *
   * @param string $courseId Identifier of the course. This identifier can be
   * either the Classroom-assigned identifier or an alias.
   * @param string $userId Identifier of the teacher to delete. The identifier can
   * be one of the following: * the numeric identifier for the user * the email
   * address of the user * the string literal `"me"`, indicating the requesting
   * user
   * @param array $optParams Optional parameters.
   * @return Google_Service_Classroom_Empty
   */
  public function delete($courseId, $userId, $optParams = array())
  {
    $params = array('courseId' => $courseId, 'userId' => $userId);
    $params = array_merge($params, $optParams);
    return $this->call('delete', array($params), "Google_Service_Classroom_Empty");
  }

  /**
   * Returns a teacher of a course. This method returns the following error codes:
   * * `PERMISSION_DENIED` if the requesting user is not permitted to view
   * teachers of this course or for access errors. * `NOT_FOUND` if no teacher of
   * this course has the requested ID or if the course does not exist.
   * (teachers.get)
   *
   * @param string $courseId Identifier of the course. This identifier can be
   * either the Classroom-assigned identifier or an alias.
   * @param string $userId Identifier of the teacher to return. The identifier can
   * be one of the following: * the numeric identifier for the user * the email
   * address of the user * the string literal `"me"`, indicating the requesting
   * user
   * @param array $optParams Optional parameters.
   * @return Google_Service_Classroom_Teacher
   */
  public function get($courseId, $userId, $optParams = array())
  {
    $params = array('courseId' => $courseId, 'userId' => $userId);
    $params = array_merge($params, $optParams);
    return $this->call('get', array($params), "Google_Service_Classroom_Teacher");
  }

  /**
   * Returns a list of teachers of this course that the requester is permitted to
   * view. This method returns the following error codes: * `NOT_FOUND` if the
   * course does not exist. * `PERMISSION_DENIED` for access errors.
   * (teachers.listCoursesTeachers)
   *
   * @param string $courseId Identifier of the course. This identifier can be
   * either the Classroom-assigned identifier or an alias.
   * @param array $optParams Optional parameters.
   *
   * @opt_param string pageToken nextPageToken value returned from a previous list
   * call, indicating that the subsequent page of results should be returned. The
   * list request must be otherwise identical to the one that resulted in this
   * token.
   * @opt_param int pageSize Maximum number of items to return. Zero means no
   * maximum. The server may return fewer than the specified number of results.
   * @return Google_Service_Classroom_ListTeachersResponse
   */
  public function listCoursesTeachers($courseId, $optParams = array())
  {
    $params = array('courseId' => $courseId);
    $params = array_merge($params, $optParams);
    return $this->call('list', array($params), "Google_Service_Classroom_ListTeachersResponse");
  }
}

/**
 * The "invitations" collection of methods.
 * Typical usage is:
 *  <code>
 *   $classroomService = new Google_Service_Classroom(...);
 *   $invitations = $classroomService->invitations;
 *  </code>
 */
class Google_Service_Classroom_Invitations_Resource extends Google_Service_Resource
{

  /**
   * Accepts an invitation, removing it and adding the invited user to the
   * teachers or students (as appropriate) of the specified course. Only the
   * invited user may accept an invitation. This method returns the following
   * error codes: * `PERMISSION_DENIED` if the requesting user is not permitted to
   * accept the requested invitation or for access errors. * `FAILED_PRECONDITION`
   * for the following request errors: * CourseMemberLimitReached *
   * CourseNotModifiable * CourseTeacherLimitReached *
   * UserGroupsMembershipLimitReached * `NOT_FOUND` if no invitation exists with
   * the requested ID. (invitations.accept)
   *
   * @param string $id Identifier of the invitation to accept.
   * @param array $optParams Optional parameters.
   * @return Google_Service_Classroom_Empty
   */
  public function accept($id, $optParams = array())
  {
    $params = array('id' => $id);
    $params = array_merge($params, $optParams);
    return $this->call('accept', array($params), "Google_Service_Classroom_Empty");
  }

  /**
   * Creates an invitation. Only one invitation for a user and course may exist at
   * a time. Delete and re-create an invitation to make changes. This method
   * returns the following error codes: * `PERMISSION_DENIED` if the requesting
   * user is not permitted to create invitations for this course or for access
   * errors. * `NOT_FOUND` if the course or the user does not exist. *
   * `FAILED_PRECONDITION` if the requested user's account is disabled or if the
   * user already has this role or a role with greater permissions. *
   * `ALREADY_EXISTS` if an invitation for the specified user and course already
   * exists. (invitations.create)
   *
   * @param Google_Invitation $postBody
   * @param array $optParams Optional parameters.
   * @return Google_Service_Classroom_Invitation
   */
  public function create(Google_Service_Classroom_Invitation $postBody, $optParams = array())
  {
    $params = array('postBody' => $postBody);
    $params = array_merge($params, $optParams);
    return $this->call('create', array($params), "Google_Service_Classroom_Invitation");
  }

  /**
   * Deletes an invitation. This method returns the following error codes: *
   * `PERMISSION_DENIED` if the requesting user is not permitted to delete the
   * requested invitation or for access errors. * `NOT_FOUND` if no invitation
   * exists with the requested ID. (invitations.delete)
   *
   * @param string $id Identifier of the invitation to delete.
   * @param array $optParams Optional parameters.
   * @return Google_Service_Classroom_Empty
   */
  public function delete($id, $optParams = array())
  {
    $params = array('id' => $id);
    $params = array_merge($params, $optParams);
    return $this->call('delete', array($params), "Google_Service_Classroom_Empty");
  }

  /**
   * Returns an invitation. This method returns the following error codes: *
   * `PERMISSION_DENIED` if the requesting user is not permitted to view the
   * requested invitation or for access errors. * `NOT_FOUND` if no invitation
   * exists with the requested ID. (invitations.get)
   *
   * @param string $id Identifier of the invitation to return.
   * @param array $optParams Optional parameters.
   * @return Google_Service_Classroom_Invitation
   */
  public function get($id, $optParams = array())
  {
    $params = array('id' => $id);
    $params = array_merge($params, $optParams);
    return $this->call('get', array($params), "Google_Service_Classroom_Invitation");
  }

  /**
   * Returns a list of invitations that the requesting user is permitted to view,
   * restricted to those that match the list request. *Note:* At least one of
   * `user_id` or `course_id` must be supplied. Both fields can be supplied. This
   * method returns the following error codes: * `PERMISSION_DENIED` for access
   * errors. (invitations.listInvitations)
   *
   * @param array $optParams Optional parameters.
   *
   * @opt_param string courseId Restricts returned invitations to those for a
   * course with the specified identifier.
   * @opt_param string pageToken nextPageToken value returned from a previous list
   * call, indicating that the subsequent page of results should be returned. The
   * list request must be otherwise identical to the one that resulted in this
   * token.
   * @opt_param string userId Restricts returned invitations to those for a
   * specific user. The identifier can be one of the following: * the numeric
   * identifier for the user * the email address of the user * the string literal
   * `"me"`, indicating the requesting user
   * @opt_param int pageSize Maximum number of items to return. Zero means no
   * maximum. The server may return fewer than the specified number of results.
   * @return Google_Service_Classroom_ListInvitationsResponse
   */
  public function listInvitations($optParams = array())
  {
    $params = array();
    $params = array_merge($params, $optParams);
    return $this->call('list', array($params), "Google_Service_Classroom_ListInvitationsResponse");
  }
}

/**
 * The "userProfiles" collection of methods.
 * Typical usage is:
 *  <code>
 *   $classroomService = new Google_Service_Classroom(...);
 *   $userProfiles = $classroomService->userProfiles;
 *  </code>
 */
class Google_Service_Classroom_UserProfiles_Resource extends Google_Service_Resource
{

  /**
   * Returns a user profile. This method returns the following error codes: *
   * `PERMISSION_DENIED` if the requesting user is not permitted to access this
   * user profile or if no profile exists with the requested ID or for access
   * errors. (userProfiles.get)
   *
   * @param string $userId Identifier of the profile to return. The identifier can
   * be one of the following: * the numeric identifier for the user * the email
   * address of the user * the string literal `"me"`, indicating the requesting
   * user
   * @param array $optParams Optional parameters.
   * @return Google_Service_Classroom_UserProfile
   */
  public function get($userId, $optParams = array())
  {
    $params = array('userId' => $userId);
    $params = array_merge($params, $optParams);
    return $this->call('get', array($params), "Google_Service_Classroom_UserProfile");
  }
}




class Google_Service_Classroom_Course extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  public $alternateLink;
  public $courseState;
  public $creationTime;
  public $description;
  public $descriptionHeading;
  public $enrollmentCode;
  public $id;
  public $name;
  public $ownerId;
  public $room;
  public $section;
  public $updateTime;


  public function setAlternateLink($alternateLink)
  {
    $this->alternateLink = $alternateLink;
  }
  public function getAlternateLink()
  {
    return $this->alternateLink;
  }
  public function setCourseState($courseState)
  {
    $this->courseState = $courseState;
  }
  public function getCourseState()
  {
    return $this->courseState;
  }
  public function setCreationTime($creationTime)
  {
    $this->creationTime = $creationTime;
  }
  public function getCreationTime()
  {
    return $this->creationTime;
  }
  public function setDescription($description)
  {
    $this->description = $description;
  }
  public function getDescription()
  {
    return $this->description;
  }
  public function setDescriptionHeading($descriptionHeading)
  {
    $this->descriptionHeading = $descriptionHeading;
  }
  public function getDescriptionHeading()
  {
    return $this->descriptionHeading;
  }
  public function setEnrollmentCode($enrollmentCode)
  {
    $this->enrollmentCode = $enrollmentCode;
  }
  public function getEnrollmentCode()
  {
    return $this->enrollmentCode;
  }
  public function setId($id)
  {
    $this->id = $id;
  }
  public function getId()
  {
    return $this->id;
  }
  public function setName($name)
  {
    $this->name = $name;
  }
  public function getName()
  {
    return $this->name;
  }
  public function setOwnerId($ownerId)
  {
    $this->ownerId = $ownerId;
  }
  public function getOwnerId()
  {
    return $this->ownerId;
  }
  public function setRoom($room)
  {
    $this->room = $room;
  }
  public function getRoom()
  {
    return $this->room;
  }
  public function setSection($section)
  {
    $this->section = $section;
  }
  public function getSection()
  {
    return $this->section;
  }
  public function setUpdateTime($updateTime)
  {
    $this->updateTime = $updateTime;
  }
  public function getUpdateTime()
  {
    return $this->updateTime;
  }
}

class Google_Service_Classroom_CourseAlias extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  public $alias;


  public function setAlias($alias)
  {
    $this->alias = $alias;
  }
  public function getAlias()
  {
    return $this->alias;
  }
}

class Google_Service_Classroom_Empty extends Google_Model
{
}

class Google_Service_Classroom_GlobalPermission extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  public $permission;


  public function setPermission($permission)
  {
    $this->permission = $permission;
  }
  public function getPermission()
  {
    return $this->permission;
  }
}

class Google_Service_Classroom_Invitation extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  public $courseId;
  public $id;
  public $role;
  public $userId;


  public function setCourseId($courseId)
  {
    $this->courseId = $courseId;
  }
  public function getCourseId()
  {
    return $this->courseId;
  }
  public function setId($id)
  {
    $this->id = $id;
  }
  public function getId()
  {
    return $this->id;
  }
  public function setRole($role)
  {
    $this->role = $role;
  }
  public function getRole()
  {
    return $this->role;
  }
  public function setUserId($userId)
  {
    $this->userId = $userId;
  }
  public function getUserId()
  {
    return $this->userId;
  }
}

class Google_Service_Classroom_ListCourseAliasesResponse extends Google_Collection
{
  protected $collection_key = 'aliases';
  protected $internal_gapi_mappings = array(
  );
  protected $aliasesType = 'Google_Service_Classroom_CourseAlias';
  protected $aliasesDataType = 'array';
  public $nextPageToken;


  public function setAliases($aliases)
  {
    $this->aliases = $aliases;
  }
  public function getAliases()
  {
    return $this->aliases;
  }
  public function setNextPageToken($nextPageToken)
  {
    $this->nextPageToken = $nextPageToken;
  }
  public function getNextPageToken()
  {
    return $this->nextPageToken;
  }
}

class Google_Service_Classroom_ListCoursesResponse extends Google_Collection
{
  protected $collection_key = 'courses';
  protected $internal_gapi_mappings = array(
  );
  protected $coursesType = 'Google_Service_Classroom_Course';
  protected $coursesDataType = 'array';
  public $nextPageToken;


  public function setCourses($courses)
  {
    $this->courses = $courses;
  }
  public function getCourses()
  {
    return $this->courses;
  }
  public function setNextPageToken($nextPageToken)
  {
    $this->nextPageToken = $nextPageToken;
  }
  public function getNextPageToken()
  {
    return $this->nextPageToken;
  }
}

class Google_Service_Classroom_ListInvitationsResponse extends Google_Collection
{
  protected $collection_key = 'invitations';
  protected $internal_gapi_mappings = array(
  );
  protected $invitationsType = 'Google_Service_Classroom_Invitation';
  protected $invitationsDataType = 'array';
  public $nextPageToken;


  public function setInvitations($invitations)
  {
    $this->invitations = $invitations;
  }
  public function getInvitations()
  {
    return $this->invitations;
  }
  public function setNextPageToken($nextPageToken)
  {
    $this->nextPageToken = $nextPageToken;
  }
  public function getNextPageToken()
  {
    return $this->nextPageToken;
  }
}

class Google_Service_Classroom_ListStudentsResponse extends Google_Collection
{
  protected $collection_key = 'students';
  protected $internal_gapi_mappings = array(
  );
  public $nextPageToken;
  protected $studentsType = 'Google_Service_Classroom_Student';
  protected $studentsDataType = 'array';


  public function setNextPageToken($nextPageToken)
  {
    $this->nextPageToken = $nextPageToken;
  }
  public function getNextPageToken()
  {
    return $this->nextPageToken;
  }
  public function setStudents($students)
  {
    $this->students = $students;
  }
  public function getStudents()
  {
    return $this->students;
  }
}

class Google_Service_Classroom_ListTeachersResponse extends Google_Collection
{
  protected $collection_key = 'teachers';
  protected $internal_gapi_mappings = array(
  );
  public $nextPageToken;
  protected $teachersType = 'Google_Service_Classroom_Teacher';
  protected $teachersDataType = 'array';


  public function setNextPageToken($nextPageToken)
  {
    $this->nextPageToken = $nextPageToken;
  }
  public function getNextPageToken()
  {
    return $this->nextPageToken;
  }
  public function setTeachers($teachers)
  {
    $this->teachers = $teachers;
  }
  public function getTeachers()
  {
    return $this->teachers;
  }
}

class Google_Service_Classroom_Name extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  public $familyName;
  public $fullName;
  public $givenName;


  public function setFamilyName($familyName)
  {
    $this->familyName = $familyName;
  }
  public function getFamilyName()
  {
    return $this->familyName;
  }
  public function setFullName($fullName)
  {
    $this->fullName = $fullName;
  }
  public function getFullName()
  {
    return $this->fullName;
  }
  public function setGivenName($givenName)
  {
    $this->givenName = $givenName;
  }
  public function getGivenName()
  {
    return $this->givenName;
  }
}

class Google_Service_Classroom_Student extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  public $courseId;
  protected $profileType = 'Google_Service_Classroom_UserProfile';
  protected $profileDataType = '';
  public $userId;


  public function setCourseId($courseId)
  {
    $this->courseId = $courseId;
  }
  public function getCourseId()
  {
    return $this->courseId;
  }
  public function setProfile(Google_Service_Classroom_UserProfile $profile)
  {
    $this->profile = $profile;
  }
  public function getProfile()
  {
    return $this->profile;
  }
  public function setUserId($userId)
  {
    $this->userId = $userId;
  }
  public function getUserId()
  {
    return $this->userId;
  }
}

class Google_Service_Classroom_Teacher extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  public $courseId;
  protected $profileType = 'Google_Service_Classroom_UserProfile';
  protected $profileDataType = '';
  public $userId;


  public function setCourseId($courseId)
  {
    $this->courseId = $courseId;
  }
  public function getCourseId()
  {
    return $this->courseId;
  }
  public function setProfile(Google_Service_Classroom_UserProfile $profile)
  {
    $this->profile = $profile;
  }
  public function getProfile()
  {
    return $this->profile;
  }
  public function setUserId($userId)
  {
    $this->userId = $userId;
  }
  public function getUserId()
  {
    return $this->userId;
  }
}

class Google_Service_Classroom_UserProfile extends Google_Collection
{
  protected $collection_key = 'permissions';
  protected $internal_gapi_mappings = array(
  );
  public $emailAddress;
  public $id;
  protected $nameType = 'Google_Service_Classroom_Name';
  protected $nameDataType = '';
  protected $permissionsType = 'Google_Service_Classroom_GlobalPermission';
  protected $permissionsDataType = 'array';
  public $photoUrl;


  public function setEmailAddress($emailAddress)
  {
    $this->emailAddress = $emailAddress;
  }
  public function getEmailAddress()
  {
    return $this->emailAddress;
  }
  public function setId($id)
  {
    $this->id = $id;
  }
  public function getId()
  {
    return $this->id;
  }
  public function setName(Google_Service_Classroom_Name $name)
  {
    $this->name = $name;
  }
  public function getName()
  {
    return $this->name;
  }
  public function setPermissions($permissions)
  {
    $this->permissions = $permissions;
  }
  public function getPermissions()
  {
    return $this->permissions;
  }
  public function setPhotoUrl($photoUrl)
  {
    $this->photoUrl = $photoUrl;
  }
  public function getPhotoUrl()
  {
    return $this->photoUrl;
  }
}
