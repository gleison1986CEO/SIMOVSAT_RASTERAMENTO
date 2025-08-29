<?php
	namespace App\Exports;
	use App\Models\User;
	use Maatwebsite\Excel\Concerns\FromCollection;

	class ExportUser implements FromCollection {
		public function collection()
		{
			return User::select('name','email')->get();
		}
	}
?>
