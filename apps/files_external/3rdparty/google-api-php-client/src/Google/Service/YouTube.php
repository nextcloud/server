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
 * Service definition for YouTube (v3).
 *
 * <p>
 * Programmatic access to YouTube features.</p>
 *
 * <p>
 * For more information about this service, see the API
 * <a href="https://developers.google.com/youtube/v3" target="_blank">Documentation</a>
 * </p>
 *
 * @author Google, Inc.
 */
class Google_Service_YouTube extends Google_Service
{
  /** Manage your YouTube account. */
  const YOUTUBE =
      "https://www.googleapis.com/auth/youtube";
  /** Manage your YouTube account. */
  const YOUTUBE_FORCE_SSL =
      "https://www.googleapis.com/auth/youtube.force-ssl";
  /** View your YouTube account. */
  const YOUTUBE_READONLY =
      "https://www.googleapis.com/auth/youtube.readonly";
  /** Manage your YouTube videos. */
  const YOUTUBE_UPLOAD =
      "https://www.googleapis.com/auth/youtube.upload";
  /** View and manage your assets and associated content on YouTube. */
  const YOUTUBEPARTNER =
      "https://www.googleapis.com/auth/youtubepartner";
  /** View private information of your YouTube channel relevant during the audit process with a YouTube partner. */
  const YOUTUBEPARTNER_CHANNEL_AUDIT =
      "https://www.googleapis.com/auth/youtubepartner-channel-audit";

  public $activities;
  public $captions;
  public $channelBanners;
  public $channelSections;
  public $channels;
  public $commentThreads;
  public $comments;
  public $guideCategories;
  public $i18nLanguages;
  public $i18nRegions;
  public $liveBroadcasts;
  public $liveStreams;
  public $playlistItems;
  public $playlists;
  public $search;
  public $subscriptions;
  public $thumbnails;
  public $videoAbuseReportReasons;
  public $videoCategories;
  public $videos;
  public $watermarks;
  

  /**
   * Constructs the internal representation of the YouTube service.
   *
   * @param Google_Client $client
   */
  public function __construct(Google_Client $client)
  {
    parent::__construct($client);
    $this->rootUrl = 'https://www.googleapis.com/';
    $this->servicePath = 'youtube/v3/';
    $this->version = 'v3';
    $this->serviceName = 'youtube';

    $this->activities = new Google_Service_YouTube_Activities_Resource(
        $this,
        $this->serviceName,
        'activities',
        array(
          'methods' => array(
            'insert' => array(
              'path' => 'activities',
              'httpMethod' => 'POST',
              'parameters' => array(
                'part' => array(
                  'location' => 'query',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),'list' => array(
              'path' => 'activities',
              'httpMethod' => 'GET',
              'parameters' => array(
                'part' => array(
                  'location' => 'query',
                  'type' => 'string',
                  'required' => true,
                ),
                'regionCode' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'publishedBefore' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'channelId' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'mine' => array(
                  'location' => 'query',
                  'type' => 'boolean',
                ),
                'maxResults' => array(
                  'location' => 'query',
                  'type' => 'integer',
                ),
                'pageToken' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'home' => array(
                  'location' => 'query',
                  'type' => 'boolean',
                ),
                'publishedAfter' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
              ),
            ),
          )
        )
    );
    $this->captions = new Google_Service_YouTube_Captions_Resource(
        $this,
        $this->serviceName,
        'captions',
        array(
          'methods' => array(
            'delete' => array(
              'path' => 'captions',
              'httpMethod' => 'DELETE',
              'parameters' => array(
                'id' => array(
                  'location' => 'query',
                  'type' => 'string',
                  'required' => true,
                ),
                'onBehalfOf' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'onBehalfOfContentOwner' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
              ),
            ),'download' => array(
              'path' => 'captions/{id}',
              'httpMethod' => 'GET',
              'parameters' => array(
                'id' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'tfmt' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'onBehalfOf' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'tlang' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'onBehalfOfContentOwner' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
              ),
            ),'insert' => array(
              'path' => 'captions',
              'httpMethod' => 'POST',
              'parameters' => array(
                'part' => array(
                  'location' => 'query',
                  'type' => 'string',
                  'required' => true,
                ),
                'onBehalfOf' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'onBehalfOfContentOwner' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'sync' => array(
                  'location' => 'query',
                  'type' => 'boolean',
                ),
              ),
            ),'list' => array(
              'path' => 'captions',
              'httpMethod' => 'GET',
              'parameters' => array(
                'part' => array(
                  'location' => 'query',
                  'type' => 'string',
                  'required' => true,
                ),
                'videoId' => array(
                  'location' => 'query',
                  'type' => 'string',
                  'required' => true,
                ),
                'onBehalfOf' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'id' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'onBehalfOfContentOwner' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
              ),
            ),'update' => array(
              'path' => 'captions',
              'httpMethod' => 'PUT',
              'parameters' => array(
                'part' => array(
                  'location' => 'query',
                  'type' => 'string',
                  'required' => true,
                ),
                'onBehalfOf' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'onBehalfOfContentOwner' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'sync' => array(
                  'location' => 'query',
                  'type' => 'boolean',
                ),
              ),
            ),
          )
        )
    );
    $this->channelBanners = new Google_Service_YouTube_ChannelBanners_Resource(
        $this,
        $this->serviceName,
        'channelBanners',
        array(
          'methods' => array(
            'insert' => array(
              'path' => 'channelBanners/insert',
              'httpMethod' => 'POST',
              'parameters' => array(
                'onBehalfOfContentOwner' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
              ),
            ),
          )
        )
    );
    $this->channelSections = new Google_Service_YouTube_ChannelSections_Resource(
        $this,
        $this->serviceName,
        'channelSections',
        array(
          'methods' => array(
            'delete' => array(
              'path' => 'channelSections',
              'httpMethod' => 'DELETE',
              'parameters' => array(
                'id' => array(
                  'location' => 'query',
                  'type' => 'string',
                  'required' => true,
                ),
                'onBehalfOfContentOwner' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
              ),
            ),'insert' => array(
              'path' => 'channelSections',
              'httpMethod' => 'POST',
              'parameters' => array(
                'part' => array(
                  'location' => 'query',
                  'type' => 'string',
                  'required' => true,
                ),
                'onBehalfOfContentOwnerChannel' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'onBehalfOfContentOwner' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
              ),
            ),'list' => array(
              'path' => 'channelSections',
              'httpMethod' => 'GET',
              'parameters' => array(
                'part' => array(
                  'location' => 'query',
                  'type' => 'string',
                  'required' => true,
                ),
                'onBehalfOfContentOwner' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'channelId' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'mine' => array(
                  'location' => 'query',
                  'type' => 'boolean',
                ),
                'hl' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'id' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
              ),
            ),'update' => array(
              'path' => 'channelSections',
              'httpMethod' => 'PUT',
              'parameters' => array(
                'part' => array(
                  'location' => 'query',
                  'type' => 'string',
                  'required' => true,
                ),
                'onBehalfOfContentOwner' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
              ),
            ),
          )
        )
    );
    $this->channels = new Google_Service_YouTube_Channels_Resource(
        $this,
        $this->serviceName,
        'channels',
        array(
          'methods' => array(
            'list' => array(
              'path' => 'channels',
              'httpMethod' => 'GET',
              'parameters' => array(
                'part' => array(
                  'location' => 'query',
                  'type' => 'string',
                  'required' => true,
                ),
                'managedByMe' => array(
                  'location' => 'query',
                  'type' => 'boolean',
                ),
                'onBehalfOfContentOwner' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'forUsername' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'mine' => array(
                  'location' => 'query',
                  'type' => 'boolean',
                ),
                'maxResults' => array(
                  'location' => 'query',
                  'type' => 'integer',
                ),
                'id' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'pageToken' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'mySubscribers' => array(
                  'location' => 'query',
                  'type' => 'boolean',
                ),
                'hl' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'categoryId' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
              ),
            ),'update' => array(
              'path' => 'channels',
              'httpMethod' => 'PUT',
              'parameters' => array(
                'part' => array(
                  'location' => 'query',
                  'type' => 'string',
                  'required' => true,
                ),
                'onBehalfOfContentOwner' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
              ),
            ),
          )
        )
    );
    $this->commentThreads = new Google_Service_YouTube_CommentThreads_Resource(
        $this,
        $this->serviceName,
        'commentThreads',
        array(
          'methods' => array(
            'insert' => array(
              'path' => 'commentThreads',
              'httpMethod' => 'POST',
              'parameters' => array(
                'part' => array(
                  'location' => 'query',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),'list' => array(
              'path' => 'commentThreads',
              'httpMethod' => 'GET',
              'parameters' => array(
                'part' => array(
                  'location' => 'query',
                  'type' => 'string',
                  'required' => true,
                ),
                'searchTerms' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'allThreadsRelatedToChannelId' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'channelId' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'videoId' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'maxResults' => array(
                  'location' => 'query',
                  'type' => 'integer',
                ),
                'id' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'pageToken' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'moderationStatus' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'textFormat' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'order' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
              ),
            ),'update' => array(
              'path' => 'commentThreads',
              'httpMethod' => 'PUT',
              'parameters' => array(
                'part' => array(
                  'location' => 'query',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),
          )
        )
    );
    $this->comments = new Google_Service_YouTube_Comments_Resource(
        $this,
        $this->serviceName,
        'comments',
        array(
          'methods' => array(
            'delete' => array(
              'path' => 'comments',
              'httpMethod' => 'DELETE',
              'parameters' => array(
                'id' => array(
                  'location' => 'query',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),'insert' => array(
              'path' => 'comments',
              'httpMethod' => 'POST',
              'parameters' => array(
                'part' => array(
                  'location' => 'query',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),'list' => array(
              'path' => 'comments',
              'httpMethod' => 'GET',
              'parameters' => array(
                'part' => array(
                  'location' => 'query',
                  'type' => 'string',
                  'required' => true,
                ),
                'maxResults' => array(
                  'location' => 'query',
                  'type' => 'integer',
                ),
                'pageToken' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'parentId' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'textFormat' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'id' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
              ),
            ),'markAsSpam' => array(
              'path' => 'comments/markAsSpam',
              'httpMethod' => 'POST',
              'parameters' => array(
                'id' => array(
                  'location' => 'query',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),'setModerationStatus' => array(
              'path' => 'comments/setModerationStatus',
              'httpMethod' => 'POST',
              'parameters' => array(
                'id' => array(
                  'location' => 'query',
                  'type' => 'string',
                  'required' => true,
                ),
                'moderationStatus' => array(
                  'location' => 'query',
                  'type' => 'string',
                  'required' => true,
                ),
                'banAuthor' => array(
                  'location' => 'query',
                  'type' => 'boolean',
                ),
              ),
            ),'update' => array(
              'path' => 'comments',
              'httpMethod' => 'PUT',
              'parameters' => array(
                'part' => array(
                  'location' => 'query',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),
          )
        )
    );
    $this->guideCategories = new Google_Service_YouTube_GuideCategories_Resource(
        $this,
        $this->serviceName,
        'guideCategories',
        array(
          'methods' => array(
            'list' => array(
              'path' => 'guideCategories',
              'httpMethod' => 'GET',
              'parameters' => array(
                'part' => array(
                  'location' => 'query',
                  'type' => 'string',
                  'required' => true,
                ),
                'regionCode' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'id' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'hl' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
              ),
            ),
          )
        )
    );
    $this->i18nLanguages = new Google_Service_YouTube_I18nLanguages_Resource(
        $this,
        $this->serviceName,
        'i18nLanguages',
        array(
          'methods' => array(
            'list' => array(
              'path' => 'i18nLanguages',
              'httpMethod' => 'GET',
              'parameters' => array(
                'part' => array(
                  'location' => 'query',
                  'type' => 'string',
                  'required' => true,
                ),
                'hl' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
              ),
            ),
          )
        )
    );
    $this->i18nRegions = new Google_Service_YouTube_I18nRegions_Resource(
        $this,
        $this->serviceName,
        'i18nRegions',
        array(
          'methods' => array(
            'list' => array(
              'path' => 'i18nRegions',
              'httpMethod' => 'GET',
              'parameters' => array(
                'part' => array(
                  'location' => 'query',
                  'type' => 'string',
                  'required' => true,
                ),
                'hl' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
              ),
            ),
          )
        )
    );
    $this->liveBroadcasts = new Google_Service_YouTube_LiveBroadcasts_Resource(
        $this,
        $this->serviceName,
        'liveBroadcasts',
        array(
          'methods' => array(
            'bind' => array(
              'path' => 'liveBroadcasts/bind',
              'httpMethod' => 'POST',
              'parameters' => array(
                'id' => array(
                  'location' => 'query',
                  'type' => 'string',
                  'required' => true,
                ),
                'part' => array(
                  'location' => 'query',
                  'type' => 'string',
                  'required' => true,
                ),
                'onBehalfOfContentOwnerChannel' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'onBehalfOfContentOwner' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'streamId' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
              ),
            ),'bind_direct' => array(
              'path' => 'liveBroadcasts/bind/direct',
              'httpMethod' => 'POST',
              'parameters' => array(
                'id' => array(
                  'location' => 'query',
                  'type' => 'string',
                  'required' => true,
                ),
                'part' => array(
                  'location' => 'query',
                  'type' => 'string',
                  'required' => true,
                ),
                'onBehalfOfContentOwnerChannel' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'onBehalfOfContentOwner' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'streamId' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
              ),
            ),'control' => array(
              'path' => 'liveBroadcasts/control',
              'httpMethod' => 'POST',
              'parameters' => array(
                'id' => array(
                  'location' => 'query',
                  'type' => 'string',
                  'required' => true,
                ),
                'part' => array(
                  'location' => 'query',
                  'type' => 'string',
                  'required' => true,
                ),
                'onBehalfOfContentOwner' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'displaySlate' => array(
                  'location' => 'query',
                  'type' => 'boolean',
                ),
                'onBehalfOfContentOwnerChannel' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'offsetTimeMs' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'walltime' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
              ),
            ),'delete' => array(
              'path' => 'liveBroadcasts',
              'httpMethod' => 'DELETE',
              'parameters' => array(
                'id' => array(
                  'location' => 'query',
                  'type' => 'string',
                  'required' => true,
                ),
                'onBehalfOfContentOwnerChannel' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'onBehalfOfContentOwner' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
              ),
            ),'insert' => array(
              'path' => 'liveBroadcasts',
              'httpMethod' => 'POST',
              'parameters' => array(
                'part' => array(
                  'location' => 'query',
                  'type' => 'string',
                  'required' => true,
                ),
                'onBehalfOfContentOwnerChannel' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'onBehalfOfContentOwner' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
              ),
            ),'list' => array(
              'path' => 'liveBroadcasts',
              'httpMethod' => 'GET',
              'parameters' => array(
                'part' => array(
                  'location' => 'query',
                  'type' => 'string',
                  'required' => true,
                ),
                'broadcastStatus' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'onBehalfOfContentOwner' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'onBehalfOfContentOwnerChannel' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'mine' => array(
                  'location' => 'query',
                  'type' => 'boolean',
                ),
                'maxResults' => array(
                  'location' => 'query',
                  'type' => 'integer',
                ),
                'pageToken' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'id' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
              ),
            ),'transition' => array(
              'path' => 'liveBroadcasts/transition',
              'httpMethod' => 'POST',
              'parameters' => array(
                'broadcastStatus' => array(
                  'location' => 'query',
                  'type' => 'string',
                  'required' => true,
                ),
                'id' => array(
                  'location' => 'query',
                  'type' => 'string',
                  'required' => true,
                ),
                'part' => array(
                  'location' => 'query',
                  'type' => 'string',
                  'required' => true,
                ),
                'onBehalfOfContentOwnerChannel' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'onBehalfOfContentOwner' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
              ),
            ),'update' => array(
              'path' => 'liveBroadcasts',
              'httpMethod' => 'PUT',
              'parameters' => array(
                'part' => array(
                  'location' => 'query',
                  'type' => 'string',
                  'required' => true,
                ),
                'onBehalfOfContentOwnerChannel' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'onBehalfOfContentOwner' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
              ),
            ),
          )
        )
    );
    $this->liveStreams = new Google_Service_YouTube_LiveStreams_Resource(
        $this,
        $this->serviceName,
        'liveStreams',
        array(
          'methods' => array(
            'delete' => array(
              'path' => 'liveStreams',
              'httpMethod' => 'DELETE',
              'parameters' => array(
                'id' => array(
                  'location' => 'query',
                  'type' => 'string',
                  'required' => true,
                ),
                'onBehalfOfContentOwnerChannel' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'onBehalfOfContentOwner' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
              ),
            ),'insert' => array(
              'path' => 'liveStreams',
              'httpMethod' => 'POST',
              'parameters' => array(
                'part' => array(
                  'location' => 'query',
                  'type' => 'string',
                  'required' => true,
                ),
                'onBehalfOfContentOwnerChannel' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'onBehalfOfContentOwner' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
              ),
            ),'list' => array(
              'path' => 'liveStreams',
              'httpMethod' => 'GET',
              'parameters' => array(
                'part' => array(
                  'location' => 'query',
                  'type' => 'string',
                  'required' => true,
                ),
                'onBehalfOfContentOwner' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'onBehalfOfContentOwnerChannel' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'mine' => array(
                  'location' => 'query',
                  'type' => 'boolean',
                ),
                'maxResults' => array(
                  'location' => 'query',
                  'type' => 'integer',
                ),
                'pageToken' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'id' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
              ),
            ),'update' => array(
              'path' => 'liveStreams',
              'httpMethod' => 'PUT',
              'parameters' => array(
                'part' => array(
                  'location' => 'query',
                  'type' => 'string',
                  'required' => true,
                ),
                'onBehalfOfContentOwnerChannel' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'onBehalfOfContentOwner' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
              ),
            ),
          )
        )
    );
    $this->playlistItems = new Google_Service_YouTube_PlaylistItems_Resource(
        $this,
        $this->serviceName,
        'playlistItems',
        array(
          'methods' => array(
            'delete' => array(
              'path' => 'playlistItems',
              'httpMethod' => 'DELETE',
              'parameters' => array(
                'id' => array(
                  'location' => 'query',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),'insert' => array(
              'path' => 'playlistItems',
              'httpMethod' => 'POST',
              'parameters' => array(
                'part' => array(
                  'location' => 'query',
                  'type' => 'string',
                  'required' => true,
                ),
                'onBehalfOfContentOwner' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
              ),
            ),'list' => array(
              'path' => 'playlistItems',
              'httpMethod' => 'GET',
              'parameters' => array(
                'part' => array(
                  'location' => 'query',
                  'type' => 'string',
                  'required' => true,
                ),
                'onBehalfOfContentOwner' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'playlistId' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'videoId' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'maxResults' => array(
                  'location' => 'query',
                  'type' => 'integer',
                ),
                'pageToken' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'id' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
              ),
            ),'update' => array(
              'path' => 'playlistItems',
              'httpMethod' => 'PUT',
              'parameters' => array(
                'part' => array(
                  'location' => 'query',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),
          )
        )
    );
    $this->playlists = new Google_Service_YouTube_Playlists_Resource(
        $this,
        $this->serviceName,
        'playlists',
        array(
          'methods' => array(
            'delete' => array(
              'path' => 'playlists',
              'httpMethod' => 'DELETE',
              'parameters' => array(
                'id' => array(
                  'location' => 'query',
                  'type' => 'string',
                  'required' => true,
                ),
                'onBehalfOfContentOwner' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
              ),
            ),'insert' => array(
              'path' => 'playlists',
              'httpMethod' => 'POST',
              'parameters' => array(
                'part' => array(
                  'location' => 'query',
                  'type' => 'string',
                  'required' => true,
                ),
                'onBehalfOfContentOwnerChannel' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'onBehalfOfContentOwner' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
              ),
            ),'list' => array(
              'path' => 'playlists',
              'httpMethod' => 'GET',
              'parameters' => array(
                'part' => array(
                  'location' => 'query',
                  'type' => 'string',
                  'required' => true,
                ),
                'onBehalfOfContentOwner' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'onBehalfOfContentOwnerChannel' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'channelId' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'mine' => array(
                  'location' => 'query',
                  'type' => 'boolean',
                ),
                'maxResults' => array(
                  'location' => 'query',
                  'type' => 'integer',
                ),
                'pageToken' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'hl' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'id' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
              ),
            ),'update' => array(
              'path' => 'playlists',
              'httpMethod' => 'PUT',
              'parameters' => array(
                'part' => array(
                  'location' => 'query',
                  'type' => 'string',
                  'required' => true,
                ),
                'onBehalfOfContentOwner' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
              ),
            ),
          )
        )
    );
    $this->search = new Google_Service_YouTube_Search_Resource(
        $this,
        $this->serviceName,
        'search',
        array(
          'methods' => array(
            'list' => array(
              'path' => 'search',
              'httpMethod' => 'GET',
              'parameters' => array(
                'part' => array(
                  'location' => 'query',
                  'type' => 'string',
                  'required' => true,
                ),
                'eventType' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'channelId' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'forDeveloper' => array(
                  'location' => 'query',
                  'type' => 'boolean',
                ),
                'videoSyndicated' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'channelType' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'videoCaption' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'publishedAfter' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'onBehalfOfContentOwner' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'pageToken' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'forContentOwner' => array(
                  'location' => 'query',
                  'type' => 'boolean',
                ),
                'regionCode' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'location' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'locationRadius' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'videoType' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'type' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'topicId' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'publishedBefore' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'videoDimension' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'videoLicense' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'maxResults' => array(
                  'location' => 'query',
                  'type' => 'integer',
                ),
                'relatedToVideoId' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'videoDefinition' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'videoDuration' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'relevanceLanguage' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'forMine' => array(
                  'location' => 'query',
                  'type' => 'boolean',
                ),
                'q' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'safeSearch' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'videoEmbeddable' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'videoCategoryId' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'order' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
              ),
            ),
          )
        )
    );
    $this->subscriptions = new Google_Service_YouTube_Subscriptions_Resource(
        $this,
        $this->serviceName,
        'subscriptions',
        array(
          'methods' => array(
            'delete' => array(
              'path' => 'subscriptions',
              'httpMethod' => 'DELETE',
              'parameters' => array(
                'id' => array(
                  'location' => 'query',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),'insert' => array(
              'path' => 'subscriptions',
              'httpMethod' => 'POST',
              'parameters' => array(
                'part' => array(
                  'location' => 'query',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),'list' => array(
              'path' => 'subscriptions',
              'httpMethod' => 'GET',
              'parameters' => array(
                'part' => array(
                  'location' => 'query',
                  'type' => 'string',
                  'required' => true,
                ),
                'onBehalfOfContentOwner' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'onBehalfOfContentOwnerChannel' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'channelId' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'mine' => array(
                  'location' => 'query',
                  'type' => 'boolean',
                ),
                'maxResults' => array(
                  'location' => 'query',
                  'type' => 'integer',
                ),
                'forChannelId' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'pageToken' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'mySubscribers' => array(
                  'location' => 'query',
                  'type' => 'boolean',
                ),
                'order' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'id' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
              ),
            ),
          )
        )
    );
    $this->thumbnails = new Google_Service_YouTube_Thumbnails_Resource(
        $this,
        $this->serviceName,
        'thumbnails',
        array(
          'methods' => array(
            'set' => array(
              'path' => 'thumbnails/set',
              'httpMethod' => 'POST',
              'parameters' => array(
                'videoId' => array(
                  'location' => 'query',
                  'type' => 'string',
                  'required' => true,
                ),
                'onBehalfOfContentOwner' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
              ),
            ),
          )
        )
    );
    $this->videoAbuseReportReasons = new Google_Service_YouTube_VideoAbuseReportReasons_Resource(
        $this,
        $this->serviceName,
        'videoAbuseReportReasons',
        array(
          'methods' => array(
            'list' => array(
              'path' => 'videoAbuseReportReasons',
              'httpMethod' => 'GET',
              'parameters' => array(
                'part' => array(
                  'location' => 'query',
                  'type' => 'string',
                  'required' => true,
                ),
                'hl' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
              ),
            ),
          )
        )
    );
    $this->videoCategories = new Google_Service_YouTube_VideoCategories_Resource(
        $this,
        $this->serviceName,
        'videoCategories',
        array(
          'methods' => array(
            'list' => array(
              'path' => 'videoCategories',
              'httpMethod' => 'GET',
              'parameters' => array(
                'part' => array(
                  'location' => 'query',
                  'type' => 'string',
                  'required' => true,
                ),
                'regionCode' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'id' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'hl' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
              ),
            ),
          )
        )
    );
    $this->videos = new Google_Service_YouTube_Videos_Resource(
        $this,
        $this->serviceName,
        'videos',
        array(
          'methods' => array(
            'delete' => array(
              'path' => 'videos',
              'httpMethod' => 'DELETE',
              'parameters' => array(
                'id' => array(
                  'location' => 'query',
                  'type' => 'string',
                  'required' => true,
                ),
                'onBehalfOfContentOwner' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
              ),
            ),'getRating' => array(
              'path' => 'videos/getRating',
              'httpMethod' => 'GET',
              'parameters' => array(
                'id' => array(
                  'location' => 'query',
                  'type' => 'string',
                  'required' => true,
                ),
                'onBehalfOfContentOwner' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
              ),
            ),'insert' => array(
              'path' => 'videos',
              'httpMethod' => 'POST',
              'parameters' => array(
                'part' => array(
                  'location' => 'query',
                  'type' => 'string',
                  'required' => true,
                ),
                'onBehalfOfContentOwner' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'stabilize' => array(
                  'location' => 'query',
                  'type' => 'boolean',
                ),
                'onBehalfOfContentOwnerChannel' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'notifySubscribers' => array(
                  'location' => 'query',
                  'type' => 'boolean',
                ),
                'autoLevels' => array(
                  'location' => 'query',
                  'type' => 'boolean',
                ),
              ),
            ),'list' => array(
              'path' => 'videos',
              'httpMethod' => 'GET',
              'parameters' => array(
                'part' => array(
                  'location' => 'query',
                  'type' => 'string',
                  'required' => true,
                ),
                'onBehalfOfContentOwner' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'regionCode' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'locale' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'videoCategoryId' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'chart' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'maxResults' => array(
                  'location' => 'query',
                  'type' => 'integer',
                ),
                'pageToken' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'hl' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'myRating' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'id' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
              ),
            ),'rate' => array(
              'path' => 'videos/rate',
              'httpMethod' => 'POST',
              'parameters' => array(
                'id' => array(
                  'location' => 'query',
                  'type' => 'string',
                  'required' => true,
                ),
                'rating' => array(
                  'location' => 'query',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),'reportAbuse' => array(
              'path' => 'videos/reportAbuse',
              'httpMethod' => 'POST',
              'parameters' => array(
                'onBehalfOfContentOwner' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
              ),
            ),'update' => array(
              'path' => 'videos',
              'httpMethod' => 'PUT',
              'parameters' => array(
                'part' => array(
                  'location' => 'query',
                  'type' => 'string',
                  'required' => true,
                ),
                'onBehalfOfContentOwner' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
              ),
            ),
          )
        )
    );
    $this->watermarks = new Google_Service_YouTube_Watermarks_Resource(
        $this,
        $this->serviceName,
        'watermarks',
        array(
          'methods' => array(
            'set' => array(
              'path' => 'watermarks/set',
              'httpMethod' => 'POST',
              'parameters' => array(
                'channelId' => array(
                  'location' => 'query',
                  'type' => 'string',
                  'required' => true,
                ),
                'onBehalfOfContentOwner' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
              ),
            ),'unset' => array(
              'path' => 'watermarks/unset',
              'httpMethod' => 'POST',
              'parameters' => array(
                'channelId' => array(
                  'location' => 'query',
                  'type' => 'string',
                  'required' => true,
                ),
                'onBehalfOfContentOwner' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
              ),
            ),
          )
        )
    );
  }
}


/**
 * The "activities" collection of methods.
 * Typical usage is:
 *  <code>
 *   $youtubeService = new Google_Service_YouTube(...);
 *   $activities = $youtubeService->activities;
 *  </code>
 */
class Google_Service_YouTube_Activities_Resource extends Google_Service_Resource
{

  /**
   * Posts a bulletin for a specific channel. (The user submitting the request
   * must be authorized to act on the channel's behalf.)
   *
   * Note: Even though an activity resource can contain information about actions
   * like a user rating a video or marking a video as a favorite, you need to use
   * other API methods to generate those activity resources. For example, you
   * would use the API's videos.rate() method to rate a video and the
   * playlistItems.insert() method to mark a video as a favorite.
   * (activities.insert)
   *
   * @param string $part The part parameter serves two purposes in this operation.
   * It identifies the properties that the write operation will set as well as the
   * properties that the API response will include.
   * @param Google_Activity $postBody
   * @param array $optParams Optional parameters.
   * @return Google_Service_YouTube_Activity
   */
  public function insert($part, Google_Service_YouTube_Activity $postBody, $optParams = array())
  {
    $params = array('part' => $part, 'postBody' => $postBody);
    $params = array_merge($params, $optParams);
    return $this->call('insert', array($params), "Google_Service_YouTube_Activity");
  }

  /**
   * Returns a list of channel activity events that match the request criteria.
   * For example, you can retrieve events associated with a particular channel,
   * events associated with the user's subscriptions and Google+ friends, or the
   * YouTube home page feed, which is customized for each user.
   * (activities.listActivities)
   *
   * @param string $part The part parameter specifies a comma-separated list of
   * one or more activity resource properties that the API response will include.
   *
   * If the parameter identifies a property that contains child properties, the
   * child properties will be included in the response. For example, in an
   * activity resource, the snippet property contains other properties that
   * identify the type of activity, a display title for the activity, and so
   * forth. If you set part=snippet, the API response will also contain all of
   * those nested properties.
   * @param array $optParams Optional parameters.
   *
   * @opt_param string regionCode The regionCode parameter instructs the API to
   * return results for the specified country. The parameter value is an ISO
   * 3166-1 alpha-2 country code. YouTube uses this value when the authorized
   * user's previous activity on YouTube does not provide enough information to
   * generate the activity feed.
   * @opt_param string publishedBefore The publishedBefore parameter specifies the
   * date and time before which an activity must have occurred for that activity
   * to be included in the API response. If the parameter value specifies a day,
   * but not a time, then any activities that occurred that day will be excluded
   * from the result set. The value is specified in ISO 8601 (YYYY-MM-
   * DDThh:mm:ss.sZ) format.
   * @opt_param string channelId The channelId parameter specifies a unique
   * YouTube channel ID. The API will then return a list of that channel's
   * activities.
   * @opt_param bool mine Set this parameter's value to true to retrieve a feed of
   * the authenticated user's activities.
   * @opt_param string maxResults The maxResults parameter specifies the maximum
   * number of items that should be returned in the result set.
   * @opt_param string pageToken The pageToken parameter identifies a specific
   * page in the result set that should be returned. In an API response, the
   * nextPageToken and prevPageToken properties identify other pages that could be
   * retrieved.
   * @opt_param bool home Set this parameter's value to true to retrieve the
   * activity feed that displays on the YouTube home page for the currently
   * authenticated user.
   * @opt_param string publishedAfter The publishedAfter parameter specifies the
   * earliest date and time that an activity could have occurred for that activity
   * to be included in the API response. If the parameter value specifies a day,
   * but not a time, then any activities that occurred that day will be included
   * in the result set. The value is specified in ISO 8601 (YYYY-MM-
   * DDThh:mm:ss.sZ) format.
   * @return Google_Service_YouTube_ActivityListResponse
   */
  public function listActivities($part, $optParams = array())
  {
    $params = array('part' => $part);
    $params = array_merge($params, $optParams);
    return $this->call('list', array($params), "Google_Service_YouTube_ActivityListResponse");
  }
}

/**
 * The "captions" collection of methods.
 * Typical usage is:
 *  <code>
 *   $youtubeService = new Google_Service_YouTube(...);
 *   $captions = $youtubeService->captions;
 *  </code>
 */
class Google_Service_YouTube_Captions_Resource extends Google_Service_Resource
{

  /**
   * Deletes a specified caption track. (captions.delete)
   *
   * @param string $id The id parameter identifies the caption track that is being
   * deleted. The value is a caption track ID as identified by the id property in
   * a caption resource.
   * @param array $optParams Optional parameters.
   *
   * @opt_param string onBehalfOf ID of the Google+ Page for the channel that the
   * request is be on behalf of
   * @opt_param string onBehalfOfContentOwner Note: This parameter is intended
   * exclusively for YouTube content partners.
   *
   * The onBehalfOfContentOwner parameter indicates that the request's
   * authorization credentials identify a YouTube CMS user who is acting on behalf
   * of the content owner specified in the parameter value. This parameter is
   * intended for YouTube content partners that own and manage many different
   * YouTube channels. It allows content owners to authenticate once and get
   * access to all their video and channel data, without having to provide
   * authentication credentials for each individual channel. The actual CMS
   * account that the user authenticates with must be linked to the specified
   * YouTube content owner.
   */
  public function delete($id, $optParams = array())
  {
    $params = array('id' => $id);
    $params = array_merge($params, $optParams);
    return $this->call('delete', array($params));
  }

  /**
   * Downloads a caption track. The caption track is returned in its original
   * format unless the request specifies a value for the tfmt parameter and in its
   * original language unless the request specifies a value for the tlang
   * parameter. (captions.download)
   *
   * @param string $id The id parameter identifies the caption track that is being
   * retrieved. The value is a caption track ID as identified by the id property
   * in a caption resource.
   * @param array $optParams Optional parameters.
   *
   * @opt_param string tfmt The tfmt parameter specifies that the caption track
   * should be returned in a specific format. If the parameter is not included in
   * the request, the track is returned in its original format.
   * @opt_param string onBehalfOf ID of the Google+ Page for the channel that the
   * request is be on behalf of
   * @opt_param string tlang The tlang parameter specifies that the API response
   * should return a translation of the specified caption track. The parameter
   * value is an ISO 639-1 two-letter language code that identifies the desired
   * caption language. The translation is generated by using machine translation,
   * such as Google Translate.
   * @opt_param string onBehalfOfContentOwner Note: This parameter is intended
   * exclusively for YouTube content partners.
   *
   * The onBehalfOfContentOwner parameter indicates that the request's
   * authorization credentials identify a YouTube CMS user who is acting on behalf
   * of the content owner specified in the parameter value. This parameter is
   * intended for YouTube content partners that own and manage many different
   * YouTube channels. It allows content owners to authenticate once and get
   * access to all their video and channel data, without having to provide
   * authentication credentials for each individual channel. The actual CMS
   * account that the user authenticates with must be linked to the specified
   * YouTube content owner.
   */
  public function download($id, $optParams = array())
  {
    $params = array('id' => $id);
    $params = array_merge($params, $optParams);
    return $this->call('download', array($params));
  }

  /**
   * Uploads a caption track. (captions.insert)
   *
   * @param string $part The part parameter specifies the caption resource parts
   * that the API response will include. Set the parameter value to snippet.
   * @param Google_Caption $postBody
   * @param array $optParams Optional parameters.
   *
   * @opt_param string onBehalfOf ID of the Google+ Page for the channel that the
   * request is be on behalf of
   * @opt_param string onBehalfOfContentOwner Note: This parameter is intended
   * exclusively for YouTube content partners.
   *
   * The onBehalfOfContentOwner parameter indicates that the request's
   * authorization credentials identify a YouTube CMS user who is acting on behalf
   * of the content owner specified in the parameter value. This parameter is
   * intended for YouTube content partners that own and manage many different
   * YouTube channels. It allows content owners to authenticate once and get
   * access to all their video and channel data, without having to provide
   * authentication credentials for each individual channel. The actual CMS
   * account that the user authenticates with must be linked to the specified
   * YouTube content owner.
   * @opt_param bool sync The sync parameter indicates whether YouTube should
   * automatically synchronize the caption file with the audio track of the video.
   * If you set the value to true, YouTube will disregard any time codes that are
   * in the uploaded caption file and generate new time codes for the captions.
   *
   * You should set the sync parameter to true if you are uploading a transcript,
   * which has no time codes, or if you suspect the time codes in your file are
   * incorrect and want YouTube to try to fix them.
   * @return Google_Service_YouTube_Caption
   */
  public function insert($part, Google_Service_YouTube_Caption $postBody, $optParams = array())
  {
    $params = array('part' => $part, 'postBody' => $postBody);
    $params = array_merge($params, $optParams);
    return $this->call('insert', array($params), "Google_Service_YouTube_Caption");
  }

  /**
   * Returns a list of caption tracks that are associated with a specified video.
   * Note that the API response does not contain the actual captions and that the
   * captions.download method provides the ability to retrieve a caption track.
   * (captions.listCaptions)
   *
   * @param string $part The part parameter specifies a comma-separated list of
   * one or more caption resource parts that the API response will include. The
   * part names that you can include in the parameter value are id and snippet.
   * @param string $videoId The videoId parameter specifies the YouTube video ID
   * of the video for which the API should return caption tracks.
   * @param array $optParams Optional parameters.
   *
   * @opt_param string onBehalfOf ID of the Google+ Page for the channel that the
   * request is on behalf of.
   * @opt_param string id The id parameter specifies a comma-separated list of IDs
   * that identify the caption resources that should be retrieved. Each ID must
   * identify a caption track associated with the specified video.
   * @opt_param string onBehalfOfContentOwner Note: This parameter is intended
   * exclusively for YouTube content partners.
   *
   * The onBehalfOfContentOwner parameter indicates that the request's
   * authorization credentials identify a YouTube CMS user who is acting on behalf
   * of the content owner specified in the parameter value. This parameter is
   * intended for YouTube content partners that own and manage many different
   * YouTube channels. It allows content owners to authenticate once and get
   * access to all their video and channel data, without having to provide
   * authentication credentials for each individual channel. The actual CMS
   * account that the user authenticates with must be linked to the specified
   * YouTube content owner.
   * @return Google_Service_YouTube_CaptionListResponse
   */
  public function listCaptions($part, $videoId, $optParams = array())
  {
    $params = array('part' => $part, 'videoId' => $videoId);
    $params = array_merge($params, $optParams);
    return $this->call('list', array($params), "Google_Service_YouTube_CaptionListResponse");
  }

  /**
   * Updates a caption track. When updating a caption track, you can change the
   * track's draft status, upload a new caption file for the track, or both.
   * (captions.update)
   *
   * @param string $part The part parameter serves two purposes in this operation.
   * It identifies the properties that the write operation will set as well as the
   * properties that the API response will include. Set the property value to
   * snippet if you are updating the track's draft status. Otherwise, set the
   * property value to id.
   * @param Google_Caption $postBody
   * @param array $optParams Optional parameters.
   *
   * @opt_param string onBehalfOf ID of the Google+ Page for the channel that the
   * request is be on behalf of
   * @opt_param string onBehalfOfContentOwner Note: This parameter is intended
   * exclusively for YouTube content partners.
   *
   * The onBehalfOfContentOwner parameter indicates that the request's
   * authorization credentials identify a YouTube CMS user who is acting on behalf
   * of the content owner specified in the parameter value. This parameter is
   * intended for YouTube content partners that own and manage many different
   * YouTube channels. It allows content owners to authenticate once and get
   * access to all their video and channel data, without having to provide
   * authentication credentials for each individual channel. The actual CMS
   * account that the user authenticates with must be linked to the specified
   * YouTube content owner.
   * @opt_param bool sync Note: The API server only processes the parameter value
   * if the request contains an updated caption file.
   *
   * The sync parameter indicates whether YouTube should automatically synchronize
   * the caption file with the audio track of the video. If you set the value to
   * true, YouTube will automatically synchronize the caption track with the audio
   * track.
   * @return Google_Service_YouTube_Caption
   */
  public function update($part, Google_Service_YouTube_Caption $postBody, $optParams = array())
  {
    $params = array('part' => $part, 'postBody' => $postBody);
    $params = array_merge($params, $optParams);
    return $this->call('update', array($params), "Google_Service_YouTube_Caption");
  }
}

/**
 * The "channelBanners" collection of methods.
 * Typical usage is:
 *  <code>
 *   $youtubeService = new Google_Service_YouTube(...);
 *   $channelBanners = $youtubeService->channelBanners;
 *  </code>
 */
class Google_Service_YouTube_ChannelBanners_Resource extends Google_Service_Resource
{

  /**
   * Uploads a channel banner image to YouTube. This method represents the first
   * two steps in a three-step process to update the banner image for a channel:
   *
   * - Call the channelBanners.insert method to upload the binary image data to
   * YouTube. The image must have a 16:9 aspect ratio and be at least 2120x1192
   * pixels. - Extract the url property's value from the response that the API
   * returns for step 1. - Call the channels.update method to update the channel's
   * branding settings. Set the brandingSettings.image.bannerExternalUrl
   * property's value to the URL obtained in step 2. (channelBanners.insert)
   *
   * @param Google_ChannelBannerResource $postBody
   * @param array $optParams Optional parameters.
   *
   * @opt_param string onBehalfOfContentOwner Note: This parameter is intended
   * exclusively for YouTube content partners.
   *
   * The onBehalfOfContentOwner parameter indicates that the request's
   * authorization credentials identify a YouTube CMS user who is acting on behalf
   * of the content owner specified in the parameter value. This parameter is
   * intended for YouTube content partners that own and manage many different
   * YouTube channels. It allows content owners to authenticate once and get
   * access to all their video and channel data, without having to provide
   * authentication credentials for each individual channel. The CMS account that
   * the user authenticates with must be linked to the specified YouTube content
   * owner.
   * @return Google_Service_YouTube_ChannelBannerResource
   */
  public function insert(Google_Service_YouTube_ChannelBannerResource $postBody, $optParams = array())
  {
    $params = array('postBody' => $postBody);
    $params = array_merge($params, $optParams);
    return $this->call('insert', array($params), "Google_Service_YouTube_ChannelBannerResource");
  }
}

/**
 * The "channelSections" collection of methods.
 * Typical usage is:
 *  <code>
 *   $youtubeService = new Google_Service_YouTube(...);
 *   $channelSections = $youtubeService->channelSections;
 *  </code>
 */
class Google_Service_YouTube_ChannelSections_Resource extends Google_Service_Resource
{

  /**
   * Deletes a channelSection. (channelSections.delete)
   *
   * @param string $id The id parameter specifies the YouTube channelSection ID
   * for the resource that is being deleted. In a channelSection resource, the id
   * property specifies the YouTube channelSection ID.
   * @param array $optParams Optional parameters.
   *
   * @opt_param string onBehalfOfContentOwner Note: This parameter is intended
   * exclusively for YouTube content partners.
   *
   * The onBehalfOfContentOwner parameter indicates that the request's
   * authorization credentials identify a YouTube CMS user who is acting on behalf
   * of the content owner specified in the parameter value. This parameter is
   * intended for YouTube content partners that own and manage many different
   * YouTube channels. It allows content owners to authenticate once and get
   * access to all their video and channel data, without having to provide
   * authentication credentials for each individual channel. The CMS account that
   * the user authenticates with must be linked to the specified YouTube content
   * owner.
   */
  public function delete($id, $optParams = array())
  {
    $params = array('id' => $id);
    $params = array_merge($params, $optParams);
    return $this->call('delete', array($params));
  }

  /**
   * Adds a channelSection for the authenticated user's channel.
   * (channelSections.insert)
   *
   * @param string $part The part parameter serves two purposes in this operation.
   * It identifies the properties that the write operation will set as well as the
   * properties that the API response will include.
   *
   * The part names that you can include in the parameter value are snippet and
   * contentDetails.
   * @param Google_ChannelSection $postBody
   * @param array $optParams Optional parameters.
   *
   * @opt_param string onBehalfOfContentOwnerChannel This parameter can only be
   * used in a properly authorized request. Note: This parameter is intended
   * exclusively for YouTube content partners.
   *
   * The onBehalfOfContentOwnerChannel parameter specifies the YouTube channel ID
   * of the channel to which a video is being added. This parameter is required
   * when a request specifies a value for the onBehalfOfContentOwner parameter,
   * and it can only be used in conjunction with that parameter. In addition, the
   * request must be authorized using a CMS account that is linked to the content
   * owner that the onBehalfOfContentOwner parameter specifies. Finally, the
   * channel that the onBehalfOfContentOwnerChannel parameter value specifies must
   * be linked to the content owner that the onBehalfOfContentOwner parameter
   * specifies.
   *
   * This parameter is intended for YouTube content partners that own and manage
   * many different YouTube channels. It allows content owners to authenticate
   * once and perform actions on behalf of the channel specified in the parameter
   * value, without having to provide authentication credentials for each separate
   * channel.
   * @opt_param string onBehalfOfContentOwner Note: This parameter is intended
   * exclusively for YouTube content partners.
   *
   * The onBehalfOfContentOwner parameter indicates that the request's
   * authorization credentials identify a YouTube CMS user who is acting on behalf
   * of the content owner specified in the parameter value. This parameter is
   * intended for YouTube content partners that own and manage many different
   * YouTube channels. It allows content owners to authenticate once and get
   * access to all their video and channel data, without having to provide
   * authentication credentials for each individual channel. The CMS account that
   * the user authenticates with must be linked to the specified YouTube content
   * owner.
   * @return Google_Service_YouTube_ChannelSection
   */
  public function insert($part, Google_Service_YouTube_ChannelSection $postBody, $optParams = array())
  {
    $params = array('part' => $part, 'postBody' => $postBody);
    $params = array_merge($params, $optParams);
    return $this->call('insert', array($params), "Google_Service_YouTube_ChannelSection");
  }

  /**
   * Returns channelSection resources that match the API request criteria.
   * (channelSections.listChannelSections)
   *
   * @param string $part The part parameter specifies a comma-separated list of
   * one or more channelSection resource properties that the API response will
   * include. The part names that you can include in the parameter value are id,
   * snippet, and contentDetails.
   *
   * If the parameter identifies a property that contains child properties, the
   * child properties will be included in the response. For example, in a
   * channelSection resource, the snippet property contains other properties, such
   * as a display title for the channelSection. If you set part=snippet, the API
   * response will also contain all of those nested properties.
   * @param array $optParams Optional parameters.
   *
   * @opt_param string onBehalfOfContentOwner Note: This parameter is intended
   * exclusively for YouTube content partners.
   *
   * The onBehalfOfContentOwner parameter indicates that the request's
   * authorization credentials identify a YouTube CMS user who is acting on behalf
   * of the content owner specified in the parameter value. This parameter is
   * intended for YouTube content partners that own and manage many different
   * YouTube channels. It allows content owners to authenticate once and get
   * access to all their video and channel data, without having to provide
   * authentication credentials for each individual channel. The CMS account that
   * the user authenticates with must be linked to the specified YouTube content
   * owner.
   * @opt_param string channelId The channelId parameter specifies a YouTube
   * channel ID. The API will only return that channel's channelSections.
   * @opt_param bool mine Set this parameter's value to true to retrieve a feed of
   * the authenticated user's channelSections.
   * @opt_param string hl The hl parameter indicates that the snippet.localized
   * property values in the returned channelSection resources should be in the
   * specified language if localized values for that language are available. For
   * example, if the API request specifies hl=de, the snippet.localized properties
   * in the API response will contain German titles if German titles are
   * available. Channel owners can provide localized channel section titles using
   * either the channelSections.insert or channelSections.update method.
   * @opt_param string id The id parameter specifies a comma-separated list of the
   * YouTube channelSection ID(s) for the resource(s) that are being retrieved. In
   * a channelSection resource, the id property specifies the YouTube
   * channelSection ID.
   * @return Google_Service_YouTube_ChannelSectionListResponse
   */
  public function listChannelSections($part, $optParams = array())
  {
    $params = array('part' => $part);
    $params = array_merge($params, $optParams);
    return $this->call('list', array($params), "Google_Service_YouTube_ChannelSectionListResponse");
  }

  /**
   * Update a channelSection. (channelSections.update)
   *
   * @param string $part The part parameter serves two purposes in this operation.
   * It identifies the properties that the write operation will set as well as the
   * properties that the API response will include.
   *
   * The part names that you can include in the parameter value are snippet and
   * contentDetails.
   * @param Google_ChannelSection $postBody
   * @param array $optParams Optional parameters.
   *
   * @opt_param string onBehalfOfContentOwner Note: This parameter is intended
   * exclusively for YouTube content partners.
   *
   * The onBehalfOfContentOwner parameter indicates that the request's
   * authorization credentials identify a YouTube CMS user who is acting on behalf
   * of the content owner specified in the parameter value. This parameter is
   * intended for YouTube content partners that own and manage many different
   * YouTube channels. It allows content owners to authenticate once and get
   * access to all their video and channel data, without having to provide
   * authentication credentials for each individual channel. The CMS account that
   * the user authenticates with must be linked to the specified YouTube content
   * owner.
   * @return Google_Service_YouTube_ChannelSection
   */
  public function update($part, Google_Service_YouTube_ChannelSection $postBody, $optParams = array())
  {
    $params = array('part' => $part, 'postBody' => $postBody);
    $params = array_merge($params, $optParams);
    return $this->call('update', array($params), "Google_Service_YouTube_ChannelSection");
  }
}

/**
 * The "channels" collection of methods.
 * Typical usage is:
 *  <code>
 *   $youtubeService = new Google_Service_YouTube(...);
 *   $channels = $youtubeService->channels;
 *  </code>
 */
class Google_Service_YouTube_Channels_Resource extends Google_Service_Resource
{

  /**
   * Returns a collection of zero or more channel resources that match the request
   * criteria. (channels.listChannels)
   *
   * @param string $part The part parameter specifies a comma-separated list of
   * one or more channel resource properties that the API response will include.
   *
   * If the parameter identifies a property that contains child properties, the
   * child properties will be included in the response. For example, in a channel
   * resource, the contentDetails property contains other properties, such as the
   * uploads properties. As such, if you set part=contentDetails, the API response
   * will also contain all of those nested properties.
   * @param array $optParams Optional parameters.
   *
   * @opt_param bool managedByMe Note: This parameter is intended exclusively for
   * YouTube content partners.
   *
   * Set this parameter's value to true to instruct the API to only return
   * channels managed by the content owner that the onBehalfOfContentOwner
   * parameter specifies. The user must be authenticated as a CMS account linked
   * to the specified content owner and onBehalfOfContentOwner must be provided.
   * @opt_param string onBehalfOfContentOwner Note: This parameter is intended
   * exclusively for YouTube content partners.
   *
   * The onBehalfOfContentOwner parameter indicates that the request's
   * authorization credentials identify a YouTube CMS user who is acting on behalf
   * of the content owner specified in the parameter value. This parameter is
   * intended for YouTube content partners that own and manage many different
   * YouTube channels. It allows content owners to authenticate once and get
   * access to all their video and channel data, without having to provide
   * authentication credentials for each individual channel. The CMS account that
   * the user authenticates with must be linked to the specified YouTube content
   * owner.
   * @opt_param string forUsername The forUsername parameter specifies a YouTube
   * username, thereby requesting the channel associated with that username.
   * @opt_param bool mine Set this parameter's value to true to instruct the API
   * to only return channels owned by the authenticated user.
   * @opt_param string maxResults The maxResults parameter specifies the maximum
   * number of items that should be returned in the result set.
   * @opt_param string id The id parameter specifies a comma-separated list of the
   * YouTube channel ID(s) for the resource(s) that are being retrieved. In a
   * channel resource, the id property specifies the channel's YouTube channel ID.
   * @opt_param string pageToken The pageToken parameter identifies a specific
   * page in the result set that should be returned. In an API response, the
   * nextPageToken and prevPageToken properties identify other pages that could be
   * retrieved.
   * @opt_param bool mySubscribers Use the subscriptions.list method and its
   * mySubscribers parameter to retrieve a list of subscribers to the
   * authenticated user's channel.
   * @opt_param string hl The hl parameter should be used for filter out the
   * properties that are not in the given language. Used for the brandingSettings
   * part.
   * @opt_param string categoryId The categoryId parameter specifies a YouTube
   * guide category, thereby requesting YouTube channels associated with that
   * category.
   * @return Google_Service_YouTube_ChannelListResponse
   */
  public function listChannels($part, $optParams = array())
  {
    $params = array('part' => $part);
    $params = array_merge($params, $optParams);
    return $this->call('list', array($params), "Google_Service_YouTube_ChannelListResponse");
  }

  /**
   * Updates a channel's metadata. Note that this method currently only supports
   * updates to the channel resource's brandingSettings and invideoPromotion
   * objects and their child properties. (channels.update)
   *
   * @param string $part The part parameter serves two purposes in this operation.
   * It identifies the properties that the write operation will set as well as the
   * properties that the API response will include.
   *
   * The API currently only allows the parameter value to be set to either
   * brandingSettings or invideoPromotion. (You cannot update both of those parts
   * with a single request.)
   *
   * Note that this method overrides the existing values for all of the mutable
   * properties that are contained in any parts that the parameter value
   * specifies.
   * @param Google_Channel $postBody
   * @param array $optParams Optional parameters.
   *
   * @opt_param string onBehalfOfContentOwner The onBehalfOfContentOwner parameter
   * indicates that the authenticated user is acting on behalf of the content
   * owner specified in the parameter value. This parameter is intended for
   * YouTube content partners that own and manage many different YouTube channels.
   * It allows content owners to authenticate once and get access to all their
   * video and channel data, without having to provide authentication credentials
   * for each individual channel. The actual CMS account that the user
   * authenticates with needs to be linked to the specified YouTube content owner.
   * @return Google_Service_YouTube_Channel
   */
  public function update($part, Google_Service_YouTube_Channel $postBody, $optParams = array())
  {
    $params = array('part' => $part, 'postBody' => $postBody);
    $params = array_merge($params, $optParams);
    return $this->call('update', array($params), "Google_Service_YouTube_Channel");
  }
}

/**
 * The "commentThreads" collection of methods.
 * Typical usage is:
 *  <code>
 *   $youtubeService = new Google_Service_YouTube(...);
 *   $commentThreads = $youtubeService->commentThreads;
 *  </code>
 */
class Google_Service_YouTube_CommentThreads_Resource extends Google_Service_Resource
{

  /**
   * Creates a new top-level comment. To add a reply to an existing comment, use
   * the comments.insert method instead. (commentThreads.insert)
   *
   * @param string $part The part parameter identifies the properties that the API
   * response will include. Set the parameter value to snippet. The snippet part
   * has a quota cost of 2 units.
   * @param Google_CommentThread $postBody
   * @param array $optParams Optional parameters.
   * @return Google_Service_YouTube_CommentThread
   */
  public function insert($part, Google_Service_YouTube_CommentThread $postBody, $optParams = array())
  {
    $params = array('part' => $part, 'postBody' => $postBody);
    $params = array_merge($params, $optParams);
    return $this->call('insert', array($params), "Google_Service_YouTube_CommentThread");
  }

  /**
   * Returns a list of comment threads that match the API request parameters.
   * (commentThreads.listCommentThreads)
   *
   * @param string $part The part parameter specifies a comma-separated list of
   * one or more commentThread resource properties that the API response will
   * include.
   * @param array $optParams Optional parameters.
   *
   * @opt_param string searchTerms The searchTerms parameter instructs the API to
   * limit the API response to only contain comments that contain the specified
   * search terms.
   *
   * Note: This parameter is not supported for use in conjunction with the id
   * parameter.
   * @opt_param string allThreadsRelatedToChannelId The
   * allThreadsRelatedToChannelId parameter instructs the API to return all
   * comment threads associated with the specified channel. The response can
   * include comments about the channel or about the channel's videos.
   * @opt_param string channelId The channelId parameter instructs the API to
   * return comment threads containing comments about the specified channel. (The
   * response will not include comments left on videos that the channel uploaded.)
   * @opt_param string videoId The videoId parameter instructs the API to return
   * comment threads associated with the specified video ID.
   * @opt_param string maxResults The maxResults parameter specifies the maximum
   * number of items that should be returned in the result set.
   *
   * Note: This parameter is not supported for use in conjunction with the id
   * parameter.
   * @opt_param string id The id parameter specifies a comma-separated list of
   * comment thread IDs for the resources that should be retrieved.
   * @opt_param string pageToken The pageToken parameter identifies a specific
   * page in the result set that should be returned. In an API response, the
   * nextPageToken property identifies the next page of the result that can be
   * retrieved.
   *
   * Note: This parameter is not supported for use in conjunction with the id
   * parameter.
   * @opt_param string moderationStatus Set this parameter to limit the returned
   * comment threads to a particular moderation state.
   *
   * Note: This parameter is not supported for use in conjunction with the id
   * parameter.
   * @opt_param string textFormat Set this parameter's value to html or plainText
   * to instruct the API to return the comments left by users in html formatted or
   * in plain text.
   * @opt_param string order The order parameter specifies the order in which the
   * API response should list comment threads. Valid values are: - time - Comment
   * threads are ordered by time. This is the default behavior. - relevance -
   * Comment threads are ordered by relevance.Note: This parameter is not
   * supported for use in conjunction with the id parameter.
   * @return Google_Service_YouTube_CommentThreadListResponse
   */
  public function listCommentThreads($part, $optParams = array())
  {
    $params = array('part' => $part);
    $params = array_merge($params, $optParams);
    return $this->call('list', array($params), "Google_Service_YouTube_CommentThreadListResponse");
  }

  /**
   * Modifies the top-level comment in a comment thread. (commentThreads.update)
   *
   * @param string $part The part parameter specifies a comma-separated list of
   * commentThread resource properties that the API response will include. You
   * must at least include the snippet part in the parameter value since that part
   * contains all of the properties that the API request can update.
   * @param Google_CommentThread $postBody
   * @param array $optParams Optional parameters.
   * @return Google_Service_YouTube_CommentThread
   */
  public function update($part, Google_Service_YouTube_CommentThread $postBody, $optParams = array())
  {
    $params = array('part' => $part, 'postBody' => $postBody);
    $params = array_merge($params, $optParams);
    return $this->call('update', array($params), "Google_Service_YouTube_CommentThread");
  }
}

/**
 * The "comments" collection of methods.
 * Typical usage is:
 *  <code>
 *   $youtubeService = new Google_Service_YouTube(...);
 *   $comments = $youtubeService->comments;
 *  </code>
 */
class Google_Service_YouTube_Comments_Resource extends Google_Service_Resource
{

  /**
   * Deletes a comment. (comments.delete)
   *
   * @param string $id The id parameter specifies the comment ID for the resource
   * that is being deleted.
   * @param array $optParams Optional parameters.
   */
  public function delete($id, $optParams = array())
  {
    $params = array('id' => $id);
    $params = array_merge($params, $optParams);
    return $this->call('delete', array($params));
  }

  /**
   * Creates a reply to an existing comment. Note: To create a top-level comment,
   * use the commentThreads.insert method. (comments.insert)
   *
   * @param string $part The part parameter identifies the properties that the API
   * response will include. Set the parameter value to snippet. The snippet part
   * has a quota cost of 2 units.
   * @param Google_Comment $postBody
   * @param array $optParams Optional parameters.
   * @return Google_Service_YouTube_Comment
   */
  public function insert($part, Google_Service_YouTube_Comment $postBody, $optParams = array())
  {
    $params = array('part' => $part, 'postBody' => $postBody);
    $params = array_merge($params, $optParams);
    return $this->call('insert', array($params), "Google_Service_YouTube_Comment");
  }

  /**
   * Returns a list of comments that match the API request parameters.
   * (comments.listComments)
   *
   * @param string $part The part parameter specifies a comma-separated list of
   * one or more comment resource properties that the API response will include.
   * @param array $optParams Optional parameters.
   *
   * @opt_param string maxResults The maxResults parameter specifies the maximum
   * number of items that should be returned in the result set.
   *
   * Note: This parameter is not supported for use in conjunction with the id
   * parameter.
   * @opt_param string pageToken The pageToken parameter identifies a specific
   * page in the result set that should be returned. In an API response, the
   * nextPageToken property identifies the next page of the result that can be
   * retrieved.
   *
   * Note: This parameter is not supported for use in conjunction with the id
   * parameter.
   * @opt_param string parentId The parentId parameter specifies the ID of the
   * comment for which replies should be retrieved.
   *
   * Note: YouTube currently supports replies only for top-level comments.
   * However, replies to replies may be supported in the future.
   * @opt_param string textFormat This parameter indicates whether the API should
   * return comments formatted as HTML or as plain text.
   * @opt_param string id The id parameter specifies a comma-separated list of
   * comment IDs for the resources that are being retrieved. In a comment
   * resource, the id property specifies the comment's ID.
   * @return Google_Service_YouTube_CommentListResponse
   */
  public function listComments($part, $optParams = array())
  {
    $params = array('part' => $part);
    $params = array_merge($params, $optParams);
    return $this->call('list', array($params), "Google_Service_YouTube_CommentListResponse");
  }

  /**
   * Expresses the caller's opinion that one or more comments should be flagged as
   * spam. (comments.markAsSpam)
   *
   * @param string $id The id parameter specifies a comma-separated list of IDs of
   * comments that the caller believes should be classified as spam.
   * @param array $optParams Optional parameters.
   */
  public function markAsSpam($id, $optParams = array())
  {
    $params = array('id' => $id);
    $params = array_merge($params, $optParams);
    return $this->call('markAsSpam', array($params));
  }

  /**
   * Sets the moderation status of one or more comments. The API request must be
   * authorized by the owner of the channel or video associated with the comments.
   * (comments.setModerationStatus)
   *
   * @param string $id The id parameter specifies a comma-separated list of IDs
   * that identify the comments for which you are updating the moderation status.
   * @param string $moderationStatus Identifies the new moderation status of the
   * specified comments.
   * @param array $optParams Optional parameters.
   *
   * @opt_param bool banAuthor The banAuthor parameter lets you indicate that you
   * want to automatically reject any additional comments written by the comment's
   * author. Set the parameter value to true to ban the author.
   *
   * Note: This parameter is only valid if the moderationStatus parameter is also
   * set to rejected.
   */
  public function setModerationStatus($id, $moderationStatus, $optParams = array())
  {
    $params = array('id' => $id, 'moderationStatus' => $moderationStatus);
    $params = array_merge($params, $optParams);
    return $this->call('setModerationStatus', array($params));
  }

  /**
   * Modifies a comment. (comments.update)
   *
   * @param string $part The part parameter identifies the properties that the API
   * response will include. You must at least include the snippet part in the
   * parameter value since that part contains all of the properties that the API
   * request can update.
   * @param Google_Comment $postBody
   * @param array $optParams Optional parameters.
   * @return Google_Service_YouTube_Comment
   */
  public function update($part, Google_Service_YouTube_Comment $postBody, $optParams = array())
  {
    $params = array('part' => $part, 'postBody' => $postBody);
    $params = array_merge($params, $optParams);
    return $this->call('update', array($params), "Google_Service_YouTube_Comment");
  }
}

/**
 * The "guideCategories" collection of methods.
 * Typical usage is:
 *  <code>
 *   $youtubeService = new Google_Service_YouTube(...);
 *   $guideCategories = $youtubeService->guideCategories;
 *  </code>
 */
class Google_Service_YouTube_GuideCategories_Resource extends Google_Service_Resource
{

  /**
   * Returns a list of categories that can be associated with YouTube channels.
   * (guideCategories.listGuideCategories)
   *
   * @param string $part The part parameter specifies the guideCategory resource
   * properties that the API response will include. Set the parameter value to
   * snippet.
   * @param array $optParams Optional parameters.
   *
   * @opt_param string regionCode The regionCode parameter instructs the API to
   * return the list of guide categories available in the specified country. The
   * parameter value is an ISO 3166-1 alpha-2 country code.
   * @opt_param string id The id parameter specifies a comma-separated list of the
   * YouTube channel category ID(s) for the resource(s) that are being retrieved.
   * In a guideCategory resource, the id property specifies the YouTube channel
   * category ID.
   * @opt_param string hl The hl parameter specifies the language that will be
   * used for text values in the API response.
   * @return Google_Service_YouTube_GuideCategoryListResponse
   */
  public function listGuideCategories($part, $optParams = array())
  {
    $params = array('part' => $part);
    $params = array_merge($params, $optParams);
    return $this->call('list', array($params), "Google_Service_YouTube_GuideCategoryListResponse");
  }
}

/**
 * The "i18nLanguages" collection of methods.
 * Typical usage is:
 *  <code>
 *   $youtubeService = new Google_Service_YouTube(...);
 *   $i18nLanguages = $youtubeService->i18nLanguages;
 *  </code>
 */
class Google_Service_YouTube_I18nLanguages_Resource extends Google_Service_Resource
{

  /**
   * Returns a list of application languages that the YouTube website supports.
   * (i18nLanguages.listI18nLanguages)
   *
   * @param string $part The part parameter specifies the i18nLanguage resource
   * properties that the API response will include. Set the parameter value to
   * snippet.
   * @param array $optParams Optional parameters.
   *
   * @opt_param string hl The hl parameter specifies the language that should be
   * used for text values in the API response.
   * @return Google_Service_YouTube_I18nLanguageListResponse
   */
  public function listI18nLanguages($part, $optParams = array())
  {
    $params = array('part' => $part);
    $params = array_merge($params, $optParams);
    return $this->call('list', array($params), "Google_Service_YouTube_I18nLanguageListResponse");
  }
}

/**
 * The "i18nRegions" collection of methods.
 * Typical usage is:
 *  <code>
 *   $youtubeService = new Google_Service_YouTube(...);
 *   $i18nRegions = $youtubeService->i18nRegions;
 *  </code>
 */
class Google_Service_YouTube_I18nRegions_Resource extends Google_Service_Resource
{

  /**
   * Returns a list of content regions that the YouTube website supports.
   * (i18nRegions.listI18nRegions)
   *
   * @param string $part The part parameter specifies the i18nRegion resource
   * properties that the API response will include. Set the parameter value to
   * snippet.
   * @param array $optParams Optional parameters.
   *
   * @opt_param string hl The hl parameter specifies the language that should be
   * used for text values in the API response.
   * @return Google_Service_YouTube_I18nRegionListResponse
   */
  public function listI18nRegions($part, $optParams = array())
  {
    $params = array('part' => $part);
    $params = array_merge($params, $optParams);
    return $this->call('list', array($params), "Google_Service_YouTube_I18nRegionListResponse");
  }
}

/**
 * The "liveBroadcasts" collection of methods.
 * Typical usage is:
 *  <code>
 *   $youtubeService = new Google_Service_YouTube(...);
 *   $liveBroadcasts = $youtubeService->liveBroadcasts;
 *  </code>
 */
class Google_Service_YouTube_LiveBroadcasts_Resource extends Google_Service_Resource
{

  /**
   * Binds a YouTube broadcast to a stream or removes an existing binding between
   * a broadcast and a stream. A broadcast can only be bound to one video stream,
   * though a video stream may be bound to more than one broadcast.
   * (liveBroadcasts.bind)
   *
   * @param string $id The id parameter specifies the unique ID of the broadcast
   * that is being bound to a video stream.
   * @param string $part The part parameter specifies a comma-separated list of
   * one or more liveBroadcast resource properties that the API response will
   * include. The part names that you can include in the parameter value are id,
   * snippet, contentDetails, and status.
   * @param array $optParams Optional parameters.
   *
   * @opt_param string onBehalfOfContentOwnerChannel This parameter can only be
   * used in a properly authorized request. Note: This parameter is intended
   * exclusively for YouTube content partners.
   *
   * The onBehalfOfContentOwnerChannel parameter specifies the YouTube channel ID
   * of the channel to which a video is being added. This parameter is required
   * when a request specifies a value for the onBehalfOfContentOwner parameter,
   * and it can only be used in conjunction with that parameter. In addition, the
   * request must be authorized using a CMS account that is linked to the content
   * owner that the onBehalfOfContentOwner parameter specifies. Finally, the
   * channel that the onBehalfOfContentOwnerChannel parameter value specifies must
   * be linked to the content owner that the onBehalfOfContentOwner parameter
   * specifies.
   *
   * This parameter is intended for YouTube content partners that own and manage
   * many different YouTube channels. It allows content owners to authenticate
   * once and perform actions on behalf of the channel specified in the parameter
   * value, without having to provide authentication credentials for each separate
   * channel.
   * @opt_param string onBehalfOfContentOwner Note: This parameter is intended
   * exclusively for YouTube content partners.
   *
   * The onBehalfOfContentOwner parameter indicates that the request's
   * authorization credentials identify a YouTube CMS user who is acting on behalf
   * of the content owner specified in the parameter value. This parameter is
   * intended for YouTube content partners that own and manage many different
   * YouTube channels. It allows content owners to authenticate once and get
   * access to all their video and channel data, without having to provide
   * authentication credentials for each individual channel. The CMS account that
   * the user authenticates with must be linked to the specified YouTube content
   * owner.
   * @opt_param string streamId The streamId parameter specifies the unique ID of
   * the video stream that is being bound to a broadcast. If this parameter is
   * omitted, the API will remove any existing binding between the broadcast and a
   * video stream.
   * @return Google_Service_YouTube_LiveBroadcast
   */
  public function bind($id, $part, $optParams = array())
  {
    $params = array('id' => $id, 'part' => $part);
    $params = array_merge($params, $optParams);
    return $this->call('bind', array($params), "Google_Service_YouTube_LiveBroadcast");
  }

  /**
   * Binds a YouTube broadcast to a stream or removes an existing binding between
   * a broadcast and a stream. A broadcast can only be bound to one video stream,
   * though a video stream may be bound to more than one broadcast.
   * (liveBroadcasts.bind_direct)
   *
   * @param string $id The id parameter specifies the unique ID of the broadcast
   * that is being bound to a video stream.
   * @param string $part The part parameter specifies a comma-separated list of
   * one or more liveBroadcast resource properties that the API response will
   * include. The part names that you can include in the parameter value are id,
   * snippet, contentDetails, and status.
   * @param array $optParams Optional parameters.
   *
   * @opt_param string onBehalfOfContentOwnerChannel This parameter can only be
   * used in a properly authorized request. Note: This parameter is intended
   * exclusively for YouTube content partners.
   *
   * The onBehalfOfContentOwnerChannel parameter specifies the YouTube channel ID
   * of the channel to which a video is being added. This parameter is required
   * when a request specifies a value for the onBehalfOfContentOwner parameter,
   * and it can only be used in conjunction with that parameter. In addition, the
   * request must be authorized using a CMS account that is linked to the content
   * owner that the onBehalfOfContentOwner parameter specifies. Finally, the
   * channel that the onBehalfOfContentOwnerChannel parameter value specifies must
   * be linked to the content owner that the onBehalfOfContentOwner parameter
   * specifies.
   *
   * This parameter is intended for YouTube content partners that own and manage
   * many different YouTube channels. It allows content owners to authenticate
   * once and perform actions on behalf of the channel specified in the parameter
   * value, without having to provide authentication credentials for each separate
   * channel.
   * @opt_param string onBehalfOfContentOwner Note: This parameter is intended
   * exclusively for YouTube content partners.
   *
   * The onBehalfOfContentOwner parameter indicates that the request's
   * authorization credentials identify a YouTube CMS user who is acting on behalf
   * of the content owner specified in the parameter value. This parameter is
   * intended for YouTube content partners that own and manage many different
   * YouTube channels. It allows content owners to authenticate once and get
   * access to all their video and channel data, without having to provide
   * authentication credentials for each individual channel. The CMS account that
   * the user authenticates with must be linked to the specified YouTube content
   * owner.
   * @opt_param string streamId The streamId parameter specifies the unique ID of
   * the video stream that is being bound to a broadcast. If this parameter is
   * omitted, the API will remove any existing binding between the broadcast and a
   * video stream.
   * @return Google_Service_YouTube_LiveBroadcast
   */
  public function bind_direct($id, $part, $optParams = array())
  {
    $params = array('id' => $id, 'part' => $part);
    $params = array_merge($params, $optParams);
    return $this->call('bind_direct', array($params), "Google_Service_YouTube_LiveBroadcast");
  }

  /**
   * Controls the settings for a slate that can be displayed in the broadcast
   * stream. (liveBroadcasts.control)
   *
   * @param string $id The id parameter specifies the YouTube live broadcast ID
   * that uniquely identifies the broadcast in which the slate is being updated.
   * @param string $part The part parameter specifies a comma-separated list of
   * one or more liveBroadcast resource properties that the API response will
   * include. The part names that you can include in the parameter value are id,
   * snippet, contentDetails, and status.
   * @param array $optParams Optional parameters.
   *
   * @opt_param string onBehalfOfContentOwner Note: This parameter is intended
   * exclusively for YouTube content partners.
   *
   * The onBehalfOfContentOwner parameter indicates that the request's
   * authorization credentials identify a YouTube CMS user who is acting on behalf
   * of the content owner specified in the parameter value. This parameter is
   * intended for YouTube content partners that own and manage many different
   * YouTube channels. It allows content owners to authenticate once and get
   * access to all their video and channel data, without having to provide
   * authentication credentials for each individual channel. The CMS account that
   * the user authenticates with must be linked to the specified YouTube content
   * owner.
   * @opt_param bool displaySlate The displaySlate parameter specifies whether the
   * slate is being enabled or disabled.
   * @opt_param string onBehalfOfContentOwnerChannel This parameter can only be
   * used in a properly authorized request. Note: This parameter is intended
   * exclusively for YouTube content partners.
   *
   * The onBehalfOfContentOwnerChannel parameter specifies the YouTube channel ID
   * of the channel to which a video is being added. This parameter is required
   * when a request specifies a value for the onBehalfOfContentOwner parameter,
   * and it can only be used in conjunction with that parameter. In addition, the
   * request must be authorized using a CMS account that is linked to the content
   * owner that the onBehalfOfContentOwner parameter specifies. Finally, the
   * channel that the onBehalfOfContentOwnerChannel parameter value specifies must
   * be linked to the content owner that the onBehalfOfContentOwner parameter
   * specifies.
   *
   * This parameter is intended for YouTube content partners that own and manage
   * many different YouTube channels. It allows content owners to authenticate
   * once and perform actions on behalf of the channel specified in the parameter
   * value, without having to provide authentication credentials for each separate
   * channel.
   * @opt_param string offsetTimeMs The offsetTimeMs parameter specifies a
   * positive time offset when the specified slate change will occur. The value is
   * measured in milliseconds from the beginning of the broadcast's monitor
   * stream, which is the time that the testing phase for the broadcast began.
   * Even though it is specified in milliseconds, the value is actually an
   * approximation, and YouTube completes the requested action as closely as
   * possible to that time.
   *
   * If you do not specify a value for this parameter, then YouTube performs the
   * action as soon as possible. See the Getting started guide for more details.
   *
   * Important: You should only specify a value for this parameter if your
   * broadcast stream is delayed.
   * @opt_param string walltime The walltime parameter specifies the wall clock
   * time at which the specified slate change will occur. The value is specified
   * in ISO 8601 (YYYY-MM-DDThh:mm:ss.sssZ) format.
   * @return Google_Service_YouTube_LiveBroadcast
   */
  public function control($id, $part, $optParams = array())
  {
    $params = array('id' => $id, 'part' => $part);
    $params = array_merge($params, $optParams);
    return $this->call('control', array($params), "Google_Service_YouTube_LiveBroadcast");
  }

  /**
   * Deletes a broadcast. (liveBroadcasts.delete)
   *
   * @param string $id The id parameter specifies the YouTube live broadcast ID
   * for the resource that is being deleted.
   * @param array $optParams Optional parameters.
   *
   * @opt_param string onBehalfOfContentOwnerChannel This parameter can only be
   * used in a properly authorized request. Note: This parameter is intended
   * exclusively for YouTube content partners.
   *
   * The onBehalfOfContentOwnerChannel parameter specifies the YouTube channel ID
   * of the channel to which a video is being added. This parameter is required
   * when a request specifies a value for the onBehalfOfContentOwner parameter,
   * and it can only be used in conjunction with that parameter. In addition, the
   * request must be authorized using a CMS account that is linked to the content
   * owner that the onBehalfOfContentOwner parameter specifies. Finally, the
   * channel that the onBehalfOfContentOwnerChannel parameter value specifies must
   * be linked to the content owner that the onBehalfOfContentOwner parameter
   * specifies.
   *
   * This parameter is intended for YouTube content partners that own and manage
   * many different YouTube channels. It allows content owners to authenticate
   * once and perform actions on behalf of the channel specified in the parameter
   * value, without having to provide authentication credentials for each separate
   * channel.
   * @opt_param string onBehalfOfContentOwner Note: This parameter is intended
   * exclusively for YouTube content partners.
   *
   * The onBehalfOfContentOwner parameter indicates that the request's
   * authorization credentials identify a YouTube CMS user who is acting on behalf
   * of the content owner specified in the parameter value. This parameter is
   * intended for YouTube content partners that own and manage many different
   * YouTube channels. It allows content owners to authenticate once and get
   * access to all their video and channel data, without having to provide
   * authentication credentials for each individual channel. The CMS account that
   * the user authenticates with must be linked to the specified YouTube content
   * owner.
   */
  public function delete($id, $optParams = array())
  {
    $params = array('id' => $id);
    $params = array_merge($params, $optParams);
    return $this->call('delete', array($params));
  }

  /**
   * Creates a broadcast. (liveBroadcasts.insert)
   *
   * @param string $part The part parameter serves two purposes in this operation.
   * It identifies the properties that the write operation will set as well as the
   * properties that the API response will include.
   *
   * The part properties that you can include in the parameter value are id,
   * snippet, contentDetails, and status.
   * @param Google_LiveBroadcast $postBody
   * @param array $optParams Optional parameters.
   *
   * @opt_param string onBehalfOfContentOwnerChannel This parameter can only be
   * used in a properly authorized request. Note: This parameter is intended
   * exclusively for YouTube content partners.
   *
   * The onBehalfOfContentOwnerChannel parameter specifies the YouTube channel ID
   * of the channel to which a video is being added. This parameter is required
   * when a request specifies a value for the onBehalfOfContentOwner parameter,
   * and it can only be used in conjunction with that parameter. In addition, the
   * request must be authorized using a CMS account that is linked to the content
   * owner that the onBehalfOfContentOwner parameter specifies. Finally, the
   * channel that the onBehalfOfContentOwnerChannel parameter value specifies must
   * be linked to the content owner that the onBehalfOfContentOwner parameter
   * specifies.
   *
   * This parameter is intended for YouTube content partners that own and manage
   * many different YouTube channels. It allows content owners to authenticate
   * once and perform actions on behalf of the channel specified in the parameter
   * value, without having to provide authentication credentials for each separate
   * channel.
   * @opt_param string onBehalfOfContentOwner Note: This parameter is intended
   * exclusively for YouTube content partners.
   *
   * The onBehalfOfContentOwner parameter indicates that the request's
   * authorization credentials identify a YouTube CMS user who is acting on behalf
   * of the content owner specified in the parameter value. This parameter is
   * intended for YouTube content partners that own and manage many different
   * YouTube channels. It allows content owners to authenticate once and get
   * access to all their video and channel data, without having to provide
   * authentication credentials for each individual channel. The CMS account that
   * the user authenticates with must be linked to the specified YouTube content
   * owner.
   * @return Google_Service_YouTube_LiveBroadcast
   */
  public function insert($part, Google_Service_YouTube_LiveBroadcast $postBody, $optParams = array())
  {
    $params = array('part' => $part, 'postBody' => $postBody);
    $params = array_merge($params, $optParams);
    return $this->call('insert', array($params), "Google_Service_YouTube_LiveBroadcast");
  }

  /**
   * Returns a list of YouTube broadcasts that match the API request parameters.
   * (liveBroadcasts.listLiveBroadcasts)
   *
   * @param string $part The part parameter specifies a comma-separated list of
   * one or more liveBroadcast resource properties that the API response will
   * include. The part names that you can include in the parameter value are id,
   * snippet, contentDetails, and status.
   * @param array $optParams Optional parameters.
   *
   * @opt_param string broadcastStatus The broadcastStatus parameter filters the
   * API response to only include broadcasts with the specified status.
   * @opt_param string onBehalfOfContentOwner Note: This parameter is intended
   * exclusively for YouTube content partners.
   *
   * The onBehalfOfContentOwner parameter indicates that the request's
   * authorization credentials identify a YouTube CMS user who is acting on behalf
   * of the content owner specified in the parameter value. This parameter is
   * intended for YouTube content partners that own and manage many different
   * YouTube channels. It allows content owners to authenticate once and get
   * access to all their video and channel data, without having to provide
   * authentication credentials for each individual channel. The CMS account that
   * the user authenticates with must be linked to the specified YouTube content
   * owner.
   * @opt_param string onBehalfOfContentOwnerChannel This parameter can only be
   * used in a properly authorized request. Note: This parameter is intended
   * exclusively for YouTube content partners.
   *
   * The onBehalfOfContentOwnerChannel parameter specifies the YouTube channel ID
   * of the channel to which a video is being added. This parameter is required
   * when a request specifies a value for the onBehalfOfContentOwner parameter,
   * and it can only be used in conjunction with that parameter. In addition, the
   * request must be authorized using a CMS account that is linked to the content
   * owner that the onBehalfOfContentOwner parameter specifies. Finally, the
   * channel that the onBehalfOfContentOwnerChannel parameter value specifies must
   * be linked to the content owner that the onBehalfOfContentOwner parameter
   * specifies.
   *
   * This parameter is intended for YouTube content partners that own and manage
   * many different YouTube channels. It allows content owners to authenticate
   * once and perform actions on behalf of the channel specified in the parameter
   * value, without having to provide authentication credentials for each separate
   * channel.
   * @opt_param bool mine The mine parameter can be used to instruct the API to
   * only return broadcasts owned by the authenticated user. Set the parameter
   * value to true to only retrieve your own broadcasts.
   * @opt_param string maxResults The maxResults parameter specifies the maximum
   * number of items that should be returned in the result set.
   * @opt_param string pageToken The pageToken parameter identifies a specific
   * page in the result set that should be returned. In an API response, the
   * nextPageToken and prevPageToken properties identify other pages that could be
   * retrieved.
   * @opt_param string id The id parameter specifies a comma-separated list of
   * YouTube broadcast IDs that identify the broadcasts being retrieved. In a
   * liveBroadcast resource, the id property specifies the broadcast's ID.
   * @return Google_Service_YouTube_LiveBroadcastListResponse
   */
  public function listLiveBroadcasts($part, $optParams = array())
  {
    $params = array('part' => $part);
    $params = array_merge($params, $optParams);
    return $this->call('list', array($params), "Google_Service_YouTube_LiveBroadcastListResponse");
  }

  /**
   * Changes the status of a YouTube live broadcast and initiates any processes
   * associated with the new status. For example, when you transition a
   * broadcast's status to testing, YouTube starts to transmit video to that
   * broadcast's monitor stream. Before calling this method, you should confirm
   * that the value of the status.streamStatus property for the stream bound to
   * your broadcast is active. (liveBroadcasts.transition)
   *
   * @param string $broadcastStatus The broadcastStatus parameter identifies the
   * state to which the broadcast is changing. Note that to transition a broadcast
   * to either the testing or live state, the status.streamStatus must be active
   * for the stream that the broadcast is bound to.
   * @param string $id The id parameter specifies the unique ID of the broadcast
   * that is transitioning to another status.
   * @param string $part The part parameter specifies a comma-separated list of
   * one or more liveBroadcast resource properties that the API response will
   * include. The part names that you can include in the parameter value are id,
   * snippet, contentDetails, and status.
   * @param array $optParams Optional parameters.
   *
   * @opt_param string onBehalfOfContentOwnerChannel This parameter can only be
   * used in a properly authorized request. Note: This parameter is intended
   * exclusively for YouTube content partners.
   *
   * The onBehalfOfContentOwnerChannel parameter specifies the YouTube channel ID
   * of the channel to which a video is being added. This parameter is required
   * when a request specifies a value for the onBehalfOfContentOwner parameter,
   * and it can only be used in conjunction with that parameter. In addition, the
   * request must be authorized using a CMS account that is linked to the content
   * owner that the onBehalfOfContentOwner parameter specifies. Finally, the
   * channel that the onBehalfOfContentOwnerChannel parameter value specifies must
   * be linked to the content owner that the onBehalfOfContentOwner parameter
   * specifies.
   *
   * This parameter is intended for YouTube content partners that own and manage
   * many different YouTube channels. It allows content owners to authenticate
   * once and perform actions on behalf of the channel specified in the parameter
   * value, without having to provide authentication credentials for each separate
   * channel.
   * @opt_param string onBehalfOfContentOwner Note: This parameter is intended
   * exclusively for YouTube content partners.
   *
   * The onBehalfOfContentOwner parameter indicates that the request's
   * authorization credentials identify a YouTube CMS user who is acting on behalf
   * of the content owner specified in the parameter value. This parameter is
   * intended for YouTube content partners that own and manage many different
   * YouTube channels. It allows content owners to authenticate once and get
   * access to all their video and channel data, without having to provide
   * authentication credentials for each individual channel. The CMS account that
   * the user authenticates with must be linked to the specified YouTube content
   * owner.
   * @return Google_Service_YouTube_LiveBroadcast
   */
  public function transition($broadcastStatus, $id, $part, $optParams = array())
  {
    $params = array('broadcastStatus' => $broadcastStatus, 'id' => $id, 'part' => $part);
    $params = array_merge($params, $optParams);
    return $this->call('transition', array($params), "Google_Service_YouTube_LiveBroadcast");
  }

  /**
   * Updates a broadcast. For example, you could modify the broadcast settings
   * defined in the liveBroadcast resource's contentDetails object.
   * (liveBroadcasts.update)
   *
   * @param string $part The part parameter serves two purposes in this operation.
   * It identifies the properties that the write operation will set as well as the
   * properties that the API response will include.
   *
   * The part properties that you can include in the parameter value are id,
   * snippet, contentDetails, and status.
   *
   * Note that this method will override the existing values for all of the
   * mutable properties that are contained in any parts that the parameter value
   * specifies. For example, a broadcast's privacy status is defined in the status
   * part. As such, if your request is updating a private or unlisted broadcast,
   * and the request's part parameter value includes the status part, the
   * broadcast's privacy setting will be updated to whatever value the request
   * body specifies. If the request body does not specify a value, the existing
   * privacy setting will be removed and the broadcast will revert to the default
   * privacy setting.
   * @param Google_LiveBroadcast $postBody
   * @param array $optParams Optional parameters.
   *
   * @opt_param string onBehalfOfContentOwnerChannel This parameter can only be
   * used in a properly authorized request. Note: This parameter is intended
   * exclusively for YouTube content partners.
   *
   * The onBehalfOfContentOwnerChannel parameter specifies the YouTube channel ID
   * of the channel to which a video is being added. This parameter is required
   * when a request specifies a value for the onBehalfOfContentOwner parameter,
   * and it can only be used in conjunction with that parameter. In addition, the
   * request must be authorized using a CMS account that is linked to the content
   * owner that the onBehalfOfContentOwner parameter specifies. Finally, the
   * channel that the onBehalfOfContentOwnerChannel parameter value specifies must
   * be linked to the content owner that the onBehalfOfContentOwner parameter
   * specifies.
   *
   * This parameter is intended for YouTube content partners that own and manage
   * many different YouTube channels. It allows content owners to authenticate
   * once and perform actions on behalf of the channel specified in the parameter
   * value, without having to provide authentication credentials for each separate
   * channel.
   * @opt_param string onBehalfOfContentOwner Note: This parameter is intended
   * exclusively for YouTube content partners.
   *
   * The onBehalfOfContentOwner parameter indicates that the request's
   * authorization credentials identify a YouTube CMS user who is acting on behalf
   * of the content owner specified in the parameter value. This parameter is
   * intended for YouTube content partners that own and manage many different
   * YouTube channels. It allows content owners to authenticate once and get
   * access to all their video and channel data, without having to provide
   * authentication credentials for each individual channel. The CMS account that
   * the user authenticates with must be linked to the specified YouTube content
   * owner.
   * @return Google_Service_YouTube_LiveBroadcast
   */
  public function update($part, Google_Service_YouTube_LiveBroadcast $postBody, $optParams = array())
  {
    $params = array('part' => $part, 'postBody' => $postBody);
    $params = array_merge($params, $optParams);
    return $this->call('update', array($params), "Google_Service_YouTube_LiveBroadcast");
  }
}

/**
 * The "liveStreams" collection of methods.
 * Typical usage is:
 *  <code>
 *   $youtubeService = new Google_Service_YouTube(...);
 *   $liveStreams = $youtubeService->liveStreams;
 *  </code>
 */
class Google_Service_YouTube_LiveStreams_Resource extends Google_Service_Resource
{

  /**
   * Deletes a video stream. (liveStreams.delete)
   *
   * @param string $id The id parameter specifies the YouTube live stream ID for
   * the resource that is being deleted.
   * @param array $optParams Optional parameters.
   *
   * @opt_param string onBehalfOfContentOwnerChannel This parameter can only be
   * used in a properly authorized request. Note: This parameter is intended
   * exclusively for YouTube content partners.
   *
   * The onBehalfOfContentOwnerChannel parameter specifies the YouTube channel ID
   * of the channel to which a video is being added. This parameter is required
   * when a request specifies a value for the onBehalfOfContentOwner parameter,
   * and it can only be used in conjunction with that parameter. In addition, the
   * request must be authorized using a CMS account that is linked to the content
   * owner that the onBehalfOfContentOwner parameter specifies. Finally, the
   * channel that the onBehalfOfContentOwnerChannel parameter value specifies must
   * be linked to the content owner that the onBehalfOfContentOwner parameter
   * specifies.
   *
   * This parameter is intended for YouTube content partners that own and manage
   * many different YouTube channels. It allows content owners to authenticate
   * once and perform actions on behalf of the channel specified in the parameter
   * value, without having to provide authentication credentials for each separate
   * channel.
   * @opt_param string onBehalfOfContentOwner Note: This parameter is intended
   * exclusively for YouTube content partners.
   *
   * The onBehalfOfContentOwner parameter indicates that the request's
   * authorization credentials identify a YouTube CMS user who is acting on behalf
   * of the content owner specified in the parameter value. This parameter is
   * intended for YouTube content partners that own and manage many different
   * YouTube channels. It allows content owners to authenticate once and get
   * access to all their video and channel data, without having to provide
   * authentication credentials for each individual channel. The CMS account that
   * the user authenticates with must be linked to the specified YouTube content
   * owner.
   */
  public function delete($id, $optParams = array())
  {
    $params = array('id' => $id);
    $params = array_merge($params, $optParams);
    return $this->call('delete', array($params));
  }

  /**
   * Creates a video stream. The stream enables you to send your video to YouTube,
   * which can then broadcast the video to your audience. (liveStreams.insert)
   *
   * @param string $part The part parameter serves two purposes in this operation.
   * It identifies the properties that the write operation will set as well as the
   * properties that the API response will include.
   *
   * The part properties that you can include in the parameter value are id,
   * snippet, cdn, and status.
   * @param Google_LiveStream $postBody
   * @param array $optParams Optional parameters.
   *
   * @opt_param string onBehalfOfContentOwnerChannel This parameter can only be
   * used in a properly authorized request. Note: This parameter is intended
   * exclusively for YouTube content partners.
   *
   * The onBehalfOfContentOwnerChannel parameter specifies the YouTube channel ID
   * of the channel to which a video is being added. This parameter is required
   * when a request specifies a value for the onBehalfOfContentOwner parameter,
   * and it can only be used in conjunction with that parameter. In addition, the
   * request must be authorized using a CMS account that is linked to the content
   * owner that the onBehalfOfContentOwner parameter specifies. Finally, the
   * channel that the onBehalfOfContentOwnerChannel parameter value specifies must
   * be linked to the content owner that the onBehalfOfContentOwner parameter
   * specifies.
   *
   * This parameter is intended for YouTube content partners that own and manage
   * many different YouTube channels. It allows content owners to authenticate
   * once and perform actions on behalf of the channel specified in the parameter
   * value, without having to provide authentication credentials for each separate
   * channel.
   * @opt_param string onBehalfOfContentOwner Note: This parameter is intended
   * exclusively for YouTube content partners.
   *
   * The onBehalfOfContentOwner parameter indicates that the request's
   * authorization credentials identify a YouTube CMS user who is acting on behalf
   * of the content owner specified in the parameter value. This parameter is
   * intended for YouTube content partners that own and manage many different
   * YouTube channels. It allows content owners to authenticate once and get
   * access to all their video and channel data, without having to provide
   * authentication credentials for each individual channel. The CMS account that
   * the user authenticates with must be linked to the specified YouTube content
   * owner.
   * @return Google_Service_YouTube_LiveStream
   */
  public function insert($part, Google_Service_YouTube_LiveStream $postBody, $optParams = array())
  {
    $params = array('part' => $part, 'postBody' => $postBody);
    $params = array_merge($params, $optParams);
    return $this->call('insert', array($params), "Google_Service_YouTube_LiveStream");
  }

  /**
   * Returns a list of video streams that match the API request parameters.
   * (liveStreams.listLiveStreams)
   *
   * @param string $part The part parameter specifies a comma-separated list of
   * one or more liveStream resource properties that the API response will
   * include. The part names that you can include in the parameter value are id,
   * snippet, cdn, and status.
   * @param array $optParams Optional parameters.
   *
   * @opt_param string onBehalfOfContentOwner Note: This parameter is intended
   * exclusively for YouTube content partners.
   *
   * The onBehalfOfContentOwner parameter indicates that the request's
   * authorization credentials identify a YouTube CMS user who is acting on behalf
   * of the content owner specified in the parameter value. This parameter is
   * intended for YouTube content partners that own and manage many different
   * YouTube channels. It allows content owners to authenticate once and get
   * access to all their video and channel data, without having to provide
   * authentication credentials for each individual channel. The CMS account that
   * the user authenticates with must be linked to the specified YouTube content
   * owner.
   * @opt_param string onBehalfOfContentOwnerChannel This parameter can only be
   * used in a properly authorized request. Note: This parameter is intended
   * exclusively for YouTube content partners.
   *
   * The onBehalfOfContentOwnerChannel parameter specifies the YouTube channel ID
   * of the channel to which a video is being added. This parameter is required
   * when a request specifies a value for the onBehalfOfContentOwner parameter,
   * and it can only be used in conjunction with that parameter. In addition, the
   * request must be authorized using a CMS account that is linked to the content
   * owner that the onBehalfOfContentOwner parameter specifies. Finally, the
   * channel that the onBehalfOfContentOwnerChannel parameter value specifies must
   * be linked to the content owner that the onBehalfOfContentOwner parameter
   * specifies.
   *
   * This parameter is intended for YouTube content partners that own and manage
   * many different YouTube channels. It allows content owners to authenticate
   * once and perform actions on behalf of the channel specified in the parameter
   * value, without having to provide authentication credentials for each separate
   * channel.
   * @opt_param bool mine The mine parameter can be used to instruct the API to
   * only return streams owned by the authenticated user. Set the parameter value
   * to true to only retrieve your own streams.
   * @opt_param string maxResults The maxResults parameter specifies the maximum
   * number of items that should be returned in the result set.
   * @opt_param string pageToken The pageToken parameter identifies a specific
   * page in the result set that should be returned. In an API response, the
   * nextPageToken and prevPageToken properties identify other pages that could be
   * retrieved.
   * @opt_param string id The id parameter specifies a comma-separated list of
   * YouTube stream IDs that identify the streams being retrieved. In a liveStream
   * resource, the id property specifies the stream's ID.
   * @return Google_Service_YouTube_LiveStreamListResponse
   */
  public function listLiveStreams($part, $optParams = array())
  {
    $params = array('part' => $part);
    $params = array_merge($params, $optParams);
    return $this->call('list', array($params), "Google_Service_YouTube_LiveStreamListResponse");
  }

  /**
   * Updates a video stream. If the properties that you want to change cannot be
   * updated, then you need to create a new stream with the proper settings.
   * (liveStreams.update)
   *
   * @param string $part The part parameter serves two purposes in this operation.
   * It identifies the properties that the write operation will set as well as the
   * properties that the API response will include.
   *
   * The part properties that you can include in the parameter value are id,
   * snippet, cdn, and status.
   *
   * Note that this method will override the existing values for all of the
   * mutable properties that are contained in any parts that the parameter value
   * specifies. If the request body does not specify a value for a mutable
   * property, the existing value for that property will be removed.
   * @param Google_LiveStream $postBody
   * @param array $optParams Optional parameters.
   *
   * @opt_param string onBehalfOfContentOwnerChannel This parameter can only be
   * used in a properly authorized request. Note: This parameter is intended
   * exclusively for YouTube content partners.
   *
   * The onBehalfOfContentOwnerChannel parameter specifies the YouTube channel ID
   * of the channel to which a video is being added. This parameter is required
   * when a request specifies a value for the onBehalfOfContentOwner parameter,
   * and it can only be used in conjunction with that parameter. In addition, the
   * request must be authorized using a CMS account that is linked to the content
   * owner that the onBehalfOfContentOwner parameter specifies. Finally, the
   * channel that the onBehalfOfContentOwnerChannel parameter value specifies must
   * be linked to the content owner that the onBehalfOfContentOwner parameter
   * specifies.
   *
   * This parameter is intended for YouTube content partners that own and manage
   * many different YouTube channels. It allows content owners to authenticate
   * once and perform actions on behalf of the channel specified in the parameter
   * value, without having to provide authentication credentials for each separate
   * channel.
   * @opt_param string onBehalfOfContentOwner Note: This parameter is intended
   * exclusively for YouTube content partners.
   *
   * The onBehalfOfContentOwner parameter indicates that the request's
   * authorization credentials identify a YouTube CMS user who is acting on behalf
   * of the content owner specified in the parameter value. This parameter is
   * intended for YouTube content partners that own and manage many different
   * YouTube channels. It allows content owners to authenticate once and get
   * access to all their video and channel data, without having to provide
   * authentication credentials for each individual channel. The CMS account that
   * the user authenticates with must be linked to the specified YouTube content
   * owner.
   * @return Google_Service_YouTube_LiveStream
   */
  public function update($part, Google_Service_YouTube_LiveStream $postBody, $optParams = array())
  {
    $params = array('part' => $part, 'postBody' => $postBody);
    $params = array_merge($params, $optParams);
    return $this->call('update', array($params), "Google_Service_YouTube_LiveStream");
  }
}

/**
 * The "playlistItems" collection of methods.
 * Typical usage is:
 *  <code>
 *   $youtubeService = new Google_Service_YouTube(...);
 *   $playlistItems = $youtubeService->playlistItems;
 *  </code>
 */
class Google_Service_YouTube_PlaylistItems_Resource extends Google_Service_Resource
{

  /**
   * Deletes a playlist item. (playlistItems.delete)
   *
   * @param string $id The id parameter specifies the YouTube playlist item ID for
   * the playlist item that is being deleted. In a playlistItem resource, the id
   * property specifies the playlist item's ID.
   * @param array $optParams Optional parameters.
   */
  public function delete($id, $optParams = array())
  {
    $params = array('id' => $id);
    $params = array_merge($params, $optParams);
    return $this->call('delete', array($params));
  }

  /**
   * Adds a resource to a playlist. (playlistItems.insert)
   *
   * @param string $part The part parameter serves two purposes in this operation.
   * It identifies the properties that the write operation will set as well as the
   * properties that the API response will include.
   * @param Google_PlaylistItem $postBody
   * @param array $optParams Optional parameters.
   *
   * @opt_param string onBehalfOfContentOwner Note: This parameter is intended
   * exclusively for YouTube content partners.
   *
   * The onBehalfOfContentOwner parameter indicates that the request's
   * authorization credentials identify a YouTube CMS user who is acting on behalf
   * of the content owner specified in the parameter value. This parameter is
   * intended for YouTube content partners that own and manage many different
   * YouTube channels. It allows content owners to authenticate once and get
   * access to all their video and channel data, without having to provide
   * authentication credentials for each individual channel. The CMS account that
   * the user authenticates with must be linked to the specified YouTube content
   * owner.
   * @return Google_Service_YouTube_PlaylistItem
   */
  public function insert($part, Google_Service_YouTube_PlaylistItem $postBody, $optParams = array())
  {
    $params = array('part' => $part, 'postBody' => $postBody);
    $params = array_merge($params, $optParams);
    return $this->call('insert', array($params), "Google_Service_YouTube_PlaylistItem");
  }

  /**
   * Returns a collection of playlist items that match the API request parameters.
   * You can retrieve all of the playlist items in a specified playlist or
   * retrieve one or more playlist items by their unique IDs.
   * (playlistItems.listPlaylistItems)
   *
   * @param string $part The part parameter specifies a comma-separated list of
   * one or more playlistItem resource properties that the API response will
   * include.
   *
   * If the parameter identifies a property that contains child properties, the
   * child properties will be included in the response. For example, in a
   * playlistItem resource, the snippet property contains numerous fields,
   * including the title, description, position, and resourceId properties. As
   * such, if you set part=snippet, the API response will contain all of those
   * properties.
   * @param array $optParams Optional parameters.
   *
   * @opt_param string onBehalfOfContentOwner Note: This parameter is intended
   * exclusively for YouTube content partners.
   *
   * The onBehalfOfContentOwner parameter indicates that the request's
   * authorization credentials identify a YouTube CMS user who is acting on behalf
   * of the content owner specified in the parameter value. This parameter is
   * intended for YouTube content partners that own and manage many different
   * YouTube channels. It allows content owners to authenticate once and get
   * access to all their video and channel data, without having to provide
   * authentication credentials for each individual channel. The CMS account that
   * the user authenticates with must be linked to the specified YouTube content
   * owner.
   * @opt_param string playlistId The playlistId parameter specifies the unique ID
   * of the playlist for which you want to retrieve playlist items. Note that even
   * though this is an optional parameter, every request to retrieve playlist
   * items must specify a value for either the id parameter or the playlistId
   * parameter.
   * @opt_param string videoId The videoId parameter specifies that the request
   * should return only the playlist items that contain the specified video.
   * @opt_param string maxResults The maxResults parameter specifies the maximum
   * number of items that should be returned in the result set.
   * @opt_param string pageToken The pageToken parameter identifies a specific
   * page in the result set that should be returned. In an API response, the
   * nextPageToken and prevPageToken properties identify other pages that could be
   * retrieved.
   * @opt_param string id The id parameter specifies a comma-separated list of one
   * or more unique playlist item IDs.
   * @return Google_Service_YouTube_PlaylistItemListResponse
   */
  public function listPlaylistItems($part, $optParams = array())
  {
    $params = array('part' => $part);
    $params = array_merge($params, $optParams);
    return $this->call('list', array($params), "Google_Service_YouTube_PlaylistItemListResponse");
  }

  /**
   * Modifies a playlist item. For example, you could update the item's position
   * in the playlist. (playlistItems.update)
   *
   * @param string $part The part parameter serves two purposes in this operation.
   * It identifies the properties that the write operation will set as well as the
   * properties that the API response will include.
   *
   * Note that this method will override the existing values for all of the
   * mutable properties that are contained in any parts that the parameter value
   * specifies. For example, a playlist item can specify a start time and end
   * time, which identify the times portion of the video that should play when
   * users watch the video in the playlist. If your request is updating a playlist
   * item that sets these values, and the request's part parameter value includes
   * the contentDetails part, the playlist item's start and end times will be
   * updated to whatever value the request body specifies. If the request body
   * does not specify values, the existing start and end times will be removed and
   * replaced with the default settings.
   * @param Google_PlaylistItem $postBody
   * @param array $optParams Optional parameters.
   * @return Google_Service_YouTube_PlaylistItem
   */
  public function update($part, Google_Service_YouTube_PlaylistItem $postBody, $optParams = array())
  {
    $params = array('part' => $part, 'postBody' => $postBody);
    $params = array_merge($params, $optParams);
    return $this->call('update', array($params), "Google_Service_YouTube_PlaylistItem");
  }
}

/**
 * The "playlists" collection of methods.
 * Typical usage is:
 *  <code>
 *   $youtubeService = new Google_Service_YouTube(...);
 *   $playlists = $youtubeService->playlists;
 *  </code>
 */
class Google_Service_YouTube_Playlists_Resource extends Google_Service_Resource
{

  /**
   * Deletes a playlist. (playlists.delete)
   *
   * @param string $id The id parameter specifies the YouTube playlist ID for the
   * playlist that is being deleted. In a playlist resource, the id property
   * specifies the playlist's ID.
   * @param array $optParams Optional parameters.
   *
   * @opt_param string onBehalfOfContentOwner Note: This parameter is intended
   * exclusively for YouTube content partners.
   *
   * The onBehalfOfContentOwner parameter indicates that the request's
   * authorization credentials identify a YouTube CMS user who is acting on behalf
   * of the content owner specified in the parameter value. This parameter is
   * intended for YouTube content partners that own and manage many different
   * YouTube channels. It allows content owners to authenticate once and get
   * access to all their video and channel data, without having to provide
   * authentication credentials for each individual channel. The CMS account that
   * the user authenticates with must be linked to the specified YouTube content
   * owner.
   */
  public function delete($id, $optParams = array())
  {
    $params = array('id' => $id);
    $params = array_merge($params, $optParams);
    return $this->call('delete', array($params));
  }

  /**
   * Creates a playlist. (playlists.insert)
   *
   * @param string $part The part parameter serves two purposes in this operation.
   * It identifies the properties that the write operation will set as well as the
   * properties that the API response will include.
   * @param Google_Playlist $postBody
   * @param array $optParams Optional parameters.
   *
   * @opt_param string onBehalfOfContentOwnerChannel This parameter can only be
   * used in a properly authorized request. Note: This parameter is intended
   * exclusively for YouTube content partners.
   *
   * The onBehalfOfContentOwnerChannel parameter specifies the YouTube channel ID
   * of the channel to which a video is being added. This parameter is required
   * when a request specifies a value for the onBehalfOfContentOwner parameter,
   * and it can only be used in conjunction with that parameter. In addition, the
   * request must be authorized using a CMS account that is linked to the content
   * owner that the onBehalfOfContentOwner parameter specifies. Finally, the
   * channel that the onBehalfOfContentOwnerChannel parameter value specifies must
   * be linked to the content owner that the onBehalfOfContentOwner parameter
   * specifies.
   *
   * This parameter is intended for YouTube content partners that own and manage
   * many different YouTube channels. It allows content owners to authenticate
   * once and perform actions on behalf of the channel specified in the parameter
   * value, without having to provide authentication credentials for each separate
   * channel.
   * @opt_param string onBehalfOfContentOwner Note: This parameter is intended
   * exclusively for YouTube content partners.
   *
   * The onBehalfOfContentOwner parameter indicates that the request's
   * authorization credentials identify a YouTube CMS user who is acting on behalf
   * of the content owner specified in the parameter value. This parameter is
   * intended for YouTube content partners that own and manage many different
   * YouTube channels. It allows content owners to authenticate once and get
   * access to all their video and channel data, without having to provide
   * authentication credentials for each individual channel. The CMS account that
   * the user authenticates with must be linked to the specified YouTube content
   * owner.
   * @return Google_Service_YouTube_Playlist
   */
  public function insert($part, Google_Service_YouTube_Playlist $postBody, $optParams = array())
  {
    $params = array('part' => $part, 'postBody' => $postBody);
    $params = array_merge($params, $optParams);
    return $this->call('insert', array($params), "Google_Service_YouTube_Playlist");
  }

  /**
   * Returns a collection of playlists that match the API request parameters. For
   * example, you can retrieve all playlists that the authenticated user owns, or
   * you can retrieve one or more playlists by their unique IDs.
   * (playlists.listPlaylists)
   *
   * @param string $part The part parameter specifies a comma-separated list of
   * one or more playlist resource properties that the API response will include.
   *
   * If the parameter identifies a property that contains child properties, the
   * child properties will be included in the response. For example, in a playlist
   * resource, the snippet property contains properties like author, title,
   * description, tags, and timeCreated. As such, if you set part=snippet, the API
   * response will contain all of those properties.
   * @param array $optParams Optional parameters.
   *
   * @opt_param string onBehalfOfContentOwner Note: This parameter is intended
   * exclusively for YouTube content partners.
   *
   * The onBehalfOfContentOwner parameter indicates that the request's
   * authorization credentials identify a YouTube CMS user who is acting on behalf
   * of the content owner specified in the parameter value. This parameter is
   * intended for YouTube content partners that own and manage many different
   * YouTube channels. It allows content owners to authenticate once and get
   * access to all their video and channel data, without having to provide
   * authentication credentials for each individual channel. The CMS account that
   * the user authenticates with must be linked to the specified YouTube content
   * owner.
   * @opt_param string onBehalfOfContentOwnerChannel This parameter can only be
   * used in a properly authorized request. Note: This parameter is intended
   * exclusively for YouTube content partners.
   *
   * The onBehalfOfContentOwnerChannel parameter specifies the YouTube channel ID
   * of the channel to which a video is being added. This parameter is required
   * when a request specifies a value for the onBehalfOfContentOwner parameter,
   * and it can only be used in conjunction with that parameter. In addition, the
   * request must be authorized using a CMS account that is linked to the content
   * owner that the onBehalfOfContentOwner parameter specifies. Finally, the
   * channel that the onBehalfOfContentOwnerChannel parameter value specifies must
   * be linked to the content owner that the onBehalfOfContentOwner parameter
   * specifies.
   *
   * This parameter is intended for YouTube content partners that own and manage
   * many different YouTube channels. It allows content owners to authenticate
   * once and perform actions on behalf of the channel specified in the parameter
   * value, without having to provide authentication credentials for each separate
   * channel.
   * @opt_param string channelId This value indicates that the API should only
   * return the specified channel's playlists.
   * @opt_param bool mine Set this parameter's value to true to instruct the API
   * to only return playlists owned by the authenticated user.
   * @opt_param string maxResults The maxResults parameter specifies the maximum
   * number of items that should be returned in the result set.
   * @opt_param string pageToken The pageToken parameter identifies a specific
   * page in the result set that should be returned. In an API response, the
   * nextPageToken and prevPageToken properties identify other pages that could be
   * retrieved.
   * @opt_param string hl The hl parameter should be used for filter out the
   * properties that are not in the given language. Used for the snippet part.
   * @opt_param string id The id parameter specifies a comma-separated list of the
   * YouTube playlist ID(s) for the resource(s) that are being retrieved. In a
   * playlist resource, the id property specifies the playlist's YouTube playlist
   * ID.
   * @return Google_Service_YouTube_PlaylistListResponse
   */
  public function listPlaylists($part, $optParams = array())
  {
    $params = array('part' => $part);
    $params = array_merge($params, $optParams);
    return $this->call('list', array($params), "Google_Service_YouTube_PlaylistListResponse");
  }

  /**
   * Modifies a playlist. For example, you could change a playlist's title,
   * description, or privacy status. (playlists.update)
   *
   * @param string $part The part parameter serves two purposes in this operation.
   * It identifies the properties that the write operation will set as well as the
   * properties that the API response will include.
   *
   * Note that this method will override the existing values for mutable
   * properties that are contained in any parts that the request body specifies.
   * For example, a playlist's description is contained in the snippet part, which
   * must be included in the request body. If the request does not specify a value
   * for the snippet.description property, the playlist's existing description
   * will be deleted.
   * @param Google_Playlist $postBody
   * @param array $optParams Optional parameters.
   *
   * @opt_param string onBehalfOfContentOwner Note: This parameter is intended
   * exclusively for YouTube content partners.
   *
   * The onBehalfOfContentOwner parameter indicates that the request's
   * authorization credentials identify a YouTube CMS user who is acting on behalf
   * of the content owner specified in the parameter value. This parameter is
   * intended for YouTube content partners that own and manage many different
   * YouTube channels. It allows content owners to authenticate once and get
   * access to all their video and channel data, without having to provide
   * authentication credentials for each individual channel. The CMS account that
   * the user authenticates with must be linked to the specified YouTube content
   * owner.
   * @return Google_Service_YouTube_Playlist
   */
  public function update($part, Google_Service_YouTube_Playlist $postBody, $optParams = array())
  {
    $params = array('part' => $part, 'postBody' => $postBody);
    $params = array_merge($params, $optParams);
    return $this->call('update', array($params), "Google_Service_YouTube_Playlist");
  }
}

/**
 * The "search" collection of methods.
 * Typical usage is:
 *  <code>
 *   $youtubeService = new Google_Service_YouTube(...);
 *   $search = $youtubeService->search;
 *  </code>
 */
class Google_Service_YouTube_Search_Resource extends Google_Service_Resource
{

  /**
   * Returns a collection of search results that match the query parameters
   * specified in the API request. By default, a search result set identifies
   * matching video, channel, and playlist resources, but you can also configure
   * queries to only retrieve a specific type of resource. (search.listSearch)
   *
   * @param string $part The part parameter specifies a comma-separated list of
   * one or more search resource properties that the API response will include.
   * Set the parameter value to snippet.
   * @param array $optParams Optional parameters.
   *
   * @opt_param string eventType The eventType parameter restricts a search to
   * broadcast events. If you specify a value for this parameter, you must also
   * set the type parameter's value to video.
   * @opt_param string channelId The channelId parameter indicates that the API
   * response should only contain resources created by the channel
   * @opt_param bool forDeveloper The forDeveloper parameter restricts the search
   * to only retrieve videos uploaded via the developer's application or website.
   * The API server uses the request's authorization credentials to identify the
   * developer. Therefore, a developer can restrict results to videos uploaded
   * through the developer's own app or website but not to videos uploaded through
   * other apps or sites.
   * @opt_param string videoSyndicated The videoSyndicated parameter lets you to
   * restrict a search to only videos that can be played outside youtube.com. If
   * you specify a value for this parameter, you must also set the type
   * parameter's value to video.
   * @opt_param string channelType The channelType parameter lets you restrict a
   * search to a particular type of channel.
   * @opt_param string videoCaption The videoCaption parameter indicates whether
   * the API should filter video search results based on whether they have
   * captions. If you specify a value for this parameter, you must also set the
   * type parameter's value to video.
   * @opt_param string publishedAfter The publishedAfter parameter indicates that
   * the API response should only contain resources created after the specified
   * time. The value is an RFC 3339 formatted date-time value
   * (1970-01-01T00:00:00Z).
   * @opt_param string onBehalfOfContentOwner Note: This parameter is intended
   * exclusively for YouTube content partners.
   *
   * The onBehalfOfContentOwner parameter indicates that the request's
   * authorization credentials identify a YouTube CMS user who is acting on behalf
   * of the content owner specified in the parameter value. This parameter is
   * intended for YouTube content partners that own and manage many different
   * YouTube channels. It allows content owners to authenticate once and get
   * access to all their video and channel data, without having to provide
   * authentication credentials for each individual channel. The CMS account that
   * the user authenticates with must be linked to the specified YouTube content
   * owner.
   * @opt_param string pageToken The pageToken parameter identifies a specific
   * page in the result set that should be returned. In an API response, the
   * nextPageToken and prevPageToken properties identify other pages that could be
   * retrieved.
   * @opt_param bool forContentOwner Note: This parameter is intended exclusively
   * for YouTube content partners.
   *
   * The forContentOwner parameter restricts the search to only retrieve resources
   * owned by the content owner specified by the onBehalfOfContentOwner parameter.
   * The user must be authenticated using a CMS account linked to the specified
   * content owner and onBehalfOfContentOwner must be provided.
   * @opt_param string regionCode The regionCode parameter instructs the API to
   * return search results for the specified country. The parameter value is an
   * ISO 3166-1 alpha-2 country code.
   * @opt_param string location The location parameter, in conjunction with the
   * locationRadius parameter, defines a circular geographic area and also
   * restricts a search to videos that specify, in their metadata, a geographic
   * location that falls within that area. The parameter value is a string that
   * specifies latitude/longitude coordinates e.g. (37.42307,-122.08427).
   *
   * - The location parameter value identifies the point at the center of the
   * area. - The locationRadius parameter specifies the maximum distance that the
   * location associated with a video can be from that point for the video to
   * still be included in the search results.The API returns an error if your
   * request specifies a value for the location parameter but does not also
   * specify a value for the locationRadius parameter.
   * @opt_param string locationRadius The locationRadius parameter, in conjunction
   * with the location parameter, defines a circular geographic area.
   *
   * The parameter value must be a floating point number followed by a measurement
   * unit. Valid measurement units are m, km, ft, and mi. For example, valid
   * parameter values include 1500m, 5km, 10000ft, and 0.75mi. The API does not
   * support locationRadius parameter values larger than 1000 kilometers.
   *
   * Note: See the definition of the location parameter for more information.
   * @opt_param string videoType The videoType parameter lets you restrict a
   * search to a particular type of videos. If you specify a value for this
   * parameter, you must also set the type parameter's value to video.
   * @opt_param string type The type parameter restricts a search query to only
   * retrieve a particular type of resource. The value is a comma-separated list
   * of resource types.
   * @opt_param string topicId The topicId parameter indicates that the API
   * response should only contain resources associated with the specified topic.
   * The value identifies a Freebase topic ID.
   * @opt_param string publishedBefore The publishedBefore parameter indicates
   * that the API response should only contain resources created before the
   * specified time. The value is an RFC 3339 formatted date-time value
   * (1970-01-01T00:00:00Z).
   * @opt_param string videoDimension The videoDimension parameter lets you
   * restrict a search to only retrieve 2D or 3D videos. If you specify a value
   * for this parameter, you must also set the type parameter's value to video.
   * @opt_param string videoLicense The videoLicense parameter filters search
   * results to only include videos with a particular license. YouTube lets video
   * uploaders choose to attach either the Creative Commons license or the
   * standard YouTube license to each of their videos. If you specify a value for
   * this parameter, you must also set the type parameter's value to video.
   * @opt_param string maxResults The maxResults parameter specifies the maximum
   * number of items that should be returned in the result set.
   * @opt_param string relatedToVideoId The relatedToVideoId parameter retrieves a
   * list of videos that are related to the video that the parameter value
   * identifies. The parameter value must be set to a YouTube video ID and, if you
   * are using this parameter, the type parameter must be set to video.
   * @opt_param string videoDefinition The videoDefinition parameter lets you
   * restrict a search to only include either high definition (HD) or standard
   * definition (SD) videos. HD videos are available for playback in at least
   * 720p, though higher resolutions, like 1080p, might also be available. If you
   * specify a value for this parameter, you must also set the type parameter's
   * value to video.
   * @opt_param string videoDuration The videoDuration parameter filters video
   * search results based on their duration. If you specify a value for this
   * parameter, you must also set the type parameter's value to video.
   * @opt_param string relevanceLanguage The relevanceLanguage parameter instructs
   * the API to return search results that are most relevant to the specified
   * language. The parameter value is typically an ISO 639-1 two-letter language
   * code. However, you should use the values zh-Hans for simplified Chinese and
   * zh-Hant for traditional Chinese. Please note that results in other languages
   * will still be returned if they are highly relevant to the search query term.
   * @opt_param bool forMine The forMine parameter restricts the search to only
   * retrieve videos owned by the authenticated user. If you set this parameter to
   * true, then the type parameter's value must also be set to video.
   * @opt_param string q The q parameter specifies the query term to search for.
   *
   * Your request can also use the Boolean NOT (-) and OR (|) operators to exclude
   * videos or to find videos that are associated with one of several search
   * terms. For example, to search for videos matching either "boating" or
   * "sailing", set the q parameter value to boating|sailing. Similarly, to search
   * for videos matching either "boating" or "sailing" but not "fishing", set the
   * q parameter value to boating|sailing -fishing. Note that the pipe character
   * must be URL-escaped when it is sent in your API request. The URL-escaped
   * value for the pipe character is %7C.
   * @opt_param string safeSearch The safeSearch parameter indicates whether the
   * search results should include restricted content as well as standard content.
   * @opt_param string videoEmbeddable The videoEmbeddable parameter lets you to
   * restrict a search to only videos that can be embedded into a webpage. If you
   * specify a value for this parameter, you must also set the type parameter's
   * value to video.
   * @opt_param string videoCategoryId The videoCategoryId parameter filters video
   * search results based on their category. If you specify a value for this
   * parameter, you must also set the type parameter's value to video.
   * @opt_param string order The order parameter specifies the method that will be
   * used to order resources in the API response.
   * @return Google_Service_YouTube_SearchListResponse
   */
  public function listSearch($part, $optParams = array())
  {
    $params = array('part' => $part);
    $params = array_merge($params, $optParams);
    return $this->call('list', array($params), "Google_Service_YouTube_SearchListResponse");
  }
}

/**
 * The "subscriptions" collection of methods.
 * Typical usage is:
 *  <code>
 *   $youtubeService = new Google_Service_YouTube(...);
 *   $subscriptions = $youtubeService->subscriptions;
 *  </code>
 */
class Google_Service_YouTube_Subscriptions_Resource extends Google_Service_Resource
{

  /**
   * Deletes a subscription. (subscriptions.delete)
   *
   * @param string $id The id parameter specifies the YouTube subscription ID for
   * the resource that is being deleted. In a subscription resource, the id
   * property specifies the YouTube subscription ID.
   * @param array $optParams Optional parameters.
   */
  public function delete($id, $optParams = array())
  {
    $params = array('id' => $id);
    $params = array_merge($params, $optParams);
    return $this->call('delete', array($params));
  }

  /**
   * Adds a subscription for the authenticated user's channel.
   * (subscriptions.insert)
   *
   * @param string $part The part parameter serves two purposes in this operation.
   * It identifies the properties that the write operation will set as well as the
   * properties that the API response will include.
   * @param Google_Subscription $postBody
   * @param array $optParams Optional parameters.
   * @return Google_Service_YouTube_Subscription
   */
  public function insert($part, Google_Service_YouTube_Subscription $postBody, $optParams = array())
  {
    $params = array('part' => $part, 'postBody' => $postBody);
    $params = array_merge($params, $optParams);
    return $this->call('insert', array($params), "Google_Service_YouTube_Subscription");
  }

  /**
   * Returns subscription resources that match the API request criteria.
   * (subscriptions.listSubscriptions)
   *
   * @param string $part The part parameter specifies a comma-separated list of
   * one or more subscription resource properties that the API response will
   * include.
   *
   * If the parameter identifies a property that contains child properties, the
   * child properties will be included in the response. For example, in a
   * subscription resource, the snippet property contains other properties, such
   * as a display title for the subscription. If you set part=snippet, the API
   * response will also contain all of those nested properties.
   * @param array $optParams Optional parameters.
   *
   * @opt_param string onBehalfOfContentOwner Note: This parameter is intended
   * exclusively for YouTube content partners.
   *
   * The onBehalfOfContentOwner parameter indicates that the request's
   * authorization credentials identify a YouTube CMS user who is acting on behalf
   * of the content owner specified in the parameter value. This parameter is
   * intended for YouTube content partners that own and manage many different
   * YouTube channels. It allows content owners to authenticate once and get
   * access to all their video and channel data, without having to provide
   * authentication credentials for each individual channel. The CMS account that
   * the user authenticates with must be linked to the specified YouTube content
   * owner.
   * @opt_param string onBehalfOfContentOwnerChannel This parameter can only be
   * used in a properly authorized request. Note: This parameter is intended
   * exclusively for YouTube content partners.
   *
   * The onBehalfOfContentOwnerChannel parameter specifies the YouTube channel ID
   * of the channel to which a video is being added. This parameter is required
   * when a request specifies a value for the onBehalfOfContentOwner parameter,
   * and it can only be used in conjunction with that parameter. In addition, the
   * request must be authorized using a CMS account that is linked to the content
   * owner that the onBehalfOfContentOwner parameter specifies. Finally, the
   * channel that the onBehalfOfContentOwnerChannel parameter value specifies must
   * be linked to the content owner that the onBehalfOfContentOwner parameter
   * specifies.
   *
   * This parameter is intended for YouTube content partners that own and manage
   * many different YouTube channels. It allows content owners to authenticate
   * once and perform actions on behalf of the channel specified in the parameter
   * value, without having to provide authentication credentials for each separate
   * channel.
   * @opt_param string channelId The channelId parameter specifies a YouTube
   * channel ID. The API will only return that channel's subscriptions.
   * @opt_param bool mine Set this parameter's value to true to retrieve a feed of
   * the authenticated user's subscriptions.
   * @opt_param string maxResults The maxResults parameter specifies the maximum
   * number of items that should be returned in the result set.
   * @opt_param string forChannelId The forChannelId parameter specifies a comma-
   * separated list of channel IDs. The API response will then only contain
   * subscriptions matching those channels.
   * @opt_param string pageToken The pageToken parameter identifies a specific
   * page in the result set that should be returned. In an API response, the
   * nextPageToken and prevPageToken properties identify other pages that could be
   * retrieved.
   * @opt_param bool mySubscribers Set this parameter's value to true to retrieve
   * a feed of the subscribers of the authenticated user.
   * @opt_param string order The order parameter specifies the method that will be
   * used to sort resources in the API response.
   * @opt_param string id The id parameter specifies a comma-separated list of the
   * YouTube subscription ID(s) for the resource(s) that are being retrieved. In a
   * subscription resource, the id property specifies the YouTube subscription ID.
   * @return Google_Service_YouTube_SubscriptionListResponse
   */
  public function listSubscriptions($part, $optParams = array())
  {
    $params = array('part' => $part);
    $params = array_merge($params, $optParams);
    return $this->call('list', array($params), "Google_Service_YouTube_SubscriptionListResponse");
  }
}

/**
 * The "thumbnails" collection of methods.
 * Typical usage is:
 *  <code>
 *   $youtubeService = new Google_Service_YouTube(...);
 *   $thumbnails = $youtubeService->thumbnails;
 *  </code>
 */
class Google_Service_YouTube_Thumbnails_Resource extends Google_Service_Resource
{

  /**
   * Uploads a custom video thumbnail to YouTube and sets it for a video.
   * (thumbnails.set)
   *
   * @param string $videoId The videoId parameter specifies a YouTube video ID for
   * which the custom video thumbnail is being provided.
   * @param array $optParams Optional parameters.
   *
   * @opt_param string onBehalfOfContentOwner Note: This parameter is intended
   * exclusively for YouTube content partners.
   *
   * The onBehalfOfContentOwner parameter indicates that the request's
   * authorization credentials identify a YouTube CMS user who is acting on behalf
   * of the content owner specified in the parameter value. This parameter is
   * intended for YouTube content partners that own and manage many different
   * YouTube channels. It allows content owners to authenticate once and get
   * access to all their video and channel data, without having to provide
   * authentication credentials for each individual channel. The actual CMS
   * account that the user authenticates with must be linked to the specified
   * YouTube content owner.
   * @return Google_Service_YouTube_ThumbnailSetResponse
   */
  public function set($videoId, $optParams = array())
  {
    $params = array('videoId' => $videoId);
    $params = array_merge($params, $optParams);
    return $this->call('set', array($params), "Google_Service_YouTube_ThumbnailSetResponse");
  }
}

/**
 * The "videoAbuseReportReasons" collection of methods.
 * Typical usage is:
 *  <code>
 *   $youtubeService = new Google_Service_YouTube(...);
 *   $videoAbuseReportReasons = $youtubeService->videoAbuseReportReasons;
 *  </code>
 */
class Google_Service_YouTube_VideoAbuseReportReasons_Resource extends Google_Service_Resource
{

  /**
   * Returns a list of abuse reasons that can be used for reporting abusive
   * videos. (videoAbuseReportReasons.listVideoAbuseReportReasons)
   *
   * @param string $part The part parameter specifies the videoCategory resource
   * parts that the API response will include. Supported values are id and
   * snippet.
   * @param array $optParams Optional parameters.
   *
   * @opt_param string hl The hl parameter specifies the language that should be
   * used for text values in the API response.
   * @return Google_Service_YouTube_VideoAbuseReportReasonListResponse
   */
  public function listVideoAbuseReportReasons($part, $optParams = array())
  {
    $params = array('part' => $part);
    $params = array_merge($params, $optParams);
    return $this->call('list', array($params), "Google_Service_YouTube_VideoAbuseReportReasonListResponse");
  }
}

/**
 * The "videoCategories" collection of methods.
 * Typical usage is:
 *  <code>
 *   $youtubeService = new Google_Service_YouTube(...);
 *   $videoCategories = $youtubeService->videoCategories;
 *  </code>
 */
class Google_Service_YouTube_VideoCategories_Resource extends Google_Service_Resource
{

  /**
   * Returns a list of categories that can be associated with YouTube videos.
   * (videoCategories.listVideoCategories)
   *
   * @param string $part The part parameter specifies the videoCategory resource
   * properties that the API response will include. Set the parameter value to
   * snippet.
   * @param array $optParams Optional parameters.
   *
   * @opt_param string regionCode The regionCode parameter instructs the API to
   * return the list of video categories available in the specified country. The
   * parameter value is an ISO 3166-1 alpha-2 country code.
   * @opt_param string id The id parameter specifies a comma-separated list of
   * video category IDs for the resources that you are retrieving.
   * @opt_param string hl The hl parameter specifies the language that should be
   * used for text values in the API response.
   * @return Google_Service_YouTube_VideoCategoryListResponse
   */
  public function listVideoCategories($part, $optParams = array())
  {
    $params = array('part' => $part);
    $params = array_merge($params, $optParams);
    return $this->call('list', array($params), "Google_Service_YouTube_VideoCategoryListResponse");
  }
}

/**
 * The "videos" collection of methods.
 * Typical usage is:
 *  <code>
 *   $youtubeService = new Google_Service_YouTube(...);
 *   $videos = $youtubeService->videos;
 *  </code>
 */
class Google_Service_YouTube_Videos_Resource extends Google_Service_Resource
{

  /**
   * Deletes a YouTube video. (videos.delete)
   *
   * @param string $id The id parameter specifies the YouTube video ID for the
   * resource that is being deleted. In a video resource, the id property
   * specifies the video's ID.
   * @param array $optParams Optional parameters.
   *
   * @opt_param string onBehalfOfContentOwner Note: This parameter is intended
   * exclusively for YouTube content partners.
   *
   * The onBehalfOfContentOwner parameter indicates that the request's
   * authorization credentials identify a YouTube CMS user who is acting on behalf
   * of the content owner specified in the parameter value. This parameter is
   * intended for YouTube content partners that own and manage many different
   * YouTube channels. It allows content owners to authenticate once and get
   * access to all their video and channel data, without having to provide
   * authentication credentials for each individual channel. The actual CMS
   * account that the user authenticates with must be linked to the specified
   * YouTube content owner.
   */
  public function delete($id, $optParams = array())
  {
    $params = array('id' => $id);
    $params = array_merge($params, $optParams);
    return $this->call('delete', array($params));
  }

  /**
   * Retrieves the ratings that the authorized user gave to a list of specified
   * videos. (videos.getRating)
   *
   * @param string $id The id parameter specifies a comma-separated list of the
   * YouTube video ID(s) for the resource(s) for which you are retrieving rating
   * data. In a video resource, the id property specifies the video's ID.
   * @param array $optParams Optional parameters.
   *
   * @opt_param string onBehalfOfContentOwner Note: This parameter is intended
   * exclusively for YouTube content partners.
   *
   * The onBehalfOfContentOwner parameter indicates that the request's
   * authorization credentials identify a YouTube CMS user who is acting on behalf
   * of the content owner specified in the parameter value. This parameter is
   * intended for YouTube content partners that own and manage many different
   * YouTube channels. It allows content owners to authenticate once and get
   * access to all their video and channel data, without having to provide
   * authentication credentials for each individual channel. The CMS account that
   * the user authenticates with must be linked to the specified YouTube content
   * owner.
   * @return Google_Service_YouTube_VideoGetRatingResponse
   */
  public function getRating($id, $optParams = array())
  {
    $params = array('id' => $id);
    $params = array_merge($params, $optParams);
    return $this->call('getRating', array($params), "Google_Service_YouTube_VideoGetRatingResponse");
  }

  /**
   * Uploads a video to YouTube and optionally sets the video's metadata.
   * (videos.insert)
   *
   * @param string $part The part parameter serves two purposes in this operation.
   * It identifies the properties that the write operation will set as well as the
   * properties that the API response will include.
   *
   * Note that not all parts contain properties that can be set when inserting or
   * updating a video. For example, the statistics object encapsulates statistics
   * that YouTube calculates for a video and does not contain values that you can
   * set or modify. If the parameter value specifies a part that does not contain
   * mutable values, that part will still be included in the API response.
   * @param Google_Video $postBody
   * @param array $optParams Optional parameters.
   *
   * @opt_param string onBehalfOfContentOwner Note: This parameter is intended
   * exclusively for YouTube content partners.
   *
   * The onBehalfOfContentOwner parameter indicates that the request's
   * authorization credentials identify a YouTube CMS user who is acting on behalf
   * of the content owner specified in the parameter value. This parameter is
   * intended for YouTube content partners that own and manage many different
   * YouTube channels. It allows content owners to authenticate once and get
   * access to all their video and channel data, without having to provide
   * authentication credentials for each individual channel. The CMS account that
   * the user authenticates with must be linked to the specified YouTube content
   * owner.
   * @opt_param bool stabilize The stabilize parameter indicates whether YouTube
   * should adjust the video to remove shaky camera motions.
   * @opt_param string onBehalfOfContentOwnerChannel This parameter can only be
   * used in a properly authorized request. Note: This parameter is intended
   * exclusively for YouTube content partners.
   *
   * The onBehalfOfContentOwnerChannel parameter specifies the YouTube channel ID
   * of the channel to which a video is being added. This parameter is required
   * when a request specifies a value for the onBehalfOfContentOwner parameter,
   * and it can only be used in conjunction with that parameter. In addition, the
   * request must be authorized using a CMS account that is linked to the content
   * owner that the onBehalfOfContentOwner parameter specifies. Finally, the
   * channel that the onBehalfOfContentOwnerChannel parameter value specifies must
   * be linked to the content owner that the onBehalfOfContentOwner parameter
   * specifies.
   *
   * This parameter is intended for YouTube content partners that own and manage
   * many different YouTube channels. It allows content owners to authenticate
   * once and perform actions on behalf of the channel specified in the parameter
   * value, without having to provide authentication credentials for each separate
   * channel.
   * @opt_param bool notifySubscribers The notifySubscribers parameter indicates
   * whether YouTube should send a notification about the new video to users who
   * subscribe to the video's channel. A parameter value of True indicates that
   * subscribers will be notified of newly uploaded videos. However, a channel
   * owner who is uploading many videos might prefer to set the value to False to
   * avoid sending a notification about each new video to the channel's
   * subscribers.
   * @opt_param bool autoLevels The autoLevels parameter indicates whether YouTube
   * should automatically enhance the video's lighting and color.
   * @return Google_Service_YouTube_Video
   */
  public function insert($part, Google_Service_YouTube_Video $postBody, $optParams = array())
  {
    $params = array('part' => $part, 'postBody' => $postBody);
    $params = array_merge($params, $optParams);
    return $this->call('insert', array($params), "Google_Service_YouTube_Video");
  }

  /**
   * Returns a list of videos that match the API request parameters.
   * (videos.listVideos)
   *
   * @param string $part The part parameter specifies a comma-separated list of
   * one or more video resource properties that the API response will include.
   *
   * If the parameter identifies a property that contains child properties, the
   * child properties will be included in the response. For example, in a video
   * resource, the snippet property contains the channelId, title, description,
   * tags, and categoryId properties. As such, if you set part=snippet, the API
   * response will contain all of those properties.
   * @param array $optParams Optional parameters.
   *
   * @opt_param string onBehalfOfContentOwner Note: This parameter is intended
   * exclusively for YouTube content partners.
   *
   * The onBehalfOfContentOwner parameter indicates that the request's
   * authorization credentials identify a YouTube CMS user who is acting on behalf
   * of the content owner specified in the parameter value. This parameter is
   * intended for YouTube content partners that own and manage many different
   * YouTube channels. It allows content owners to authenticate once and get
   * access to all their video and channel data, without having to provide
   * authentication credentials for each individual channel. The CMS account that
   * the user authenticates with must be linked to the specified YouTube content
   * owner.
   * @opt_param string regionCode The regionCode parameter instructs the API to
   * select a video chart available in the specified region. This parameter can
   * only be used in conjunction with the chart parameter. The parameter value is
   * an ISO 3166-1 alpha-2 country code.
   * @opt_param string locale DEPRECATED
   * @opt_param string videoCategoryId The videoCategoryId parameter identifies
   * the video category for which the chart should be retrieved. This parameter
   * can only be used in conjunction with the chart parameter. By default, charts
   * are not restricted to a particular category.
   * @opt_param string chart The chart parameter identifies the chart that you
   * want to retrieve.
   * @opt_param string maxResults The maxResults parameter specifies the maximum
   * number of items that should be returned in the result set.
   *
   * Note: This parameter is supported for use in conjunction with the myRating
   * parameter, but it is not supported for use in conjunction with the id
   * parameter.
   * @opt_param string pageToken The pageToken parameter identifies a specific
   * page in the result set that should be returned. In an API response, the
   * nextPageToken and prevPageToken properties identify other pages that could be
   * retrieved.
   *
   * Note: This parameter is supported for use in conjunction with the myRating
   * parameter, but it is not supported for use in conjunction with the id
   * parameter.
   * @opt_param string hl The hl parameter instructs the API to retrieve localized
   * resource metadata for a specific application language that the YouTube
   * website supports. The parameter value must be a language code included in the
   * list returned by the i18nLanguages.list method.
   *
   * If localized resource details are available in that language, the resource's
   * snippet.localized object will contain the localized values. However, if
   * localized details are not available, the snippet.localized object will
   * contain resource details in the resource's default language.
   * @opt_param string myRating Set this parameter's value to like or dislike to
   * instruct the API to only return videos liked or disliked by the authenticated
   * user.
   * @opt_param string id The id parameter specifies a comma-separated list of the
   * YouTube video ID(s) for the resource(s) that are being retrieved. In a video
   * resource, the id property specifies the video's ID.
   * @return Google_Service_YouTube_VideoListResponse
   */
  public function listVideos($part, $optParams = array())
  {
    $params = array('part' => $part);
    $params = array_merge($params, $optParams);
    return $this->call('list', array($params), "Google_Service_YouTube_VideoListResponse");
  }

  /**
   * Add a like or dislike rating to a video or remove a rating from a video.
   * (videos.rate)
   *
   * @param string $id The id parameter specifies the YouTube video ID of the
   * video that is being rated or having its rating removed.
   * @param string $rating Specifies the rating to record.
   * @param array $optParams Optional parameters.
   */
  public function rate($id, $rating, $optParams = array())
  {
    $params = array('id' => $id, 'rating' => $rating);
    $params = array_merge($params, $optParams);
    return $this->call('rate', array($params));
  }

  /**
   * Report abuse for a video. (videos.reportAbuse)
   *
   * @param Google_VideoAbuseReport $postBody
   * @param array $optParams Optional parameters.
   *
   * @opt_param string onBehalfOfContentOwner Note: This parameter is intended
   * exclusively for YouTube content partners.
   *
   * The onBehalfOfContentOwner parameter indicates that the request's
   * authorization credentials identify a YouTube CMS user who is acting on behalf
   * of the content owner specified in the parameter value. This parameter is
   * intended for YouTube content partners that own and manage many different
   * YouTube channels. It allows content owners to authenticate once and get
   * access to all their video and channel data, without having to provide
   * authentication credentials for each individual channel. The CMS account that
   * the user authenticates with must be linked to the specified YouTube content
   * owner.
   */
  public function reportAbuse(Google_Service_YouTube_VideoAbuseReport $postBody, $optParams = array())
  {
    $params = array('postBody' => $postBody);
    $params = array_merge($params, $optParams);
    return $this->call('reportAbuse', array($params));
  }

  /**
   * Updates a video's metadata. (videos.update)
   *
   * @param string $part The part parameter serves two purposes in this operation.
   * It identifies the properties that the write operation will set as well as the
   * properties that the API response will include.
   *
   * Note that this method will override the existing values for all of the
   * mutable properties that are contained in any parts that the parameter value
   * specifies. For example, a video's privacy setting is contained in the status
   * part. As such, if your request is updating a private video, and the request's
   * part parameter value includes the status part, the video's privacy setting
   * will be updated to whatever value the request body specifies. If the request
   * body does not specify a value, the existing privacy setting will be removed
   * and the video will revert to the default privacy setting.
   *
   * In addition, not all parts contain properties that can be set when inserting
   * or updating a video. For example, the statistics object encapsulates
   * statistics that YouTube calculates for a video and does not contain values
   * that you can set or modify. If the parameter value specifies a part that does
   * not contain mutable values, that part will still be included in the API
   * response.
   * @param Google_Video $postBody
   * @param array $optParams Optional parameters.
   *
   * @opt_param string onBehalfOfContentOwner Note: This parameter is intended
   * exclusively for YouTube content partners.
   *
   * The onBehalfOfContentOwner parameter indicates that the request's
   * authorization credentials identify a YouTube CMS user who is acting on behalf
   * of the content owner specified in the parameter value. This parameter is
   * intended for YouTube content partners that own and manage many different
   * YouTube channels. It allows content owners to authenticate once and get
   * access to all their video and channel data, without having to provide
   * authentication credentials for each individual channel. The actual CMS
   * account that the user authenticates with must be linked to the specified
   * YouTube content owner.
   * @return Google_Service_YouTube_Video
   */
  public function update($part, Google_Service_YouTube_Video $postBody, $optParams = array())
  {
    $params = array('part' => $part, 'postBody' => $postBody);
    $params = array_merge($params, $optParams);
    return $this->call('update', array($params), "Google_Service_YouTube_Video");
  }
}

/**
 * The "watermarks" collection of methods.
 * Typical usage is:
 *  <code>
 *   $youtubeService = new Google_Service_YouTube(...);
 *   $watermarks = $youtubeService->watermarks;
 *  </code>
 */
class Google_Service_YouTube_Watermarks_Resource extends Google_Service_Resource
{

  /**
   * Uploads a watermark image to YouTube and sets it for a channel.
   * (watermarks.set)
   *
   * @param string $channelId The channelId parameter specifies the YouTube
   * channel ID for which the watermark is being provided.
   * @param Google_InvideoBranding $postBody
   * @param array $optParams Optional parameters.
   *
   * @opt_param string onBehalfOfContentOwner Note: This parameter is intended
   * exclusively for YouTube content partners.
   *
   * The onBehalfOfContentOwner parameter indicates that the request's
   * authorization credentials identify a YouTube CMS user who is acting on behalf
   * of the content owner specified in the parameter value. This parameter is
   * intended for YouTube content partners that own and manage many different
   * YouTube channels. It allows content owners to authenticate once and get
   * access to all their video and channel data, without having to provide
   * authentication credentials for each individual channel. The CMS account that
   * the user authenticates with must be linked to the specified YouTube content
   * owner.
   */
  public function set($channelId, Google_Service_YouTube_InvideoBranding $postBody, $optParams = array())
  {
    $params = array('channelId' => $channelId, 'postBody' => $postBody);
    $params = array_merge($params, $optParams);
    return $this->call('set', array($params));
  }

  /**
   * Deletes a channel's watermark image. (watermarks.unsetWatermarks)
   *
   * @param string $channelId The channelId parameter specifies the YouTube
   * channel ID for which the watermark is being unset.
   * @param array $optParams Optional parameters.
   *
   * @opt_param string onBehalfOfContentOwner Note: This parameter is intended
   * exclusively for YouTube content partners.
   *
   * The onBehalfOfContentOwner parameter indicates that the request's
   * authorization credentials identify a YouTube CMS user who is acting on behalf
   * of the content owner specified in the parameter value. This parameter is
   * intended for YouTube content partners that own and manage many different
   * YouTube channels. It allows content owners to authenticate once and get
   * access to all their video and channel data, without having to provide
   * authentication credentials for each individual channel. The CMS account that
   * the user authenticates with must be linked to the specified YouTube content
   * owner.
   */
  public function unsetWatermarks($channelId, $optParams = array())
  {
    $params = array('channelId' => $channelId);
    $params = array_merge($params, $optParams);
    return $this->call('unset', array($params));
  }
}




class Google_Service_YouTube_AccessPolicy extends Google_Collection
{
  protected $collection_key = 'exception';
  protected $internal_gapi_mappings = array(
  );
  public $allowed;
  public $exception;


  public function setAllowed($allowed)
  {
    $this->allowed = $allowed;
  }
  public function getAllowed()
  {
    return $this->allowed;
  }
  public function setException($exception)
  {
    $this->exception = $exception;
  }
  public function getException()
  {
    return $this->exception;
  }
}

class Google_Service_YouTube_Activity extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  protected $contentDetailsType = 'Google_Service_YouTube_ActivityContentDetails';
  protected $contentDetailsDataType = '';
  public $etag;
  public $id;
  public $kind;
  protected $snippetType = 'Google_Service_YouTube_ActivitySnippet';
  protected $snippetDataType = '';


  public function setContentDetails(Google_Service_YouTube_ActivityContentDetails $contentDetails)
  {
    $this->contentDetails = $contentDetails;
  }
  public function getContentDetails()
  {
    return $this->contentDetails;
  }
  public function setEtag($etag)
  {
    $this->etag = $etag;
  }
  public function getEtag()
  {
    return $this->etag;
  }
  public function setId($id)
  {
    $this->id = $id;
  }
  public function getId()
  {
    return $this->id;
  }
  public function setKind($kind)
  {
    $this->kind = $kind;
  }
  public function getKind()
  {
    return $this->kind;
  }
  public function setSnippet(Google_Service_YouTube_ActivitySnippet $snippet)
  {
    $this->snippet = $snippet;
  }
  public function getSnippet()
  {
    return $this->snippet;
  }
}

class Google_Service_YouTube_ActivityContentDetails extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  protected $bulletinType = 'Google_Service_YouTube_ActivityContentDetailsBulletin';
  protected $bulletinDataType = '';
  protected $channelItemType = 'Google_Service_YouTube_ActivityContentDetailsChannelItem';
  protected $channelItemDataType = '';
  protected $commentType = 'Google_Service_YouTube_ActivityContentDetailsComment';
  protected $commentDataType = '';
  protected $favoriteType = 'Google_Service_YouTube_ActivityContentDetailsFavorite';
  protected $favoriteDataType = '';
  protected $likeType = 'Google_Service_YouTube_ActivityContentDetailsLike';
  protected $likeDataType = '';
  protected $playlistItemType = 'Google_Service_YouTube_ActivityContentDetailsPlaylistItem';
  protected $playlistItemDataType = '';
  protected $promotedItemType = 'Google_Service_YouTube_ActivityContentDetailsPromotedItem';
  protected $promotedItemDataType = '';
  protected $recommendationType = 'Google_Service_YouTube_ActivityContentDetailsRecommendation';
  protected $recommendationDataType = '';
  protected $socialType = 'Google_Service_YouTube_ActivityContentDetailsSocial';
  protected $socialDataType = '';
  protected $subscriptionType = 'Google_Service_YouTube_ActivityContentDetailsSubscription';
  protected $subscriptionDataType = '';
  protected $uploadType = 'Google_Service_YouTube_ActivityContentDetailsUpload';
  protected $uploadDataType = '';


  public function setBulletin(Google_Service_YouTube_ActivityContentDetailsBulletin $bulletin)
  {
    $this->bulletin = $bulletin;
  }
  public function getBulletin()
  {
    return $this->bulletin;
  }
  public function setChannelItem(Google_Service_YouTube_ActivityContentDetailsChannelItem $channelItem)
  {
    $this->channelItem = $channelItem;
  }
  public function getChannelItem()
  {
    return $this->channelItem;
  }
  public function setComment(Google_Service_YouTube_ActivityContentDetailsComment $comment)
  {
    $this->comment = $comment;
  }
  public function getComment()
  {
    return $this->comment;
  }
  public function setFavorite(Google_Service_YouTube_ActivityContentDetailsFavorite $favorite)
  {
    $this->favorite = $favorite;
  }
  public function getFavorite()
  {
    return $this->favorite;
  }
  public function setLike(Google_Service_YouTube_ActivityContentDetailsLike $like)
  {
    $this->like = $like;
  }
  public function getLike()
  {
    return $this->like;
  }
  public function setPlaylistItem(Google_Service_YouTube_ActivityContentDetailsPlaylistItem $playlistItem)
  {
    $this->playlistItem = $playlistItem;
  }
  public function getPlaylistItem()
  {
    return $this->playlistItem;
  }
  public function setPromotedItem(Google_Service_YouTube_ActivityContentDetailsPromotedItem $promotedItem)
  {
    $this->promotedItem = $promotedItem;
  }
  public function getPromotedItem()
  {
    return $this->promotedItem;
  }
  public function setRecommendation(Google_Service_YouTube_ActivityContentDetailsRecommendation $recommendation)
  {
    $this->recommendation = $recommendation;
  }
  public function getRecommendation()
  {
    return $this->recommendation;
  }
  public function setSocial(Google_Service_YouTube_ActivityContentDetailsSocial $social)
  {
    $this->social = $social;
  }
  public function getSocial()
  {
    return $this->social;
  }
  public function setSubscription(Google_Service_YouTube_ActivityContentDetailsSubscription $subscription)
  {
    $this->subscription = $subscription;
  }
  public function getSubscription()
  {
    return $this->subscription;
  }
  public function setUpload(Google_Service_YouTube_ActivityContentDetailsUpload $upload)
  {
    $this->upload = $upload;
  }
  public function getUpload()
  {
    return $this->upload;
  }
}

class Google_Service_YouTube_ActivityContentDetailsBulletin extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  protected $resourceIdType = 'Google_Service_YouTube_ResourceId';
  protected $resourceIdDataType = '';


  public function setResourceId(Google_Service_YouTube_ResourceId $resourceId)
  {
    $this->resourceId = $resourceId;
  }
  public function getResourceId()
  {
    return $this->resourceId;
  }
}

class Google_Service_YouTube_ActivityContentDetailsChannelItem extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  protected $resourceIdType = 'Google_Service_YouTube_ResourceId';
  protected $resourceIdDataType = '';


  public function setResourceId(Google_Service_YouTube_ResourceId $resourceId)
  {
    $this->resourceId = $resourceId;
  }
  public function getResourceId()
  {
    return $this->resourceId;
  }
}

class Google_Service_YouTube_ActivityContentDetailsComment extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  protected $resourceIdType = 'Google_Service_YouTube_ResourceId';
  protected $resourceIdDataType = '';


  public function setResourceId(Google_Service_YouTube_ResourceId $resourceId)
  {
    $this->resourceId = $resourceId;
  }
  public function getResourceId()
  {
    return $this->resourceId;
  }
}

class Google_Service_YouTube_ActivityContentDetailsFavorite extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  protected $resourceIdType = 'Google_Service_YouTube_ResourceId';
  protected $resourceIdDataType = '';


  public function setResourceId(Google_Service_YouTube_ResourceId $resourceId)
  {
    $this->resourceId = $resourceId;
  }
  public function getResourceId()
  {
    return $this->resourceId;
  }
}

class Google_Service_YouTube_ActivityContentDetailsLike extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  protected $resourceIdType = 'Google_Service_YouTube_ResourceId';
  protected $resourceIdDataType = '';


  public function setResourceId(Google_Service_YouTube_ResourceId $resourceId)
  {
    $this->resourceId = $resourceId;
  }
  public function getResourceId()
  {
    return $this->resourceId;
  }
}

class Google_Service_YouTube_ActivityContentDetailsPlaylistItem extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  public $playlistId;
  public $playlistItemId;
  protected $resourceIdType = 'Google_Service_YouTube_ResourceId';
  protected $resourceIdDataType = '';


  public function setPlaylistId($playlistId)
  {
    $this->playlistId = $playlistId;
  }
  public function getPlaylistId()
  {
    return $this->playlistId;
  }
  public function setPlaylistItemId($playlistItemId)
  {
    $this->playlistItemId = $playlistItemId;
  }
  public function getPlaylistItemId()
  {
    return $this->playlistItemId;
  }
  public function setResourceId(Google_Service_YouTube_ResourceId $resourceId)
  {
    $this->resourceId = $resourceId;
  }
  public function getResourceId()
  {
    return $this->resourceId;
  }
}

class Google_Service_YouTube_ActivityContentDetailsPromotedItem extends Google_Collection
{
  protected $collection_key = 'impressionUrl';
  protected $internal_gapi_mappings = array(
  );
  public $adTag;
  public $clickTrackingUrl;
  public $creativeViewUrl;
  public $ctaType;
  public $customCtaButtonText;
  public $descriptionText;
  public $destinationUrl;
  public $forecastingUrl;
  public $impressionUrl;
  public $videoId;


  public function setAdTag($adTag)
  {
    $this->adTag = $adTag;
  }
  public function getAdTag()
  {
    return $this->adTag;
  }
  public function setClickTrackingUrl($clickTrackingUrl)
  {
    $this->clickTrackingUrl = $clickTrackingUrl;
  }
  public function getClickTrackingUrl()
  {
    return $this->clickTrackingUrl;
  }
  public function setCreativeViewUrl($creativeViewUrl)
  {
    $this->creativeViewUrl = $creativeViewUrl;
  }
  public function getCreativeViewUrl()
  {
    return $this->creativeViewUrl;
  }
  public function setCtaType($ctaType)
  {
    $this->ctaType = $ctaType;
  }
  public function getCtaType()
  {
    return $this->ctaType;
  }
  public function setCustomCtaButtonText($customCtaButtonText)
  {
    $this->customCtaButtonText = $customCtaButtonText;
  }
  public function getCustomCtaButtonText()
  {
    return $this->customCtaButtonText;
  }
  public function setDescriptionText($descriptionText)
  {
    $this->descriptionText = $descriptionText;
  }
  public function getDescriptionText()
  {
    return $this->descriptionText;
  }
  public function setDestinationUrl($destinationUrl)
  {
    $this->destinationUrl = $destinationUrl;
  }
  public function getDestinationUrl()
  {
    return $this->destinationUrl;
  }
  public function setForecastingUrl($forecastingUrl)
  {
    $this->forecastingUrl = $forecastingUrl;
  }
  public function getForecastingUrl()
  {
    return $this->forecastingUrl;
  }
  public function setImpressionUrl($impressionUrl)
  {
    $this->impressionUrl = $impressionUrl;
  }
  public function getImpressionUrl()
  {
    return $this->impressionUrl;
  }
  public function setVideoId($videoId)
  {
    $this->videoId = $videoId;
  }
  public function getVideoId()
  {
    return $this->videoId;
  }
}

class Google_Service_YouTube_ActivityContentDetailsRecommendation extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  public $reason;
  protected $resourceIdType = 'Google_Service_YouTube_ResourceId';
  protected $resourceIdDataType = '';
  protected $seedResourceIdType = 'Google_Service_YouTube_ResourceId';
  protected $seedResourceIdDataType = '';


  public function setReason($reason)
  {
    $this->reason = $reason;
  }
  public function getReason()
  {
    return $this->reason;
  }
  public function setResourceId(Google_Service_YouTube_ResourceId $resourceId)
  {
    $this->resourceId = $resourceId;
  }
  public function getResourceId()
  {
    return $this->resourceId;
  }
  public function setSeedResourceId(Google_Service_YouTube_ResourceId $seedResourceId)
  {
    $this->seedResourceId = $seedResourceId;
  }
  public function getSeedResourceId()
  {
    return $this->seedResourceId;
  }
}

class Google_Service_YouTube_ActivityContentDetailsSocial extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  public $author;
  public $imageUrl;
  public $referenceUrl;
  protected $resourceIdType = 'Google_Service_YouTube_ResourceId';
  protected $resourceIdDataType = '';
  public $type;


  public function setAuthor($author)
  {
    $this->author = $author;
  }
  public function getAuthor()
  {
    return $this->author;
  }
  public function setImageUrl($imageUrl)
  {
    $this->imageUrl = $imageUrl;
  }
  public function getImageUrl()
  {
    return $this->imageUrl;
  }
  public function setReferenceUrl($referenceUrl)
  {
    $this->referenceUrl = $referenceUrl;
  }
  public function getReferenceUrl()
  {
    return $this->referenceUrl;
  }
  public function setResourceId(Google_Service_YouTube_ResourceId $resourceId)
  {
    $this->resourceId = $resourceId;
  }
  public function getResourceId()
  {
    return $this->resourceId;
  }
  public function setType($type)
  {
    $this->type = $type;
  }
  public function getType()
  {
    return $this->type;
  }
}

class Google_Service_YouTube_ActivityContentDetailsSubscription extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  protected $resourceIdType = 'Google_Service_YouTube_ResourceId';
  protected $resourceIdDataType = '';


  public function setResourceId(Google_Service_YouTube_ResourceId $resourceId)
  {
    $this->resourceId = $resourceId;
  }
  public function getResourceId()
  {
    return $this->resourceId;
  }
}

class Google_Service_YouTube_ActivityContentDetailsUpload extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  public $videoId;


  public function setVideoId($videoId)
  {
    $this->videoId = $videoId;
  }
  public function getVideoId()
  {
    return $this->videoId;
  }
}

class Google_Service_YouTube_ActivityListResponse extends Google_Collection
{
  protected $collection_key = 'items';
  protected $internal_gapi_mappings = array(
  );
  public $etag;
  public $eventId;
  protected $itemsType = 'Google_Service_YouTube_Activity';
  protected $itemsDataType = 'array';
  public $kind;
  public $nextPageToken;
  protected $pageInfoType = 'Google_Service_YouTube_PageInfo';
  protected $pageInfoDataType = '';
  public $prevPageToken;
  protected $tokenPaginationType = 'Google_Service_YouTube_TokenPagination';
  protected $tokenPaginationDataType = '';
  public $visitorId;


  public function setEtag($etag)
  {
    $this->etag = $etag;
  }
  public function getEtag()
  {
    return $this->etag;
  }
  public function setEventId($eventId)
  {
    $this->eventId = $eventId;
  }
  public function getEventId()
  {
    return $this->eventId;
  }
  public function setItems($items)
  {
    $this->items = $items;
  }
  public function getItems()
  {
    return $this->items;
  }
  public function setKind($kind)
  {
    $this->kind = $kind;
  }
  public function getKind()
  {
    return $this->kind;
  }
  public function setNextPageToken($nextPageToken)
  {
    $this->nextPageToken = $nextPageToken;
  }
  public function getNextPageToken()
  {
    return $this->nextPageToken;
  }
  public function setPageInfo(Google_Service_YouTube_PageInfo $pageInfo)
  {
    $this->pageInfo = $pageInfo;
  }
  public function getPageInfo()
  {
    return $this->pageInfo;
  }
  public function setPrevPageToken($prevPageToken)
  {
    $this->prevPageToken = $prevPageToken;
  }
  public function getPrevPageToken()
  {
    return $this->prevPageToken;
  }
  public function setTokenPagination(Google_Service_YouTube_TokenPagination $tokenPagination)
  {
    $this->tokenPagination = $tokenPagination;
  }
  public function getTokenPagination()
  {
    return $this->tokenPagination;
  }
  public function setVisitorId($visitorId)
  {
    $this->visitorId = $visitorId;
  }
  public function getVisitorId()
  {
    return $this->visitorId;
  }
}

class Google_Service_YouTube_ActivitySnippet extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  public $channelId;
  public $channelTitle;
  public $description;
  public $groupId;
  public $publishedAt;
  protected $thumbnailsType = 'Google_Service_YouTube_ThumbnailDetails';
  protected $thumbnailsDataType = '';
  public $title;
  public $type;


  public function setChannelId($channelId)
  {
    $this->channelId = $channelId;
  }
  public function getChannelId()
  {
    return $this->channelId;
  }
  public function setChannelTitle($channelTitle)
  {
    $this->channelTitle = $channelTitle;
  }
  public function getChannelTitle()
  {
    return $this->channelTitle;
  }
  public function setDescription($description)
  {
    $this->description = $description;
  }
  public function getDescription()
  {
    return $this->description;
  }
  public function setGroupId($groupId)
  {
    $this->groupId = $groupId;
  }
  public function getGroupId()
  {
    return $this->groupId;
  }
  public function setPublishedAt($publishedAt)
  {
    $this->publishedAt = $publishedAt;
  }
  public function getPublishedAt()
  {
    return $this->publishedAt;
  }
  public function setThumbnails(Google_Service_YouTube_ThumbnailDetails $thumbnails)
  {
    $this->thumbnails = $thumbnails;
  }
  public function getThumbnails()
  {
    return $this->thumbnails;
  }
  public function setTitle($title)
  {
    $this->title = $title;
  }
  public function getTitle()
  {
    return $this->title;
  }
  public function setType($type)
  {
    $this->type = $type;
  }
  public function getType()
  {
    return $this->type;
  }
}

class Google_Service_YouTube_Caption extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  public $etag;
  public $id;
  public $kind;
  protected $snippetType = 'Google_Service_YouTube_CaptionSnippet';
  protected $snippetDataType = '';


  public function setEtag($etag)
  {
    $this->etag = $etag;
  }
  public function getEtag()
  {
    return $this->etag;
  }
  public function setId($id)
  {
    $this->id = $id;
  }
  public function getId()
  {
    return $this->id;
  }
  public function setKind($kind)
  {
    $this->kind = $kind;
  }
  public function getKind()
  {
    return $this->kind;
  }
  public function setSnippet(Google_Service_YouTube_CaptionSnippet $snippet)
  {
    $this->snippet = $snippet;
  }
  public function getSnippet()
  {
    return $this->snippet;
  }
}

class Google_Service_YouTube_CaptionListResponse extends Google_Collection
{
  protected $collection_key = 'items';
  protected $internal_gapi_mappings = array(
  );
  public $etag;
  public $eventId;
  protected $itemsType = 'Google_Service_YouTube_Caption';
  protected $itemsDataType = 'array';
  public $kind;
  public $visitorId;


  public function setEtag($etag)
  {
    $this->etag = $etag;
  }
  public function getEtag()
  {
    return $this->etag;
  }
  public function setEventId($eventId)
  {
    $this->eventId = $eventId;
  }
  public function getEventId()
  {
    return $this->eventId;
  }
  public function setItems($items)
  {
    $this->items = $items;
  }
  public function getItems()
  {
    return $this->items;
  }
  public function setKind($kind)
  {
    $this->kind = $kind;
  }
  public function getKind()
  {
    return $this->kind;
  }
  public function setVisitorId($visitorId)
  {
    $this->visitorId = $visitorId;
  }
  public function getVisitorId()
  {
    return $this->visitorId;
  }
}

class Google_Service_YouTube_CaptionSnippet extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  public $audioTrackType;
  public $failureReason;
  public $isAutoSynced;
  public $isCC;
  public $isDraft;
  public $isEasyReader;
  public $isLarge;
  public $language;
  public $lastUpdated;
  public $name;
  public $status;
  public $trackKind;
  public $videoId;


  public function setAudioTrackType($audioTrackType)
  {
    $this->audioTrackType = $audioTrackType;
  }
  public function getAudioTrackType()
  {
    return $this->audioTrackType;
  }
  public function setFailureReason($failureReason)
  {
    $this->failureReason = $failureReason;
  }
  public function getFailureReason()
  {
    return $this->failureReason;
  }
  public function setIsAutoSynced($isAutoSynced)
  {
    $this->isAutoSynced = $isAutoSynced;
  }
  public function getIsAutoSynced()
  {
    return $this->isAutoSynced;
  }
  public function setIsCC($isCC)
  {
    $this->isCC = $isCC;
  }
  public function getIsCC()
  {
    return $this->isCC;
  }
  public function setIsDraft($isDraft)
  {
    $this->isDraft = $isDraft;
  }
  public function getIsDraft()
  {
    return $this->isDraft;
  }
  public function setIsEasyReader($isEasyReader)
  {
    $this->isEasyReader = $isEasyReader;
  }
  public function getIsEasyReader()
  {
    return $this->isEasyReader;
  }
  public function setIsLarge($isLarge)
  {
    $this->isLarge = $isLarge;
  }
  public function getIsLarge()
  {
    return $this->isLarge;
  }
  public function setLanguage($language)
  {
    $this->language = $language;
  }
  public function getLanguage()
  {
    return $this->language;
  }
  public function setLastUpdated($lastUpdated)
  {
    $this->lastUpdated = $lastUpdated;
  }
  public function getLastUpdated()
  {
    return $this->lastUpdated;
  }
  public function setName($name)
  {
    $this->name = $name;
  }
  public function getName()
  {
    return $this->name;
  }
  public function setStatus($status)
  {
    $this->status = $status;
  }
  public function getStatus()
  {
    return $this->status;
  }
  public function setTrackKind($trackKind)
  {
    $this->trackKind = $trackKind;
  }
  public function getTrackKind()
  {
    return $this->trackKind;
  }
  public function setVideoId($videoId)
  {
    $this->videoId = $videoId;
  }
  public function getVideoId()
  {
    return $this->videoId;
  }
}

class Google_Service_YouTube_CdnSettings extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  public $format;
  protected $ingestionInfoType = 'Google_Service_YouTube_IngestionInfo';
  protected $ingestionInfoDataType = '';
  public $ingestionType;


  public function setFormat($format)
  {
    $this->format = $format;
  }
  public function getFormat()
  {
    return $this->format;
  }
  public function setIngestionInfo(Google_Service_YouTube_IngestionInfo $ingestionInfo)
  {
    $this->ingestionInfo = $ingestionInfo;
  }
  public function getIngestionInfo()
  {
    return $this->ingestionInfo;
  }
  public function setIngestionType($ingestionType)
  {
    $this->ingestionType = $ingestionType;
  }
  public function getIngestionType()
  {
    return $this->ingestionType;
  }
}

class Google_Service_YouTube_Channel extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  protected $auditDetailsType = 'Google_Service_YouTube_ChannelAuditDetails';
  protected $auditDetailsDataType = '';
  protected $brandingSettingsType = 'Google_Service_YouTube_ChannelBrandingSettings';
  protected $brandingSettingsDataType = '';
  protected $contentDetailsType = 'Google_Service_YouTube_ChannelContentDetails';
  protected $contentDetailsDataType = '';
  protected $contentOwnerDetailsType = 'Google_Service_YouTube_ChannelContentOwnerDetails';
  protected $contentOwnerDetailsDataType = '';
  protected $conversionPingsType = 'Google_Service_YouTube_ChannelConversionPings';
  protected $conversionPingsDataType = '';
  public $etag;
  public $id;
  protected $invideoPromotionType = 'Google_Service_YouTube_InvideoPromotion';
  protected $invideoPromotionDataType = '';
  public $kind;
  protected $localizationsType = 'Google_Service_YouTube_ChannelLocalization';
  protected $localizationsDataType = 'map';
  protected $snippetType = 'Google_Service_YouTube_ChannelSnippet';
  protected $snippetDataType = '';
  protected $statisticsType = 'Google_Service_YouTube_ChannelStatistics';
  protected $statisticsDataType = '';
  protected $statusType = 'Google_Service_YouTube_ChannelStatus';
  protected $statusDataType = '';
  protected $topicDetailsType = 'Google_Service_YouTube_ChannelTopicDetails';
  protected $topicDetailsDataType = '';


  public function setAuditDetails(Google_Service_YouTube_ChannelAuditDetails $auditDetails)
  {
    $this->auditDetails = $auditDetails;
  }
  public function getAuditDetails()
  {
    return $this->auditDetails;
  }
  public function setBrandingSettings(Google_Service_YouTube_ChannelBrandingSettings $brandingSettings)
  {
    $this->brandingSettings = $brandingSettings;
  }
  public function getBrandingSettings()
  {
    return $this->brandingSettings;
  }
  public function setContentDetails(Google_Service_YouTube_ChannelContentDetails $contentDetails)
  {
    $this->contentDetails = $contentDetails;
  }
  public function getContentDetails()
  {
    return $this->contentDetails;
  }
  public function setContentOwnerDetails(Google_Service_YouTube_ChannelContentOwnerDetails $contentOwnerDetails)
  {
    $this->contentOwnerDetails = $contentOwnerDetails;
  }
  public function getContentOwnerDetails()
  {
    return $this->contentOwnerDetails;
  }
  public function setConversionPings(Google_Service_YouTube_ChannelConversionPings $conversionPings)
  {
    $this->conversionPings = $conversionPings;
  }
  public function getConversionPings()
  {
    return $this->conversionPings;
  }
  public function setEtag($etag)
  {
    $this->etag = $etag;
  }
  public function getEtag()
  {
    return $this->etag;
  }
  public function setId($id)
  {
    $this->id = $id;
  }
  public function getId()
  {
    return $this->id;
  }
  public function setInvideoPromotion(Google_Service_YouTube_InvideoPromotion $invideoPromotion)
  {
    $this->invideoPromotion = $invideoPromotion;
  }
  public function getInvideoPromotion()
  {
    return $this->invideoPromotion;
  }
  public function setKind($kind)
  {
    $this->kind = $kind;
  }
  public function getKind()
  {
    return $this->kind;
  }
  public function setLocalizations($localizations)
  {
    $this->localizations = $localizations;
  }
  public function getLocalizations()
  {
    return $this->localizations;
  }
  public function setSnippet(Google_Service_YouTube_ChannelSnippet $snippet)
  {
    $this->snippet = $snippet;
  }
  public function getSnippet()
  {
    return $this->snippet;
  }
  public function setStatistics(Google_Service_YouTube_ChannelStatistics $statistics)
  {
    $this->statistics = $statistics;
  }
  public function getStatistics()
  {
    return $this->statistics;
  }
  public function setStatus(Google_Service_YouTube_ChannelStatus $status)
  {
    $this->status = $status;
  }
  public function getStatus()
  {
    return $this->status;
  }
  public function setTopicDetails(Google_Service_YouTube_ChannelTopicDetails $topicDetails)
  {
    $this->topicDetails = $topicDetails;
  }
  public function getTopicDetails()
  {
    return $this->topicDetails;
  }
}

class Google_Service_YouTube_ChannelAuditDetails extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  public $communityGuidelinesGoodStanding;
  public $contentIdClaimsGoodStanding;
  public $copyrightStrikesGoodStanding;
  public $overallGoodStanding;


  public function setCommunityGuidelinesGoodStanding($communityGuidelinesGoodStanding)
  {
    $this->communityGuidelinesGoodStanding = $communityGuidelinesGoodStanding;
  }
  public function getCommunityGuidelinesGoodStanding()
  {
    return $this->communityGuidelinesGoodStanding;
  }
  public function setContentIdClaimsGoodStanding($contentIdClaimsGoodStanding)
  {
    $this->contentIdClaimsGoodStanding = $contentIdClaimsGoodStanding;
  }
  public function getContentIdClaimsGoodStanding()
  {
    return $this->contentIdClaimsGoodStanding;
  }
  public function setCopyrightStrikesGoodStanding($copyrightStrikesGoodStanding)
  {
    $this->copyrightStrikesGoodStanding = $copyrightStrikesGoodStanding;
  }
  public function getCopyrightStrikesGoodStanding()
  {
    return $this->copyrightStrikesGoodStanding;
  }
  public function setOverallGoodStanding($overallGoodStanding)
  {
    $this->overallGoodStanding = $overallGoodStanding;
  }
  public function getOverallGoodStanding()
  {
    return $this->overallGoodStanding;
  }
}

class Google_Service_YouTube_ChannelBannerResource extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  public $etag;
  public $kind;
  public $url;


  public function setEtag($etag)
  {
    $this->etag = $etag;
  }
  public function getEtag()
  {
    return $this->etag;
  }
  public function setKind($kind)
  {
    $this->kind = $kind;
  }
  public function getKind()
  {
    return $this->kind;
  }
  public function setUrl($url)
  {
    $this->url = $url;
  }
  public function getUrl()
  {
    return $this->url;
  }
}

class Google_Service_YouTube_ChannelBrandingSettings extends Google_Collection
{
  protected $collection_key = 'hints';
  protected $internal_gapi_mappings = array(
  );
  protected $channelType = 'Google_Service_YouTube_ChannelSettings';
  protected $channelDataType = '';
  protected $hintsType = 'Google_Service_YouTube_PropertyValue';
  protected $hintsDataType = 'array';
  protected $imageType = 'Google_Service_YouTube_ImageSettings';
  protected $imageDataType = '';
  protected $watchType = 'Google_Service_YouTube_WatchSettings';
  protected $watchDataType = '';


  public function setChannel(Google_Service_YouTube_ChannelSettings $channel)
  {
    $this->channel = $channel;
  }
  public function getChannel()
  {
    return $this->channel;
  }
  public function setHints($hints)
  {
    $this->hints = $hints;
  }
  public function getHints()
  {
    return $this->hints;
  }
  public function setImage(Google_Service_YouTube_ImageSettings $image)
  {
    $this->image = $image;
  }
  public function getImage()
  {
    return $this->image;
  }
  public function setWatch(Google_Service_YouTube_WatchSettings $watch)
  {
    $this->watch = $watch;
  }
  public function getWatch()
  {
    return $this->watch;
  }
}

class Google_Service_YouTube_ChannelContentDetails extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  public $googlePlusUserId;
  protected $relatedPlaylistsType = 'Google_Service_YouTube_ChannelContentDetailsRelatedPlaylists';
  protected $relatedPlaylistsDataType = '';


  public function setGooglePlusUserId($googlePlusUserId)
  {
    $this->googlePlusUserId = $googlePlusUserId;
  }
  public function getGooglePlusUserId()
  {
    return $this->googlePlusUserId;
  }
  public function setRelatedPlaylists(Google_Service_YouTube_ChannelContentDetailsRelatedPlaylists $relatedPlaylists)
  {
    $this->relatedPlaylists = $relatedPlaylists;
  }
  public function getRelatedPlaylists()
  {
    return $this->relatedPlaylists;
  }
}

class Google_Service_YouTube_ChannelContentDetailsRelatedPlaylists extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  public $favorites;
  public $likes;
  public $uploads;
  public $watchHistory;
  public $watchLater;


  public function setFavorites($favorites)
  {
    $this->favorites = $favorites;
  }
  public function getFavorites()
  {
    return $this->favorites;
  }
  public function setLikes($likes)
  {
    $this->likes = $likes;
  }
  public function getLikes()
  {
    return $this->likes;
  }
  public function setUploads($uploads)
  {
    $this->uploads = $uploads;
  }
  public function getUploads()
  {
    return $this->uploads;
  }
  public function setWatchHistory($watchHistory)
  {
    $this->watchHistory = $watchHistory;
  }
  public function getWatchHistory()
  {
    return $this->watchHistory;
  }
  public function setWatchLater($watchLater)
  {
    $this->watchLater = $watchLater;
  }
  public function getWatchLater()
  {
    return $this->watchLater;
  }
}

class Google_Service_YouTube_ChannelContentOwnerDetails extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  public $contentOwner;
  public $timeLinked;


  public function setContentOwner($contentOwner)
  {
    $this->contentOwner = $contentOwner;
  }
  public function getContentOwner()
  {
    return $this->contentOwner;
  }
  public function setTimeLinked($timeLinked)
  {
    $this->timeLinked = $timeLinked;
  }
  public function getTimeLinked()
  {
    return $this->timeLinked;
  }
}

class Google_Service_YouTube_ChannelConversionPing extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  public $context;
  public $conversionUrl;


  public function setContext($context)
  {
    $this->context = $context;
  }
  public function getContext()
  {
    return $this->context;
  }
  public function setConversionUrl($conversionUrl)
  {
    $this->conversionUrl = $conversionUrl;
  }
  public function getConversionUrl()
  {
    return $this->conversionUrl;
  }
}

class Google_Service_YouTube_ChannelConversionPings extends Google_Collection
{
  protected $collection_key = 'pings';
  protected $internal_gapi_mappings = array(
  );
  protected $pingsType = 'Google_Service_YouTube_ChannelConversionPing';
  protected $pingsDataType = 'array';


  public function setPings($pings)
  {
    $this->pings = $pings;
  }
  public function getPings()
  {
    return $this->pings;
  }
}

class Google_Service_YouTube_ChannelId extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  public $value;


  public function setValue($value)
  {
    $this->value = $value;
  }
  public function getValue()
  {
    return $this->value;
  }
}

class Google_Service_YouTube_ChannelListResponse extends Google_Collection
{
  protected $collection_key = 'items';
  protected $internal_gapi_mappings = array(
  );
  public $etag;
  public $eventId;
  protected $itemsType = 'Google_Service_YouTube_Channel';
  protected $itemsDataType = 'array';
  public $kind;
  public $nextPageToken;
  protected $pageInfoType = 'Google_Service_YouTube_PageInfo';
  protected $pageInfoDataType = '';
  public $prevPageToken;
  protected $tokenPaginationType = 'Google_Service_YouTube_TokenPagination';
  protected $tokenPaginationDataType = '';
  public $visitorId;


  public function setEtag($etag)
  {
    $this->etag = $etag;
  }
  public function getEtag()
  {
    return $this->etag;
  }
  public function setEventId($eventId)
  {
    $this->eventId = $eventId;
  }
  public function getEventId()
  {
    return $this->eventId;
  }
  public function setItems($items)
  {
    $this->items = $items;
  }
  public function getItems()
  {
    return $this->items;
  }
  public function setKind($kind)
  {
    $this->kind = $kind;
  }
  public function getKind()
  {
    return $this->kind;
  }
  public function setNextPageToken($nextPageToken)
  {
    $this->nextPageToken = $nextPageToken;
  }
  public function getNextPageToken()
  {
    return $this->nextPageToken;
  }
  public function setPageInfo(Google_Service_YouTube_PageInfo $pageInfo)
  {
    $this->pageInfo = $pageInfo;
  }
  public function getPageInfo()
  {
    return $this->pageInfo;
  }
  public function setPrevPageToken($prevPageToken)
  {
    $this->prevPageToken = $prevPageToken;
  }
  public function getPrevPageToken()
  {
    return $this->prevPageToken;
  }
  public function setTokenPagination(Google_Service_YouTube_TokenPagination $tokenPagination)
  {
    $this->tokenPagination = $tokenPagination;
  }
  public function getTokenPagination()
  {
    return $this->tokenPagination;
  }
  public function setVisitorId($visitorId)
  {
    $this->visitorId = $visitorId;
  }
  public function getVisitorId()
  {
    return $this->visitorId;
  }
}

class Google_Service_YouTube_ChannelLocalization extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  public $description;
  public $title;


  public function setDescription($description)
  {
    $this->description = $description;
  }
  public function getDescription()
  {
    return $this->description;
  }
  public function setTitle($title)
  {
    $this->title = $title;
  }
  public function getTitle()
  {
    return $this->title;
  }
}

class Google_Service_YouTube_ChannelLocalizations extends Google_Model
{
}

class Google_Service_YouTube_ChannelSection extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  protected $contentDetailsType = 'Google_Service_YouTube_ChannelSectionContentDetails';
  protected $contentDetailsDataType = '';
  public $etag;
  public $id;
  public $kind;
  protected $localizationsType = 'Google_Service_YouTube_ChannelSectionLocalization';
  protected $localizationsDataType = 'map';
  protected $snippetType = 'Google_Service_YouTube_ChannelSectionSnippet';
  protected $snippetDataType = '';
  protected $targetingType = 'Google_Service_YouTube_ChannelSectionTargeting';
  protected $targetingDataType = '';


  public function setContentDetails(Google_Service_YouTube_ChannelSectionContentDetails $contentDetails)
  {
    $this->contentDetails = $contentDetails;
  }
  public function getContentDetails()
  {
    return $this->contentDetails;
  }
  public function setEtag($etag)
  {
    $this->etag = $etag;
  }
  public function getEtag()
  {
    return $this->etag;
  }
  public function setId($id)
  {
    $this->id = $id;
  }
  public function getId()
  {
    return $this->id;
  }
  public function setKind($kind)
  {
    $this->kind = $kind;
  }
  public function getKind()
  {
    return $this->kind;
  }
  public function setLocalizations($localizations)
  {
    $this->localizations = $localizations;
  }
  public function getLocalizations()
  {
    return $this->localizations;
  }
  public function setSnippet(Google_Service_YouTube_ChannelSectionSnippet $snippet)
  {
    $this->snippet = $snippet;
  }
  public function getSnippet()
  {
    return $this->snippet;
  }
  public function setTargeting(Google_Service_YouTube_ChannelSectionTargeting $targeting)
  {
    $this->targeting = $targeting;
  }
  public function getTargeting()
  {
    return $this->targeting;
  }
}

class Google_Service_YouTube_ChannelSectionContentDetails extends Google_Collection
{
  protected $collection_key = 'playlists';
  protected $internal_gapi_mappings = array(
  );
  public $channels;
  public $playlists;


  public function setChannels($channels)
  {
    $this->channels = $channels;
  }
  public function getChannels()
  {
    return $this->channels;
  }
  public function setPlaylists($playlists)
  {
    $this->playlists = $playlists;
  }
  public function getPlaylists()
  {
    return $this->playlists;
  }
}

class Google_Service_YouTube_ChannelSectionListResponse extends Google_Collection
{
  protected $collection_key = 'items';
  protected $internal_gapi_mappings = array(
  );
  public $etag;
  public $eventId;
  protected $itemsType = 'Google_Service_YouTube_ChannelSection';
  protected $itemsDataType = 'array';
  public $kind;
  public $visitorId;


  public function setEtag($etag)
  {
    $this->etag = $etag;
  }
  public function getEtag()
  {
    return $this->etag;
  }
  public function setEventId($eventId)
  {
    $this->eventId = $eventId;
  }
  public function getEventId()
  {
    return $this->eventId;
  }
  public function setItems($items)
  {
    $this->items = $items;
  }
  public function getItems()
  {
    return $this->items;
  }
  public function setKind($kind)
  {
    $this->kind = $kind;
  }
  public function getKind()
  {
    return $this->kind;
  }
  public function setVisitorId($visitorId)
  {
    $this->visitorId = $visitorId;
  }
  public function getVisitorId()
  {
    return $this->visitorId;
  }
}

class Google_Service_YouTube_ChannelSectionLocalization extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  public $title;


  public function setTitle($title)
  {
    $this->title = $title;
  }
  public function getTitle()
  {
    return $this->title;
  }
}

class Google_Service_YouTube_ChannelSectionLocalizations extends Google_Model
{
}

class Google_Service_YouTube_ChannelSectionSnippet extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  public $channelId;
  public $defaultLanguage;
  protected $localizedType = 'Google_Service_YouTube_ChannelSectionLocalization';
  protected $localizedDataType = '';
  public $position;
  public $style;
  public $title;
  public $type;


  public function setChannelId($channelId)
  {
    $this->channelId = $channelId;
  }
  public function getChannelId()
  {
    return $this->channelId;
  }
  public function setDefaultLanguage($defaultLanguage)
  {
    $this->defaultLanguage = $defaultLanguage;
  }
  public function getDefaultLanguage()
  {
    return $this->defaultLanguage;
  }
  public function setLocalized(Google_Service_YouTube_ChannelSectionLocalization $localized)
  {
    $this->localized = $localized;
  }
  public function getLocalized()
  {
    return $this->localized;
  }
  public function setPosition($position)
  {
    $this->position = $position;
  }
  public function getPosition()
  {
    return $this->position;
  }
  public function setStyle($style)
  {
    $this->style = $style;
  }
  public function getStyle()
  {
    return $this->style;
  }
  public function setTitle($title)
  {
    $this->title = $title;
  }
  public function getTitle()
  {
    return $this->title;
  }
  public function setType($type)
  {
    $this->type = $type;
  }
  public function getType()
  {
    return $this->type;
  }
}

class Google_Service_YouTube_ChannelSectionTargeting extends Google_Collection
{
  protected $collection_key = 'regions';
  protected $internal_gapi_mappings = array(
  );
  public $countries;
  public $languages;
  public $regions;


  public function setCountries($countries)
  {
    $this->countries = $countries;
  }
  public function getCountries()
  {
    return $this->countries;
  }
  public function setLanguages($languages)
  {
    $this->languages = $languages;
  }
  public function getLanguages()
  {
    return $this->languages;
  }
  public function setRegions($regions)
  {
    $this->regions = $regions;
  }
  public function getRegions()
  {
    return $this->regions;
  }
}

class Google_Service_YouTube_ChannelSettings extends Google_Collection
{
  protected $collection_key = 'featuredChannelsUrls';
  protected $internal_gapi_mappings = array(
  );
  public $country;
  public $defaultLanguage;
  public $defaultTab;
  public $description;
  public $featuredChannelsTitle;
  public $featuredChannelsUrls;
  public $keywords;
  public $moderateComments;
  public $profileColor;
  public $showBrowseView;
  public $showRelatedChannels;
  public $title;
  public $trackingAnalyticsAccountId;
  public $unsubscribedTrailer;


  public function setCountry($country)
  {
    $this->country = $country;
  }
  public function getCountry()
  {
    return $this->country;
  }
  public function setDefaultLanguage($defaultLanguage)
  {
    $this->defaultLanguage = $defaultLanguage;
  }
  public function getDefaultLanguage()
  {
    return $this->defaultLanguage;
  }
  public function setDefaultTab($defaultTab)
  {
    $this->defaultTab = $defaultTab;
  }
  public function getDefaultTab()
  {
    return $this->defaultTab;
  }
  public function setDescription($description)
  {
    $this->description = $description;
  }
  public function getDescription()
  {
    return $this->description;
  }
  public function setFeaturedChannelsTitle($featuredChannelsTitle)
  {
    $this->featuredChannelsTitle = $featuredChannelsTitle;
  }
  public function getFeaturedChannelsTitle()
  {
    return $this->featuredChannelsTitle;
  }
  public function setFeaturedChannelsUrls($featuredChannelsUrls)
  {
    $this->featuredChannelsUrls = $featuredChannelsUrls;
  }
  public function getFeaturedChannelsUrls()
  {
    return $this->featuredChannelsUrls;
  }
  public function setKeywords($keywords)
  {
    $this->keywords = $keywords;
  }
  public function getKeywords()
  {
    return $this->keywords;
  }
  public function setModerateComments($moderateComments)
  {
    $this->moderateComments = $moderateComments;
  }
  public function getModerateComments()
  {
    return $this->moderateComments;
  }
  public function setProfileColor($profileColor)
  {
    $this->profileColor = $profileColor;
  }
  public function getProfileColor()
  {
    return $this->profileColor;
  }
  public function setShowBrowseView($showBrowseView)
  {
    $this->showBrowseView = $showBrowseView;
  }
  public function getShowBrowseView()
  {
    return $this->showBrowseView;
  }
  public function setShowRelatedChannels($showRelatedChannels)
  {
    $this->showRelatedChannels = $showRelatedChannels;
  }
  public function getShowRelatedChannels()
  {
    return $this->showRelatedChannels;
  }
  public function setTitle($title)
  {
    $this->title = $title;
  }
  public function getTitle()
  {
    return $this->title;
  }
  public function setTrackingAnalyticsAccountId($trackingAnalyticsAccountId)
  {
    $this->trackingAnalyticsAccountId = $trackingAnalyticsAccountId;
  }
  public function getTrackingAnalyticsAccountId()
  {
    return $this->trackingAnalyticsAccountId;
  }
  public function setUnsubscribedTrailer($unsubscribedTrailer)
  {
    $this->unsubscribedTrailer = $unsubscribedTrailer;
  }
  public function getUnsubscribedTrailer()
  {
    return $this->unsubscribedTrailer;
  }
}

class Google_Service_YouTube_ChannelSnippet extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  public $country;
  public $defaultLanguage;
  public $description;
  protected $localizedType = 'Google_Service_YouTube_ChannelLocalization';
  protected $localizedDataType = '';
  public $publishedAt;
  protected $thumbnailsType = 'Google_Service_YouTube_ThumbnailDetails';
  protected $thumbnailsDataType = '';
  public $title;


  public function setCountry($country)
  {
    $this->country = $country;
  }
  public function getCountry()
  {
    return $this->country;
  }
  public function setDefaultLanguage($defaultLanguage)
  {
    $this->defaultLanguage = $defaultLanguage;
  }
  public function getDefaultLanguage()
  {
    return $this->defaultLanguage;
  }
  public function setDescription($description)
  {
    $this->description = $description;
  }
  public function getDescription()
  {
    return $this->description;
  }
  public function setLocalized(Google_Service_YouTube_ChannelLocalization $localized)
  {
    $this->localized = $localized;
  }
  public function getLocalized()
  {
    return $this->localized;
  }
  public function setPublishedAt($publishedAt)
  {
    $this->publishedAt = $publishedAt;
  }
  public function getPublishedAt()
  {
    return $this->publishedAt;
  }
  public function setThumbnails(Google_Service_YouTube_ThumbnailDetails $thumbnails)
  {
    $this->thumbnails = $thumbnails;
  }
  public function getThumbnails()
  {
    return $this->thumbnails;
  }
  public function setTitle($title)
  {
    $this->title = $title;
  }
  public function getTitle()
  {
    return $this->title;
  }
}

class Google_Service_YouTube_ChannelStatistics extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  public $commentCount;
  public $hiddenSubscriberCount;
  public $subscriberCount;
  public $videoCount;
  public $viewCount;


  public function setCommentCount($commentCount)
  {
    $this->commentCount = $commentCount;
  }
  public function getCommentCount()
  {
    return $this->commentCount;
  }
  public function setHiddenSubscriberCount($hiddenSubscriberCount)
  {
    $this->hiddenSubscriberCount = $hiddenSubscriberCount;
  }
  public function getHiddenSubscriberCount()
  {
    return $this->hiddenSubscriberCount;
  }
  public function setSubscriberCount($subscriberCount)
  {
    $this->subscriberCount = $subscriberCount;
  }
  public function getSubscriberCount()
  {
    return $this->subscriberCount;
  }
  public function setVideoCount($videoCount)
  {
    $this->videoCount = $videoCount;
  }
  public function getVideoCount()
  {
    return $this->videoCount;
  }
  public function setViewCount($viewCount)
  {
    $this->viewCount = $viewCount;
  }
  public function getViewCount()
  {
    return $this->viewCount;
  }
}

class Google_Service_YouTube_ChannelStatus extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  public $isLinked;
  public $longUploadsStatus;
  public $privacyStatus;


  public function setIsLinked($isLinked)
  {
    $this->isLinked = $isLinked;
  }
  public function getIsLinked()
  {
    return $this->isLinked;
  }
  public function setLongUploadsStatus($longUploadsStatus)
  {
    $this->longUploadsStatus = $longUploadsStatus;
  }
  public function getLongUploadsStatus()
  {
    return $this->longUploadsStatus;
  }
  public function setPrivacyStatus($privacyStatus)
  {
    $this->privacyStatus = $privacyStatus;
  }
  public function getPrivacyStatus()
  {
    return $this->privacyStatus;
  }
}

class Google_Service_YouTube_ChannelTopicDetails extends Google_Collection
{
  protected $collection_key = 'topicIds';
  protected $internal_gapi_mappings = array(
  );
  public $topicIds;


  public function setTopicIds($topicIds)
  {
    $this->topicIds = $topicIds;
  }
  public function getTopicIds()
  {
    return $this->topicIds;
  }
}

class Google_Service_YouTube_Comment extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  public $etag;
  public $id;
  public $kind;
  protected $snippetType = 'Google_Service_YouTube_CommentSnippet';
  protected $snippetDataType = '';


  public function setEtag($etag)
  {
    $this->etag = $etag;
  }
  public function getEtag()
  {
    return $this->etag;
  }
  public function setId($id)
  {
    $this->id = $id;
  }
  public function getId()
  {
    return $this->id;
  }
  public function setKind($kind)
  {
    $this->kind = $kind;
  }
  public function getKind()
  {
    return $this->kind;
  }
  public function setSnippet(Google_Service_YouTube_CommentSnippet $snippet)
  {
    $this->snippet = $snippet;
  }
  public function getSnippet()
  {
    return $this->snippet;
  }
}

class Google_Service_YouTube_CommentListResponse extends Google_Collection
{
  protected $collection_key = 'items';
  protected $internal_gapi_mappings = array(
  );
  public $etag;
  public $eventId;
  protected $itemsType = 'Google_Service_YouTube_Comment';
  protected $itemsDataType = 'array';
  public $kind;
  public $nextPageToken;
  protected $pageInfoType = 'Google_Service_YouTube_PageInfo';
  protected $pageInfoDataType = '';
  protected $tokenPaginationType = 'Google_Service_YouTube_TokenPagination';
  protected $tokenPaginationDataType = '';
  public $visitorId;


  public function setEtag($etag)
  {
    $this->etag = $etag;
  }
  public function getEtag()
  {
    return $this->etag;
  }
  public function setEventId($eventId)
  {
    $this->eventId = $eventId;
  }
  public function getEventId()
  {
    return $this->eventId;
  }
  public function setItems($items)
  {
    $this->items = $items;
  }
  public function getItems()
  {
    return $this->items;
  }
  public function setKind($kind)
  {
    $this->kind = $kind;
  }
  public function getKind()
  {
    return $this->kind;
  }
  public function setNextPageToken($nextPageToken)
  {
    $this->nextPageToken = $nextPageToken;
  }
  public function getNextPageToken()
  {
    return $this->nextPageToken;
  }
  public function setPageInfo(Google_Service_YouTube_PageInfo $pageInfo)
  {
    $this->pageInfo = $pageInfo;
  }
  public function getPageInfo()
  {
    return $this->pageInfo;
  }
  public function setTokenPagination(Google_Service_YouTube_TokenPagination $tokenPagination)
  {
    $this->tokenPagination = $tokenPagination;
  }
  public function getTokenPagination()
  {
    return $this->tokenPagination;
  }
  public function setVisitorId($visitorId)
  {
    $this->visitorId = $visitorId;
  }
  public function getVisitorId()
  {
    return $this->visitorId;
  }
}

class Google_Service_YouTube_CommentSnippet extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  protected $authorChannelIdType = 'Google_Service_YouTube_ChannelId';
  protected $authorChannelIdDataType = '';
  public $authorChannelUrl;
  public $authorDisplayName;
  public $authorGoogleplusProfileUrl;
  public $authorProfileImageUrl;
  public $canRate;
  public $channelId;
  public $likeCount;
  public $moderationStatus;
  public $parentId;
  public $publishedAt;
  public $textDisplay;
  public $textOriginal;
  public $updatedAt;
  public $videoId;
  public $viewerRating;


  public function setAuthorChannelId(Google_Service_YouTube_ChannelId $authorChannelId)
  {
    $this->authorChannelId = $authorChannelId;
  }
  public function getAuthorChannelId()
  {
    return $this->authorChannelId;
  }
  public function setAuthorChannelUrl($authorChannelUrl)
  {
    $this->authorChannelUrl = $authorChannelUrl;
  }
  public function getAuthorChannelUrl()
  {
    return $this->authorChannelUrl;
  }
  public function setAuthorDisplayName($authorDisplayName)
  {
    $this->authorDisplayName = $authorDisplayName;
  }
  public function getAuthorDisplayName()
  {
    return $this->authorDisplayName;
  }
  public function setAuthorGoogleplusProfileUrl($authorGoogleplusProfileUrl)
  {
    $this->authorGoogleplusProfileUrl = $authorGoogleplusProfileUrl;
  }
  public function getAuthorGoogleplusProfileUrl()
  {
    return $this->authorGoogleplusProfileUrl;
  }
  public function setAuthorProfileImageUrl($authorProfileImageUrl)
  {
    $this->authorProfileImageUrl = $authorProfileImageUrl;
  }
  public function getAuthorProfileImageUrl()
  {
    return $this->authorProfileImageUrl;
  }
  public function setCanRate($canRate)
  {
    $this->canRate = $canRate;
  }
  public function getCanRate()
  {
    return $this->canRate;
  }
  public function setChannelId($channelId)
  {
    $this->channelId = $channelId;
  }
  public function getChannelId()
  {
    return $this->channelId;
  }
  public function setLikeCount($likeCount)
  {
    $this->likeCount = $likeCount;
  }
  public function getLikeCount()
  {
    return $this->likeCount;
  }
  public function setModerationStatus($moderationStatus)
  {
    $this->moderationStatus = $moderationStatus;
  }
  public function getModerationStatus()
  {
    return $this->moderationStatus;
  }
  public function setParentId($parentId)
  {
    $this->parentId = $parentId;
  }
  public function getParentId()
  {
    return $this->parentId;
  }
  public function setPublishedAt($publishedAt)
  {
    $this->publishedAt = $publishedAt;
  }
  public function getPublishedAt()
  {
    return $this->publishedAt;
  }
  public function setTextDisplay($textDisplay)
  {
    $this->textDisplay = $textDisplay;
  }
  public function getTextDisplay()
  {
    return $this->textDisplay;
  }
  public function setTextOriginal($textOriginal)
  {
    $this->textOriginal = $textOriginal;
  }
  public function getTextOriginal()
  {
    return $this->textOriginal;
  }
  public function setUpdatedAt($updatedAt)
  {
    $this->updatedAt = $updatedAt;
  }
  public function getUpdatedAt()
  {
    return $this->updatedAt;
  }
  public function setVideoId($videoId)
  {
    $this->videoId = $videoId;
  }
  public function getVideoId()
  {
    return $this->videoId;
  }
  public function setViewerRating($viewerRating)
  {
    $this->viewerRating = $viewerRating;
  }
  public function getViewerRating()
  {
    return $this->viewerRating;
  }
}

class Google_Service_YouTube_CommentThread extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  public $etag;
  public $id;
  public $kind;
  protected $repliesType = 'Google_Service_YouTube_CommentThreadReplies';
  protected $repliesDataType = '';
  protected $snippetType = 'Google_Service_YouTube_CommentThreadSnippet';
  protected $snippetDataType = '';


  public function setEtag($etag)
  {
    $this->etag = $etag;
  }
  public function getEtag()
  {
    return $this->etag;
  }
  public function setId($id)
  {
    $this->id = $id;
  }
  public function getId()
  {
    return $this->id;
  }
  public function setKind($kind)
  {
    $this->kind = $kind;
  }
  public function getKind()
  {
    return $this->kind;
  }
  public function setReplies(Google_Service_YouTube_CommentThreadReplies $replies)
  {
    $this->replies = $replies;
  }
  public function getReplies()
  {
    return $this->replies;
  }
  public function setSnippet(Google_Service_YouTube_CommentThreadSnippet $snippet)
  {
    $this->snippet = $snippet;
  }
  public function getSnippet()
  {
    return $this->snippet;
  }
}

class Google_Service_YouTube_CommentThreadListResponse extends Google_Collection
{
  protected $collection_key = 'items';
  protected $internal_gapi_mappings = array(
  );
  public $etag;
  public $eventId;
  protected $itemsType = 'Google_Service_YouTube_CommentThread';
  protected $itemsDataType = 'array';
  public $kind;
  public $nextPageToken;
  protected $pageInfoType = 'Google_Service_YouTube_PageInfo';
  protected $pageInfoDataType = '';
  protected $tokenPaginationType = 'Google_Service_YouTube_TokenPagination';
  protected $tokenPaginationDataType = '';
  public $visitorId;


  public function setEtag($etag)
  {
    $this->etag = $etag;
  }
  public function getEtag()
  {
    return $this->etag;
  }
  public function setEventId($eventId)
  {
    $this->eventId = $eventId;
  }
  public function getEventId()
  {
    return $this->eventId;
  }
  public function setItems($items)
  {
    $this->items = $items;
  }
  public function getItems()
  {
    return $this->items;
  }
  public function setKind($kind)
  {
    $this->kind = $kind;
  }
  public function getKind()
  {
    return $this->kind;
  }
  public function setNextPageToken($nextPageToken)
  {
    $this->nextPageToken = $nextPageToken;
  }
  public function getNextPageToken()
  {
    return $this->nextPageToken;
  }
  public function setPageInfo(Google_Service_YouTube_PageInfo $pageInfo)
  {
    $this->pageInfo = $pageInfo;
  }
  public function getPageInfo()
  {
    return $this->pageInfo;
  }
  public function setTokenPagination(Google_Service_YouTube_TokenPagination $tokenPagination)
  {
    $this->tokenPagination = $tokenPagination;
  }
  public function getTokenPagination()
  {
    return $this->tokenPagination;
  }
  public function setVisitorId($visitorId)
  {
    $this->visitorId = $visitorId;
  }
  public function getVisitorId()
  {
    return $this->visitorId;
  }
}

class Google_Service_YouTube_CommentThreadReplies extends Google_Collection
{
  protected $collection_key = 'comments';
  protected $internal_gapi_mappings = array(
  );
  protected $commentsType = 'Google_Service_YouTube_Comment';
  protected $commentsDataType = 'array';


  public function setComments($comments)
  {
    $this->comments = $comments;
  }
  public function getComments()
  {
    return $this->comments;
  }
}

class Google_Service_YouTube_CommentThreadSnippet extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  public $canReply;
  public $channelId;
  public $isPublic;
  protected $topLevelCommentType = 'Google_Service_YouTube_Comment';
  protected $topLevelCommentDataType = '';
  public $totalReplyCount;
  public $videoId;


  public function setCanReply($canReply)
  {
    $this->canReply = $canReply;
  }
  public function getCanReply()
  {
    return $this->canReply;
  }
  public function setChannelId($channelId)
  {
    $this->channelId = $channelId;
  }
  public function getChannelId()
  {
    return $this->channelId;
  }
  public function setIsPublic($isPublic)
  {
    $this->isPublic = $isPublic;
  }
  public function getIsPublic()
  {
    return $this->isPublic;
  }
  public function setTopLevelComment(Google_Service_YouTube_Comment $topLevelComment)
  {
    $this->topLevelComment = $topLevelComment;
  }
  public function getTopLevelComment()
  {
    return $this->topLevelComment;
  }
  public function setTotalReplyCount($totalReplyCount)
  {
    $this->totalReplyCount = $totalReplyCount;
  }
  public function getTotalReplyCount()
  {
    return $this->totalReplyCount;
  }
  public function setVideoId($videoId)
  {
    $this->videoId = $videoId;
  }
  public function getVideoId()
  {
    return $this->videoId;
  }
}

class Google_Service_YouTube_ContentRating extends Google_Collection
{
  protected $collection_key = 'djctqRatingReasons';
  protected $internal_gapi_mappings = array(
  );
  public $acbRating;
  public $agcomRating;
  public $anatelRating;
  public $bbfcRating;
  public $bfvcRating;
  public $bmukkRating;
  public $catvRating;
  public $catvfrRating;
  public $cbfcRating;
  public $cccRating;
  public $cceRating;
  public $chfilmRating;
  public $chvrsRating;
  public $cicfRating;
  public $cnaRating;
  public $cncRating;
  public $csaRating;
  public $cscfRating;
  public $czfilmRating;
  public $djctqRating;
  public $djctqRatingReasons;
  public $eefilmRating;
  public $egfilmRating;
  public $eirinRating;
  public $fcbmRating;
  public $fcoRating;
  public $fmocRating;
  public $fpbRating;
  public $fskRating;
  public $grfilmRating;
  public $icaaRating;
  public $ifcoRating;
  public $ilfilmRating;
  public $incaaRating;
  public $kfcbRating;
  public $kijkwijzerRating;
  public $kmrbRating;
  public $lsfRating;
  public $mccaaRating;
  public $mccypRating;
  public $mdaRating;
  public $medietilsynetRating;
  public $mekuRating;
  public $mibacRating;
  public $mocRating;
  public $moctwRating;
  public $mpaaRating;
  public $mtrcbRating;
  public $nbcRating;
  public $nbcplRating;
  public $nfrcRating;
  public $nfvcbRating;
  public $nkclvRating;
  public $oflcRating;
  public $pefilmRating;
  public $rcnofRating;
  public $resorteviolenciaRating;
  public $rtcRating;
  public $rteRating;
  public $russiaRating;
  public $skfilmRating;
  public $smaisRating;
  public $smsaRating;
  public $tvpgRating;
  public $ytRating;


  public function setAcbRating($acbRating)
  {
    $this->acbRating = $acbRating;
  }
  public function getAcbRating()
  {
    return $this->acbRating;
  }
  public function setAgcomRating($agcomRating)
  {
    $this->agcomRating = $agcomRating;
  }
  public function getAgcomRating()
  {
    return $this->agcomRating;
  }
  public function setAnatelRating($anatelRating)
  {
    $this->anatelRating = $anatelRating;
  }
  public function getAnatelRating()
  {
    return $this->anatelRating;
  }
  public function setBbfcRating($bbfcRating)
  {
    $this->bbfcRating = $bbfcRating;
  }
  public function getBbfcRating()
  {
    return $this->bbfcRating;
  }
  public function setBfvcRating($bfvcRating)
  {
    $this->bfvcRating = $bfvcRating;
  }
  public function getBfvcRating()
  {
    return $this->bfvcRating;
  }
  public function setBmukkRating($bmukkRating)
  {
    $this->bmukkRating = $bmukkRating;
  }
  public function getBmukkRating()
  {
    return $this->bmukkRating;
  }
  public function setCatvRating($catvRating)
  {
    $this->catvRating = $catvRating;
  }
  public function getCatvRating()
  {
    return $this->catvRating;
  }
  public function setCatvfrRating($catvfrRating)
  {
    $this->catvfrRating = $catvfrRating;
  }
  public function getCatvfrRating()
  {
    return $this->catvfrRating;
  }
  public function setCbfcRating($cbfcRating)
  {
    $this->cbfcRating = $cbfcRating;
  }
  public function getCbfcRating()
  {
    return $this->cbfcRating;
  }
  public function setCccRating($cccRating)
  {
    $this->cccRating = $cccRating;
  }
  public function getCccRating()
  {
    return $this->cccRating;
  }
  public function setCceRating($cceRating)
  {
    $this->cceRating = $cceRating;
  }
  public function getCceRating()
  {
    return $this->cceRating;
  }
  public function setChfilmRating($chfilmRating)
  {
    $this->chfilmRating = $chfilmRating;
  }
  public function getChfilmRating()
  {
    return $this->chfilmRating;
  }
  public function setChvrsRating($chvrsRating)
  {
    $this->chvrsRating = $chvrsRating;
  }
  public function getChvrsRating()
  {
    return $this->chvrsRating;
  }
  public function setCicfRating($cicfRating)
  {
    $this->cicfRating = $cicfRating;
  }
  public function getCicfRating()
  {
    return $this->cicfRating;
  }
  public function setCnaRating($cnaRating)
  {
    $this->cnaRating = $cnaRating;
  }
  public function getCnaRating()
  {
    return $this->cnaRating;
  }
  public function setCncRating($cncRating)
  {
    $this->cncRating = $cncRating;
  }
  public function getCncRating()
  {
    return $this->cncRating;
  }
  public function setCsaRating($csaRating)
  {
    $this->csaRating = $csaRating;
  }
  public function getCsaRating()
  {
    return $this->csaRating;
  }
  public function setCscfRating($cscfRating)
  {
    $this->cscfRating = $cscfRating;
  }
  public function getCscfRating()
  {
    return $this->cscfRating;
  }
  public function setCzfilmRating($czfilmRating)
  {
    $this->czfilmRating = $czfilmRating;
  }
  public function getCzfilmRating()
  {
    return $this->czfilmRating;
  }
  public function setDjctqRating($djctqRating)
  {
    $this->djctqRating = $djctqRating;
  }
  public function getDjctqRating()
  {
    return $this->djctqRating;
  }
  public function setDjctqRatingReasons($djctqRatingReasons)
  {
    $this->djctqRatingReasons = $djctqRatingReasons;
  }
  public function getDjctqRatingReasons()
  {
    return $this->djctqRatingReasons;
  }
  public function setEefilmRating($eefilmRating)
  {
    $this->eefilmRating = $eefilmRating;
  }
  public function getEefilmRating()
  {
    return $this->eefilmRating;
  }
  public function setEgfilmRating($egfilmRating)
  {
    $this->egfilmRating = $egfilmRating;
  }
  public function getEgfilmRating()
  {
    return $this->egfilmRating;
  }
  public function setEirinRating($eirinRating)
  {
    $this->eirinRating = $eirinRating;
  }
  public function getEirinRating()
  {
    return $this->eirinRating;
  }
  public function setFcbmRating($fcbmRating)
  {
    $this->fcbmRating = $fcbmRating;
  }
  public function getFcbmRating()
  {
    return $this->fcbmRating;
  }
  public function setFcoRating($fcoRating)
  {
    $this->fcoRating = $fcoRating;
  }
  public function getFcoRating()
  {
    return $this->fcoRating;
  }
  public function setFmocRating($fmocRating)
  {
    $this->fmocRating = $fmocRating;
  }
  public function getFmocRating()
  {
    return $this->fmocRating;
  }
  public function setFpbRating($fpbRating)
  {
    $this->fpbRating = $fpbRating;
  }
  public function getFpbRating()
  {
    return $this->fpbRating;
  }
  public function setFskRating($fskRating)
  {
    $this->fskRating = $fskRating;
  }
  public function getFskRating()
  {
    return $this->fskRating;
  }
  public function setGrfilmRating($grfilmRating)
  {
    $this->grfilmRating = $grfilmRating;
  }
  public function getGrfilmRating()
  {
    return $this->grfilmRating;
  }
  public function setIcaaRating($icaaRating)
  {
    $this->icaaRating = $icaaRating;
  }
  public function getIcaaRating()
  {
    return $this->icaaRating;
  }
  public function setIfcoRating($ifcoRating)
  {
    $this->ifcoRating = $ifcoRating;
  }
  public function getIfcoRating()
  {
    return $this->ifcoRating;
  }
  public function setIlfilmRating($ilfilmRating)
  {
    $this->ilfilmRating = $ilfilmRating;
  }
  public function getIlfilmRating()
  {
    return $this->ilfilmRating;
  }
  public function setIncaaRating($incaaRating)
  {
    $this->incaaRating = $incaaRating;
  }
  public function getIncaaRating()
  {
    return $this->incaaRating;
  }
  public function setKfcbRating($kfcbRating)
  {
    $this->kfcbRating = $kfcbRating;
  }
  public function getKfcbRating()
  {
    return $this->kfcbRating;
  }
  public function setKijkwijzerRating($kijkwijzerRating)
  {
    $this->kijkwijzerRating = $kijkwijzerRating;
  }
  public function getKijkwijzerRating()
  {
    return $this->kijkwijzerRating;
  }
  public function setKmrbRating($kmrbRating)
  {
    $this->kmrbRating = $kmrbRating;
  }
  public function getKmrbRating()
  {
    return $this->kmrbRating;
  }
  public function setLsfRating($lsfRating)
  {
    $this->lsfRating = $lsfRating;
  }
  public function getLsfRating()
  {
    return $this->lsfRating;
  }
  public function setMccaaRating($mccaaRating)
  {
    $this->mccaaRating = $mccaaRating;
  }
  public function getMccaaRating()
  {
    return $this->mccaaRating;
  }
  public function setMccypRating($mccypRating)
  {
    $this->mccypRating = $mccypRating;
  }
  public function getMccypRating()
  {
    return $this->mccypRating;
  }
  public function setMdaRating($mdaRating)
  {
    $this->mdaRating = $mdaRating;
  }
  public function getMdaRating()
  {
    return $this->mdaRating;
  }
  public function setMedietilsynetRating($medietilsynetRating)
  {
    $this->medietilsynetRating = $medietilsynetRating;
  }
  public function getMedietilsynetRating()
  {
    return $this->medietilsynetRating;
  }
  public function setMekuRating($mekuRating)
  {
    $this->mekuRating = $mekuRating;
  }
  public function getMekuRating()
  {
    return $this->mekuRating;
  }
  public function setMibacRating($mibacRating)
  {
    $this->mibacRating = $mibacRating;
  }
  public function getMibacRating()
  {
    return $this->mibacRating;
  }
  public function setMocRating($mocRating)
  {
    $this->mocRating = $mocRating;
  }
  public function getMocRating()
  {
    return $this->mocRating;
  }
  public function setMoctwRating($moctwRating)
  {
    $this->moctwRating = $moctwRating;
  }
  public function getMoctwRating()
  {
    return $this->moctwRating;
  }
  public function setMpaaRating($mpaaRating)
  {
    $this->mpaaRating = $mpaaRating;
  }
  public function getMpaaRating()
  {
    return $this->mpaaRating;
  }
  public function setMtrcbRating($mtrcbRating)
  {
    $this->mtrcbRating = $mtrcbRating;
  }
  public function getMtrcbRating()
  {
    return $this->mtrcbRating;
  }
  public function setNbcRating($nbcRating)
  {
    $this->nbcRating = $nbcRating;
  }
  public function getNbcRating()
  {
    return $this->nbcRating;
  }
  public function setNbcplRating($nbcplRating)
  {
    $this->nbcplRating = $nbcplRating;
  }
  public function getNbcplRating()
  {
    return $this->nbcplRating;
  }
  public function setNfrcRating($nfrcRating)
  {
    $this->nfrcRating = $nfrcRating;
  }
  public function getNfrcRating()
  {
    return $this->nfrcRating;
  }
  public function setNfvcbRating($nfvcbRating)
  {
    $this->nfvcbRating = $nfvcbRating;
  }
  public function getNfvcbRating()
  {
    return $this->nfvcbRating;
  }
  public function setNkclvRating($nkclvRating)
  {
    $this->nkclvRating = $nkclvRating;
  }
  public function getNkclvRating()
  {
    return $this->nkclvRating;
  }
  public function setOflcRating($oflcRating)
  {
    $this->oflcRating = $oflcRating;
  }
  public function getOflcRating()
  {
    return $this->oflcRating;
  }
  public function setPefilmRating($pefilmRating)
  {
    $this->pefilmRating = $pefilmRating;
  }
  public function getPefilmRating()
  {
    return $this->pefilmRating;
  }
  public function setRcnofRating($rcnofRating)
  {
    $this->rcnofRating = $rcnofRating;
  }
  public function getRcnofRating()
  {
    return $this->rcnofRating;
  }
  public function setResorteviolenciaRating($resorteviolenciaRating)
  {
    $this->resorteviolenciaRating = $resorteviolenciaRating;
  }
  public function getResorteviolenciaRating()
  {
    return $this->resorteviolenciaRating;
  }
  public function setRtcRating($rtcRating)
  {
    $this->rtcRating = $rtcRating;
  }
  public function getRtcRating()
  {
    return $this->rtcRating;
  }
  public function setRteRating($rteRating)
  {
    $this->rteRating = $rteRating;
  }
  public function getRteRating()
  {
    return $this->rteRating;
  }
  public function setRussiaRating($russiaRating)
  {
    $this->russiaRating = $russiaRating;
  }
  public function getRussiaRating()
  {
    return $this->russiaRating;
  }
  public function setSkfilmRating($skfilmRating)
  {
    $this->skfilmRating = $skfilmRating;
  }
  public function getSkfilmRating()
  {
    return $this->skfilmRating;
  }
  public function setSmaisRating($smaisRating)
  {
    $this->smaisRating = $smaisRating;
  }
  public function getSmaisRating()
  {
    return $this->smaisRating;
  }
  public function setSmsaRating($smsaRating)
  {
    $this->smsaRating = $smsaRating;
  }
  public function getSmsaRating()
  {
    return $this->smsaRating;
  }
  public function setTvpgRating($tvpgRating)
  {
    $this->tvpgRating = $tvpgRating;
  }
  public function getTvpgRating()
  {
    return $this->tvpgRating;
  }
  public function setYtRating($ytRating)
  {
    $this->ytRating = $ytRating;
  }
  public function getYtRating()
  {
    return $this->ytRating;
  }
}

class Google_Service_YouTube_GeoPoint extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  public $altitude;
  public $latitude;
  public $longitude;


  public function setAltitude($altitude)
  {
    $this->altitude = $altitude;
  }
  public function getAltitude()
  {
    return $this->altitude;
  }
  public function setLatitude($latitude)
  {
    $this->latitude = $latitude;
  }
  public function getLatitude()
  {
    return $this->latitude;
  }
  public function setLongitude($longitude)
  {
    $this->longitude = $longitude;
  }
  public function getLongitude()
  {
    return $this->longitude;
  }
}

class Google_Service_YouTube_GuideCategory extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  public $etag;
  public $id;
  public $kind;
  protected $snippetType = 'Google_Service_YouTube_GuideCategorySnippet';
  protected $snippetDataType = '';


  public function setEtag($etag)
  {
    $this->etag = $etag;
  }
  public function getEtag()
  {
    return $this->etag;
  }
  public function setId($id)
  {
    $this->id = $id;
  }
  public function getId()
  {
    return $this->id;
  }
  public function setKind($kind)
  {
    $this->kind = $kind;
  }
  public function getKind()
  {
    return $this->kind;
  }
  public function setSnippet(Google_Service_YouTube_GuideCategorySnippet $snippet)
  {
    $this->snippet = $snippet;
  }
  public function getSnippet()
  {
    return $this->snippet;
  }
}

class Google_Service_YouTube_GuideCategoryListResponse extends Google_Collection
{
  protected $collection_key = 'items';
  protected $internal_gapi_mappings = array(
  );
  public $etag;
  public $eventId;
  protected $itemsType = 'Google_Service_YouTube_GuideCategory';
  protected $itemsDataType = 'array';
  public $kind;
  public $nextPageToken;
  protected $pageInfoType = 'Google_Service_YouTube_PageInfo';
  protected $pageInfoDataType = '';
  public $prevPageToken;
  protected $tokenPaginationType = 'Google_Service_YouTube_TokenPagination';
  protected $tokenPaginationDataType = '';
  public $visitorId;


  public function setEtag($etag)
  {
    $this->etag = $etag;
  }
  public function getEtag()
  {
    return $this->etag;
  }
  public function setEventId($eventId)
  {
    $this->eventId = $eventId;
  }
  public function getEventId()
  {
    return $this->eventId;
  }
  public function setItems($items)
  {
    $this->items = $items;
  }
  public function getItems()
  {
    return $this->items;
  }
  public function setKind($kind)
  {
    $this->kind = $kind;
  }
  public function getKind()
  {
    return $this->kind;
  }
  public function setNextPageToken($nextPageToken)
  {
    $this->nextPageToken = $nextPageToken;
  }
  public function getNextPageToken()
  {
    return $this->nextPageToken;
  }
  public function setPageInfo(Google_Service_YouTube_PageInfo $pageInfo)
  {
    $this->pageInfo = $pageInfo;
  }
  public function getPageInfo()
  {
    return $this->pageInfo;
  }
  public function setPrevPageToken($prevPageToken)
  {
    $this->prevPageToken = $prevPageToken;
  }
  public function getPrevPageToken()
  {
    return $this->prevPageToken;
  }
  public function setTokenPagination(Google_Service_YouTube_TokenPagination $tokenPagination)
  {
    $this->tokenPagination = $tokenPagination;
  }
  public function getTokenPagination()
  {
    return $this->tokenPagination;
  }
  public function setVisitorId($visitorId)
  {
    $this->visitorId = $visitorId;
  }
  public function getVisitorId()
  {
    return $this->visitorId;
  }
}

class Google_Service_YouTube_GuideCategorySnippet extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  public $channelId;
  public $title;


  public function setChannelId($channelId)
  {
    $this->channelId = $channelId;
  }
  public function getChannelId()
  {
    return $this->channelId;
  }
  public function setTitle($title)
  {
    $this->title = $title;
  }
  public function getTitle()
  {
    return $this->title;
  }
}

class Google_Service_YouTube_I18nLanguage extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  public $etag;
  public $id;
  public $kind;
  protected $snippetType = 'Google_Service_YouTube_I18nLanguageSnippet';
  protected $snippetDataType = '';


  public function setEtag($etag)
  {
    $this->etag = $etag;
  }
  public function getEtag()
  {
    return $this->etag;
  }
  public function setId($id)
  {
    $this->id = $id;
  }
  public function getId()
  {
    return $this->id;
  }
  public function setKind($kind)
  {
    $this->kind = $kind;
  }
  public function getKind()
  {
    return $this->kind;
  }
  public function setSnippet(Google_Service_YouTube_I18nLanguageSnippet $snippet)
  {
    $this->snippet = $snippet;
  }
  public function getSnippet()
  {
    return $this->snippet;
  }
}

class Google_Service_YouTube_I18nLanguageListResponse extends Google_Collection
{
  protected $collection_key = 'items';
  protected $internal_gapi_mappings = array(
  );
  public $etag;
  public $eventId;
  protected $itemsType = 'Google_Service_YouTube_I18nLanguage';
  protected $itemsDataType = 'array';
  public $kind;
  public $visitorId;


  public function setEtag($etag)
  {
    $this->etag = $etag;
  }
  public function getEtag()
  {
    return $this->etag;
  }
  public function setEventId($eventId)
  {
    $this->eventId = $eventId;
  }
  public function getEventId()
  {
    return $this->eventId;
  }
  public function setItems($items)
  {
    $this->items = $items;
  }
  public function getItems()
  {
    return $this->items;
  }
  public function setKind($kind)
  {
    $this->kind = $kind;
  }
  public function getKind()
  {
    return $this->kind;
  }
  public function setVisitorId($visitorId)
  {
    $this->visitorId = $visitorId;
  }
  public function getVisitorId()
  {
    return $this->visitorId;
  }
}

class Google_Service_YouTube_I18nLanguageSnippet extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  public $hl;
  public $name;


  public function setHl($hl)
  {
    $this->hl = $hl;
  }
  public function getHl()
  {
    return $this->hl;
  }
  public function setName($name)
  {
    $this->name = $name;
  }
  public function getName()
  {
    return $this->name;
  }
}

class Google_Service_YouTube_I18nRegion extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  public $etag;
  public $id;
  public $kind;
  protected $snippetType = 'Google_Service_YouTube_I18nRegionSnippet';
  protected $snippetDataType = '';


  public function setEtag($etag)
  {
    $this->etag = $etag;
  }
  public function getEtag()
  {
    return $this->etag;
  }
  public function setId($id)
  {
    $this->id = $id;
  }
  public function getId()
  {
    return $this->id;
  }
  public function setKind($kind)
  {
    $this->kind = $kind;
  }
  public function getKind()
  {
    return $this->kind;
  }
  public function setSnippet(Google_Service_YouTube_I18nRegionSnippet $snippet)
  {
    $this->snippet = $snippet;
  }
  public function getSnippet()
  {
    return $this->snippet;
  }
}

class Google_Service_YouTube_I18nRegionListResponse extends Google_Collection
{
  protected $collection_key = 'items';
  protected $internal_gapi_mappings = array(
  );
  public $etag;
  public $eventId;
  protected $itemsType = 'Google_Service_YouTube_I18nRegion';
  protected $itemsDataType = 'array';
  public $kind;
  public $visitorId;


  public function setEtag($etag)
  {
    $this->etag = $etag;
  }
  public function getEtag()
  {
    return $this->etag;
  }
  public function setEventId($eventId)
  {
    $this->eventId = $eventId;
  }
  public function getEventId()
  {
    return $this->eventId;
  }
  public function setItems($items)
  {
    $this->items = $items;
  }
  public function getItems()
  {
    return $this->items;
  }
  public function setKind($kind)
  {
    $this->kind = $kind;
  }
  public function getKind()
  {
    return $this->kind;
  }
  public function setVisitorId($visitorId)
  {
    $this->visitorId = $visitorId;
  }
  public function getVisitorId()
  {
    return $this->visitorId;
  }
}

class Google_Service_YouTube_I18nRegionSnippet extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  public $gl;
  public $name;


  public function setGl($gl)
  {
    $this->gl = $gl;
  }
  public function getGl()
  {
    return $this->gl;
  }
  public function setName($name)
  {
    $this->name = $name;
  }
  public function getName()
  {
    return $this->name;
  }
}

class Google_Service_YouTube_ImageSettings extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  protected $backgroundImageUrlType = 'Google_Service_YouTube_LocalizedProperty';
  protected $backgroundImageUrlDataType = '';
  public $bannerExternalUrl;
  public $bannerImageUrl;
  public $bannerMobileExtraHdImageUrl;
  public $bannerMobileHdImageUrl;
  public $bannerMobileImageUrl;
  public $bannerMobileLowImageUrl;
  public $bannerMobileMediumHdImageUrl;
  public $bannerTabletExtraHdImageUrl;
  public $bannerTabletHdImageUrl;
  public $bannerTabletImageUrl;
  public $bannerTabletLowImageUrl;
  public $bannerTvHighImageUrl;
  public $bannerTvImageUrl;
  public $bannerTvLowImageUrl;
  public $bannerTvMediumImageUrl;
  protected $largeBrandedBannerImageImapScriptType = 'Google_Service_YouTube_LocalizedProperty';
  protected $largeBrandedBannerImageImapScriptDataType = '';
  protected $largeBrandedBannerImageUrlType = 'Google_Service_YouTube_LocalizedProperty';
  protected $largeBrandedBannerImageUrlDataType = '';
  protected $smallBrandedBannerImageImapScriptType = 'Google_Service_YouTube_LocalizedProperty';
  protected $smallBrandedBannerImageImapScriptDataType = '';
  protected $smallBrandedBannerImageUrlType = 'Google_Service_YouTube_LocalizedProperty';
  protected $smallBrandedBannerImageUrlDataType = '';
  public $trackingImageUrl;
  public $watchIconImageUrl;


  public function setBackgroundImageUrl(Google_Service_YouTube_LocalizedProperty $backgroundImageUrl)
  {
    $this->backgroundImageUrl = $backgroundImageUrl;
  }
  public function getBackgroundImageUrl()
  {
    return $this->backgroundImageUrl;
  }
  public function setBannerExternalUrl($bannerExternalUrl)
  {
    $this->bannerExternalUrl = $bannerExternalUrl;
  }
  public function getBannerExternalUrl()
  {
    return $this->bannerExternalUrl;
  }
  public function setBannerImageUrl($bannerImageUrl)
  {
    $this->bannerImageUrl = $bannerImageUrl;
  }
  public function getBannerImageUrl()
  {
    return $this->bannerImageUrl;
  }
  public function setBannerMobileExtraHdImageUrl($bannerMobileExtraHdImageUrl)
  {
    $this->bannerMobileExtraHdImageUrl = $bannerMobileExtraHdImageUrl;
  }
  public function getBannerMobileExtraHdImageUrl()
  {
    return $this->bannerMobileExtraHdImageUrl;
  }
  public function setBannerMobileHdImageUrl($bannerMobileHdImageUrl)
  {
    $this->bannerMobileHdImageUrl = $bannerMobileHdImageUrl;
  }
  public function getBannerMobileHdImageUrl()
  {
    return $this->bannerMobileHdImageUrl;
  }
  public function setBannerMobileImageUrl($bannerMobileImageUrl)
  {
    $this->bannerMobileImageUrl = $bannerMobileImageUrl;
  }
  public function getBannerMobileImageUrl()
  {
    return $this->bannerMobileImageUrl;
  }
  public function setBannerMobileLowImageUrl($bannerMobileLowImageUrl)
  {
    $this->bannerMobileLowImageUrl = $bannerMobileLowImageUrl;
  }
  public function getBannerMobileLowImageUrl()
  {
    return $this->bannerMobileLowImageUrl;
  }
  public function setBannerMobileMediumHdImageUrl($bannerMobileMediumHdImageUrl)
  {
    $this->bannerMobileMediumHdImageUrl = $bannerMobileMediumHdImageUrl;
  }
  public function getBannerMobileMediumHdImageUrl()
  {
    return $this->bannerMobileMediumHdImageUrl;
  }
  public function setBannerTabletExtraHdImageUrl($bannerTabletExtraHdImageUrl)
  {
    $this->bannerTabletExtraHdImageUrl = $bannerTabletExtraHdImageUrl;
  }
  public function getBannerTabletExtraHdImageUrl()
  {
    return $this->bannerTabletExtraHdImageUrl;
  }
  public function setBannerTabletHdImageUrl($bannerTabletHdImageUrl)
  {
    $this->bannerTabletHdImageUrl = $bannerTabletHdImageUrl;
  }
  public function getBannerTabletHdImageUrl()
  {
    return $this->bannerTabletHdImageUrl;
  }
  public function setBannerTabletImageUrl($bannerTabletImageUrl)
  {
    $this->bannerTabletImageUrl = $bannerTabletImageUrl;
  }
  public function getBannerTabletImageUrl()
  {
    return $this->bannerTabletImageUrl;
  }
  public function setBannerTabletLowImageUrl($bannerTabletLowImageUrl)
  {
    $this->bannerTabletLowImageUrl = $bannerTabletLowImageUrl;
  }
  public function getBannerTabletLowImageUrl()
  {
    return $this->bannerTabletLowImageUrl;
  }
  public function setBannerTvHighImageUrl($bannerTvHighImageUrl)
  {
    $this->bannerTvHighImageUrl = $bannerTvHighImageUrl;
  }
  public function getBannerTvHighImageUrl()
  {
    return $this->bannerTvHighImageUrl;
  }
  public function setBannerTvImageUrl($bannerTvImageUrl)
  {
    $this->bannerTvImageUrl = $bannerTvImageUrl;
  }
  public function getBannerTvImageUrl()
  {
    return $this->bannerTvImageUrl;
  }
  public function setBannerTvLowImageUrl($bannerTvLowImageUrl)
  {
    $this->bannerTvLowImageUrl = $bannerTvLowImageUrl;
  }
  public function getBannerTvLowImageUrl()
  {
    return $this->bannerTvLowImageUrl;
  }
  public function setBannerTvMediumImageUrl($bannerTvMediumImageUrl)
  {
    $this->bannerTvMediumImageUrl = $bannerTvMediumImageUrl;
  }
  public function getBannerTvMediumImageUrl()
  {
    return $this->bannerTvMediumImageUrl;
  }
  public function setLargeBrandedBannerImageImapScript(Google_Service_YouTube_LocalizedProperty $largeBrandedBannerImageImapScript)
  {
    $this->largeBrandedBannerImageImapScript = $largeBrandedBannerImageImapScript;
  }
  public function getLargeBrandedBannerImageImapScript()
  {
    return $this->largeBrandedBannerImageImapScript;
  }
  public function setLargeBrandedBannerImageUrl(Google_Service_YouTube_LocalizedProperty $largeBrandedBannerImageUrl)
  {
    $this->largeBrandedBannerImageUrl = $largeBrandedBannerImageUrl;
  }
  public function getLargeBrandedBannerImageUrl()
  {
    return $this->largeBrandedBannerImageUrl;
  }
  public function setSmallBrandedBannerImageImapScript(Google_Service_YouTube_LocalizedProperty $smallBrandedBannerImageImapScript)
  {
    $this->smallBrandedBannerImageImapScript = $smallBrandedBannerImageImapScript;
  }
  public function getSmallBrandedBannerImageImapScript()
  {
    return $this->smallBrandedBannerImageImapScript;
  }
  public function setSmallBrandedBannerImageUrl(Google_Service_YouTube_LocalizedProperty $smallBrandedBannerImageUrl)
  {
    $this->smallBrandedBannerImageUrl = $smallBrandedBannerImageUrl;
  }
  public function getSmallBrandedBannerImageUrl()
  {
    return $this->smallBrandedBannerImageUrl;
  }
  public function setTrackingImageUrl($trackingImageUrl)
  {
    $this->trackingImageUrl = $trackingImageUrl;
  }
  public function getTrackingImageUrl()
  {
    return $this->trackingImageUrl;
  }
  public function setWatchIconImageUrl($watchIconImageUrl)
  {
    $this->watchIconImageUrl = $watchIconImageUrl;
  }
  public function getWatchIconImageUrl()
  {
    return $this->watchIconImageUrl;
  }
}

class Google_Service_YouTube_IngestionInfo extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  public $backupIngestionAddress;
  public $ingestionAddress;
  public $streamName;


  public function setBackupIngestionAddress($backupIngestionAddress)
  {
    $this->backupIngestionAddress = $backupIngestionAddress;
  }
  public function getBackupIngestionAddress()
  {
    return $this->backupIngestionAddress;
  }
  public function setIngestionAddress($ingestionAddress)
  {
    $this->ingestionAddress = $ingestionAddress;
  }
  public function getIngestionAddress()
  {
    return $this->ingestionAddress;
  }
  public function setStreamName($streamName)
  {
    $this->streamName = $streamName;
  }
  public function getStreamName()
  {
    return $this->streamName;
  }
}

class Google_Service_YouTube_InvideoBranding extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  public $imageBytes;
  public $imageUrl;
  protected $positionType = 'Google_Service_YouTube_InvideoPosition';
  protected $positionDataType = '';
  public $targetChannelId;
  protected $timingType = 'Google_Service_YouTube_InvideoTiming';
  protected $timingDataType = '';


  public function setImageBytes($imageBytes)
  {
    $this->imageBytes = $imageBytes;
  }
  public function getImageBytes()
  {
    return $this->imageBytes;
  }
  public function setImageUrl($imageUrl)
  {
    $this->imageUrl = $imageUrl;
  }
  public function getImageUrl()
  {
    return $this->imageUrl;
  }
  public function setPosition(Google_Service_YouTube_InvideoPosition $position)
  {
    $this->position = $position;
  }
  public function getPosition()
  {
    return $this->position;
  }
  public function setTargetChannelId($targetChannelId)
  {
    $this->targetChannelId = $targetChannelId;
  }
  public function getTargetChannelId()
  {
    return $this->targetChannelId;
  }
  public function setTiming(Google_Service_YouTube_InvideoTiming $timing)
  {
    $this->timing = $timing;
  }
  public function getTiming()
  {
    return $this->timing;
  }
}

class Google_Service_YouTube_InvideoPosition extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  public $cornerPosition;
  public $type;


  public function setCornerPosition($cornerPosition)
  {
    $this->cornerPosition = $cornerPosition;
  }
  public function getCornerPosition()
  {
    return $this->cornerPosition;
  }
  public function setType($type)
  {
    $this->type = $type;
  }
  public function getType()
  {
    return $this->type;
  }
}

class Google_Service_YouTube_InvideoPromotion extends Google_Collection
{
  protected $collection_key = 'items';
  protected $internal_gapi_mappings = array(
  );
  protected $defaultTimingType = 'Google_Service_YouTube_InvideoTiming';
  protected $defaultTimingDataType = '';
  protected $itemsType = 'Google_Service_YouTube_PromotedItem';
  protected $itemsDataType = 'array';
  protected $positionType = 'Google_Service_YouTube_InvideoPosition';
  protected $positionDataType = '';
  public $useSmartTiming;


  public function setDefaultTiming(Google_Service_YouTube_InvideoTiming $defaultTiming)
  {
    $this->defaultTiming = $defaultTiming;
  }
  public function getDefaultTiming()
  {
    return $this->defaultTiming;
  }
  public function setItems($items)
  {
    $this->items = $items;
  }
  public function getItems()
  {
    return $this->items;
  }
  public function setPosition(Google_Service_YouTube_InvideoPosition $position)
  {
    $this->position = $position;
  }
  public function getPosition()
  {
    return $this->position;
  }
  public function setUseSmartTiming($useSmartTiming)
  {
    $this->useSmartTiming = $useSmartTiming;
  }
  public function getUseSmartTiming()
  {
    return $this->useSmartTiming;
  }
}

class Google_Service_YouTube_InvideoTiming extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  public $durationMs;
  public $offsetMs;
  public $type;


  public function setDurationMs($durationMs)
  {
    $this->durationMs = $durationMs;
  }
  public function getDurationMs()
  {
    return $this->durationMs;
  }
  public function setOffsetMs($offsetMs)
  {
    $this->offsetMs = $offsetMs;
  }
  public function getOffsetMs()
  {
    return $this->offsetMs;
  }
  public function setType($type)
  {
    $this->type = $type;
  }
  public function getType()
  {
    return $this->type;
  }
}

class Google_Service_YouTube_LanguageTag extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  public $value;


  public function setValue($value)
  {
    $this->value = $value;
  }
  public function getValue()
  {
    return $this->value;
  }
}

class Google_Service_YouTube_LiveBroadcast extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  protected $contentDetailsType = 'Google_Service_YouTube_LiveBroadcastContentDetails';
  protected $contentDetailsDataType = '';
  public $etag;
  public $id;
  public $kind;
  protected $snippetType = 'Google_Service_YouTube_LiveBroadcastSnippet';
  protected $snippetDataType = '';
  protected $statisticsType = 'Google_Service_YouTube_LiveBroadcastStatistics';
  protected $statisticsDataType = '';
  protected $statusType = 'Google_Service_YouTube_LiveBroadcastStatus';
  protected $statusDataType = '';
  protected $topicDetailsType = 'Google_Service_YouTube_LiveBroadcastTopicDetails';
  protected $topicDetailsDataType = '';


  public function setContentDetails(Google_Service_YouTube_LiveBroadcastContentDetails $contentDetails)
  {
    $this->contentDetails = $contentDetails;
  }
  public function getContentDetails()
  {
    return $this->contentDetails;
  }
  public function setEtag($etag)
  {
    $this->etag = $etag;
  }
  public function getEtag()
  {
    return $this->etag;
  }
  public function setId($id)
  {
    $this->id = $id;
  }
  public function getId()
  {
    return $this->id;
  }
  public function setKind($kind)
  {
    $this->kind = $kind;
  }
  public function getKind()
  {
    return $this->kind;
  }
  public function setSnippet(Google_Service_YouTube_LiveBroadcastSnippet $snippet)
  {
    $this->snippet = $snippet;
  }
  public function getSnippet()
  {
    return $this->snippet;
  }
  public function setStatistics(Google_Service_YouTube_LiveBroadcastStatistics $statistics)
  {
    $this->statistics = $statistics;
  }
  public function getStatistics()
  {
    return $this->statistics;
  }
  public function setStatus(Google_Service_YouTube_LiveBroadcastStatus $status)
  {
    $this->status = $status;
  }
  public function getStatus()
  {
    return $this->status;
  }
  public function setTopicDetails(Google_Service_YouTube_LiveBroadcastTopicDetails $topicDetails)
  {
    $this->topicDetails = $topicDetails;
  }
  public function getTopicDetails()
  {
    return $this->topicDetails;
  }
}

class Google_Service_YouTube_LiveBroadcastContentDetails extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  public $boundStreamId;
  public $enableClosedCaptions;
  public $enableContentEncryption;
  public $enableDvr;
  public $enableEmbed;
  public $enableLowLatency;
  protected $monitorStreamType = 'Google_Service_YouTube_MonitorStreamInfo';
  protected $monitorStreamDataType = '';
  public $recordFromStart;
  public $startWithSlate;


  public function setBoundStreamId($boundStreamId)
  {
    $this->boundStreamId = $boundStreamId;
  }
  public function getBoundStreamId()
  {
    return $this->boundStreamId;
  }
  public function setEnableClosedCaptions($enableClosedCaptions)
  {
    $this->enableClosedCaptions = $enableClosedCaptions;
  }
  public function getEnableClosedCaptions()
  {
    return $this->enableClosedCaptions;
  }
  public function setEnableContentEncryption($enableContentEncryption)
  {
    $this->enableContentEncryption = $enableContentEncryption;
  }
  public function getEnableContentEncryption()
  {
    return $this->enableContentEncryption;
  }
  public function setEnableDvr($enableDvr)
  {
    $this->enableDvr = $enableDvr;
  }
  public function getEnableDvr()
  {
    return $this->enableDvr;
  }
  public function setEnableEmbed($enableEmbed)
  {
    $this->enableEmbed = $enableEmbed;
  }
  public function getEnableEmbed()
  {
    return $this->enableEmbed;
  }
  public function setEnableLowLatency($enableLowLatency)
  {
    $this->enableLowLatency = $enableLowLatency;
  }
  public function getEnableLowLatency()
  {
    return $this->enableLowLatency;
  }
  public function setMonitorStream(Google_Service_YouTube_MonitorStreamInfo $monitorStream)
  {
    $this->monitorStream = $monitorStream;
  }
  public function getMonitorStream()
  {
    return $this->monitorStream;
  }
  public function setRecordFromStart($recordFromStart)
  {
    $this->recordFromStart = $recordFromStart;
  }
  public function getRecordFromStart()
  {
    return $this->recordFromStart;
  }
  public function setStartWithSlate($startWithSlate)
  {
    $this->startWithSlate = $startWithSlate;
  }
  public function getStartWithSlate()
  {
    return $this->startWithSlate;
  }
}

class Google_Service_YouTube_LiveBroadcastListResponse extends Google_Collection
{
  protected $collection_key = 'items';
  protected $internal_gapi_mappings = array(
  );
  public $etag;
  public $eventId;
  protected $itemsType = 'Google_Service_YouTube_LiveBroadcast';
  protected $itemsDataType = 'array';
  public $kind;
  public $nextPageToken;
  protected $pageInfoType = 'Google_Service_YouTube_PageInfo';
  protected $pageInfoDataType = '';
  public $prevPageToken;
  protected $tokenPaginationType = 'Google_Service_YouTube_TokenPagination';
  protected $tokenPaginationDataType = '';
  public $visitorId;


  public function setEtag($etag)
  {
    $this->etag = $etag;
  }
  public function getEtag()
  {
    return $this->etag;
  }
  public function setEventId($eventId)
  {
    $this->eventId = $eventId;
  }
  public function getEventId()
  {
    return $this->eventId;
  }
  public function setItems($items)
  {
    $this->items = $items;
  }
  public function getItems()
  {
    return $this->items;
  }
  public function setKind($kind)
  {
    $this->kind = $kind;
  }
  public function getKind()
  {
    return $this->kind;
  }
  public function setNextPageToken($nextPageToken)
  {
    $this->nextPageToken = $nextPageToken;
  }
  public function getNextPageToken()
  {
    return $this->nextPageToken;
  }
  public function setPageInfo(Google_Service_YouTube_PageInfo $pageInfo)
  {
    $this->pageInfo = $pageInfo;
  }
  public function getPageInfo()
  {
    return $this->pageInfo;
  }
  public function setPrevPageToken($prevPageToken)
  {
    $this->prevPageToken = $prevPageToken;
  }
  public function getPrevPageToken()
  {
    return $this->prevPageToken;
  }
  public function setTokenPagination(Google_Service_YouTube_TokenPagination $tokenPagination)
  {
    $this->tokenPagination = $tokenPagination;
  }
  public function getTokenPagination()
  {
    return $this->tokenPagination;
  }
  public function setVisitorId($visitorId)
  {
    $this->visitorId = $visitorId;
  }
  public function getVisitorId()
  {
    return $this->visitorId;
  }
}

class Google_Service_YouTube_LiveBroadcastSnippet extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  public $actualEndTime;
  public $actualStartTime;
  public $channelId;
  public $description;
  public $isDefaultBroadcast;
  public $liveChatId;
  public $publishedAt;
  public $scheduledEndTime;
  public $scheduledStartTime;
  protected $thumbnailsType = 'Google_Service_YouTube_ThumbnailDetails';
  protected $thumbnailsDataType = '';
  public $title;


  public function setActualEndTime($actualEndTime)
  {
    $this->actualEndTime = $actualEndTime;
  }
  public function getActualEndTime()
  {
    return $this->actualEndTime;
  }
  public function setActualStartTime($actualStartTime)
  {
    $this->actualStartTime = $actualStartTime;
  }
  public function getActualStartTime()
  {
    return $this->actualStartTime;
  }
  public function setChannelId($channelId)
  {
    $this->channelId = $channelId;
  }
  public function getChannelId()
  {
    return $this->channelId;
  }
  public function setDescription($description)
  {
    $this->description = $description;
  }
  public function getDescription()
  {
    return $this->description;
  }
  public function setIsDefaultBroadcast($isDefaultBroadcast)
  {
    $this->isDefaultBroadcast = $isDefaultBroadcast;
  }
  public function getIsDefaultBroadcast()
  {
    return $this->isDefaultBroadcast;
  }
  public function setLiveChatId($liveChatId)
  {
    $this->liveChatId = $liveChatId;
  }
  public function getLiveChatId()
  {
    return $this->liveChatId;
  }
  public function setPublishedAt($publishedAt)
  {
    $this->publishedAt = $publishedAt;
  }
  public function getPublishedAt()
  {
    return $this->publishedAt;
  }
  public function setScheduledEndTime($scheduledEndTime)
  {
    $this->scheduledEndTime = $scheduledEndTime;
  }
  public function getScheduledEndTime()
  {
    return $this->scheduledEndTime;
  }
  public function setScheduledStartTime($scheduledStartTime)
  {
    $this->scheduledStartTime = $scheduledStartTime;
  }
  public function getScheduledStartTime()
  {
    return $this->scheduledStartTime;
  }
  public function setThumbnails(Google_Service_YouTube_ThumbnailDetails $thumbnails)
  {
    $this->thumbnails = $thumbnails;
  }
  public function getThumbnails()
  {
    return $this->thumbnails;
  }
  public function setTitle($title)
  {
    $this->title = $title;
  }
  public function getTitle()
  {
    return $this->title;
  }
}

class Google_Service_YouTube_LiveBroadcastStatistics extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  public $concurrentViewers;
  public $totalChatCount;


  public function setConcurrentViewers($concurrentViewers)
  {
    $this->concurrentViewers = $concurrentViewers;
  }
  public function getConcurrentViewers()
  {
    return $this->concurrentViewers;
  }
  public function setTotalChatCount($totalChatCount)
  {
    $this->totalChatCount = $totalChatCount;
  }
  public function getTotalChatCount()
  {
    return $this->totalChatCount;
  }
}

class Google_Service_YouTube_LiveBroadcastStatus extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  public $lifeCycleStatus;
  public $liveBroadcastPriority;
  public $privacyStatus;
  public $recordingStatus;


  public function setLifeCycleStatus($lifeCycleStatus)
  {
    $this->lifeCycleStatus = $lifeCycleStatus;
  }
  public function getLifeCycleStatus()
  {
    return $this->lifeCycleStatus;
  }
  public function setLiveBroadcastPriority($liveBroadcastPriority)
  {
    $this->liveBroadcastPriority = $liveBroadcastPriority;
  }
  public function getLiveBroadcastPriority()
  {
    return $this->liveBroadcastPriority;
  }
  public function setPrivacyStatus($privacyStatus)
  {
    $this->privacyStatus = $privacyStatus;
  }
  public function getPrivacyStatus()
  {
    return $this->privacyStatus;
  }
  public function setRecordingStatus($recordingStatus)
  {
    $this->recordingStatus = $recordingStatus;
  }
  public function getRecordingStatus()
  {
    return $this->recordingStatus;
  }
}

class Google_Service_YouTube_LiveBroadcastTopic extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  protected $snippetType = 'Google_Service_YouTube_LiveBroadcastTopicSnippet';
  protected $snippetDataType = '';
  public $type;
  public $unmatched;


  public function setSnippet(Google_Service_YouTube_LiveBroadcastTopicSnippet $snippet)
  {
    $this->snippet = $snippet;
  }
  public function getSnippet()
  {
    return $this->snippet;
  }
  public function setType($type)
  {
    $this->type = $type;
  }
  public function getType()
  {
    return $this->type;
  }
  public function setUnmatched($unmatched)
  {
    $this->unmatched = $unmatched;
  }
  public function getUnmatched()
  {
    return $this->unmatched;
  }
}

class Google_Service_YouTube_LiveBroadcastTopicDetails extends Google_Collection
{
  protected $collection_key = 'topics';
  protected $internal_gapi_mappings = array(
  );
  protected $topicsType = 'Google_Service_YouTube_LiveBroadcastTopic';
  protected $topicsDataType = 'array';


  public function setTopics($topics)
  {
    $this->topics = $topics;
  }
  public function getTopics()
  {
    return $this->topics;
  }
}

class Google_Service_YouTube_LiveBroadcastTopicSnippet extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  public $name;
  public $releaseDate;


  public function setName($name)
  {
    $this->name = $name;
  }
  public function getName()
  {
    return $this->name;
  }
  public function setReleaseDate($releaseDate)
  {
    $this->releaseDate = $releaseDate;
  }
  public function getReleaseDate()
  {
    return $this->releaseDate;
  }
}

class Google_Service_YouTube_LiveStream extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  protected $cdnType = 'Google_Service_YouTube_CdnSettings';
  protected $cdnDataType = '';
  protected $contentDetailsType = 'Google_Service_YouTube_LiveStreamContentDetails';
  protected $contentDetailsDataType = '';
  public $etag;
  public $id;
  public $kind;
  protected $snippetType = 'Google_Service_YouTube_LiveStreamSnippet';
  protected $snippetDataType = '';
  protected $statusType = 'Google_Service_YouTube_LiveStreamStatus';
  protected $statusDataType = '';


  public function setCdn(Google_Service_YouTube_CdnSettings $cdn)
  {
    $this->cdn = $cdn;
  }
  public function getCdn()
  {
    return $this->cdn;
  }
  public function setContentDetails(Google_Service_YouTube_LiveStreamContentDetails $contentDetails)
  {
    $this->contentDetails = $contentDetails;
  }
  public function getContentDetails()
  {
    return $this->contentDetails;
  }
  public function setEtag($etag)
  {
    $this->etag = $etag;
  }
  public function getEtag()
  {
    return $this->etag;
  }
  public function setId($id)
  {
    $this->id = $id;
  }
  public function getId()
  {
    return $this->id;
  }
  public function setKind($kind)
  {
    $this->kind = $kind;
  }
  public function getKind()
  {
    return $this->kind;
  }
  public function setSnippet(Google_Service_YouTube_LiveStreamSnippet $snippet)
  {
    $this->snippet = $snippet;
  }
  public function getSnippet()
  {
    return $this->snippet;
  }
  public function setStatus(Google_Service_YouTube_LiveStreamStatus $status)
  {
    $this->status = $status;
  }
  public function getStatus()
  {
    return $this->status;
  }
}

class Google_Service_YouTube_LiveStreamConfigurationIssue extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  public $description;
  public $reason;
  public $severity;
  public $type;


  public function setDescription($description)
  {
    $this->description = $description;
  }
  public function getDescription()
  {
    return $this->description;
  }
  public function setReason($reason)
  {
    $this->reason = $reason;
  }
  public function getReason()
  {
    return $this->reason;
  }
  public function setSeverity($severity)
  {
    $this->severity = $severity;
  }
  public function getSeverity()
  {
    return $this->severity;
  }
  public function setType($type)
  {
    $this->type = $type;
  }
  public function getType()
  {
    return $this->type;
  }
}

class Google_Service_YouTube_LiveStreamContentDetails extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  public $closedCaptionsIngestionUrl;
  public $isReusable;


  public function setClosedCaptionsIngestionUrl($closedCaptionsIngestionUrl)
  {
    $this->closedCaptionsIngestionUrl = $closedCaptionsIngestionUrl;
  }
  public function getClosedCaptionsIngestionUrl()
  {
    return $this->closedCaptionsIngestionUrl;
  }
  public function setIsReusable($isReusable)
  {
    $this->isReusable = $isReusable;
  }
  public function getIsReusable()
  {
    return $this->isReusable;
  }
}

class Google_Service_YouTube_LiveStreamHealthStatus extends Google_Collection
{
  protected $collection_key = 'configurationIssues';
  protected $internal_gapi_mappings = array(
  );
  protected $configurationIssuesType = 'Google_Service_YouTube_LiveStreamConfigurationIssue';
  protected $configurationIssuesDataType = 'array';
  public $lastUpdateTimeSeconds;
  public $status;


  public function setConfigurationIssues($configurationIssues)
  {
    $this->configurationIssues = $configurationIssues;
  }
  public function getConfigurationIssues()
  {
    return $this->configurationIssues;
  }
  public function setLastUpdateTimeSeconds($lastUpdateTimeSeconds)
  {
    $this->lastUpdateTimeSeconds = $lastUpdateTimeSeconds;
  }
  public function getLastUpdateTimeSeconds()
  {
    return $this->lastUpdateTimeSeconds;
  }
  public function setStatus($status)
  {
    $this->status = $status;
  }
  public function getStatus()
  {
    return $this->status;
  }
}

class Google_Service_YouTube_LiveStreamListResponse extends Google_Collection
{
  protected $collection_key = 'items';
  protected $internal_gapi_mappings = array(
  );
  public $etag;
  public $eventId;
  protected $itemsType = 'Google_Service_YouTube_LiveStream';
  protected $itemsDataType = 'array';
  public $kind;
  public $nextPageToken;
  protected $pageInfoType = 'Google_Service_YouTube_PageInfo';
  protected $pageInfoDataType = '';
  public $prevPageToken;
  protected $tokenPaginationType = 'Google_Service_YouTube_TokenPagination';
  protected $tokenPaginationDataType = '';
  public $visitorId;


  public function setEtag($etag)
  {
    $this->etag = $etag;
  }
  public function getEtag()
  {
    return $this->etag;
  }
  public function setEventId($eventId)
  {
    $this->eventId = $eventId;
  }
  public function getEventId()
  {
    return $this->eventId;
  }
  public function setItems($items)
  {
    $this->items = $items;
  }
  public function getItems()
  {
    return $this->items;
  }
  public function setKind($kind)
  {
    $this->kind = $kind;
  }
  public function getKind()
  {
    return $this->kind;
  }
  public function setNextPageToken($nextPageToken)
  {
    $this->nextPageToken = $nextPageToken;
  }
  public function getNextPageToken()
  {
    return $this->nextPageToken;
  }
  public function setPageInfo(Google_Service_YouTube_PageInfo $pageInfo)
  {
    $this->pageInfo = $pageInfo;
  }
  public function getPageInfo()
  {
    return $this->pageInfo;
  }
  public function setPrevPageToken($prevPageToken)
  {
    $this->prevPageToken = $prevPageToken;
  }
  public function getPrevPageToken()
  {
    return $this->prevPageToken;
  }
  public function setTokenPagination(Google_Service_YouTube_TokenPagination $tokenPagination)
  {
    $this->tokenPagination = $tokenPagination;
  }
  public function getTokenPagination()
  {
    return $this->tokenPagination;
  }
  public function setVisitorId($visitorId)
  {
    $this->visitorId = $visitorId;
  }
  public function getVisitorId()
  {
    return $this->visitorId;
  }
}

class Google_Service_YouTube_LiveStreamSnippet extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  public $channelId;
  public $description;
  public $isDefaultStream;
  public $publishedAt;
  public $title;


  public function setChannelId($channelId)
  {
    $this->channelId = $channelId;
  }
  public function getChannelId()
  {
    return $this->channelId;
  }
  public function setDescription($description)
  {
    $this->description = $description;
  }
  public function getDescription()
  {
    return $this->description;
  }
  public function setIsDefaultStream($isDefaultStream)
  {
    $this->isDefaultStream = $isDefaultStream;
  }
  public function getIsDefaultStream()
  {
    return $this->isDefaultStream;
  }
  public function setPublishedAt($publishedAt)
  {
    $this->publishedAt = $publishedAt;
  }
  public function getPublishedAt()
  {
    return $this->publishedAt;
  }
  public function setTitle($title)
  {
    $this->title = $title;
  }
  public function getTitle()
  {
    return $this->title;
  }
}

class Google_Service_YouTube_LiveStreamStatus extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  protected $healthStatusType = 'Google_Service_YouTube_LiveStreamHealthStatus';
  protected $healthStatusDataType = '';
  public $streamStatus;


  public function setHealthStatus(Google_Service_YouTube_LiveStreamHealthStatus $healthStatus)
  {
    $this->healthStatus = $healthStatus;
  }
  public function getHealthStatus()
  {
    return $this->healthStatus;
  }
  public function setStreamStatus($streamStatus)
  {
    $this->streamStatus = $streamStatus;
  }
  public function getStreamStatus()
  {
    return $this->streamStatus;
  }
}

class Google_Service_YouTube_LocalizedProperty extends Google_Collection
{
  protected $collection_key = 'localized';
  protected $internal_gapi_mappings = array(
  );
  public $default;
  protected $defaultLanguageType = 'Google_Service_YouTube_LanguageTag';
  protected $defaultLanguageDataType = '';
  protected $localizedType = 'Google_Service_YouTube_LocalizedString';
  protected $localizedDataType = 'array';


  public function setDefault($default)
  {
    $this->default = $default;
  }
  public function getDefault()
  {
    return $this->default;
  }
  public function setDefaultLanguage(Google_Service_YouTube_LanguageTag $defaultLanguage)
  {
    $this->defaultLanguage = $defaultLanguage;
  }
  public function getDefaultLanguage()
  {
    return $this->defaultLanguage;
  }
  public function setLocalized($localized)
  {
    $this->localized = $localized;
  }
  public function getLocalized()
  {
    return $this->localized;
  }
}

class Google_Service_YouTube_LocalizedString extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  public $language;
  public $value;


  public function setLanguage($language)
  {
    $this->language = $language;
  }
  public function getLanguage()
  {
    return $this->language;
  }
  public function setValue($value)
  {
    $this->value = $value;
  }
  public function getValue()
  {
    return $this->value;
  }
}

class Google_Service_YouTube_MonitorStreamInfo extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  public $broadcastStreamDelayMs;
  public $embedHtml;
  public $enableMonitorStream;


  public function setBroadcastStreamDelayMs($broadcastStreamDelayMs)
  {
    $this->broadcastStreamDelayMs = $broadcastStreamDelayMs;
  }
  public function getBroadcastStreamDelayMs()
  {
    return $this->broadcastStreamDelayMs;
  }
  public function setEmbedHtml($embedHtml)
  {
    $this->embedHtml = $embedHtml;
  }
  public function getEmbedHtml()
  {
    return $this->embedHtml;
  }
  public function setEnableMonitorStream($enableMonitorStream)
  {
    $this->enableMonitorStream = $enableMonitorStream;
  }
  public function getEnableMonitorStream()
  {
    return $this->enableMonitorStream;
  }
}

class Google_Service_YouTube_PageInfo extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  public $resultsPerPage;
  public $totalResults;


  public function setResultsPerPage($resultsPerPage)
  {
    $this->resultsPerPage = $resultsPerPage;
  }
  public function getResultsPerPage()
  {
    return $this->resultsPerPage;
  }
  public function setTotalResults($totalResults)
  {
    $this->totalResults = $totalResults;
  }
  public function getTotalResults()
  {
    return $this->totalResults;
  }
}

class Google_Service_YouTube_Playlist extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  protected $contentDetailsType = 'Google_Service_YouTube_PlaylistContentDetails';
  protected $contentDetailsDataType = '';
  public $etag;
  public $id;
  public $kind;
  protected $localizationsType = 'Google_Service_YouTube_PlaylistLocalization';
  protected $localizationsDataType = 'map';
  protected $playerType = 'Google_Service_YouTube_PlaylistPlayer';
  protected $playerDataType = '';
  protected $snippetType = 'Google_Service_YouTube_PlaylistSnippet';
  protected $snippetDataType = '';
  protected $statusType = 'Google_Service_YouTube_PlaylistStatus';
  protected $statusDataType = '';


  public function setContentDetails(Google_Service_YouTube_PlaylistContentDetails $contentDetails)
  {
    $this->contentDetails = $contentDetails;
  }
  public function getContentDetails()
  {
    return $this->contentDetails;
  }
  public function setEtag($etag)
  {
    $this->etag = $etag;
  }
  public function getEtag()
  {
    return $this->etag;
  }
  public function setId($id)
  {
    $this->id = $id;
  }
  public function getId()
  {
    return $this->id;
  }
  public function setKind($kind)
  {
    $this->kind = $kind;
  }
  public function getKind()
  {
    return $this->kind;
  }
  public function setLocalizations($localizations)
  {
    $this->localizations = $localizations;
  }
  public function getLocalizations()
  {
    return $this->localizations;
  }
  public function setPlayer(Google_Service_YouTube_PlaylistPlayer $player)
  {
    $this->player = $player;
  }
  public function getPlayer()
  {
    return $this->player;
  }
  public function setSnippet(Google_Service_YouTube_PlaylistSnippet $snippet)
  {
    $this->snippet = $snippet;
  }
  public function getSnippet()
  {
    return $this->snippet;
  }
  public function setStatus(Google_Service_YouTube_PlaylistStatus $status)
  {
    $this->status = $status;
  }
  public function getStatus()
  {
    return $this->status;
  }
}

class Google_Service_YouTube_PlaylistContentDetails extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  public $itemCount;


  public function setItemCount($itemCount)
  {
    $this->itemCount = $itemCount;
  }
  public function getItemCount()
  {
    return $this->itemCount;
  }
}

class Google_Service_YouTube_PlaylistItem extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  protected $contentDetailsType = 'Google_Service_YouTube_PlaylistItemContentDetails';
  protected $contentDetailsDataType = '';
  public $etag;
  public $id;
  public $kind;
  protected $snippetType = 'Google_Service_YouTube_PlaylistItemSnippet';
  protected $snippetDataType = '';
  protected $statusType = 'Google_Service_YouTube_PlaylistItemStatus';
  protected $statusDataType = '';


  public function setContentDetails(Google_Service_YouTube_PlaylistItemContentDetails $contentDetails)
  {
    $this->contentDetails = $contentDetails;
  }
  public function getContentDetails()
  {
    return $this->contentDetails;
  }
  public function setEtag($etag)
  {
    $this->etag = $etag;
  }
  public function getEtag()
  {
    return $this->etag;
  }
  public function setId($id)
  {
    $this->id = $id;
  }
  public function getId()
  {
    return $this->id;
  }
  public function setKind($kind)
  {
    $this->kind = $kind;
  }
  public function getKind()
  {
    return $this->kind;
  }
  public function setSnippet(Google_Service_YouTube_PlaylistItemSnippet $snippet)
  {
    $this->snippet = $snippet;
  }
  public function getSnippet()
  {
    return $this->snippet;
  }
  public function setStatus(Google_Service_YouTube_PlaylistItemStatus $status)
  {
    $this->status = $status;
  }
  public function getStatus()
  {
    return $this->status;
  }
}

class Google_Service_YouTube_PlaylistItemContentDetails extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  public $endAt;
  public $note;
  public $startAt;
  public $videoId;


  public function setEndAt($endAt)
  {
    $this->endAt = $endAt;
  }
  public function getEndAt()
  {
    return $this->endAt;
  }
  public function setNote($note)
  {
    $this->note = $note;
  }
  public function getNote()
  {
    return $this->note;
  }
  public function setStartAt($startAt)
  {
    $this->startAt = $startAt;
  }
  public function getStartAt()
  {
    return $this->startAt;
  }
  public function setVideoId($videoId)
  {
    $this->videoId = $videoId;
  }
  public function getVideoId()
  {
    return $this->videoId;
  }
}

class Google_Service_YouTube_PlaylistItemListResponse extends Google_Collection
{
  protected $collection_key = 'items';
  protected $internal_gapi_mappings = array(
  );
  public $etag;
  public $eventId;
  protected $itemsType = 'Google_Service_YouTube_PlaylistItem';
  protected $itemsDataType = 'array';
  public $kind;
  public $nextPageToken;
  protected $pageInfoType = 'Google_Service_YouTube_PageInfo';
  protected $pageInfoDataType = '';
  public $prevPageToken;
  protected $tokenPaginationType = 'Google_Service_YouTube_TokenPagination';
  protected $tokenPaginationDataType = '';
  public $visitorId;


  public function setEtag($etag)
  {
    $this->etag = $etag;
  }
  public function getEtag()
  {
    return $this->etag;
  }
  public function setEventId($eventId)
  {
    $this->eventId = $eventId;
  }
  public function getEventId()
  {
    return $this->eventId;
  }
  public function setItems($items)
  {
    $this->items = $items;
  }
  public function getItems()
  {
    return $this->items;
  }
  public function setKind($kind)
  {
    $this->kind = $kind;
  }
  public function getKind()
  {
    return $this->kind;
  }
  public function setNextPageToken($nextPageToken)
  {
    $this->nextPageToken = $nextPageToken;
  }
  public function getNextPageToken()
  {
    return $this->nextPageToken;
  }
  public function setPageInfo(Google_Service_YouTube_PageInfo $pageInfo)
  {
    $this->pageInfo = $pageInfo;
  }
  public function getPageInfo()
  {
    return $this->pageInfo;
  }
  public function setPrevPageToken($prevPageToken)
  {
    $this->prevPageToken = $prevPageToken;
  }
  public function getPrevPageToken()
  {
    return $this->prevPageToken;
  }
  public function setTokenPagination(Google_Service_YouTube_TokenPagination $tokenPagination)
  {
    $this->tokenPagination = $tokenPagination;
  }
  public function getTokenPagination()
  {
    return $this->tokenPagination;
  }
  public function setVisitorId($visitorId)
  {
    $this->visitorId = $visitorId;
  }
  public function getVisitorId()
  {
    return $this->visitorId;
  }
}

class Google_Service_YouTube_PlaylistItemSnippet extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  public $channelId;
  public $channelTitle;
  public $description;
  public $playlistId;
  public $position;
  public $publishedAt;
  protected $resourceIdType = 'Google_Service_YouTube_ResourceId';
  protected $resourceIdDataType = '';
  protected $thumbnailsType = 'Google_Service_YouTube_ThumbnailDetails';
  protected $thumbnailsDataType = '';
  public $title;


  public function setChannelId($channelId)
  {
    $this->channelId = $channelId;
  }
  public function getChannelId()
  {
    return $this->channelId;
  }
  public function setChannelTitle($channelTitle)
  {
    $this->channelTitle = $channelTitle;
  }
  public function getChannelTitle()
  {
    return $this->channelTitle;
  }
  public function setDescription($description)
  {
    $this->description = $description;
  }
  public function getDescription()
  {
    return $this->description;
  }
  public function setPlaylistId($playlistId)
  {
    $this->playlistId = $playlistId;
  }
  public function getPlaylistId()
  {
    return $this->playlistId;
  }
  public function setPosition($position)
  {
    $this->position = $position;
  }
  public function getPosition()
  {
    return $this->position;
  }
  public function setPublishedAt($publishedAt)
  {
    $this->publishedAt = $publishedAt;
  }
  public function getPublishedAt()
  {
    return $this->publishedAt;
  }
  public function setResourceId(Google_Service_YouTube_ResourceId $resourceId)
  {
    $this->resourceId = $resourceId;
  }
  public function getResourceId()
  {
    return $this->resourceId;
  }
  public function setThumbnails(Google_Service_YouTube_ThumbnailDetails $thumbnails)
  {
    $this->thumbnails = $thumbnails;
  }
  public function getThumbnails()
  {
    return $this->thumbnails;
  }
  public function setTitle($title)
  {
    $this->title = $title;
  }
  public function getTitle()
  {
    return $this->title;
  }
}

class Google_Service_YouTube_PlaylistItemStatus extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  public $privacyStatus;


  public function setPrivacyStatus($privacyStatus)
  {
    $this->privacyStatus = $privacyStatus;
  }
  public function getPrivacyStatus()
  {
    return $this->privacyStatus;
  }
}

class Google_Service_YouTube_PlaylistListResponse extends Google_Collection
{
  protected $collection_key = 'items';
  protected $internal_gapi_mappings = array(
  );
  public $etag;
  public $eventId;
  protected $itemsType = 'Google_Service_YouTube_Playlist';
  protected $itemsDataType = 'array';
  public $kind;
  public $nextPageToken;
  protected $pageInfoType = 'Google_Service_YouTube_PageInfo';
  protected $pageInfoDataType = '';
  public $prevPageToken;
  protected $tokenPaginationType = 'Google_Service_YouTube_TokenPagination';
  protected $tokenPaginationDataType = '';
  public $visitorId;


  public function setEtag($etag)
  {
    $this->etag = $etag;
  }
  public function getEtag()
  {
    return $this->etag;
  }
  public function setEventId($eventId)
  {
    $this->eventId = $eventId;
  }
  public function getEventId()
  {
    return $this->eventId;
  }
  public function setItems($items)
  {
    $this->items = $items;
  }
  public function getItems()
  {
    return $this->items;
  }
  public function setKind($kind)
  {
    $this->kind = $kind;
  }
  public function getKind()
  {
    return $this->kind;
  }
  public function setNextPageToken($nextPageToken)
  {
    $this->nextPageToken = $nextPageToken;
  }
  public function getNextPageToken()
  {
    return $this->nextPageToken;
  }
  public function setPageInfo(Google_Service_YouTube_PageInfo $pageInfo)
  {
    $this->pageInfo = $pageInfo;
  }
  public function getPageInfo()
  {
    return $this->pageInfo;
  }
  public function setPrevPageToken($prevPageToken)
  {
    $this->prevPageToken = $prevPageToken;
  }
  public function getPrevPageToken()
  {
    return $this->prevPageToken;
  }
  public function setTokenPagination(Google_Service_YouTube_TokenPagination $tokenPagination)
  {
    $this->tokenPagination = $tokenPagination;
  }
  public function getTokenPagination()
  {
    return $this->tokenPagination;
  }
  public function setVisitorId($visitorId)
  {
    $this->visitorId = $visitorId;
  }
  public function getVisitorId()
  {
    return $this->visitorId;
  }
}

class Google_Service_YouTube_PlaylistLocalization extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  public $description;
  public $title;


  public function setDescription($description)
  {
    $this->description = $description;
  }
  public function getDescription()
  {
    return $this->description;
  }
  public function setTitle($title)
  {
    $this->title = $title;
  }
  public function getTitle()
  {
    return $this->title;
  }
}

class Google_Service_YouTube_PlaylistLocalizations extends Google_Model
{
}

class Google_Service_YouTube_PlaylistPlayer extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  public $embedHtml;


  public function setEmbedHtml($embedHtml)
  {
    $this->embedHtml = $embedHtml;
  }
  public function getEmbedHtml()
  {
    return $this->embedHtml;
  }
}

class Google_Service_YouTube_PlaylistSnippet extends Google_Collection
{
  protected $collection_key = 'tags';
  protected $internal_gapi_mappings = array(
  );
  public $channelId;
  public $channelTitle;
  public $defaultLanguage;
  public $description;
  protected $localizedType = 'Google_Service_YouTube_PlaylistLocalization';
  protected $localizedDataType = '';
  public $publishedAt;
  public $tags;
  protected $thumbnailsType = 'Google_Service_YouTube_ThumbnailDetails';
  protected $thumbnailsDataType = '';
  public $title;


  public function setChannelId($channelId)
  {
    $this->channelId = $channelId;
  }
  public function getChannelId()
  {
    return $this->channelId;
  }
  public function setChannelTitle($channelTitle)
  {
    $this->channelTitle = $channelTitle;
  }
  public function getChannelTitle()
  {
    return $this->channelTitle;
  }
  public function setDefaultLanguage($defaultLanguage)
  {
    $this->defaultLanguage = $defaultLanguage;
  }
  public function getDefaultLanguage()
  {
    return $this->defaultLanguage;
  }
  public function setDescription($description)
  {
    $this->description = $description;
  }
  public function getDescription()
  {
    return $this->description;
  }
  public function setLocalized(Google_Service_YouTube_PlaylistLocalization $localized)
  {
    $this->localized = $localized;
  }
  public function getLocalized()
  {
    return $this->localized;
  }
  public function setPublishedAt($publishedAt)
  {
    $this->publishedAt = $publishedAt;
  }
  public function getPublishedAt()
  {
    return $this->publishedAt;
  }
  public function setTags($tags)
  {
    $this->tags = $tags;
  }
  public function getTags()
  {
    return $this->tags;
  }
  public function setThumbnails(Google_Service_YouTube_ThumbnailDetails $thumbnails)
  {
    $this->thumbnails = $thumbnails;
  }
  public function getThumbnails()
  {
    return $this->thumbnails;
  }
  public function setTitle($title)
  {
    $this->title = $title;
  }
  public function getTitle()
  {
    return $this->title;
  }
}

class Google_Service_YouTube_PlaylistStatus extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  public $privacyStatus;


  public function setPrivacyStatus($privacyStatus)
  {
    $this->privacyStatus = $privacyStatus;
  }
  public function getPrivacyStatus()
  {
    return $this->privacyStatus;
  }
}

class Google_Service_YouTube_PromotedItem extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  public $customMessage;
  protected $idType = 'Google_Service_YouTube_PromotedItemId';
  protected $idDataType = '';
  public $promotedByContentOwner;
  protected $timingType = 'Google_Service_YouTube_InvideoTiming';
  protected $timingDataType = '';


  public function setCustomMessage($customMessage)
  {
    $this->customMessage = $customMessage;
  }
  public function getCustomMessage()
  {
    return $this->customMessage;
  }
  public function setId(Google_Service_YouTube_PromotedItemId $id)
  {
    $this->id = $id;
  }
  public function getId()
  {
    return $this->id;
  }
  public function setPromotedByContentOwner($promotedByContentOwner)
  {
    $this->promotedByContentOwner = $promotedByContentOwner;
  }
  public function getPromotedByContentOwner()
  {
    return $this->promotedByContentOwner;
  }
  public function setTiming(Google_Service_YouTube_InvideoTiming $timing)
  {
    $this->timing = $timing;
  }
  public function getTiming()
  {
    return $this->timing;
  }
}

class Google_Service_YouTube_PromotedItemId extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  public $recentlyUploadedBy;
  public $type;
  public $videoId;
  public $websiteUrl;


  public function setRecentlyUploadedBy($recentlyUploadedBy)
  {
    $this->recentlyUploadedBy = $recentlyUploadedBy;
  }
  public function getRecentlyUploadedBy()
  {
    return $this->recentlyUploadedBy;
  }
  public function setType($type)
  {
    $this->type = $type;
  }
  public function getType()
  {
    return $this->type;
  }
  public function setVideoId($videoId)
  {
    $this->videoId = $videoId;
  }
  public function getVideoId()
  {
    return $this->videoId;
  }
  public function setWebsiteUrl($websiteUrl)
  {
    $this->websiteUrl = $websiteUrl;
  }
  public function getWebsiteUrl()
  {
    return $this->websiteUrl;
  }
}

class Google_Service_YouTube_PropertyValue extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  public $property;
  public $value;


  public function setProperty($property)
  {
    $this->property = $property;
  }
  public function getProperty()
  {
    return $this->property;
  }
  public function setValue($value)
  {
    $this->value = $value;
  }
  public function getValue()
  {
    return $this->value;
  }
}

class Google_Service_YouTube_ResourceId extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  public $channelId;
  public $kind;
  public $playlistId;
  public $videoId;


  public function setChannelId($channelId)
  {
    $this->channelId = $channelId;
  }
  public function getChannelId()
  {
    return $this->channelId;
  }
  public function setKind($kind)
  {
    $this->kind = $kind;
  }
  public function getKind()
  {
    return $this->kind;
  }
  public function setPlaylistId($playlistId)
  {
    $this->playlistId = $playlistId;
  }
  public function getPlaylistId()
  {
    return $this->playlistId;
  }
  public function setVideoId($videoId)
  {
    $this->videoId = $videoId;
  }
  public function getVideoId()
  {
    return $this->videoId;
  }
}

class Google_Service_YouTube_SearchListResponse extends Google_Collection
{
  protected $collection_key = 'items';
  protected $internal_gapi_mappings = array(
  );
  public $etag;
  public $eventId;
  protected $itemsType = 'Google_Service_YouTube_SearchResult';
  protected $itemsDataType = 'array';
  public $kind;
  public $nextPageToken;
  protected $pageInfoType = 'Google_Service_YouTube_PageInfo';
  protected $pageInfoDataType = '';
  public $prevPageToken;
  protected $tokenPaginationType = 'Google_Service_YouTube_TokenPagination';
  protected $tokenPaginationDataType = '';
  public $visitorId;


  public function setEtag($etag)
  {
    $this->etag = $etag;
  }
  public function getEtag()
  {
    return $this->etag;
  }
  public function setEventId($eventId)
  {
    $this->eventId = $eventId;
  }
  public function getEventId()
  {
    return $this->eventId;
  }
  public function setItems($items)
  {
    $this->items = $items;
  }
  public function getItems()
  {
    return $this->items;
  }
  public function setKind($kind)
  {
    $this->kind = $kind;
  }
  public function getKind()
  {
    return $this->kind;
  }
  public function setNextPageToken($nextPageToken)
  {
    $this->nextPageToken = $nextPageToken;
  }
  public function getNextPageToken()
  {
    return $this->nextPageToken;
  }
  public function setPageInfo(Google_Service_YouTube_PageInfo $pageInfo)
  {
    $this->pageInfo = $pageInfo;
  }
  public function getPageInfo()
  {
    return $this->pageInfo;
  }
  public function setPrevPageToken($prevPageToken)
  {
    $this->prevPageToken = $prevPageToken;
  }
  public function getPrevPageToken()
  {
    return $this->prevPageToken;
  }
  public function setTokenPagination(Google_Service_YouTube_TokenPagination $tokenPagination)
  {
    $this->tokenPagination = $tokenPagination;
  }
  public function getTokenPagination()
  {
    return $this->tokenPagination;
  }
  public function setVisitorId($visitorId)
  {
    $this->visitorId = $visitorId;
  }
  public function getVisitorId()
  {
    return $this->visitorId;
  }
}

class Google_Service_YouTube_SearchResult extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  public $etag;
  protected $idType = 'Google_Service_YouTube_ResourceId';
  protected $idDataType = '';
  public $kind;
  protected $snippetType = 'Google_Service_YouTube_SearchResultSnippet';
  protected $snippetDataType = '';


  public function setEtag($etag)
  {
    $this->etag = $etag;
  }
  public function getEtag()
  {
    return $this->etag;
  }
  public function setId(Google_Service_YouTube_ResourceId $id)
  {
    $this->id = $id;
  }
  public function getId()
  {
    return $this->id;
  }
  public function setKind($kind)
  {
    $this->kind = $kind;
  }
  public function getKind()
  {
    return $this->kind;
  }
  public function setSnippet(Google_Service_YouTube_SearchResultSnippet $snippet)
  {
    $this->snippet = $snippet;
  }
  public function getSnippet()
  {
    return $this->snippet;
  }
}

class Google_Service_YouTube_SearchResultSnippet extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  public $channelId;
  public $channelTitle;
  public $description;
  public $liveBroadcastContent;
  public $publishedAt;
  protected $thumbnailsType = 'Google_Service_YouTube_ThumbnailDetails';
  protected $thumbnailsDataType = '';
  public $title;


  public function setChannelId($channelId)
  {
    $this->channelId = $channelId;
  }
  public function getChannelId()
  {
    return $this->channelId;
  }
  public function setChannelTitle($channelTitle)
  {
    $this->channelTitle = $channelTitle;
  }
  public function getChannelTitle()
  {
    return $this->channelTitle;
  }
  public function setDescription($description)
  {
    $this->description = $description;
  }
  public function getDescription()
  {
    return $this->description;
  }
  public function setLiveBroadcastContent($liveBroadcastContent)
  {
    $this->liveBroadcastContent = $liveBroadcastContent;
  }
  public function getLiveBroadcastContent()
  {
    return $this->liveBroadcastContent;
  }
  public function setPublishedAt($publishedAt)
  {
    $this->publishedAt = $publishedAt;
  }
  public function getPublishedAt()
  {
    return $this->publishedAt;
  }
  public function setThumbnails(Google_Service_YouTube_ThumbnailDetails $thumbnails)
  {
    $this->thumbnails = $thumbnails;
  }
  public function getThumbnails()
  {
    return $this->thumbnails;
  }
  public function setTitle($title)
  {
    $this->title = $title;
  }
  public function getTitle()
  {
    return $this->title;
  }
}

class Google_Service_YouTube_Subscription extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  protected $contentDetailsType = 'Google_Service_YouTube_SubscriptionContentDetails';
  protected $contentDetailsDataType = '';
  public $etag;
  public $id;
  public $kind;
  protected $snippetType = 'Google_Service_YouTube_SubscriptionSnippet';
  protected $snippetDataType = '';
  protected $subscriberSnippetType = 'Google_Service_YouTube_SubscriptionSubscriberSnippet';
  protected $subscriberSnippetDataType = '';


  public function setContentDetails(Google_Service_YouTube_SubscriptionContentDetails $contentDetails)
  {
    $this->contentDetails = $contentDetails;
  }
  public function getContentDetails()
  {
    return $this->contentDetails;
  }
  public function setEtag($etag)
  {
    $this->etag = $etag;
  }
  public function getEtag()
  {
    return $this->etag;
  }
  public function setId($id)
  {
    $this->id = $id;
  }
  public function getId()
  {
    return $this->id;
  }
  public function setKind($kind)
  {
    $this->kind = $kind;
  }
  public function getKind()
  {
    return $this->kind;
  }
  public function setSnippet(Google_Service_YouTube_SubscriptionSnippet $snippet)
  {
    $this->snippet = $snippet;
  }
  public function getSnippet()
  {
    return $this->snippet;
  }
  public function setSubscriberSnippet(Google_Service_YouTube_SubscriptionSubscriberSnippet $subscriberSnippet)
  {
    $this->subscriberSnippet = $subscriberSnippet;
  }
  public function getSubscriberSnippet()
  {
    return $this->subscriberSnippet;
  }
}

class Google_Service_YouTube_SubscriptionContentDetails extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  public $activityType;
  public $newItemCount;
  public $totalItemCount;


  public function setActivityType($activityType)
  {
    $this->activityType = $activityType;
  }
  public function getActivityType()
  {
    return $this->activityType;
  }
  public function setNewItemCount($newItemCount)
  {
    $this->newItemCount = $newItemCount;
  }
  public function getNewItemCount()
  {
    return $this->newItemCount;
  }
  public function setTotalItemCount($totalItemCount)
  {
    $this->totalItemCount = $totalItemCount;
  }
  public function getTotalItemCount()
  {
    return $this->totalItemCount;
  }
}

class Google_Service_YouTube_SubscriptionListResponse extends Google_Collection
{
  protected $collection_key = 'items';
  protected $internal_gapi_mappings = array(
  );
  public $etag;
  public $eventId;
  protected $itemsType = 'Google_Service_YouTube_Subscription';
  protected $itemsDataType = 'array';
  public $kind;
  public $nextPageToken;
  protected $pageInfoType = 'Google_Service_YouTube_PageInfo';
  protected $pageInfoDataType = '';
  public $prevPageToken;
  protected $tokenPaginationType = 'Google_Service_YouTube_TokenPagination';
  protected $tokenPaginationDataType = '';
  public $visitorId;


  public function setEtag($etag)
  {
    $this->etag = $etag;
  }
  public function getEtag()
  {
    return $this->etag;
  }
  public function setEventId($eventId)
  {
    $this->eventId = $eventId;
  }
  public function getEventId()
  {
    return $this->eventId;
  }
  public function setItems($items)
  {
    $this->items = $items;
  }
  public function getItems()
  {
    return $this->items;
  }
  public function setKind($kind)
  {
    $this->kind = $kind;
  }
  public function getKind()
  {
    return $this->kind;
  }
  public function setNextPageToken($nextPageToken)
  {
    $this->nextPageToken = $nextPageToken;
  }
  public function getNextPageToken()
  {
    return $this->nextPageToken;
  }
  public function setPageInfo(Google_Service_YouTube_PageInfo $pageInfo)
  {
    $this->pageInfo = $pageInfo;
  }
  public function getPageInfo()
  {
    return $this->pageInfo;
  }
  public function setPrevPageToken($prevPageToken)
  {
    $this->prevPageToken = $prevPageToken;
  }
  public function getPrevPageToken()
  {
    return $this->prevPageToken;
  }
  public function setTokenPagination(Google_Service_YouTube_TokenPagination $tokenPagination)
  {
    $this->tokenPagination = $tokenPagination;
  }
  public function getTokenPagination()
  {
    return $this->tokenPagination;
  }
  public function setVisitorId($visitorId)
  {
    $this->visitorId = $visitorId;
  }
  public function getVisitorId()
  {
    return $this->visitorId;
  }
}

class Google_Service_YouTube_SubscriptionSnippet extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  public $channelId;
  public $channelTitle;
  public $description;
  public $publishedAt;
  protected $resourceIdType = 'Google_Service_YouTube_ResourceId';
  protected $resourceIdDataType = '';
  protected $thumbnailsType = 'Google_Service_YouTube_ThumbnailDetails';
  protected $thumbnailsDataType = '';
  public $title;


  public function setChannelId($channelId)
  {
    $this->channelId = $channelId;
  }
  public function getChannelId()
  {
    return $this->channelId;
  }
  public function setChannelTitle($channelTitle)
  {
    $this->channelTitle = $channelTitle;
  }
  public function getChannelTitle()
  {
    return $this->channelTitle;
  }
  public function setDescription($description)
  {
    $this->description = $description;
  }
  public function getDescription()
  {
    return $this->description;
  }
  public function setPublishedAt($publishedAt)
  {
    $this->publishedAt = $publishedAt;
  }
  public function getPublishedAt()
  {
    return $this->publishedAt;
  }
  public function setResourceId(Google_Service_YouTube_ResourceId $resourceId)
  {
    $this->resourceId = $resourceId;
  }
  public function getResourceId()
  {
    return $this->resourceId;
  }
  public function setThumbnails(Google_Service_YouTube_ThumbnailDetails $thumbnails)
  {
    $this->thumbnails = $thumbnails;
  }
  public function getThumbnails()
  {
    return $this->thumbnails;
  }
  public function setTitle($title)
  {
    $this->title = $title;
  }
  public function getTitle()
  {
    return $this->title;
  }
}

class Google_Service_YouTube_SubscriptionSubscriberSnippet extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  public $channelId;
  public $description;
  protected $thumbnailsType = 'Google_Service_YouTube_ThumbnailDetails';
  protected $thumbnailsDataType = '';
  public $title;


  public function setChannelId($channelId)
  {
    $this->channelId = $channelId;
  }
  public function getChannelId()
  {
    return $this->channelId;
  }
  public function setDescription($description)
  {
    $this->description = $description;
  }
  public function getDescription()
  {
    return $this->description;
  }
  public function setThumbnails(Google_Service_YouTube_ThumbnailDetails $thumbnails)
  {
    $this->thumbnails = $thumbnails;
  }
  public function getThumbnails()
  {
    return $this->thumbnails;
  }
  public function setTitle($title)
  {
    $this->title = $title;
  }
  public function getTitle()
  {
    return $this->title;
  }
}

class Google_Service_YouTube_Thumbnail extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  public $height;
  public $url;
  public $width;


  public function setHeight($height)
  {
    $this->height = $height;
  }
  public function getHeight()
  {
    return $this->height;
  }
  public function setUrl($url)
  {
    $this->url = $url;
  }
  public function getUrl()
  {
    return $this->url;
  }
  public function setWidth($width)
  {
    $this->width = $width;
  }
  public function getWidth()
  {
    return $this->width;
  }
}

class Google_Service_YouTube_ThumbnailDetails extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  protected $defaultType = 'Google_Service_YouTube_Thumbnail';
  protected $defaultDataType = '';
  protected $highType = 'Google_Service_YouTube_Thumbnail';
  protected $highDataType = '';
  protected $maxresType = 'Google_Service_YouTube_Thumbnail';
  protected $maxresDataType = '';
  protected $mediumType = 'Google_Service_YouTube_Thumbnail';
  protected $mediumDataType = '';
  protected $standardType = 'Google_Service_YouTube_Thumbnail';
  protected $standardDataType = '';


  public function setDefault(Google_Service_YouTube_Thumbnail $default)
  {
    $this->default = $default;
  }
  public function getDefault()
  {
    return $this->default;
  }
  public function setHigh(Google_Service_YouTube_Thumbnail $high)
  {
    $this->high = $high;
  }
  public function getHigh()
  {
    return $this->high;
  }
  public function setMaxres(Google_Service_YouTube_Thumbnail $maxres)
  {
    $this->maxres = $maxres;
  }
  public function getMaxres()
  {
    return $this->maxres;
  }
  public function setMedium(Google_Service_YouTube_Thumbnail $medium)
  {
    $this->medium = $medium;
  }
  public function getMedium()
  {
    return $this->medium;
  }
  public function setStandard(Google_Service_YouTube_Thumbnail $standard)
  {
    $this->standard = $standard;
  }
  public function getStandard()
  {
    return $this->standard;
  }
}

class Google_Service_YouTube_ThumbnailSetResponse extends Google_Collection
{
  protected $collection_key = 'items';
  protected $internal_gapi_mappings = array(
  );
  public $etag;
  public $eventId;
  protected $itemsType = 'Google_Service_YouTube_ThumbnailDetails';
  protected $itemsDataType = 'array';
  public $kind;
  public $visitorId;


  public function setEtag($etag)
  {
    $this->etag = $etag;
  }
  public function getEtag()
  {
    return $this->etag;
  }
  public function setEventId($eventId)
  {
    $this->eventId = $eventId;
  }
  public function getEventId()
  {
    return $this->eventId;
  }
  public function setItems($items)
  {
    $this->items = $items;
  }
  public function getItems()
  {
    return $this->items;
  }
  public function setKind($kind)
  {
    $this->kind = $kind;
  }
  public function getKind()
  {
    return $this->kind;
  }
  public function setVisitorId($visitorId)
  {
    $this->visitorId = $visitorId;
  }
  public function getVisitorId()
  {
    return $this->visitorId;
  }
}

class Google_Service_YouTube_TokenPagination extends Google_Model
{
}

class Google_Service_YouTube_Video extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  protected $ageGatingType = 'Google_Service_YouTube_VideoAgeGating';
  protected $ageGatingDataType = '';
  protected $contentDetailsType = 'Google_Service_YouTube_VideoContentDetails';
  protected $contentDetailsDataType = '';
  protected $conversionPingsType = 'Google_Service_YouTube_VideoConversionPings';
  protected $conversionPingsDataType = '';
  public $etag;
  protected $fileDetailsType = 'Google_Service_YouTube_VideoFileDetails';
  protected $fileDetailsDataType = '';
  public $id;
  public $kind;
  protected $liveStreamingDetailsType = 'Google_Service_YouTube_VideoLiveStreamingDetails';
  protected $liveStreamingDetailsDataType = '';
  protected $localizationsType = 'Google_Service_YouTube_VideoLocalization';
  protected $localizationsDataType = 'map';
  protected $monetizationDetailsType = 'Google_Service_YouTube_VideoMonetizationDetails';
  protected $monetizationDetailsDataType = '';
  protected $playerType = 'Google_Service_YouTube_VideoPlayer';
  protected $playerDataType = '';
  protected $processingDetailsType = 'Google_Service_YouTube_VideoProcessingDetails';
  protected $processingDetailsDataType = '';
  protected $projectDetailsType = 'Google_Service_YouTube_VideoProjectDetails';
  protected $projectDetailsDataType = '';
  protected $recordingDetailsType = 'Google_Service_YouTube_VideoRecordingDetails';
  protected $recordingDetailsDataType = '';
  protected $snippetType = 'Google_Service_YouTube_VideoSnippet';
  protected $snippetDataType = '';
  protected $statisticsType = 'Google_Service_YouTube_VideoStatistics';
  protected $statisticsDataType = '';
  protected $statusType = 'Google_Service_YouTube_VideoStatus';
  protected $statusDataType = '';
  protected $suggestionsType = 'Google_Service_YouTube_VideoSuggestions';
  protected $suggestionsDataType = '';
  protected $topicDetailsType = 'Google_Service_YouTube_VideoTopicDetails';
  protected $topicDetailsDataType = '';


  public function setAgeGating(Google_Service_YouTube_VideoAgeGating $ageGating)
  {
    $this->ageGating = $ageGating;
  }
  public function getAgeGating()
  {
    return $this->ageGating;
  }
  public function setContentDetails(Google_Service_YouTube_VideoContentDetails $contentDetails)
  {
    $this->contentDetails = $contentDetails;
  }
  public function getContentDetails()
  {
    return $this->contentDetails;
  }
  public function setConversionPings(Google_Service_YouTube_VideoConversionPings $conversionPings)
  {
    $this->conversionPings = $conversionPings;
  }
  public function getConversionPings()
  {
    return $this->conversionPings;
  }
  public function setEtag($etag)
  {
    $this->etag = $etag;
  }
  public function getEtag()
  {
    return $this->etag;
  }
  public function setFileDetails(Google_Service_YouTube_VideoFileDetails $fileDetails)
  {
    $this->fileDetails = $fileDetails;
  }
  public function getFileDetails()
  {
    return $this->fileDetails;
  }
  public function setId($id)
  {
    $this->id = $id;
  }
  public function getId()
  {
    return $this->id;
  }
  public function setKind($kind)
  {
    $this->kind = $kind;
  }
  public function getKind()
  {
    return $this->kind;
  }
  public function setLiveStreamingDetails(Google_Service_YouTube_VideoLiveStreamingDetails $liveStreamingDetails)
  {
    $this->liveStreamingDetails = $liveStreamingDetails;
  }
  public function getLiveStreamingDetails()
  {
    return $this->liveStreamingDetails;
  }
  public function setLocalizations($localizations)
  {
    $this->localizations = $localizations;
  }
  public function getLocalizations()
  {
    return $this->localizations;
  }
  public function setMonetizationDetails(Google_Service_YouTube_VideoMonetizationDetails $monetizationDetails)
  {
    $this->monetizationDetails = $monetizationDetails;
  }
  public function getMonetizationDetails()
  {
    return $this->monetizationDetails;
  }
  public function setPlayer(Google_Service_YouTube_VideoPlayer $player)
  {
    $this->player = $player;
  }
  public function getPlayer()
  {
    return $this->player;
  }
  public function setProcessingDetails(Google_Service_YouTube_VideoProcessingDetails $processingDetails)
  {
    $this->processingDetails = $processingDetails;
  }
  public function getProcessingDetails()
  {
    return $this->processingDetails;
  }
  public function setProjectDetails(Google_Service_YouTube_VideoProjectDetails $projectDetails)
  {
    $this->projectDetails = $projectDetails;
  }
  public function getProjectDetails()
  {
    return $this->projectDetails;
  }
  public function setRecordingDetails(Google_Service_YouTube_VideoRecordingDetails $recordingDetails)
  {
    $this->recordingDetails = $recordingDetails;
  }
  public function getRecordingDetails()
  {
    return $this->recordingDetails;
  }
  public function setSnippet(Google_Service_YouTube_VideoSnippet $snippet)
  {
    $this->snippet = $snippet;
  }
  public function getSnippet()
  {
    return $this->snippet;
  }
  public function setStatistics(Google_Service_YouTube_VideoStatistics $statistics)
  {
    $this->statistics = $statistics;
  }
  public function getStatistics()
  {
    return $this->statistics;
  }
  public function setStatus(Google_Service_YouTube_VideoStatus $status)
  {
    $this->status = $status;
  }
  public function getStatus()
  {
    return $this->status;
  }
  public function setSuggestions(Google_Service_YouTube_VideoSuggestions $suggestions)
  {
    $this->suggestions = $suggestions;
  }
  public function getSuggestions()
  {
    return $this->suggestions;
  }
  public function setTopicDetails(Google_Service_YouTube_VideoTopicDetails $topicDetails)
  {
    $this->topicDetails = $topicDetails;
  }
  public function getTopicDetails()
  {
    return $this->topicDetails;
  }
}

class Google_Service_YouTube_VideoAbuseReport extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  public $comments;
  public $language;
  public $reasonId;
  public $secondaryReasonId;
  public $videoId;


  public function setComments($comments)
  {
    $this->comments = $comments;
  }
  public function getComments()
  {
    return $this->comments;
  }
  public function setLanguage($language)
  {
    $this->language = $language;
  }
  public function getLanguage()
  {
    return $this->language;
  }
  public function setReasonId($reasonId)
  {
    $this->reasonId = $reasonId;
  }
  public function getReasonId()
  {
    return $this->reasonId;
  }
  public function setSecondaryReasonId($secondaryReasonId)
  {
    $this->secondaryReasonId = $secondaryReasonId;
  }
  public function getSecondaryReasonId()
  {
    return $this->secondaryReasonId;
  }
  public function setVideoId($videoId)
  {
    $this->videoId = $videoId;
  }
  public function getVideoId()
  {
    return $this->videoId;
  }
}

class Google_Service_YouTube_VideoAbuseReportReason extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  public $etag;
  public $id;
  public $kind;
  protected $snippetType = 'Google_Service_YouTube_VideoAbuseReportReasonSnippet';
  protected $snippetDataType = '';


  public function setEtag($etag)
  {
    $this->etag = $etag;
  }
  public function getEtag()
  {
    return $this->etag;
  }
  public function setId($id)
  {
    $this->id = $id;
  }
  public function getId()
  {
    return $this->id;
  }
  public function setKind($kind)
  {
    $this->kind = $kind;
  }
  public function getKind()
  {
    return $this->kind;
  }
  public function setSnippet(Google_Service_YouTube_VideoAbuseReportReasonSnippet $snippet)
  {
    $this->snippet = $snippet;
  }
  public function getSnippet()
  {
    return $this->snippet;
  }
}

class Google_Service_YouTube_VideoAbuseReportReasonListResponse extends Google_Collection
{
  protected $collection_key = 'items';
  protected $internal_gapi_mappings = array(
  );
  public $etag;
  public $eventId;
  protected $itemsType = 'Google_Service_YouTube_VideoAbuseReportReason';
  protected $itemsDataType = 'array';
  public $kind;
  public $visitorId;


  public function setEtag($etag)
  {
    $this->etag = $etag;
  }
  public function getEtag()
  {
    return $this->etag;
  }
  public function setEventId($eventId)
  {
    $this->eventId = $eventId;
  }
  public function getEventId()
  {
    return $this->eventId;
  }
  public function setItems($items)
  {
    $this->items = $items;
  }
  public function getItems()
  {
    return $this->items;
  }
  public function setKind($kind)
  {
    $this->kind = $kind;
  }
  public function getKind()
  {
    return $this->kind;
  }
  public function setVisitorId($visitorId)
  {
    $this->visitorId = $visitorId;
  }
  public function getVisitorId()
  {
    return $this->visitorId;
  }
}

class Google_Service_YouTube_VideoAbuseReportReasonSnippet extends Google_Collection
{
  protected $collection_key = 'secondaryReasons';
  protected $internal_gapi_mappings = array(
  );
  public $label;
  protected $secondaryReasonsType = 'Google_Service_YouTube_VideoAbuseReportSecondaryReason';
  protected $secondaryReasonsDataType = 'array';


  public function setLabel($label)
  {
    $this->label = $label;
  }
  public function getLabel()
  {
    return $this->label;
  }
  public function setSecondaryReasons($secondaryReasons)
  {
    $this->secondaryReasons = $secondaryReasons;
  }
  public function getSecondaryReasons()
  {
    return $this->secondaryReasons;
  }
}

class Google_Service_YouTube_VideoAbuseReportSecondaryReason extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  public $id;
  public $label;


  public function setId($id)
  {
    $this->id = $id;
  }
  public function getId()
  {
    return $this->id;
  }
  public function setLabel($label)
  {
    $this->label = $label;
  }
  public function getLabel()
  {
    return $this->label;
  }
}

class Google_Service_YouTube_VideoAgeGating extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  public $alcoholContent;
  public $restricted;
  public $videoGameRating;


  public function setAlcoholContent($alcoholContent)
  {
    $this->alcoholContent = $alcoholContent;
  }
  public function getAlcoholContent()
  {
    return $this->alcoholContent;
  }
  public function setRestricted($restricted)
  {
    $this->restricted = $restricted;
  }
  public function getRestricted()
  {
    return $this->restricted;
  }
  public function setVideoGameRating($videoGameRating)
  {
    $this->videoGameRating = $videoGameRating;
  }
  public function getVideoGameRating()
  {
    return $this->videoGameRating;
  }
}

class Google_Service_YouTube_VideoCategory extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  public $etag;
  public $id;
  public $kind;
  protected $snippetType = 'Google_Service_YouTube_VideoCategorySnippet';
  protected $snippetDataType = '';


  public function setEtag($etag)
  {
    $this->etag = $etag;
  }
  public function getEtag()
  {
    return $this->etag;
  }
  public function setId($id)
  {
    $this->id = $id;
  }
  public function getId()
  {
    return $this->id;
  }
  public function setKind($kind)
  {
    $this->kind = $kind;
  }
  public function getKind()
  {
    return $this->kind;
  }
  public function setSnippet(Google_Service_YouTube_VideoCategorySnippet $snippet)
  {
    $this->snippet = $snippet;
  }
  public function getSnippet()
  {
    return $this->snippet;
  }
}

class Google_Service_YouTube_VideoCategoryListResponse extends Google_Collection
{
  protected $collection_key = 'items';
  protected $internal_gapi_mappings = array(
  );
  public $etag;
  public $eventId;
  protected $itemsType = 'Google_Service_YouTube_VideoCategory';
  protected $itemsDataType = 'array';
  public $kind;
  public $nextPageToken;
  protected $pageInfoType = 'Google_Service_YouTube_PageInfo';
  protected $pageInfoDataType = '';
  public $prevPageToken;
  protected $tokenPaginationType = 'Google_Service_YouTube_TokenPagination';
  protected $tokenPaginationDataType = '';
  public $visitorId;


  public function setEtag($etag)
  {
    $this->etag = $etag;
  }
  public function getEtag()
  {
    return $this->etag;
  }
  public function setEventId($eventId)
  {
    $this->eventId = $eventId;
  }
  public function getEventId()
  {
    return $this->eventId;
  }
  public function setItems($items)
  {
    $this->items = $items;
  }
  public function getItems()
  {
    return $this->items;
  }
  public function setKind($kind)
  {
    $this->kind = $kind;
  }
  public function getKind()
  {
    return $this->kind;
  }
  public function setNextPageToken($nextPageToken)
  {
    $this->nextPageToken = $nextPageToken;
  }
  public function getNextPageToken()
  {
    return $this->nextPageToken;
  }
  public function setPageInfo(Google_Service_YouTube_PageInfo $pageInfo)
  {
    $this->pageInfo = $pageInfo;
  }
  public function getPageInfo()
  {
    return $this->pageInfo;
  }
  public function setPrevPageToken($prevPageToken)
  {
    $this->prevPageToken = $prevPageToken;
  }
  public function getPrevPageToken()
  {
    return $this->prevPageToken;
  }
  public function setTokenPagination(Google_Service_YouTube_TokenPagination $tokenPagination)
  {
    $this->tokenPagination = $tokenPagination;
  }
  public function getTokenPagination()
  {
    return $this->tokenPagination;
  }
  public function setVisitorId($visitorId)
  {
    $this->visitorId = $visitorId;
  }
  public function getVisitorId()
  {
    return $this->visitorId;
  }
}

class Google_Service_YouTube_VideoCategorySnippet extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  public $assignable;
  public $channelId;
  public $title;


  public function setAssignable($assignable)
  {
    $this->assignable = $assignable;
  }
  public function getAssignable()
  {
    return $this->assignable;
  }
  public function setChannelId($channelId)
  {
    $this->channelId = $channelId;
  }
  public function getChannelId()
  {
    return $this->channelId;
  }
  public function setTitle($title)
  {
    $this->title = $title;
  }
  public function getTitle()
  {
    return $this->title;
  }
}

class Google_Service_YouTube_VideoContentDetails extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  public $caption;
  protected $contentRatingType = 'Google_Service_YouTube_ContentRating';
  protected $contentRatingDataType = '';
  protected $countryRestrictionType = 'Google_Service_YouTube_AccessPolicy';
  protected $countryRestrictionDataType = '';
  public $definition;
  public $dimension;
  public $duration;
  public $licensedContent;
  protected $regionRestrictionType = 'Google_Service_YouTube_VideoContentDetailsRegionRestriction';
  protected $regionRestrictionDataType = '';


  public function setCaption($caption)
  {
    $this->caption = $caption;
  }
  public function getCaption()
  {
    return $this->caption;
  }
  public function setContentRating(Google_Service_YouTube_ContentRating $contentRating)
  {
    $this->contentRating = $contentRating;
  }
  public function getContentRating()
  {
    return $this->contentRating;
  }
  public function setCountryRestriction(Google_Service_YouTube_AccessPolicy $countryRestriction)
  {
    $this->countryRestriction = $countryRestriction;
  }
  public function getCountryRestriction()
  {
    return $this->countryRestriction;
  }
  public function setDefinition($definition)
  {
    $this->definition = $definition;
  }
  public function getDefinition()
  {
    return $this->definition;
  }
  public function setDimension($dimension)
  {
    $this->dimension = $dimension;
  }
  public function getDimension()
  {
    return $this->dimension;
  }
  public function setDuration($duration)
  {
    $this->duration = $duration;
  }
  public function getDuration()
  {
    return $this->duration;
  }
  public function setLicensedContent($licensedContent)
  {
    $this->licensedContent = $licensedContent;
  }
  public function getLicensedContent()
  {
    return $this->licensedContent;
  }
  public function setRegionRestriction(Google_Service_YouTube_VideoContentDetailsRegionRestriction $regionRestriction)
  {
    $this->regionRestriction = $regionRestriction;
  }
  public function getRegionRestriction()
  {
    return $this->regionRestriction;
  }
}

class Google_Service_YouTube_VideoContentDetailsRegionRestriction extends Google_Collection
{
  protected $collection_key = 'blocked';
  protected $internal_gapi_mappings = array(
  );
  public $allowed;
  public $blocked;


  public function setAllowed($allowed)
  {
    $this->allowed = $allowed;
  }
  public function getAllowed()
  {
    return $this->allowed;
  }
  public function setBlocked($blocked)
  {
    $this->blocked = $blocked;
  }
  public function getBlocked()
  {
    return $this->blocked;
  }
}

class Google_Service_YouTube_VideoConversionPing extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  public $context;
  public $conversionUrl;


  public function setContext($context)
  {
    $this->context = $context;
  }
  public function getContext()
  {
    return $this->context;
  }
  public function setConversionUrl($conversionUrl)
  {
    $this->conversionUrl = $conversionUrl;
  }
  public function getConversionUrl()
  {
    return $this->conversionUrl;
  }
}

class Google_Service_YouTube_VideoConversionPings extends Google_Collection
{
  protected $collection_key = 'pings';
  protected $internal_gapi_mappings = array(
  );
  protected $pingsType = 'Google_Service_YouTube_VideoConversionPing';
  protected $pingsDataType = 'array';


  public function setPings($pings)
  {
    $this->pings = $pings;
  }
  public function getPings()
  {
    return $this->pings;
  }
}

class Google_Service_YouTube_VideoFileDetails extends Google_Collection
{
  protected $collection_key = 'videoStreams';
  protected $internal_gapi_mappings = array(
  );
  protected $audioStreamsType = 'Google_Service_YouTube_VideoFileDetailsAudioStream';
  protected $audioStreamsDataType = 'array';
  public $bitrateBps;
  public $container;
  public $creationTime;
  public $durationMs;
  public $fileName;
  public $fileSize;
  public $fileType;
  protected $recordingLocationType = 'Google_Service_YouTube_GeoPoint';
  protected $recordingLocationDataType = '';
  protected $videoStreamsType = 'Google_Service_YouTube_VideoFileDetailsVideoStream';
  protected $videoStreamsDataType = 'array';


  public function setAudioStreams($audioStreams)
  {
    $this->audioStreams = $audioStreams;
  }
  public function getAudioStreams()
  {
    return $this->audioStreams;
  }
  public function setBitrateBps($bitrateBps)
  {
    $this->bitrateBps = $bitrateBps;
  }
  public function getBitrateBps()
  {
    return $this->bitrateBps;
  }
  public function setContainer($container)
  {
    $this->container = $container;
  }
  public function getContainer()
  {
    return $this->container;
  }
  public function setCreationTime($creationTime)
  {
    $this->creationTime = $creationTime;
  }
  public function getCreationTime()
  {
    return $this->creationTime;
  }
  public function setDurationMs($durationMs)
  {
    $this->durationMs = $durationMs;
  }
  public function getDurationMs()
  {
    return $this->durationMs;
  }
  public function setFileName($fileName)
  {
    $this->fileName = $fileName;
  }
  public function getFileName()
  {
    return $this->fileName;
  }
  public function setFileSize($fileSize)
  {
    $this->fileSize = $fileSize;
  }
  public function getFileSize()
  {
    return $this->fileSize;
  }
  public function setFileType($fileType)
  {
    $this->fileType = $fileType;
  }
  public function getFileType()
  {
    return $this->fileType;
  }
  public function setRecordingLocation(Google_Service_YouTube_GeoPoint $recordingLocation)
  {
    $this->recordingLocation = $recordingLocation;
  }
  public function getRecordingLocation()
  {
    return $this->recordingLocation;
  }
  public function setVideoStreams($videoStreams)
  {
    $this->videoStreams = $videoStreams;
  }
  public function getVideoStreams()
  {
    return $this->videoStreams;
  }
}

class Google_Service_YouTube_VideoFileDetailsAudioStream extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  public $bitrateBps;
  public $channelCount;
  public $codec;
  public $vendor;


  public function setBitrateBps($bitrateBps)
  {
    $this->bitrateBps = $bitrateBps;
  }
  public function getBitrateBps()
  {
    return $this->bitrateBps;
  }
  public function setChannelCount($channelCount)
  {
    $this->channelCount = $channelCount;
  }
  public function getChannelCount()
  {
    return $this->channelCount;
  }
  public function setCodec($codec)
  {
    $this->codec = $codec;
  }
  public function getCodec()
  {
    return $this->codec;
  }
  public function setVendor($vendor)
  {
    $this->vendor = $vendor;
  }
  public function getVendor()
  {
    return $this->vendor;
  }
}

class Google_Service_YouTube_VideoFileDetailsVideoStream extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  public $aspectRatio;
  public $bitrateBps;
  public $codec;
  public $frameRateFps;
  public $heightPixels;
  public $rotation;
  public $vendor;
  public $widthPixels;


  public function setAspectRatio($aspectRatio)
  {
    $this->aspectRatio = $aspectRatio;
  }
  public function getAspectRatio()
  {
    return $this->aspectRatio;
  }
  public function setBitrateBps($bitrateBps)
  {
    $this->bitrateBps = $bitrateBps;
  }
  public function getBitrateBps()
  {
    return $this->bitrateBps;
  }
  public function setCodec($codec)
  {
    $this->codec = $codec;
  }
  public function getCodec()
  {
    return $this->codec;
  }
  public function setFrameRateFps($frameRateFps)
  {
    $this->frameRateFps = $frameRateFps;
  }
  public function getFrameRateFps()
  {
    return $this->frameRateFps;
  }
  public function setHeightPixels($heightPixels)
  {
    $this->heightPixels = $heightPixels;
  }
  public function getHeightPixels()
  {
    return $this->heightPixels;
  }
  public function setRotation($rotation)
  {
    $this->rotation = $rotation;
  }
  public function getRotation()
  {
    return $this->rotation;
  }
  public function setVendor($vendor)
  {
    $this->vendor = $vendor;
  }
  public function getVendor()
  {
    return $this->vendor;
  }
  public function setWidthPixels($widthPixels)
  {
    $this->widthPixels = $widthPixels;
  }
  public function getWidthPixels()
  {
    return $this->widthPixels;
  }
}

class Google_Service_YouTube_VideoGetRatingResponse extends Google_Collection
{
  protected $collection_key = 'items';
  protected $internal_gapi_mappings = array(
  );
  public $etag;
  public $eventId;
  protected $itemsType = 'Google_Service_YouTube_VideoRating';
  protected $itemsDataType = 'array';
  public $kind;
  public $visitorId;


  public function setEtag($etag)
  {
    $this->etag = $etag;
  }
  public function getEtag()
  {
    return $this->etag;
  }
  public function setEventId($eventId)
  {
    $this->eventId = $eventId;
  }
  public function getEventId()
  {
    return $this->eventId;
  }
  public function setItems($items)
  {
    $this->items = $items;
  }
  public function getItems()
  {
    return $this->items;
  }
  public function setKind($kind)
  {
    $this->kind = $kind;
  }
  public function getKind()
  {
    return $this->kind;
  }
  public function setVisitorId($visitorId)
  {
    $this->visitorId = $visitorId;
  }
  public function getVisitorId()
  {
    return $this->visitorId;
  }
}

class Google_Service_YouTube_VideoListResponse extends Google_Collection
{
  protected $collection_key = 'items';
  protected $internal_gapi_mappings = array(
  );
  public $etag;
  public $eventId;
  protected $itemsType = 'Google_Service_YouTube_Video';
  protected $itemsDataType = 'array';
  public $kind;
  public $nextPageToken;
  protected $pageInfoType = 'Google_Service_YouTube_PageInfo';
  protected $pageInfoDataType = '';
  public $prevPageToken;
  protected $tokenPaginationType = 'Google_Service_YouTube_TokenPagination';
  protected $tokenPaginationDataType = '';
  public $visitorId;


  public function setEtag($etag)
  {
    $this->etag = $etag;
  }
  public function getEtag()
  {
    return $this->etag;
  }
  public function setEventId($eventId)
  {
    $this->eventId = $eventId;
  }
  public function getEventId()
  {
    return $this->eventId;
  }
  public function setItems($items)
  {
    $this->items = $items;
  }
  public function getItems()
  {
    return $this->items;
  }
  public function setKind($kind)
  {
    $this->kind = $kind;
  }
  public function getKind()
  {
    return $this->kind;
  }
  public function setNextPageToken($nextPageToken)
  {
    $this->nextPageToken = $nextPageToken;
  }
  public function getNextPageToken()
  {
    return $this->nextPageToken;
  }
  public function setPageInfo(Google_Service_YouTube_PageInfo $pageInfo)
  {
    $this->pageInfo = $pageInfo;
  }
  public function getPageInfo()
  {
    return $this->pageInfo;
  }
  public function setPrevPageToken($prevPageToken)
  {
    $this->prevPageToken = $prevPageToken;
  }
  public function getPrevPageToken()
  {
    return $this->prevPageToken;
  }
  public function setTokenPagination(Google_Service_YouTube_TokenPagination $tokenPagination)
  {
    $this->tokenPagination = $tokenPagination;
  }
  public function getTokenPagination()
  {
    return $this->tokenPagination;
  }
  public function setVisitorId($visitorId)
  {
    $this->visitorId = $visitorId;
  }
  public function getVisitorId()
  {
    return $this->visitorId;
  }
}

class Google_Service_YouTube_VideoLiveStreamingDetails extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  public $actualEndTime;
  public $actualStartTime;
  public $concurrentViewers;
  public $scheduledEndTime;
  public $scheduledStartTime;


  public function setActualEndTime($actualEndTime)
  {
    $this->actualEndTime = $actualEndTime;
  }
  public function getActualEndTime()
  {
    return $this->actualEndTime;
  }
  public function setActualStartTime($actualStartTime)
  {
    $this->actualStartTime = $actualStartTime;
  }
  public function getActualStartTime()
  {
    return $this->actualStartTime;
  }
  public function setConcurrentViewers($concurrentViewers)
  {
    $this->concurrentViewers = $concurrentViewers;
  }
  public function getConcurrentViewers()
  {
    return $this->concurrentViewers;
  }
  public function setScheduledEndTime($scheduledEndTime)
  {
    $this->scheduledEndTime = $scheduledEndTime;
  }
  public function getScheduledEndTime()
  {
    return $this->scheduledEndTime;
  }
  public function setScheduledStartTime($scheduledStartTime)
  {
    $this->scheduledStartTime = $scheduledStartTime;
  }
  public function getScheduledStartTime()
  {
    return $this->scheduledStartTime;
  }
}

class Google_Service_YouTube_VideoLocalization extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  public $description;
  public $title;


  public function setDescription($description)
  {
    $this->description = $description;
  }
  public function getDescription()
  {
    return $this->description;
  }
  public function setTitle($title)
  {
    $this->title = $title;
  }
  public function getTitle()
  {
    return $this->title;
  }
}

class Google_Service_YouTube_VideoLocalizations extends Google_Model
{
}

class Google_Service_YouTube_VideoMonetizationDetails extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  protected $accessType = 'Google_Service_YouTube_AccessPolicy';
  protected $accessDataType = '';


  public function setAccess(Google_Service_YouTube_AccessPolicy $access)
  {
    $this->access = $access;
  }
  public function getAccess()
  {
    return $this->access;
  }
}

class Google_Service_YouTube_VideoPlayer extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  public $embedHtml;


  public function setEmbedHtml($embedHtml)
  {
    $this->embedHtml = $embedHtml;
  }
  public function getEmbedHtml()
  {
    return $this->embedHtml;
  }
}

class Google_Service_YouTube_VideoProcessingDetails extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  public $editorSuggestionsAvailability;
  public $fileDetailsAvailability;
  public $processingFailureReason;
  public $processingIssuesAvailability;
  protected $processingProgressType = 'Google_Service_YouTube_VideoProcessingDetailsProcessingProgress';
  protected $processingProgressDataType = '';
  public $processingStatus;
  public $tagSuggestionsAvailability;
  public $thumbnailsAvailability;


  public function setEditorSuggestionsAvailability($editorSuggestionsAvailability)
  {
    $this->editorSuggestionsAvailability = $editorSuggestionsAvailability;
  }
  public function getEditorSuggestionsAvailability()
  {
    return $this->editorSuggestionsAvailability;
  }
  public function setFileDetailsAvailability($fileDetailsAvailability)
  {
    $this->fileDetailsAvailability = $fileDetailsAvailability;
  }
  public function getFileDetailsAvailability()
  {
    return $this->fileDetailsAvailability;
  }
  public function setProcessingFailureReason($processingFailureReason)
  {
    $this->processingFailureReason = $processingFailureReason;
  }
  public function getProcessingFailureReason()
  {
    return $this->processingFailureReason;
  }
  public function setProcessingIssuesAvailability($processingIssuesAvailability)
  {
    $this->processingIssuesAvailability = $processingIssuesAvailability;
  }
  public function getProcessingIssuesAvailability()
  {
    return $this->processingIssuesAvailability;
  }
  public function setProcessingProgress(Google_Service_YouTube_VideoProcessingDetailsProcessingProgress $processingProgress)
  {
    $this->processingProgress = $processingProgress;
  }
  public function getProcessingProgress()
  {
    return $this->processingProgress;
  }
  public function setProcessingStatus($processingStatus)
  {
    $this->processingStatus = $processingStatus;
  }
  public function getProcessingStatus()
  {
    return $this->processingStatus;
  }
  public function setTagSuggestionsAvailability($tagSuggestionsAvailability)
  {
    $this->tagSuggestionsAvailability = $tagSuggestionsAvailability;
  }
  public function getTagSuggestionsAvailability()
  {
    return $this->tagSuggestionsAvailability;
  }
  public function setThumbnailsAvailability($thumbnailsAvailability)
  {
    $this->thumbnailsAvailability = $thumbnailsAvailability;
  }
  public function getThumbnailsAvailability()
  {
    return $this->thumbnailsAvailability;
  }
}

class Google_Service_YouTube_VideoProcessingDetailsProcessingProgress extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  public $partsProcessed;
  public $partsTotal;
  public $timeLeftMs;


  public function setPartsProcessed($partsProcessed)
  {
    $this->partsProcessed = $partsProcessed;
  }
  public function getPartsProcessed()
  {
    return $this->partsProcessed;
  }
  public function setPartsTotal($partsTotal)
  {
    $this->partsTotal = $partsTotal;
  }
  public function getPartsTotal()
  {
    return $this->partsTotal;
  }
  public function setTimeLeftMs($timeLeftMs)
  {
    $this->timeLeftMs = $timeLeftMs;
  }
  public function getTimeLeftMs()
  {
    return $this->timeLeftMs;
  }
}

class Google_Service_YouTube_VideoProjectDetails extends Google_Collection
{
  protected $collection_key = 'tags';
  protected $internal_gapi_mappings = array(
  );
  public $tags;


  public function setTags($tags)
  {
    $this->tags = $tags;
  }
  public function getTags()
  {
    return $this->tags;
  }
}

class Google_Service_YouTube_VideoRating extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  public $rating;
  public $videoId;


  public function setRating($rating)
  {
    $this->rating = $rating;
  }
  public function getRating()
  {
    return $this->rating;
  }
  public function setVideoId($videoId)
  {
    $this->videoId = $videoId;
  }
  public function getVideoId()
  {
    return $this->videoId;
  }
}

class Google_Service_YouTube_VideoRecordingDetails extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  protected $locationType = 'Google_Service_YouTube_GeoPoint';
  protected $locationDataType = '';
  public $locationDescription;
  public $recordingDate;


  public function setLocation(Google_Service_YouTube_GeoPoint $location)
  {
    $this->location = $location;
  }
  public function getLocation()
  {
    return $this->location;
  }
  public function setLocationDescription($locationDescription)
  {
    $this->locationDescription = $locationDescription;
  }
  public function getLocationDescription()
  {
    return $this->locationDescription;
  }
  public function setRecordingDate($recordingDate)
  {
    $this->recordingDate = $recordingDate;
  }
  public function getRecordingDate()
  {
    return $this->recordingDate;
  }
}

class Google_Service_YouTube_VideoSnippet extends Google_Collection
{
  protected $collection_key = 'tags';
  protected $internal_gapi_mappings = array(
  );
  public $categoryId;
  public $channelId;
  public $channelTitle;
  public $defaultAudioLanguage;
  public $defaultLanguage;
  public $description;
  public $liveBroadcastContent;
  protected $localizedType = 'Google_Service_YouTube_VideoLocalization';
  protected $localizedDataType = '';
  public $publishedAt;
  public $tags;
  protected $thumbnailsType = 'Google_Service_YouTube_ThumbnailDetails';
  protected $thumbnailsDataType = '';
  public $title;


  public function setCategoryId($categoryId)
  {
    $this->categoryId = $categoryId;
  }
  public function getCategoryId()
  {
    return $this->categoryId;
  }
  public function setChannelId($channelId)
  {
    $this->channelId = $channelId;
  }
  public function getChannelId()
  {
    return $this->channelId;
  }
  public function setChannelTitle($channelTitle)
  {
    $this->channelTitle = $channelTitle;
  }
  public function getChannelTitle()
  {
    return $this->channelTitle;
  }
  public function setDefaultAudioLanguage($defaultAudioLanguage)
  {
    $this->defaultAudioLanguage = $defaultAudioLanguage;
  }
  public function getDefaultAudioLanguage()
  {
    return $this->defaultAudioLanguage;
  }
  public function setDefaultLanguage($defaultLanguage)
  {
    $this->defaultLanguage = $defaultLanguage;
  }
  public function getDefaultLanguage()
  {
    return $this->defaultLanguage;
  }
  public function setDescription($description)
  {
    $this->description = $description;
  }
  public function getDescription()
  {
    return $this->description;
  }
  public function setLiveBroadcastContent($liveBroadcastContent)
  {
    $this->liveBroadcastContent = $liveBroadcastContent;
  }
  public function getLiveBroadcastContent()
  {
    return $this->liveBroadcastContent;
  }
  public function setLocalized(Google_Service_YouTube_VideoLocalization $localized)
  {
    $this->localized = $localized;
  }
  public function getLocalized()
  {
    return $this->localized;
  }
  public function setPublishedAt($publishedAt)
  {
    $this->publishedAt = $publishedAt;
  }
  public function getPublishedAt()
  {
    return $this->publishedAt;
  }
  public function setTags($tags)
  {
    $this->tags = $tags;
  }
  public function getTags()
  {
    return $this->tags;
  }
  public function setThumbnails(Google_Service_YouTube_ThumbnailDetails $thumbnails)
  {
    $this->thumbnails = $thumbnails;
  }
  public function getThumbnails()
  {
    return $this->thumbnails;
  }
  public function setTitle($title)
  {
    $this->title = $title;
  }
  public function getTitle()
  {
    return $this->title;
  }
}

class Google_Service_YouTube_VideoStatistics extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  public $commentCount;
  public $dislikeCount;
  public $favoriteCount;
  public $likeCount;
  public $viewCount;


  public function setCommentCount($commentCount)
  {
    $this->commentCount = $commentCount;
  }
  public function getCommentCount()
  {
    return $this->commentCount;
  }
  public function setDislikeCount($dislikeCount)
  {
    $this->dislikeCount = $dislikeCount;
  }
  public function getDislikeCount()
  {
    return $this->dislikeCount;
  }
  public function setFavoriteCount($favoriteCount)
  {
    $this->favoriteCount = $favoriteCount;
  }
  public function getFavoriteCount()
  {
    return $this->favoriteCount;
  }
  public function setLikeCount($likeCount)
  {
    $this->likeCount = $likeCount;
  }
  public function getLikeCount()
  {
    return $this->likeCount;
  }
  public function setViewCount($viewCount)
  {
    $this->viewCount = $viewCount;
  }
  public function getViewCount()
  {
    return $this->viewCount;
  }
}

class Google_Service_YouTube_VideoStatus extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  public $embeddable;
  public $failureReason;
  public $license;
  public $privacyStatus;
  public $publicStatsViewable;
  public $publishAt;
  public $rejectionReason;
  public $uploadStatus;


  public function setEmbeddable($embeddable)
  {
    $this->embeddable = $embeddable;
  }
  public function getEmbeddable()
  {
    return $this->embeddable;
  }
  public function setFailureReason($failureReason)
  {
    $this->failureReason = $failureReason;
  }
  public function getFailureReason()
  {
    return $this->failureReason;
  }
  public function setLicense($license)
  {
    $this->license = $license;
  }
  public function getLicense()
  {
    return $this->license;
  }
  public function setPrivacyStatus($privacyStatus)
  {
    $this->privacyStatus = $privacyStatus;
  }
  public function getPrivacyStatus()
  {
    return $this->privacyStatus;
  }
  public function setPublicStatsViewable($publicStatsViewable)
  {
    $this->publicStatsViewable = $publicStatsViewable;
  }
  public function getPublicStatsViewable()
  {
    return $this->publicStatsViewable;
  }
  public function setPublishAt($publishAt)
  {
    $this->publishAt = $publishAt;
  }
  public function getPublishAt()
  {
    return $this->publishAt;
  }
  public function setRejectionReason($rejectionReason)
  {
    $this->rejectionReason = $rejectionReason;
  }
  public function getRejectionReason()
  {
    return $this->rejectionReason;
  }
  public function setUploadStatus($uploadStatus)
  {
    $this->uploadStatus = $uploadStatus;
  }
  public function getUploadStatus()
  {
    return $this->uploadStatus;
  }
}

class Google_Service_YouTube_VideoSuggestions extends Google_Collection
{
  protected $collection_key = 'tagSuggestions';
  protected $internal_gapi_mappings = array(
  );
  public $editorSuggestions;
  public $processingErrors;
  public $processingHints;
  public $processingWarnings;
  protected $tagSuggestionsType = 'Google_Service_YouTube_VideoSuggestionsTagSuggestion';
  protected $tagSuggestionsDataType = 'array';


  public function setEditorSuggestions($editorSuggestions)
  {
    $this->editorSuggestions = $editorSuggestions;
  }
  public function getEditorSuggestions()
  {
    return $this->editorSuggestions;
  }
  public function setProcessingErrors($processingErrors)
  {
    $this->processingErrors = $processingErrors;
  }
  public function getProcessingErrors()
  {
    return $this->processingErrors;
  }
  public function setProcessingHints($processingHints)
  {
    $this->processingHints = $processingHints;
  }
  public function getProcessingHints()
  {
    return $this->processingHints;
  }
  public function setProcessingWarnings($processingWarnings)
  {
    $this->processingWarnings = $processingWarnings;
  }
  public function getProcessingWarnings()
  {
    return $this->processingWarnings;
  }
  public function setTagSuggestions($tagSuggestions)
  {
    $this->tagSuggestions = $tagSuggestions;
  }
  public function getTagSuggestions()
  {
    return $this->tagSuggestions;
  }
}

class Google_Service_YouTube_VideoSuggestionsTagSuggestion extends Google_Collection
{
  protected $collection_key = 'categoryRestricts';
  protected $internal_gapi_mappings = array(
  );
  public $categoryRestricts;
  public $tag;


  public function setCategoryRestricts($categoryRestricts)
  {
    $this->categoryRestricts = $categoryRestricts;
  }
  public function getCategoryRestricts()
  {
    return $this->categoryRestricts;
  }
  public function setTag($tag)
  {
    $this->tag = $tag;
  }
  public function getTag()
  {
    return $this->tag;
  }
}

class Google_Service_YouTube_VideoTopicDetails extends Google_Collection
{
  protected $collection_key = 'topicIds';
  protected $internal_gapi_mappings = array(
  );
  public $relevantTopicIds;
  public $topicIds;


  public function setRelevantTopicIds($relevantTopicIds)
  {
    $this->relevantTopicIds = $relevantTopicIds;
  }
  public function getRelevantTopicIds()
  {
    return $this->relevantTopicIds;
  }
  public function setTopicIds($topicIds)
  {
    $this->topicIds = $topicIds;
  }
  public function getTopicIds()
  {
    return $this->topicIds;
  }
}

class Google_Service_YouTube_WatchSettings extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  public $backgroundColor;
  public $featuredPlaylistId;
  public $textColor;


  public function setBackgroundColor($backgroundColor)
  {
    $this->backgroundColor = $backgroundColor;
  }
  public function getBackgroundColor()
  {
    return $this->backgroundColor;
  }
  public function setFeaturedPlaylistId($featuredPlaylistId)
  {
    $this->featuredPlaylistId = $featuredPlaylistId;
  }
  public function getFeaturedPlaylistId()
  {
    return $this->featuredPlaylistId;
  }
  public function setTextColor($textColor)
  {
    $this->textColor = $textColor;
  }
  public function getTextColor()
  {
    return $this->textColor;
  }
}
