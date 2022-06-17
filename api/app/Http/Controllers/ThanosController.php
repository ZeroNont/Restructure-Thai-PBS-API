<?php

namespace App\Http\Controllers;

use Exception;
use Illuminate\Http\Request;
use Validator;
use Illuminate\Support\Facades\DB;
use App\Helpers\Response;
use App\Helpers\Util;

class ThanosController extends Controller
{

    public function __construct(Request $req)
    {
        $this->req = $req;
    }

    // API

    public function snap() // [DELETE]
    {

        $res = new Response(__METHOD__, $this->req->all());
        try {
            
            // @1 Clean Expired Tokens in Active Sessions
            DB::connection('main')->table('active_sessions')->where('expired_at', '<=', Util::now())->delete();

            // @#
            $res->set('PEACE');

        } catch (Exception $e) {

            $res->debug($e->getMessage());

        }

        $response = $res->get();
        return response()->json($response['content'], $response['status']);
    }

}
