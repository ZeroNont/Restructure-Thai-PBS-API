<?php

namespace App\Http\Controllers;

use Exception;
use Illuminate\Http\Request;
use Validator;
use Illuminate\Support\Facades\DB;
use App\Helpers\Response;
use App\Helpers\Util;

class MasterDataController extends Controller
{

    public function __construct(Request $req)
    {
        $this->req = $req;
    }

    // API    

    public function listProposalPrefix()
    {

        $res = new Response(__METHOD__, $this->req->all());
        try {
            
            $data = [
                'V' => [],
                'B' => []
            ];
            $temp = DB::connection('main')->select('SELECT * FROM proposal_prefixes ORDER BY no ASC');
            foreach ($temp as $row) {
                $data[$row->level_code][] = [
                    'prop_prefix_id' => $row->prop_prefix_id,
                    'name' => $row->name
                ];
            }

            // @#
            $res->set('OK', $data);

        } catch (Exception $e) {

            $res->debug($e->getMessage());

        }

        $response = $res->get();
        return response()->json($response['content'], $response['status']);
    }

    public function listMeetingSubject()
    {

        $res = new Response(__METHOD__, $this->req->all());
        try {
            
            // @0 Validating
            $this->clean(['keyword']);
            $validator = Validator::make($this->req->all(), [
                'keyword' => Util::rule(false, 'keyword')
            ]);
            if ($validator->fails()) {
                $res->set('INPUT', $validator->errors());
                throw new Exception();
            }

            $blind = [];
            $script = "SELECT subject, meeting_id FROM meetings WHERE status_code != 'CANCELED' ";
            if (!empty($this->req->input('keyword'))) {
                $script.= 'AND subject LIKE :keyword ';
                $blind['keyword'] = '%'.$this->req->input('keyword').'%';
            }
            $script.= 'ORDER BY meeting_id ASC LIMIT 5 ';

            $data = [];
            foreach (DB::connection('main')->select($script, $blind) as $row) {
                $data[] = [
                    'tag_id' => $row->meeting_id,
                    'subject' => $row->subject
                ];
            }

            // @#
            $res->set('OK', $data);

        } catch (Exception $e) {

            $res->debug($e->getMessage());

        }

        $response = $res->get();
        return response()->json($response['content'], $response['status']);
    }

}
