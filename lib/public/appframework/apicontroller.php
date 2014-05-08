<?php
/**
 * ownCloud - App Framework
 *
 * @author Bernhard Posselt
 * @copyright 2012 Bernhard Posselt nukeawhale@gmail.com
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE
 * License as published by the Free Software Foundation; either
 * version 3 of the License, or any later version.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU AFFERO GENERAL PUBLIC LICENSE for more details.
 *
 * You should have received a copy of the GNU Affero General Public
 * License along with this library.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

/**
 * Public interface of ownCloud for apps to use.
 * AppFramework\Controller class
 */

namespace OCP\AppFramework;

use OCP\AppFramework\Http\Response;
use OCP\IRequest;


/**
 * Base class to inherit your controllers from that are used for RESTful APIs
 */
abstract class ApiController extends Controller {

    private $corsMethods;
    private $corsAllowedHeaders;
    private $corsMaxAge;

    /**
     * constructor of the controller
     * @param string $appName the name of the app
     * @param IRequest $request an instance of the request
     * @param string $corsMethods: comma seperated string of HTTP verbs which 
     * should be allowed for websites or webapps when calling your API, defaults to
     * 'PUT, POST, GET, DELETE, PATCH'
     * @param string $corsAllowedHeaders: comma seperated string of HTTP headers
     * which should be allowed for websites or webapps when calling your API, 
     * defaults to 'Authorization, Content-Type, Accept'
     * @param int $corsMaxAge number in seconds how long a preflighted OPTIONS
     * request should be cached, defaults to 1728000 seconds
     */
    public function __construct($appName, 
                                IRequest $request, 
                                $corsMethods='PUT, POST, GET, DELETE, PATCH',
                                $corsAllowedHeaders='Authorization, Content-Type, Accept',
                                $corsMaxAge=1728000){
        parent::__construct($appName, $request);
        $this->corsMethods = $corsMethods;
        $this->corsAllowedHeaders = $corsAllowedHeaders;
        $this->corsMaxAge = $corsMaxAge;
    }


    /**
     * This method implements a preflighted cors response for you that you can
     * link to for the options request
     *
     * @NoAdminRequired
     * @NoCSRFRequired
     * @PublicPage
     */
    public function preflightedCors() {
        if(isset($this->request->server['HTTP_ORIGIN'])) {
            $origin = $this->request->server['HTTP_ORIGIN'];
        } else {
            $origin = '*';
        }

        $response = new Response();
        $response->addHeader('Access-Control-Allow-Origin', $origin);
        $response->addHeader('Access-Control-Allow-Methods', $this->corsMethods);
        $response->addHeader('Access-Control-Max-Age', $this->corsMaxAge);
        $response->addHeader('Access-Control-Allow-Headers', $this->corsAllowedHeaders);
        $response->addHeader('Access-Control-Allow-Credentials', 'false');
        return $response;
    }


}
