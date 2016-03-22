<?php
/**
 * AmoRestApi
 *
 * Copyright (c) 2015, Dmitry Mamontov <d.slonyara@gmail.com>.
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions
 * are met:
 *
 *   * Redistributions of source code must retain the above copyright
 *     notice, this list of conditions and the following disclaimer.
 *
 *   * Redistributions in binary form must reproduce the above copyright
 *     notice, this list of conditions and the following disclaimer in
 *     the documentation and/or other materials provided with the
 *     distribution.
 *
 *   * Neither the name of Dmitry Mamontov nor the names of his
 *     contributors may be used to endorse or promote products derived
 *     from this software without specific prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS
 * FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE
 * COPYRIGHT OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT,
 * INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING,
 * BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
 * LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER
 * CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT
 * LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN
 * ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
 * POSSIBILITY OF SUCH DAMAGE.
 *
 * @package   amo-restapi
 * @author    Dmitry Mamontov <d.slonyara@gmail.com>
 * @copyright 2015 Dmitry Mamontov <d.slonyara@gmail.com>
 * @license   http://www.opensource.org/licenses/BSD-3-Clause  The BSD 3-Clause License
 * @since     File available since Release 1.0.3
 */
/**
 * AmoRestApi - The main class
 *
 * @author    Dmitry Mamontov <d.slonyara@gmail.com>
 * @copyright 2015 Dmitry Mamontov <d.slonyara@gmail.com>
 * @license   http://www.opensource.org/licenses/BSD-3-Clause  The BSD 3-Clause License
 * @version   Release: 1.0.3
 * @link      https://github.com/dmamontov/amo-restapi/
 * @since     Class available since Release 1.0.3
 */

class AmoRestApi
{
    /*
     * URL fro RestAPI
     */
    const URL = 'https://%s.amocrm.ru/private/api/v2/json/';

    /*
     * Auth URL fro RestAPI
     */
    const AUTH_URL = 'https://%s.amocrm.ru/private/api/';

    /*
     * Methods
     */
    const METHOD_GET = 'GET';
    const METHOD_POST = 'POST';

    /**
     * Login access to API
     * @var string
     * @access protected
     */
    protected $login;

    /**
     * Hash
     * @var string
     * @access protected
     */
    protected $key;

    /**
     * Sub domain
     * @var string
     * @access protected
     */
    protected $subDomain;

    /**
     * Curl instance
     * @var resource
     * @access private
     */
    private $curl;

    /**
     * Current account info
     * @var array
     * @access protected
     */
    protected $accountInfo;

    /**
     * Accounts custom fields
     * @var array
     * @access protected
     */
    protected $customFields;

    /**
     * Accounts leads statues info
     * @var array
     * @access protected
     */
    protected $leadsStatuses;

    /**
     * Class constructor
     * @param string $subDomain
     * @param string $login
     * @param string $key
     * @throws Exception
     * @access public
     * @final
     */
    final public function __construct($subDomain, $login, $key)
    {
        $this->subDomain = $subDomain;
        $this->login = $login;
        $this->key = $key;

        $auth = $this->curlRequest(
           sprintf(self::AUTH_URL . 'auth.php?type=json', $subDomain),
           self::METHOD_POST,
           array('USER_LOGIN' => $login, 'USER_HASH'  => $key)
       );

        if (!array_key_exists('auth', $auth) || $auth['auth'] !== true) {
            throw new Exception('Authorization error.');
        }
    }

    /**
     * Get Account Info
     * @return array
     * @access public
     * @final
     */
    final public function getAccountInfo()
    {
        if ($this->accountInfo) {
            return $this->accountInfo;
        }

        $request = $this->curlRequest(sprintf(self::URL . 'accounts/current', $this->subDomain));

        if (is_array($request) && isset($request['account'])) {
            $this->accountInfo = $request['account'];
            return $this->accountInfo;
        } else {
            return false;
        }
    }

    /**
     * Set Contacts
     * @param array $contacts
     * @return array
     * @access public
     * @final
     */
    final public function setContacts($contacts = null)
    {
        if (is_null($contacts) || empty($contacts)) {
            return false;
        }

        return $this->curlRequest(
            sprintf(self::URL . 'contacts/set', $this->subDomain),
            self::METHOD_POST,
            json_encode(array('request' => array('contacts' => $contacts))),
            array('Content-Type: application/json')
        );
    }

    /**
     * Get Contacts List
     * @param int $limitRows
     * @param int $limitOffset
     * @param mixed $ids
     * @param string $query
     * @param string $responsible
     * @param string $type
     * @param DateTime $dateModified
     * @return array
     * @access public
     * @final
     */
    final public function getContactsList(
        $limitRows = null,
        $limitOffset = null,
        $ids = null,
        $query = null,
        $responsible = null,
        $type = null,
        DateTime $dateModified = null
    ) {
        $headers = null;
        if (is_null($dateModified) === false) {
            $headers = array('if-modified-since: ' . $dateModified->format('D, d M Y H:i:s'));
        }

        $parameters = array();
        if (is_null($limitRows) === false) {
            $parameters['limit_rows'] = $limitRows;
            if (is_null($limitRows) === false) {
                $parameters['limit_offset'] = $limitOffset;
            }
        }

        if (is_null($ids) === false) {
            $parameters['id'] = $ids;
        }

        if (is_null($query) === false) {
            $parameters['query'] = $query;
        }

        if (is_null($responsible) === false) {
            $parameters['responsible_user_id'] = $responsible;
        }

        if (is_null($type) === false) {
            $parameters['type'] = $type;
        }

        return $this->curlRequest(
            sprintf(self::URL . 'contacts/list', $this->subDomain),
            self::METHOD_GET,
            count($parameters) > 0 ? http_build_query($parameters) : null,
            $headers
        );
    }

    /**
     * Get Contacts Links
     * @param int $limitRows
     * @param int $limitOffset
     * @param mixed $ids
     * @param DateTime $dateModified
     * @return array
     * @access public
     * @final
     */
    final public function getContactsLinks(
        $limitRows = null,
        $limitOffset = null,
        $ids = null,
        DateTime $dateModified = null
    ) {
        $headers = null;
        if (is_null($dateModified) === false) {
            $headers = array('if-modified-since: ' . $dateModified->format('D, d M Y H:i:s'));
        }

        $parameters = array();
        if (is_null($limitRows) === false) {
            $parameters['limit_rows'] = $limitRows;
            if (is_null($limitRows) === false) {
                $parameters['limit_offset'] = $limitOffset;
            }
        }

        if (is_null($ids) === false) {
            $parameters['contacts_link'] = $ids;
        }

        return $this->curlRequest(
            sprintf(self::URL . 'contacts/links', $this->subDomain),
            self::METHOD_GET,
            count($parameters) > 0 ? http_build_query($parameters) : null,
            $headers
        );
    }

    /**
     * Set Leads
     * @param array $leads
     * @return array
     * @access public
     * @final
     */
    final public function setLeads($leads = null)
    {
        if (is_null($leads) || empty($leads)) {
            return false;
        }

        return $this->curlRequest(
            sprintf(self::URL . 'leads/set', $this->subDomain),
            self::METHOD_POST,
            json_encode(array('request' => array('leads' => $leads))),
            array('Content-Type: application/json')
        );
    }

    /**
     * Get Leads List
     * @param int $limitRows
     * @param int $limitOffset
     * @param mixed $ids
     * @param string $query
     * @param string $responsible
     * @param mixed $status
     * @param DateTime $dateModified
     * @return array
     * @access public
     * @final
     */
    final public function getLeadsList(
        $limitRows = null,
        $limitOffset = null,
        $ids = null,
        $query = null,
        $responsible = null,
        $status = null,
        DateTime $dateModified = null
    ) {
        $headers = null;
        if (is_null($dateModified) === false) {
            $headers = array('if-modified-since: ' . $dateModified->format('D, d M Y H:i:s'));
        }

        $parameters = array();
        if (is_null($limitRows) === false) {
            $parameters['limit_rows'] = $limitRows;
            if (is_null($limitRows) === false) {
                $parameters['limit_offset'] = $limitOffset;
            }
        }

        if (is_null($ids) === false) {
            $parameters['id'] = $ids;
        }

        if (is_null($query) === false) {
            $parameters['query'] = $query;
        }

        if (is_null($responsible) === false) {
            $parameters['responsible_user_id'] = $responsible;
        }

        if (is_null($status) === false) {
            $parameters['status'] = $status;
        }

        return $this->curlRequest(
            sprintf(self::URL . 'leads/list', $this->subDomain),
            self::METHOD_GET,
            count($parameters) > 0 ? http_build_query($parameters) : null,
            $headers
        );
    }

    /**
     * Set Company
     * @param array $company
     * @return array
     * @access public
     * @final
     */
    final public function setCompany($company = null)
    {
        if (is_null($company) || empty($company)) {
            return false;
        }

        return $this->curlRequest(
            sprintf(self::URL . 'company/set', $this->subDomain),
            self::METHOD_POST,
            json_encode(array('request' => array('contacts' => $company))),
            array('Content-Type: application/json')
        );
    }

    /**
     * Get Company List
     * @param int $limitRows
     * @param int $limitOffset
     * @param mixed $ids
     * @param string $query
     * @param string $responsible
     * @param DateTime $dateModified
     * @return array
     * @access public
     * @final
     */
    final public function getCompanyList(
        $limitRows = null,
        $limitOffset = null,
        $ids = null,
        $query = null,
        $responsible = null,
        DateTime $dateModified = null
    ) {
        $headers = null;
        if (is_null($dateModified) === false) {
            $headers = array('if-modified-since: ' . $dateModified->format('D, d M Y H:i:s'));
        }

        $parameters = array();
        if (is_null($limitRows) === false) {
            $parameters['limit_rows'] = $limitRows;
            if (is_null($limitRows) === false) {
                $parameters['limit_offset'] = $limitOffset;
            }
        }

        if (is_null($ids) === false) {
            $parameters['id'] = $ids;
        }

        if (is_null($query) === false) {
            $parameters['query'] = $query;
        }

        if (is_null($responsible) === false) {
            $parameters['responsible_user_id'] = $responsible;
        }

        return $this->curlRequest(
            sprintf(self::URL . 'company/list', $this->subDomain),
            self::METHOD_GET,
            count($parameters) > 0 ? http_build_query($parameters) : null,
            $headers
        );
    }

    /**
     * Set Tasks
     * @param array $tasks
     * @return array
     * @access public
     * @final
     */
    final public function setTasks($tasks = null)
    {
        if (is_null($tasks) || empty($tasks)) {
            return false;
        }

        return $this->curlRequest(
            sprintf(self::URL . 'tasks/set', $this->subDomain),
            self::METHOD_POST,
            json_encode(array('request' => array('tasks' => $tasks))),
            array('Content-Type: application/json')
        );
    }

    /**
     * Get Tasks List
     * @param int $limitRows
     * @param int $limitOffset
     * @param mixed $ids
     * @param string $query
     * @param string $responsible
     * @param string $type
     * @param DateTime $dateModified
     * @return array
     * @access public
     * @final
     */
    final public function getTasksList(
        $limitRows = null,
        $limitOffset = null,
        $ids = null,
        $elemenId = null,
        $responsible = null,
        $type = null,
        DateTime $dateModified = null
    ) {
        $headers = null;
        if (is_null($dateModified) === false) {
            $headers = array('if-modified-since: ' . $dateModified->format('D, d M Y H:i:s'));
        }

        $parameters = array();
        if (is_null($limitRows) === false) {
            $parameters['limit_rows'] = $limitRows;
            if (is_null($limitRows) === false) {
                $parameters['limit_offset'] = $limitOffset;
            }
        }

        if (is_null($ids) === false) {
            $parameters['id'] = $ids;
        }

        if (is_null($elemenId) === false) {
            $parameters['element_id'] = $elemenId;
        }

        if (is_null($responsible) === false) {
            $parameters['responsible_user_id'] = $responsible;
        }

        if (is_null($type) === false) {
            $parameters['type'] = $type;
        }

        return $this->curlRequest(
            sprintf(self::URL . 'tasks/list', $this->subDomain),
            self::METHOD_GET,
            count($parameters) > 0 ? http_build_query($parameters) : null,
            $headers
        );
    }

    /**
     * Set Notes
     * @param array $notes
     * @return array
     * @access public
     * @final
     */
    final public function setNotes($notes = null)
    {
        if (is_null($notes) || empty($notes)) {
            return false;
        }

        return $this->curlRequest(
            sprintf(self::URL . 'notes/set', $this->subDomain),
            self::METHOD_POST,
            json_encode(array('request' => array('notes' => $notes))),
            array('Content-Type: application/json')
       );
    }

    /**
     * Get Notes List
     * @param int $limitRows
     * @param int $limitOffset
     * @param mixed $ids
     * @param string $element_id
     * @param string $type
     * @param DateTime $dateModified
     * @return array
     * @access public
     * @final
     */
    final public function getNotesList(
        $limitRows = null,
        $limitOffset = null,
        $ids = null,
        $elementId = null,
        $type = null,
        DateTime $dateModified = null
    ) {
        $headers = null;
        if (is_null($dateModified) === false) {
            $headers = array('if-modified-since: ' . $dateModified->format('D, d M Y H:i:s'));
        }

        $parameters = array();
        if (is_null($limitRows) === false) {
            $parameters['limit_rows'] = $limitRows;
            if (is_null($limitRows) === false) {
                $parameters['limit_offset'] = $limitOffset;
            }
        }

        if (is_null($ids) === false) {
            $parameters['id'] = $ids;
        }

        if (is_null($elementId) === false) {
            $parameters['element_id'] = $elementId;
        }

        if (is_null($type) === false) {
            $parameters['type'] = $type;
        }

        return $this->curlRequest(
            sprintf(self::URL . 'notes/list', $this->subDomain),
            self::METHOD_GET,
            count($parameters) > 0 ? http_build_query($parameters) : null,
            $headers
        );
    }

    /**
     * Set Fields
     * @param array $fields
     * @return array
     * @access public
     * @final
     */
    final public function setFields($fields = null)
    {
        if (is_null($fields) || empty($fields)) {
            return false;
        }

        return $this->curlRequest(
            sprintf(self::URL . 'fields/set', $this->subDomain),
            self::METHOD_POST,
            json_encode(array('request' => array('fields' => $fields))),
            array('Content-Type: application/json')
        );
    }

    /**
     * Add Calls
     * @param string $code
     * @param string $key
     * @param array $calls
     * @return array
     * @throws Exception
     * @access public
     * @final
     * @todo The key is available from technical support.
     */
    final public function addCalls($code, $key, $calls)
    {
        if (
            is_null($code) || empty($code) ||
            is_null($key) || empty($key) ||
            is_null($calls) || empty($calls)
        ) {
            throw new Exception('Incorrect parameters.');
        }

        return $this->curlRequest(
            sprintf(
                'https://%s.amocrm.ru/api/calls/add?%s',
                $this->subDomain,
                http_build_query(array('code' => $code, 'key' => $key))
            ),
            self::METHOD_POST,
            json_encode(array('request' => $calls)),
            array('Content-Type: application/json')
        );
    }

    /**
     * Get accounts custom fields and store in self::customFields
     * @return mixed
     * @access public
     * @final
     */
    final public function getCustomFields()
    {
        if ($this->customFields) {
            return $this->customFields;
        }

        $account = $this->getAccountInfo();
        $this->customFields = $account['custom_fields'];

        return $this->customFields;
    }

    /**
     * Getting custom fields id
     * @param string $fieldName
     * @param string $fieldSection (possible values contacts or companies)
     * @return mixed
     * @access public
     * @final
     */
    final public function getCustomFieldID($fieldName, $fieldSection = 'contacts')
    {
        $customFields = $this->getCustomFields();
        if (is_array($customFields) && isset($customFields[$fieldSection]) && is_array($customFields[$fieldSection])) {
            foreach ($customFields[$fieldSection] as $customFieldDetails) {
                if ($fieldName === $customFieldDetails['code']) {
                    return $customFieldDetails['id'];
                }
            }
        }
    }

    /**
     * Get list of possible leads statuses
     * @return mixed
     * @access public
     * @final
     */
    final public function getLeadsStatuses()
    {
        if ($this->leadsStatuses) {
            return $this->leadsStatuses;
        }

        $account = $this->getAccountInfo();
        $this->leadsStatuses = $account['leads_statuses'];

        return $this->leadsStatuses;
    }

    /**
     * Get lead status id by name
     * @param string $name
     * @return mixed
     * @access public
     * @final
     */
    public function getLeadStatusID($name)
    {
        $leadsStatuses = $this->getLeadsStatuses();
        if (is_array($leadsStatuses)) {
            foreach ($leadsStatuses as $leadsStatus) {
                if ($name === $leadsStatus['name']) {
                    return $leadsStatus['id'];
                }
            }
        }
    }

    /**
     * Execution of the request
     * @param string $url
     * @param string $method
     * @param array $parameters
     * @param array $headers
     * @param integer $timeout
     * @throws Exception
     * @return mixed
     * @access private
     */
    private function curlRequest($url, $method = 'GET', $parameters = null, $headers = null, $cookie = '/tmp/cookie.txt', $timeout = 30)
    {
        if ($method == self::METHOD_GET && is_null($parameters) == false) {
            $url .= "?$parameters";
        } elseif ($method == self::METHOD_POST && !$this->isJson($parameters)) {
            $parameters = http_build_query($parameters);
        }

        // Get curl handler or initiate it
        if (!$this->curl) {
            $this->curl = curl_init();
        }

        //Set general arguments
        curl_setopt($this->curl, CURLOPT_USERAGENT, 'amoCRM-API-client/1.0');
        curl_setopt($this->curl, CURLOPT_URL, $url);
        curl_setopt($this->curl, CURLOPT_FAILONERROR, false);
        curl_setopt($this->curl, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($this->curl, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($this->curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($this->curl, CURLOPT_TIMEOUT, $timeout);
        curl_setopt($this->curl, CURLOPT_HEADER, false);
        curl_setopt($this->curl, CURLOPT_COOKIEFILE, $cookie);
        curl_setopt($this->curl, CURLOPT_COOKIEJAR, $cookie);

        // Reset some arguments, in order to avoid use some from previous request
        curl_setopt($this->curl, CURLOPT_POST, false);

        if (is_null($headers) === false && count($headers) > 0) {
            curl_setopt($this->curl, CURLOPT_HTTPHEADER, $headers);
        } else {
            curl_setopt($this->curl, CURLOPT_HTTPHEADER, array());
        }

        if ($method == self::METHOD_POST && is_null($parameters) === false) {
            curl_setopt($this->curl, CURLOPT_POST, true);
            curl_setopt($this->curl, CURLOPT_POSTFIELDS, $parameters);
        }

        $response = curl_exec($this->curl);
        $statusCode = curl_getinfo($this->curl, CURLINFO_HTTP_CODE);

        $errno = curl_errno($this->curl);
        $error = curl_error($this->curl);

        if ($errno) {
            throw new Exception($error, $errno);
        }

        $response = json_decode($response, true);

        if ($statusCode >= 400) {
            throw new Exception($response['response']['error'], $statusCode);
        }
        
        $backtrace = debug_backtrace();
        $caller = next($backtrace)['function'];
        if (strpos($caller, 'set') !== false) {
            $response['response'] = $this->prepareResponse($response['response'], strtolower(ltrim($caller, 'set')));
        }

        return isset($response['response']) && count($response['response']) == 0 ? true : $response['response'];
    }

    /**
     * Pretreatment of response from the server.
     * @param array $response
     * @param string $type
     * @return array
     * @access private
     */
    private function prepareResponse($response, $type)
    {
        if (!array_key_exists($type, $response) || !array_key_exists('server_time', $response)) {
            return $response;
        }

        if (isset($response[$type]['add']) && is_array($response[$type]['add'])) {
            foreach ($response[$type]['add'] as $key => $info) {
                $response[$type]['add'][$key]['last_modified'] = $response['server_time'];
            }
        }

        return $response;
    }

    /**
     * Check if passed argument is JSON
     * @param string $string
     * @return bool
     * @access private
     */
    private function isJson($string)
    {
        if (is_string($string) == false) {
            return false;
        }
        json_decode($string);
        return (json_last_error() == JSON_ERROR_NONE);
    }

    /**
     * Do some actions when instance destroyed
     * @access public
     */
    public function __destruct()
    {
        //Close curl session
        curl_close($this->curl);
    }
}
