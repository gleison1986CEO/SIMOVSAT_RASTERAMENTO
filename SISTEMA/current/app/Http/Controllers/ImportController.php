<?php

namespace App\Http\Controllers;

use App\Models\Chip;
use App\Models\Rastreadores;
use App\CsvData;
use Redirect;
use DB;
use App\Http\Requests\CsvImportRequest;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class ImportController extends Controller
{


    public function error()
    {
        return view('error');
    }

    public function getImport()
    {
        return view('import');
    }

    public function parseImport(CsvImportRequest $request)
    {

        $path = $request->file('csv_file')->getRealPath();

        if ($request->has('header')) {
            $data = Excel::load($path, function($reader) {})->get()->toArray();
        } else {
            $data = array_map('str_getcsv', file($path));
        }

        if (count($data) > 0) {
            if ($request->has('header')) {
                $csv_header_fields = [];
                foreach ($data[0] as $key => $value) {
                    $csv_header_fields[] = $key;
                }
            }
            $csv_data = array_slice($data, 0, 100000000000000);

            $csv_data_file = CsvData::create([
                'csv_filename' => $request->file('csv_file')->getClientOriginalName(),
                'csv_header' => $request->has('header'),
                'csv_data' => json_encode($data)
            ]);
        } else {
            return Redirect::to('admin/users/clients/chip');
        }

        return view('import_fields', compact( 'csv_header_fields', 'csv_data', 'csv_data_file'));

    }

    public function processImport(Request $request)
    {
        $data = CsvData::find($request->csv_data_file_id);
        $csv_data = json_decode($data->csv_data, true);
        foreach ($csv_data as $row) {
            $contact = new Chip();
            foreach (config('app.db_fields') as $index => $field) {
                if ($data->csv_header) {
                    $contact->$field = $row[$request->fields[$field]];
                } else {
                    $contact->$field = $row[$request->fields[$index]];
                }
            }
            try{
            $saved = $contact->save();
            } catch ( \Illuminate\Database\QueryException $e) {
            return Redirect::to('admin/users/clients/error_import');

        }
            
           
        }

        return Redirect::to('admin/users/clients/chip');
    }



////////////////rastreadores

    public function getImport_rastreadores()
    {
        return view('import_rastreadores');
    }

    public function parseImport_rastreadores(CsvImportRequest $request)
    {

        $path = $request->file('csv_file')->getRealPath();

        if ($request->has('header')) {
            $data = Excel::load($path, function($reader) {})->get()->toArray();
        } else {
            $data = array_map('str_getcsv', file($path));
        }

        if (count($data) > 0) {
            if ($request->has('header')) {
                $csv_header_fields = [];
                foreach ($data[0] as $key => $value) {
                    $csv_header_fields[] = $key;
                }
            }
            $csv_data = array_slice($data, 0, 100000000000000);

            $csv_data_file = CsvData::create([
                'csv_filename' => $request->file('csv_file')->getClientOriginalName(),
                'csv_header' => $request->has('header'),
                'csv_data' => json_encode($data)
            ]);
        } else {
            return Redirect::to('admin/users/clients/rastreadores');
        }

        return view('import_fields_rastreadores', compact( 'csv_header_fields', 'csv_data', 'csv_data_file'));

    }

    public function processImport_rastreadores(Request $request)
    {
        $data = CsvData::find($request->csv_data_file_id);
        $csv_data = json_decode($data->csv_data, true);
        foreach ($csv_data as $row) {
            $contact = new Rastreadores();
            foreach (config('app.db_fields_rastreadores') as $index => $field) {
                if ($data->csv_header) {
                    $contact->$field = $row[$request->fields[$field]];
                } else {
                    $contact->$field = $row[$request->fields[$index]];
                }
            }
            try{
                $contact->save();
                } catch ( \Illuminate\Database\QueryException $e) {
                return Redirect::to('admin/users/clients/error_import');
    
            }
                
        }

        return Redirect::to('admin/users/clients/rastreadores');
    }

}
