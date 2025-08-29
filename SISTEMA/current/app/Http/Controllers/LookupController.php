<?php namespace App\Http\Controllers;

use App\Exceptions\PermissionException;
use App\Http\Controllers\Controller;

use App;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Tobuli\Exceptions\ValidationException;

class LookupController extends Controller
{
    /*
     * @var Tobuli\Lookups\LookupTable
     */
    protected $lookup;

    public function __construct(Request $request)
    {
        parent::__construct();

        $lookupTable = $request->route()->parameter('lookup');

        switch ($lookupTable) {
            default:
                $lookupTableClass = "Tobuli\\Lookups\\Tables\\" . studly_case($lookupTable) . "LookupTable";
        }

        if ( ! class_exists($lookupTableClass))
            abort(404);

        $this->middleware(function ($request, $next) use ($lookupTableClass){
            $this->lookup = App::make($lookupTableClass);
            $this->lookup->setUser($this->user);

            if ( ! $this->lookup->checkPermission())
                throw new PermissionException();

            return $next($request);
        });
    }

    public function index()
    {
        $data = [
            'html'     => $this->lookup->html(),
            'lookup'   => $this->lookup,
        ];

        if (request()->ajax())
            return view('front::Lookup.modal', $data);
        else
            return view('front::Lookup.index', $data);
    }

    public function table()
    {
        $data = [
            'html'     => $this->lookup->html(),
            'lookup'   => $this->lookup,
        ];

        return view('front::Lookup.table', $data);
    }

    public function data()
    {
        //return $this->lookup->ajax();

        return $this->lookup->render($this->lookup->getPrintView());
    }

    public function edit()
    {
        $data = [
            'lookup'    => $this->lookup,
            'url'       => $this->lookup->getRoute('update'),
            'tableId'   => $this->lookup->getTableId(),
            'columns'   => $this->lookup->getRemembableColumns()->pluck('title', 'data')->toArray(),
            'current'   => $this->lookup->getCurrentColumns()->pluck('data')->toArray(),
        ];

        return view('front::Lookup.edit', $data);
    }

    public function update()
    {
        $validator = Validator::make(request()->all(), [
            [
                'columns' => 'required|array',
                'columns.*' => "in:" . $this->lookup->getRemembableColumns()->implode('data', ','),
            ]
        ]);

        if ($validator->fails())
            throw new ValidationException($validator->messages());


        $this->lookup->rememberColumns(request('columns'));

        return [
            'status' => 1,
        ];
    }
}
