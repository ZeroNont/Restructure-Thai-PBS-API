<?php

namespace App\Http\Controllers;

use Exception;
use Illuminate\Http\Request;
use Validator;
use Illuminate\Support\Facades\DB;
use App\Helpers\Response;
use App\Helpers\Util;
use Illuminate\Support\Facades\Storage;

class UtilitiesController extends Controller
{

    private const PATH_DIR = '/uploads';

    public function __construct(Request $req)
    {
        $this->req = $req;
    }

    // API

    public function render($primary, $module, $reference, $ext) // [GET]
    {

        try {

            $validator = Validator::make([
                'primary' => (int) $primary,
                'module' => $module,
                'reference' => (int) $reference,
                'ext' => $ext
            ], [
                'primary' => Util::rule(true, 'primary'),
                'module' => Util::rule(true, 'utilities.module_code'),
                'reference' => Util::rule(true, 'primary'),
                'ext' => Util::rule(true, 'utilities.ext_file')
            ]);
            if ($validator->fails()) {
                throw new Exception();
            }

            $exist = DB::connection('main')->select('SELECT * FROM file_uploads WHERE upload_id = :upload_id AND module_code = :module_code AND ref_id = :ref_id AND file_ext = :file_ext LIMIT 1', [
                'upload_id' => (int) $primary,
                'module_code' => $module,
                'ref_id' => (int) $reference,
                'file_ext' => $ext
            ]);
            if (!$exist) {
                throw new Exception();
            }

            $path = Storage::path(self::PATH_DIR.'/'.$exist[0]->module_code.'/'.$exist[0]->ref_id.'/'.$exist[0]->new_name);
            if (!file_exists($path)) {
                throw new Exception();
            }

            // @#
            return response()->download($path);

        } catch (Exception $e) {

            return response(null, 403);

        }

    }

    public function upload() // [POST]
    {

        $res = new Response(__METHOD__, $this->req->all());
        try {
            
            $this->clean(['subject', 'note']);

            // @0 Validating
            $schema = [
                'module_code' => Util::rule(true, 'utilities.module_code'),
                'ref_id' => Util::rule(true, 'primary'),
                'subject' => Util::rule(true, 'text.title'),
                'note' => Util::rule(false, 'text.note')
            ];
            $schema['attached_file'] = (in_array($this->req->input('module_code'), ['PROFILE'])) ? Util::rule(true, 'utilities.attached_img') : Util::rule(true, 'utilities.attached_file');
            $validator = Validator::make($this->req->all(), $schema);
            if ($validator->fails()) {
                $res->set('INPUT', $validator->errors());
                throw new Exception();
            }            
            if (!$this->req->hasFile('attached_file')) {
                $this->set('INPUT');
                throw new Exception();
            }

            // Has Relation Module
            $reference = (in_array($this->req->input('module_code'), ['PROFILE'])) ? (int) $this->req->input('auth_user_id') : (int) $this->req->input('ref_id');
            if (!self::hasModule($this->req->input('module_code'), $reference)) {
                $res->set('EXIST');
                throw new Exception();
            }

            // Configure
            $ext = $this->req->file('attached_file')->extension();
            $rename = Util::genStr('FILE').'.'.$ext;
            
            // Uploading
            $path = $this->req->file('attached_file')->storeAs(self::PATH_DIR.'/'.$this->req->input('module_code').'/'.$reference, $rename);
            if (empty($path)) {                
                $res->set('INTERNAL');
                throw new Exception();
            }

            // Insert
            $id = DB::connection('main')->table('file_uploads')->insertGetId([
                'created_at' => Util::now(),
                'created_user_id' => $this->req->input('auth_user_id'),
                'module_code' => $this->req->input('module_code'),
                'ref_id' => $reference,
                'origin_name' => pathinfo($this->req->file('attached_file')->getClientOriginalName(), PATHINFO_FILENAME).'.'.$ext,
                'new_name' => $rename,
                'file_ext' => $ext,
                'file_size' => $this->req->file('attached_file')->getSize(),
                'title' => $this->req->input('subject'),
                'note' => null
            ], 'upload_id');

            // Post Action
            self::postAction($this->req->input('module_code'), (int) $reference, (int) $id);

            // @#
            $res->set('OK', [
                'upload_id' => (int) $id
            ]);

        } catch (Exception $e) {

            $res->debug($e->getMessage());

        }

        $response = $res->get();
        return response()->json($response['content'], $response['status']);
    }

    public function delete($id)
    {
        $res = new Response(__METHOD__, $this->req->all());
        try {
            
            // @0 Validating
            $validator = Validator::make(['upload_id' => $id], [
                'upload_id' => Util::rule(true, 'primary')
            ]);
            if ($validator->fails()) {
                $res->set('INPUT', $validator->errors());
                throw new Exception();
            }

            $exist = DB::connection('main')->select('SELECT * FROM file_uploads WHERE upload_id = :upload_id LIMIT 1', [
                'upload_id' => $id
            ]);
            if (!$exist) {
                $res->set('EXIST');
                throw new Exception();
            }

            if (!unlink(storage_path('app/uploads/'.$exist[0]->module_code.'/'.$exist[0]->ref_id.'/'.$exist[0]->new_name))) {
                $res->set('EXIST');
                throw new Exception();
            }
            DB::connection('main')->table('file_uploads')->where('upload_id', $exist[0]->upload_id)->delete();

            // @#
            $res->set('OK');

        } catch (Exception $e) {

            $res->debug($e->getMessage());

        }

        $response = $res->get();
        return response()->json($response['content'], $response['status']);
    }

    // Function

    private static function hasModule(string $module, int $id): bool
    {
        $script = null;
        $blind = [];
        
        switch ($module) {

            // File

            case 'PROP': {                
                $script = 'SELECT * FROM meeting_proposals WHERE prop_id = :prop_id LIMIT 1';
                $blind['prop_id'] = $id;
            } break;

            case 'TOPIC': {
                $script = 'SELECT * FROM topics WHERE topic_id = :topic_id LIMIT 1';
                $blind['topic_id'] = $id;
            } break;

            case 'AGENDA': {
                $script = 'SELECT * FROM meetings WHERE meeting_id = :meeting_id LIMIT 1';
                $blind['meeting_id'] = $id;
            } break;

            // Image

            case 'PROFILE': {
                $script = 'SELECT * FROM users WHERE user_id = :user_id LIMIT 1';
                $blind['user_id'] = $id;
            } break;

        }
        return (DB::connection('main')->select($script, $blind)) ? true : false;
    }

    private static function postAction(string $module, int $reference, int $except): void
    { 
        switch ($module) {

            case 'PROFILE': { // Delete Previous File
                
                $previous = DB::connection('main')->select("SELECT * FROM file_uploads WHERE module_code = 'PROFILE' AND ref_id = :ref_id AND upload_id != :except", [
                    'ref_id' => $reference,
                    'except' => $except
                ]);

                $slot = [];
                foreach ($previous as $row) {
                    $slot[] = $row->upload_id;
                    unlink(storage_path('app/uploads/'.$row->module_code.'/'.$row->ref_id.'/'.$row->new_name));
                }
                
                DB::connection('main')->table('file_uploads')->whereIn('upload_id', $slot)->delete();

            } break;

        }
    }

}
