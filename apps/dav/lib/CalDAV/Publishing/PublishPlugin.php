<?php

namespace OCA\DAV\CalDAV\Publishing;

//use OCA\DAV\CalDAV\Publishing\Xml;

use Sabre\DAV\PropFind;
use Sabre\DAV\INode;
use Sabre\DAV\Server;
use Sabre\DAV\ServerPlugin;
use Sabre\HTTP\RequestInterface;
use Sabre\HTTP\ResponseInterface;
use OCA\DAV\CalDAV\Publishing\Xml\Publisher;
use OCA\DAV\CalDAV\Calendar;

class PublishPlugin extends ServerPlugin
{
    const NS_OWNCLOUD = 'http://owncloud.org/ns';
    const NS_CALENDARSERVER = 'http://calendarserver.org/ns/';

    /**
     * Reference to SabreDAV server object.
     *
     * @var \Sabre\DAV\Server
     */
    protected $server;

    /**
     * This method should return a list of server-features.
     *
     * This is for example 'versioning' and is added to the DAV: header
     * in an OPTIONS response.
     *
     * @return string[]
     */
    public function getFeatures()
    {
        return ['oc-calendar-publishing'];
    }

    /**
     * Returns a plugin name.
     *
     * Using this name other plugins will be able to access other plugins
     * using Sabre\DAV\Server::getPlugin
     *
     * @return string
     */
    public function getPluginName()
    {
        return 'oc-calendar-publishing';
    }

    /**
     * This initializes the plugin.
     *
     * This function is called by Sabre\DAV\Server, after
     * addPlugin is called.
     *
     * This method should set up the required event subscriptions.
     *
     * @param Server $server
     */
    public function initialize(Server $server)
    {
        $this->server = $server;

        $this->server->on('method:POST', [$this, 'httpPost']);
        $this->server->on('propFind',    [$this, 'propFind']);
    }

    public function propFind(PropFind $propFind, INode $node)
    {
        if ($node instanceof Calendar) {
            $token = md5(\OC::$server->getConfig()->getSystemValue('secret', '').$node->getName());

        $propFind->handle('{'.self::NS_CALENDARSERVER.'}publish-url', function () use ($node, $token) {
          if ($node->getPublishStatus()) {
              return new Publisher($token, $node->getPublishStatus());
          }
        });

        $propFind->handle('{'.self::NS_CALENDARSERVER.'}pre-publish-url', function () use ($node, $token) {
          if ($node->getPublishStatus()) {
              return new Publisher($token, false);
          }
        });
        }
    }

  /**
   * We intercept this to handle POST requests on calendars.
   *
   * @param RequestInterface $request
   * @param ResponseInterface $response
   *
   * @return null|bool
   */
  public function httpPost(RequestInterface $request, ResponseInterface $response)
  {
      $path = $request->getPath();

      // Only handling xml
      $contentType = $request->getHeader('Content-Type');
      if (strpos($contentType, 'application/xml') === false && strpos($contentType, 'text/xml') === false) {
          return;
      }

      // Making sure the node exists
      try {
          $node = $this->server->tree->getNodeForPath($path);
      } catch (DAV\Exception\NotFound $e) {
          return;
      }

      $requestBody = $request->getBodyAsString();

      // If this request handler could not deal with this POST request, it
      // will return 'null' and other plugins get a chance to handle the
      // request.
      //
      // However, we already requested the full body. This is a problem,
      // because a body can only be read once. This is why we preemptively
      // re-populated the request body with the existing data.
      $request->setBody($requestBody);

      $message = $this->server->xml->parse($requestBody, $request->getUrl(), $documentType);

      switch ($documentType) {

          case '{'.self::NS_CALENDARSERVER.'}publish-calendar' :

              // We can only deal with IShareableCalendar objects
              if (!$node instanceof Calendar) {
                  return;
              }
              $this->server->transactionType = 'post-publish-calendar';

              // Getting ACL info
              $acl = $this->server->getPlugin('acl');

              // If there's no ACL support, we allow everything
              if ($acl) {
                  $acl->checkPrivileges($path, '{DAV:}write');
              }

              $node->setPublishStatus(true);

              // iCloud sends back the 202, so we will too.
              $response->setStatus(202);

              // Adding this because sending a response body may cause issues,
              // and I wanted some type of indicator the response was handled.
              $response->setHeader('X-Sabre-Status', 'everything-went-well');

              // Breaking the event chain
              return false;

          case '{'.self::NS_CALENDARSERVER.'}unpublish-calendar' :

              // We can only deal with IShareableCalendar objects
              if (!$node instanceof Calendar) {
                  return;
              }
              $this->server->transactionType = 'post-unpublish-calendar';

              // Getting ACL info
              $acl = $this->server->getPlugin('acl');

              // If there's no ACL support, we allow everything
              if ($acl) {
                  $acl->checkPrivileges($path, '{DAV:}write');
              }

              $node->setPublishStatus(false);

              $response->setStatus(200);

              // Adding this because sending a response body may cause issues,
              // and I wanted some type of indicator the response was handled.
              $response->setHeader('X-Sabre-Status', 'everything-went-well');

              // Breaking the event chain
              return false;

      }
  }
}
