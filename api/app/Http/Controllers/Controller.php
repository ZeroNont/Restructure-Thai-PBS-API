<?php

namespace App\Http\Controllers;

use Laravel\Lumen\Routing\Controller as BaseController;
use App\Helpers\Util;

class Controller extends BaseController
{

    protected $req;

    protected function clean(array $list): void
    {
        foreach ($list as $key) {
            if ($this->req->input($key)) {
                $this->req->merge([$key => Util::trim($this->req->input($key))]);
            }
            if ($this->req->input($key) === '') {
                $this->req->merge([$key => null]);
            }
        }
    }

    protected function cast(string $type, array $list): void
    {
        foreach ($list as $key) {
            if ($this->req->input($key)) {
                $this->req->merge([$key => Util::cast($type, $this->req->input($key))]);
            }
            if ($this->req->input($key) === '') {
                $this->req->merge([$key => null]);
            }
        }
    }

}
