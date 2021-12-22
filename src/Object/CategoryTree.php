<?php

namespace App\Object;

class CategoryTree
{
    public $categories = [];

    private $count;

    public function __construct()
    {
        $this->count = 1;
    }

    public function displayCategoryTree($categoryTree, $parentItem = null) {
        foreach ($categoryTree as $parent => $child) {
            $categoryName = htmlspecialchars($parent);
            if (utf8_encode($categoryName) == $categoryName && $categoryName != '') {
                $category = [
                    'name' => $categoryName,
                    'id' => $this->count,
                ];
                if ($parentItem) {
                    $category['parentId'] = $parentItem['id'];
                }

                $this->categories[] = $category;
                $this->count++;
                $this->displayCategoryTree($child, $category);
            }
        }
    }

    public function buildCategoryTree($categoryLines, $separator) {
        $catTree = array();
        foreach ($categoryLines as $catLine) {

            $path = explode($separator, $catLine);
            $node = & $catTree;
            foreach ($path as $cat) {
                $cat = trim($cat);
                if (!isset($node[$cat])) {
                    $node[$cat] = array();
                }
                $node = & $node[$cat];
            }
        }
        return $catTree;
    }
}
