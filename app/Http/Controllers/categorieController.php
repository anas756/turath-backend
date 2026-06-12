<?php

namespace App\Http\Controllers;

use App\Http\Requests\Categorie\StoreRequest;
use App\Http\Requests\Categorie\UpdateRequest;
use App\Models\Categorie;
use App\Services\CategorieService;
use Illuminate\Http\JsonResponse;

class CategorieController extends Controller
{
    protected CategorieService $categorieService;

    public function __construct(CategorieService $categorieService)
    {
        $this->categorieService = $categorieService;
    }

    /**
     * Display all categories
     */
    public function index(): JsonResponse
    {
        $categories = $this->categorieService->index();

        return response()->json([
            'success' => true,
            'data' => $categories
        ]);
    }

    /**
     * Store category
     */
    public function store(StoreRequest $request): JsonResponse
    {
        $categorie = $this->categorieService->store(
            $request->validated()
        );

        return response()->json([
            'success' => true,
            'message' => 'Category created successfully',
            'data' => $categorie
        ], 201);
    }

    /**
     * Show category
     */
    public function show(Categorie $category): JsonResponse
    {
        $category->load('documents');

        return response()->json([
            'success' => true,
            'data' => $category
        ]);
    }

    /**
     * Update category
     */
    public function update(UpdateRequest $request,Categorie $category): JsonResponse {

        $updatedCategorie = $this->categorieService->update(
            $category,
            $request->validated()
        );

        return response()->json([
            'success' => true,
            'message' => 'Category updated successfully',
            'data' => $updatedCategorie
        ]);
    }

    /**
     * Delete category
     */
    public function destroy(Categorie $category): JsonResponse
    {
        $this->categorieService->destroy($category);

        return response()->json([
            'success' => true,
            'message' => 'Category deleted successfully'
        ]);
    }
}
