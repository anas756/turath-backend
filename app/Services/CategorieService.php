<?php

namespace App\Services;

use App\Models\Categorie;
use Illuminate\Support\Str;

class CategorieService
{
    /**
     * Get all categories
     */
    public function index()
    {
        return Categorie::with('documents')->get();
    }

    /**
     * Store category
     */
    public function store(array $data): Categorie
    {
        $data['slug'] = Str::slug($data['name']);

        return Categorie::create($data);
    }

    /**
     * Update category
     */
    public function update( Categorie $categorie, $data): Categorie {

        if (isset($data['name'])) {
            $data['slug'] = Str::slug($data['name']);
        }

        $categorie->update($data);

        return $categorie->fresh();
    }

    /**
     * Delete category
     */
    public function destroy(Categorie $categorie): bool
    {
        return $categorie->delete();
    }
}
