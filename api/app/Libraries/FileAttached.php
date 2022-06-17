<?php

namespace App\Libraries;

use Exception;
use Illuminate\Support\Facades\DB;

class FileAttached
{

    public static function url(string $module, int $primary, int $reference, string $ext): string
    {
        return env('APP_URL') . '/utilities/file/render/' . $primary . '/' . $module . '/' . $reference . '/' . $ext;
    }

    public static function getModule(string $module, int $reference): array
    {
        $response = [];
        try {
            $file = DB::connection('main')->select("SELECT * FROM file_uploads WHERE module_code = :module_code AND ref_id = :ref_id ORDER BY created_at ASC", [
                'module_code' => $module,
                'ref_id' => $reference
            ]);
            foreach ($file as $row) {
                $response[] = [
                    'upload_id' => $row->upload_id,
                    'created_at' => $row->created_at,
                    'updated_at' => $row->updated_at,
                    'module_code' => $row->module_code,
                    'ref_id' => $row->ref_id,
                    'origin_name' => $row->origin_name,
                    'new_name' => $row->new_name,
                    'file_ext' => $row->file_ext,
                    'file_size' => $row->file_size,
                    'title' => $row->title,
                    'note' => $row->note,
                    'url' => self::url($row->module_code, $row->upload_id, $row->ref_id, $row->file_ext)
                ];
            }
        } catch (Exception $e) {
            $response = [];
        }
        return $response;
    }

    public static function deleteModule(string $module, int $reference): bool
    {
        $response = true;
        try {

            $file = DB::connection('main')->select("SELECT * FROM file_uploads WHERE module_code = :module_code AND ref_id = :ref_id", [
                'module_code' => $module,
                'ref_id' => $reference
            ]);
            foreach ($file as $row) {
                if (!unlink(storage_path('app/uploads/' . $row->module_code . '/' . $row->ref_id . '/' . $row->new_name))) {
                    throw new Exception();
                }
                DB::connection('main')->table('file_uploads')->where('upload_id', $row->upload_id)->delete();
            }
        } catch (Exception $e) {
            $response = false;
        }
        return $response;
    }
}
