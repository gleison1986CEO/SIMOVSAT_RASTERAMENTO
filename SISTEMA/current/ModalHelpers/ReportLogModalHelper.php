<?php namespace ModalHelpers;

use App\Exceptions\PermissionException;
use App\Exceptions\ResourseNotFoundException;
use Auth;
use CustomFacades\Repositories\ReportLogRepo;
use CustomFacades\Repositories\UserRepo;
use Tobuli\Entities\ReportLog;
use Tobuli\Exceptions\ValidationException;
use Tobuli\Reports\ReportManager;

ini_set('memory_limit', '-1');
set_time_limit(600);

class ReportLogModalHelper extends ModalHelper
{
	private $formats = [];
	private $mimes = [];

	function __construct()
	{
		parent::__construct();

		$this->formats = [
			'html' => trans('front.html'),
			'xls' => trans('front.xls'),
			'pdf' => trans('front.pdf'),
            'pdf_land' => trans('front.pdf_land'),
            'csv' => trans('front.csv'),
		];

		$this->mimes = [
			'html' => 'plain/text',
			'xls' => 'application/vnd.ms-excel',
			'pdf' => 'application/pdf',
            'pdf_land' => 'application/pdf',
            'csv' => 'text/csv',
		];

        $this->exts = [
            'html' => 'html',
            'xls' => 'xls',
            'pdf' => 'pdf',
            'pdf_land' => 'pdf',
            'csv' => 'csv',
        ];
	}

	public function get()
	{
		$filter	= ['user_ids' => [$this->user->id]];

		if ($this->user->isManager()) {
			$filter = ['user_ids' => UserRepo::getWhere(['manager_id' => $this->user->id])->pluck('id', 'id')->all()];
			$filter['user_ids'][] = $this->user->id;
		}

		if ($this->user->isAdmin())
		    unset($filter['user_ids']);

		$logs = ReportLogRepo::searchAndPaginate(['filter' => $filter], 'id', 'desc', 10);

		foreach ( $logs as $index => $log )
		{
			$logs[ $index ]->type_text = ReportManager::getTitle($log->type);
			$logs[ $index ]->format_text = empty($this->formats[ $log->format ]) ? $log->format : $this->formats[ $log->format ];
		}

		return $logs;
	}

	public function download($id)
	{
		$where = isAdmin() ? ['id' => $id] : ['id' => $id, 'user_id' => $this->user->id];

		$log = ReportLogRepo::findWhere($where);

		if ( $log ) {
			$data = $log->data;

			$headers = [
				'Content-Type' => $this->mimes[ $log->format ],
				'Content-Length' => $log->size,
				'Content-Disposition' => 'attachment; filename="' . $log->title . '.' . $this->exts[ $log->format ] . '"'
			];
		}

		return compact('data', 'headers');
	}

	public function destroy()
	{
		if ( empty($this->data['id']) )
		    throw new ResourseNotFoundException('front.report');

		$ids = is_array( $this->data['id'] ) ? $this->data['id'] : [ $this->data['id'] ];

		$items = ReportLogRepo::getWhereIn( $ids );

		if ( empty($items) )
            throw new ResourseNotFoundException('front.report');

		foreach ( $items as $item )
		{
		    if ( ! $this->user->can('remove', $item) )
		        continue;

            $item->delete();
		}

		return ['status' => 1];
	}

    public function destroyAll(): array
    {
        $items = ReportLog::userAccessible($this->user)->cursor();

        foreach ($items as $item) {
            $item->delete();
        }

        return ['status' => 1];
    }
}