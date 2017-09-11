<?php

use Phalcon\Mvc\Controller;

class ControllerBase extends Controller
{
    /**
     * These functions are available for multiple controllers
     */

    /**
     * Register LOG in another DB
     */
    public function registerLog() {
        // gets user token
        $token_decoded = $this->decodeToken($this->getToken());

        // Gets URL route from request
        $url = $this->request->get();

        // Initiates log db transaction
        $this->db_log->begin();
        $newLog = new Logs();
        $newLog->username = $token_decoded->username_username; // gets username
        $newLog->route = $url['_url']; // gets route
        $newLog->date = $this->getNowDateTime();
        if (!$newLog->save()) {
            // rollback transaction
            $this->db_log->rollback();
            // Send errors
            $errors = array();
            foreach ($newLog->getMessages() as $message) {
                $errors[] = $message->getMessage();
            }
            $this->buildErrorResponse(400, 'common.COULD_NOT_BE_CREATED', $errors);
        } else {
            // Commit the transaction
            $this->db_log->commit();
        }
    }

    /**
     * Generated NOW datetime based on a timezone
     */
    public function getNowDateTime() {
        $now = new DateTime();
        $now->setTimezone(new DateTimeZone('UTC'));
        $now = $now->format('Y-m-d H:i:s');
        return $now;
    }

    /**
     * Generated NOW datetime based on a timezone and added XX minutes
     */
    public function getNowDateTimePlusMinutes($minutes_to_add) {
        $now = new DateTime();
        $now->setTimezone(new DateTimeZone('UTC'));
        $now->add(new DateInterval('PT' . $minutes_to_add . 'M'));
        $now = $now->format('Y-m-d H:i:s');
        return $now;
    }

    /**
     * Converts ISO8601 date to DateTime UTC
     */
    public function iso8601_to_utc($date) {
        return $datetime = date('Y-m-d H:i:s', strtotime($date));
    }

    /**
     * Converts DateTime UTC date to ISO8601
     */
    public function utc_to_iso8601($date) {
        if( !empty($date) && ($date != '0000-00-00') && ($date != '0000-00-00 00:00') && ($date != '0000-00-00 00:00:00') ) {
            $datetime = new DateTime($date);
            return $datetime->format('Y-m-d\TH:i:s\Z');
        } else {
            return null;
        }
    }

    /**
     * Array push associative.
     */
    public function array_push_assoc($array, $key, $value){
        $array[$key] = $value;
        return $array;
    }

    /**
     * Generates limits for queries.
     */
    public function getQueryLimit($limit) {
        if ($limit != '') {
            if ($limit > 150) {
                $setLimit = 150;
            }
            if ($limit <= 0) {
                $setLimit = 1;
            }
            if ( ($limit >= 1) && ($limit <= 150)) {
                $setLimit = $limit;
            }
        } else {
            $setLimit = 5;
        }
        return $setLimit;
    }

    /**
     * Verifies if is get request
     */
    public function initializeGet() {
        if (!$this->request->isGet()) {
            die();
        }
    }

    /**
     * Verifies if is post request
     */
    public function initializePost() {
        if (!$this->request->isPost()) {
            die();
        }
    }

    /**
     * Verifies if is patch request
     */
    public function initializePatch() {
        if (!$this->request->isPatch()) {
            die();
        }
    }

    /**
     * Verifies if is patch request
     */
    public function initializeDelete() {
        if (!$this->request->isDelete()) {
            die();
        }
    }

    /**
     * Encode token.
     */
    public function encodeToken($data) {
        // Encode token
        $token_encoded = $this->jwt->encode($data, $this->tokenConfig['secret']);
        $token_encoded = $this->mycrypt->encryptBase64($token_encoded);
        return $token_encoded;
    }

    /**
     * Decode token.
     */
    public function decodeToken($token) {
        // Decode token
        $token = $this->mycrypt->decryptBase64($token);
        $token = $this->jwt->decode($token, $this->tokenConfig['secret'], array('HS256'));
        return $token;
    }

    /**
     * Returns token from the request.
     * Uses token URL query field, or Authorization header
     */
    public function getToken() {
        $authHeader = $this->request->getHeader('Authorization');
        $authQuery = $this->request->getQuery('token');
        return $authQuery ? $authQuery : $this->parseBearerValue($authHeader);
    }

    protected function parseBearerValue($string) {
        if (strpos(trim($string), 'Bearer') !== 0) {
            return null;
        }
        return preg_replace('/.*\s/', '', $string);
    }

    /**
     * Builds success responses.
     */
    public function buildSuccessResponse($code, $messages, $data = '') {
        switch ($code) {
            case 200:
                $status = 'OK';
                break;
            case 201:
                $status = 'Created';
                break;
            case 202:
                break;
        }
        $generated = array(
            "status" => $status,
            "code" => $code,
            "messages" => $messages,
            "data" => $data
        );
        $this->response->setStatusCode($code, $status)->sendHeaders();
        $this->response->setContentType('application/json', 'UTF-8');
        $this->response->setJsonContent($generated, JSON_NUMERIC_CHECK)->send();
        die();
    }

    /**
     * Builds error responses.
     */
    public function buildErrorResponse($code, $messages, $data = '') {
        switch ($code) {
            case 400:
                $status = 'Bad Request';
                break;
            case 401:
                $status = 'Unauthorized';
                break;
            case 403:
                $status = 'Forbidden';
                break;
            case 404:
                $status = 'Not Found';
                break;
            case 409:
                $status = 'Conflict';
                break;
        }
        $generated = array(
            "status" => $status,
            "code" => $code,
            "messages" => $messages,
            "data" => $data
        );
        $this->response->setStatusCode($code, $status)->sendHeaders();
        $this->response->setContentType('application/json', 'UTF-8');
        $this->response->setJsonContent($generated, JSON_NUMERIC_CHECK)->send();
        die();
    }
}
