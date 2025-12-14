<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\Admin\ProductService;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Auth;
use App\Models\Product;
use App\Models\Category;



class ProductController extends Controller
{
    protected $productService;

    // Inject ProductService using constructor injection
    public function __construct(ProductService $productService)
    {
        $this->productService = $productService;
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        Session::put('page', 'products');
        $result = $this->productService->products();
        if ($result['status'] == "error") {
            return redirect('admin/dashboard')->with('error_message', $result['message']);
        }
        return view('admin.products.index', [
            'products' => $result['products'],
            'productsModule' => $result['productsModule'],
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $title = 'Add Product';
        $getCategories = Category::getCategories('Admin');
        return view('admin.products.add_edit_product', compact('title', 'getCategories'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $message = $this->productService->addEditProduct($request);
        return redirect()->route('products.index')->with('success_message', $message);
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
        $title = 'Edit Product';
        $product = Product::findOrFail($id);
        $getCategories = Category::getCategories('Admin');
        return view('admin.products.add_edit_product', compact('title', 'getCategories', 'product'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $request->merge(['id' => $id]);
        $message = $this->productService->addEditProduct($request);
        return redirect()->route('products.index')->with('success_message', $message);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $result = $this->productService->deleteProduct($id);
        return redirect()->back()->with('success_message', $result['message']);
    }

    /**
     * Update Product Status
     */
    public function updateProductStatus(Request $request)
    {
        if($request->ajax()) {
            $data = $request->all();
            $status = $this->productService->updateProductStatus($data);
            return response()->json(['status' => $status, 'product_id' => $data['product_id']]);
        }
    }

    /**
     * Upload Product Image
     */
    public function uploadImage(Request $request)
    {
        if ($request->hasFile('file')) {
            $fileName = $this->productService->handleImageUpload($request->file('file'));
            return response()->json(['fileName' => $fileName]);
        }
        return response()->json(['error' => 'No file uploaded'], 400);
    }

    /**
     * Upload Product Video
     */
    public function uploadVideo(Request $request)
    {
        if ($request->hasFile('file')) {
            $fileName = $this->productService->handleVideoUpload($request->file('file'));
            return response()->json(['fileName' => $fileName]);
        }
        return response()->json(['error' => 'No file uploaded'], 400);
    }

    /**
     * Delete Product Main Image
     */
    public function deleteProductMainImage(string $id)
    {
        $message = $this->productService->deleteProductMainImage($id);
        return redirect()->back()->with('success_message', $message);
    }

    /**
     * Delete Product Video
     */
    public function deleteProductVideo(string $id)
    {
        $message = $this->productService->deleteProductVideo($id);
        return redirect()->back()->with('success_message', $message);
    }
}
