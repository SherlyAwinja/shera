<?php

namespace App\Services\Admin;
use App\Models\Filter;
use App\Models\AdminsRole;
use Illuminate\Support\Facades\Auth;

class FilterService
{
    public function getAll()
    {
        // 1. Fetch all filters (with relations if needed)
        $filters = Filter::with('categories')->get();

        // 2. Set Admin/Subadmin Permissions for Filters
        $filtersModuleCount = AdminsRole::where([
            'subadmin_id' => Auth::guard('admin')->user()->id,
            'module' => 'filters'
        ])->count();

        $status = "success";
        $message = "";
        $filtersModule = [];

        if (Auth::guard('admin')->user()->role == "admin") {

            // Full access for main admin
            $filtersModule = [
                'view_access' => 1,
                'edit_access' => 1,
                'full_access' => 1
            ];

        } elseif ($filtersModuleCount == 0) {

            // No access
            $status = "error";
            $message = "This feature is restricted for you";

        } else {

            // Get permissions for subadmin
            $filtersModule = AdminsRole::where([
                'subadmin_id' => Auth::guard('admin')->user()->id,
                'module' => 'filters'
            ])->first()->toArray();
        }

        // 3. Return the same structure as ProductService
        return [
            "filters" => $filters,
            "filtersModule" => $filtersModule,
            "status" => $status,
            "message" => $message
        ];
    }

    public function find($id)
    {
        return Filter::with('categories')->findOrFail($id);
    }

    public function store(array $data)
    {
        $filter = Filter::create([
            'filter_name' => $data['filter_name'],
            'filter_column' => $data['filter_column'],
            'sort' => $data['sort'] ?? 0,
            'status' => $data['status'] ?? 1,
        ]);

        $filter->categories()->sync($data['category_ids']);
        return $filter;
    }

    public function update($id, array $data)
    {
        $filter = $this->find($id);
        $filter->update([
            'filter_name' => $data['filter_name'],
            'filter_column' => $data['filter_column'],
            'sort' => $data['sort'] ?? 0,
            'status' => $data['status'] ?? 1,
        ]);

        $filter->categories()->sync($data['category_ids']);
        return $filter;
    }

    public function delete($id)
    {
        $filter = $this->find($id);
        $filter->categories()->detach(); // Detach related categories
        return $filter->delete();
    }

    public function updateFilterStatus($data)
    {
        $status = ($data['status'] == 'Active') ? 0 : 1;
        Filter::where('id', $data['filter_id'])->update(['status' => $status]);
        return $status;
    }
}
