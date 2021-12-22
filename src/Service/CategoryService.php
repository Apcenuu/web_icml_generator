<?php

namespace App\Service;

use App\Object\CategoryTree;

class CategoryService
{
    private $categoryTree;

    public function __construct()
    {
        $this->categoryTree = new CategoryTree();
    }

    public function getOffers($offers, $categoriesArray)
    {

        $tree = $this->categoryTree->buildCategoryTree($categoriesArray, ' > ');
        $this->categoryTree->displayCategoryTree($tree);

        foreach ($offers as $key => $offer) {
            foreach ($this->categoryTree->categories as $category) {
                if ($offer['category'] == $category['name']) {

                    $offers[$key]['categoryId'] = [$category['id']];
                    unset($offers[$key]['category']);
                }
            }
        }
        return $offers;
    }

    public function getCategories()
    {
        return $this->categoryTree->categories;
    }
}
