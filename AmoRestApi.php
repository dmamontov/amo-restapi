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
 * @since     File available since Release 1.0.0
 */
/**
 * AmoRestApi - The main class
 *
 * @author    Dmitry Mamontov <d.slonyara@gmail.com>
 * @copyright 2015 Dmitry Mamontov <d.slonyara@gmail.com>
 * @license   http://www.opensource.org/licenses/BSD-3-Clause  The BSD 3-Clause License
 * @version   Release: 1.0.0
 * @link      https://github.com/dmamontov/amo-restapi/
 * @since     Class available since Release 1.0.0
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
	 */
	protected $curl;

	/**
	 * Current account info
	 */
	protected $account_info;

	/**
	 * Accounts custom fields
	 */
	protected $custom_fields;

	/**
	 * Accounts leads statues info
	 */
	protected $leads_statuses;

    /**
     * Class constructor
     * @param string $subDomain
     * @param string $login
     * @param string $key
     * @return void
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

       if ($auth['auth'] !== true) {
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
	    if ( $this->account_info ) {
		    return $this->account_info;
	    }

	    $request = $this->curlRequest(sprintf(self::URL . 'accounts/current', $this->subDomain));

	    if ( is_array( $request ) && isset( $request['account'] ) ) {
		    $this->account_info = $request['account'];
		    return $this->account_info;
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
        if (is_null($contacts)) {
            return false;
        }

	    //Prepare request
	    $request['request']['contacts'] = $contacts;
	    $request_json = json_encode( $request );

	    $headers = array('Content-Type: application/json');

        return $this->curlRequest(sprintf(self::URL . 'contacts/set', $this->subDomain), self::METHOD_POST, $request_json, $headers);
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
    )
    {
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
            count($parameters) > 0 ? http_build_query($parameters) : null);
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
    )
    {
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
            count($parameters) > 0 ? http_build_query($parameters) : null);
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
        if (is_null($leads)) {
            return false;
        }

	    //Prepare request
	    $request['request']['leads'] = $leads;
	    $request_json = json_encode( $request );
	    $headers = array('Content-Type: application/json');

	    //Do request
	    $response = $this->curlRequest(sprintf(self::URL . 'leads/set', $this->subDomain), self::METHOD_POST,  $request_json, $headers);

	    //Parse leads ids from response and return along with last modified time
	    if ( isset( $response['leads']['add'] ) && is_array( $response['leads']['add'] ) ) {
		    $added_leads = array();
		    foreach ( $response['leads']['add'] as $key => $lead_info ) {
			    $added_leads[ $key ]['id']            = $lead_info['id'];
			    $added_leads[ $key ]['last_modified'] = $response['server_time'];
		    }

		    return $added_leads;
	    } elseif ( isset( $response['leads']['update'] ) ) {
		    return $response;
	    } else {
		    return false;
	    }
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
    )
    {
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
            count($parameters) > 0 ? http_build_query($parameters) : null);
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
        if (is_null($company)) {
            return false;
        }
    
        return $this->curlRequest(sprintf(self::URL . 'company/list', $this->subDomain), self::METHOD_POST, $company);
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
    )
    {
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
            count($parameters) > 0 ? http_build_query($parameters) : null);
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
        if (is_null($tasks)) {
            return false;
        }

        return $this->curlRequest(sprintf(self::URL . 'tasks/set', $this->subDomain), self::METHOD_POST, $tasks);
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
        $query = null,
        $responsible = null,
        $type = null,
        DateTime $dateModified = null
    )
    {
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
            sprintf(self::URL . 'tasks/list', $this->subDomain),
            self::METHOD_GET,
            count($parameters) > 0 ? http_build_query($parameters) : null);
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
        if (is_null($notes)) {
            return false;
        }
    
        return $this->curlRequest(sprintf(self::URL . 'notes/set', $this->subDomain), self::METHOD_POST, $notes);
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
        $element_id = null,
        $type = null,
        DateTime $dateModified = null
    )
    {
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

        if (is_null($responsible) === false) {
            $parameters['responsible_user_id'] = $responsible;
        }

        if (is_null($element_id) === false) {
            $parameters['element_id'] = $element_id;
        }

        return $this->curlRequest(
            sprintf(self::URL . 'notes/list', $this->subDomain),
            self::METHOD_GET,
            count($parameters) > 0 ? http_build_query($parameters) : null);
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
        if (is_null($fields)) {
            return false;
        }
    
        return $this->curlRequest(sprintf(self::URL . 'fields/set', $this->subDomain), self::METHOD_POST, $fields);
    }

    /**
     * Execution of the request
     * @param string $url
     * @param string $method
     * @param array $parameters
     * @param array $headers
     * @param integer $timeout
     * @return mixed
     * @access protected
     */
    protected function curlRequest($url, $method = 'GET', $parameters = null, $headers = null, $timeout = 30)
    {
        if ($method == self::METHOD_GET && is_null($parameters) == false) {
            $url .= "?$parameters";
        }

	    // Get curl handler or initiate it
	    if ( ! $this->curl ) {
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
        curl_setopt($this->curl ,CURLOPT_COOKIEFILE, '-');
        curl_setopt($this->curl ,CURLOPT_COOKIEJAR, '-');

	    // Reset some arguments, in order to avoid use some from previous request
	    curl_setopt($this->curl ,CURLOPT_POST, false);
	    curl_setopt($this->curl,CURLOPT_HTTPHEADER, false);

        if (is_null($headers) === false) {
            curl_setopt($this->curl, CURLOPT_HTTPHEADER, $headers);
        }

        if ($method == self::METHOD_POST && is_null($parameters) === false) {
            curl_setopt($this->curl, CURLOPT_POST, true);

	        //Encode parameters if them already not encoded in json
	        if ( ! $this->isJson( $parameters ) ) {
		        $parameters = http_build_query( $parameters );
	        }

            curl_setopt($this->curl, CURLOPT_POSTFIELDS, $parameters);
        }

        $response = curl_exec($this->curl);
        $statusCode = curl_getinfo($this->curl, CURLINFO_HTTP_CODE);

        $errno = curl_errno($this->curl);
        $error = curl_error($this->curl);

        if ($errno) {
            throw new Exception($error, $errno);
        }

        $result = json_decode($response, true);

        if ($statusCode >= 400) {
            throw new Exception($result['message'], $statusCode);
        }

        return isset($result['response']) && count($result['response']) == 0 ? true : $result['response'];
    }

	/**
	 * Check if passed argument is JSON
	 *
	 * @param $string
	 *
	 * @return bool
	 */
	protected function isJson($string) {
		if ( ! is_string( $string ) ) {
			return false;
		}
		json_decode($string);
		return (json_last_error() == JSON_ERROR_NONE);
	}

	/**
	 * Get accounts custom fields and store in self::custom_fields
	 * @return mixed
	 */
	protected function getCustomFields() {
		if ( $this->custom_fields ) {
			return $this->custom_fields;
		}

		$account = $this->getAccountInfo();
		$this->custom_fields = $account['custom_fields'];

		return $this->custom_fields;
	}

	/**
	 * Getting custom fields id
	 *
	 * @param        $field_name
	 * @param string $field_section (possible values contacts or companies)
	 *
	 * @return mixed
	 */
	public function getCustomFieldID( $field_name, $field_section = 'contacts' ) {
		$custom_fields = $this->getCustomFields();
		if ( is_array( $custom_fields ) && isset( $custom_fields[$field_section] ) && is_array( $custom_fields[$field_section] ) ) {
			foreach ( $custom_fields[$field_section] as $custom_field_details ) {
				if ( $field_name === $custom_field_details['code'] ) {
					return $custom_field_details['id'];
				}
			}
		}
	}

	/**
	 * Get list of possible leads statuses
	 * @return mixed
	 */
	protected function getLeadsStatuses() {
		if ( $this->leads_statuses ) {
			return $this->leads_statuses;
		}

		$account = $this->getAccountInfo();
		$this->leads_statuses = $account['leads_statuses'];

		return $this->leads_statuses;
	}

	/**
	 * Get lead status id by name
	 *
	 * @param $name
	 *
	 * @return mixed
	 */
	public function getLeadStatusID( $name ) {
		$leads_statuses = $this->getLeadsStatuses();
		if ( is_array( $leads_statuses ) ) {
			foreach ( $leads_statuses as $leads_status ) {
				if ( $name === $leads_status['name'] ) {
					return $leads_status['id'];
				}
			}
		}
	}

	/**
	 * Do some actions when instance destroyed
	 */
	function __destruct() {
		//Close curl session
		curl_close($this->curl);
	}

}
