<?php

namespace App\Libraries;

use App\Libraries\Util;
use Exception;
use DateTime;
use MongoDB\BSON\UTCDateTime;
use Illuminate\Support\Facades\DB;

class Response
{

    const CODE = [
        // Success
        'OK' => [true, 200],
        'CREATED' => [true, 201],
        'RESET' => [true, 200],
        // Failure
        'ERROR' => [false, 500],
        'INTERNAL' => [false, 500],
        'INPUT' => [false, 400],
        'EXIST' => [false, 400],
        'ACCEPT' => [false, 406],
        'AUTH' => [false, 401],
        'ACCESS' => [false, 403],
        'PROGRESS' => [false, 422],
        'EXPIRED' => [false, 422],
        // Thanos
        'PEACE' => [true, 200]
    ];

    private string $method;
    private string $code = 'ERROR';
    private bool $success = false;
    private int $status = 500;
    private $data = null;
    private $option = null;
    private $message = null;
    private array $request = [];

    public function __construct(string $method, array $request = [])
    {
        $this->method = $method;
        $this->request = $request;
    }

    public function set(string $code, $data = null, $option = null): void
    {
        $this->code = $code;
        $this->success = self::CODE[$this->code][0];
        $this->status = self::CODE[$this->code][1];
        $this->data = $data;
        $this->option = $option;
    }

    public function get(): array
    {
        // Default
        $response = [
            'status' => $this->status,
            'content' => [
                'code' => $this->code,
                'success' => $this->success
            ]
        ];
        // Mode
        if ($this->success) {
            $response['content']['data'] = $this->data;
        } else {
            $response['content']['error'] = $this->data;
        }
        // Option
        if (!empty($this->option)) {
            $response['content']['option'] = $this->option;
        }

        // MongoDB Log
        if (env('MONGO_LOG')) {
            self::log($this->method, $this->request, $response, $this->message);
        }

        return $response;
    }

    public function debug(string $message): void
    {
        // Stacking Exception
        $this->message = Util::trim($message);
    }

    private static function log(string $method, $request, $response, $exception): bool
    {
        $status = true;
        try {
            DB::connection('log')->collection('requests')->insert([
                'stage' => env('APP_ENV'),
                'created' => new UTCDateTime(new DateTime(date('Y-m-d H:i:s', time() + 25200))),
                'ip' => empty(Util::trim($request['ip'])) ? null : Util::trim($request['ip']),
                'method' => $method,
                'request' => (object) $request,
                'response' => (object) $response,
                'exception' => $exception
            ]);
        } catch (Exception $e) {
            // Connection Error
            $status = false;
        }
        return $status;
    }
}