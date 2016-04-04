<?php

/*
 * The MIT License
 *
 * Copyright 2015 jeppe.
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 */

/**
 * Description of GW2ResponseEvent
 *
 * @author jeppe
 */
require_once 'Event.php';
class GW2ResponseEvent extends Event{
    private $userId;
    private $jsonResponse;
    private $endpoint;
    private $httpResponseCode;
    
    function __construct($userId, $jsonResponse, $endpoint, $httpResponseCode) {
        parent::__construct(true);
        $this->userId = $userId;
        $this->jsonResponse = $jsonResponse;
        $this->endpoint = $endpoint;
        $this->httpResponseCode = $httpResponseCode;
    }

    function getUserId() {
        return $this->userId;
    }
    
    function setUserId($userId){
        $this->userId = $userId;
    }
    
    function getJsonResponse() {
        return $this->jsonResponse;
    }
    
    function setJsonResponse($jsonResponse) {
        $this->jsonResponse = $jsonResponse;
    }

    function getEndpoint() {
        return $this->endpoint;
    }

    function getHttpResponseCode() {
        return $this->httpResponseCode;
    }
    
    public function toString() {
        return "userId: $this->userId, endpoint: $this->endpoint, http_code: $this->httpResponseCode, jsonResponse: {".implode(",", $this->jsonResponse)."}";
    }
    
    public function __toString() {
        return "userId: $this->userId, endpoint: $this->endpoint, http_code: $this->httpResponseCode, jsonResponse: {".implode(",", $this->jsonResponse)."}";
    }
}
