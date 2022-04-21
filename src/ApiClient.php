<?php

namespace yanggs07\saltapi;

use yii\web\ForbiddenHttpException;

class ApiClient extends \lspbupt\curl\CurlHttp
{
    public $protocol = 'https';
    public $timeout = 30;

    public string $cacert = '';
    public bool $skipCertCheck = false;

    public string $username = '';
    public string $password = '';


    public function init()
    {
        parent::init();
        $this->beforeRequest = function (array $params, self $curl) {
            $curl->setHeader('Accept', 'application/json');
            if ($this->skipCertCheck) {
                curl_setopt($this->getCurl(), CURLOPT_SSL_VERIFYPEER, false);
            } elseif ($this->cacert) {
                curl_setopt($this->getCurl(), CURLOPT_CAINFO, $this->cacert);
            }
            return $params;
        };
        $this->afterRequest = function (string $data, self $curl) {
            return json_decode($data, true)['return'][0];
        };
    }

    public function pamLogin(): PamSession
    {
        $ret = $this->setPost()->send('/login', [
            'username' => $this->username,
            'password' => $this->password,
            'eauth' => 'pam',
        ]);
        $token = $ret['token'] ?? '';
        if (!$token) {
            throw new ForbiddenHttpException('login failed');
        }
        return PamSession::start($this, $ret['token'], (int)$ret['expire']);
    }

    public function localRun(PamSession $session, string $target, string $function, $arg = '')
    {
        return $session->auth($this)->setPost()->send('/', [
            'client' => 'local',
            'tgt' => $target,
            'fun' => $function,
            'arg' => $arg,
        ]);
    }

    public function localAsyncRun(PamSession $session, string $target, string $function, $arg = '')
    {
        return $session->auth($this)->setPost()->send('/', [
            'client' => 'local_async',
            'tgt' => $target,
            'fun' => $function,
            'arg' => $arg,
        ]);
    }
    public function job(PamSession $session, string $jobId)
    {
        return $session->auth($this)->setGet()->send("/jobs/$jobId", []);
    }

}