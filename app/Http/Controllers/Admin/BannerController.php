<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\Admin\BannerRequest;
use App\Services\Admin\BannerService;
use App\Models\Banner;
use App\Models\ColumnPrefence;
use App\Models\AdminsRole;
use Session;
use Auth;

class BannerController extends Controller
{
    protected $bannerService;

    public function __construct(BannerService $bannerService)
    {
        $this->bannerService = $bannerService;
    }
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        Session::put('page', 'banners');
        $result = $this->bannerService->banners();
        if ($result['status'] == 'error') {
            return redirect('admin/dashboard')->with('error_message', $result['message']);
        }

        $banners = $result['banners'];
        $bannersModule = $result['bannersModule'];

        $columnPreference = ColumnPrefence::where('admin_id', Auth::guard('admin')->id())
            ->where('table_name', 'banners')
            ->first();

        $bannersSavedOrder = $columnPreference ? json_decode($columnPreference->column_order, true) :
        null;
        $bannerHiddenColumns = $columnPreference ? json_decode($columnPreference->hidden_columns, true) :
        [];

        return view('admin.banners.index', compact(
            'banners',
            'bannersModule',
            'bannersSavedOrder',
            'bannerHiddenColumns'
        ));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $result = $this->bannerService->deleteBanner($id);
        return redirect()->back()->with('success_message', $result['message']);
    }

    /**
     * Update banner status via AJAX
     */
    public function updateStatus(Request $request)
    {
        if ($request->ajax()) {
            $data = $request->all();
            $status = $this->bannerService->updateBannerStatus($data);
            return response()->json(['status' => $status, 'banner_id' => $data['banner_id']]);
        }
    }
}
