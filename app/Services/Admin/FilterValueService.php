<?php

namespace App\Services\Admin;
use App\Models\FilterValue;
use App\Models\AdminsRole;
use Illuminate\Support\Facades\Auth;

class FilterValueService
{
    public function getAll($filterId)
    {
        // 1. Get all filter values for the given filter
        $filtersValues = FilterValue::where('filter_id', $filterId)->get();

        // 2. Check permissions for subadmin
        $filtersValuesModuleCount = AdminsRole::where([
            'subadmin_id' => Auth::guard('admin')->user()->id,
            'module' => 'filters_values'
        ])->count();

        $status = "success";
        $message = "";
        $filtersValuesModule = [];

        if (Auth::guard('admin')->user()->role == "admin") {

            // Full access for main admin
            $filtersValuesModule = [
                'view_access' => 1,
                'edit_access' => 1,
                'full_access' => 1
            ];

        } elseif ($filtersValuesModuleCount == 0) {

            // No access
            $status = "error";
            $message = "This feature is restricted for you!";

        } else {

            // Permissions for subadmin
            $filtersValuesModule = AdminsRole::where([
                'subadmin_id' => Auth::guard('admin')->user()->id,
                'module' => 'filters_values'
            ])->first()->toArray();
        }

        return [
            "filtersValues" => $filtersValues,
            "filtersValuesModule" => $filtersValuesModule,
            "status" => $status,
            "message" => $message
        ];
    }

    public function store($data, $filterId)
    {
        return FilterValue::create([
            'filter_id' => $filterId,
            'value' => $data['value'],
            'sort' => $data['sort'] ?? 0,
            'status' => $data['status'] ?? 1,
        ]);
    }

    public function find($filterId, $id)
    {
        return FilterValue::where('filter_id', $filterId)->findOrFail($id);
    }

    public function update($id, array $data, $filterId)
    {
        $filterValue = $this->find($filterId, $id);
        $filterValue->update([
            'value' => $data['value'],
            'sort' => $data['sort'] ?? 0,
            'status' => $data['status'] ?? 1,
        ]);
        return $filterValue;
    }

    public function delete($id, $filterId)
    {
        $filterValue = $this->find($filterId, $id);
        return $filterValue->delete();
    }
}
