<?php

namespace App\Http\Controllers;

use App\Services\CategoryService;
use Illuminate\Http\Request;

class CategoryController extends Controller
{

    protected $categoryService;

    public function __construct()
    {
        $this->categoryService = new CategoryService();
    }

    /**
     * Display a listing of the resource.
     *
     * @return mixed
     */
    public function index()
    {
        return $this->categoryService->get();
    }

    /**
     * @param Request $request
     * @return array
     */
    public function create(Request $request)
    {
        return $this->categoryService->download($request->src);
    }
}
