<?php


namespace App\Services;


use App\Models\Category;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\File;
use PhpParser\Node\Stmt\DeclareDeclare;

class CategoryService
{

    /**
     * @param $src
     * @return array
     */
    public function download($src)
    {
        $rh = explode('.', $src);
        $rh = $rh[count($rh)-1];

        if ($rh !== "json" && $rh !== "csv"){
            return ['status' => false, 'error' => 'none typ file'];
        }
        $local='test.'.$rh;
        file_put_contents($local, file_get_contents($src));

        $contents = File::get($local);

        if ($rh == 'json') {
            $contents = json_decode($contents, true);
            $this->create($contents);
        }else{
            $this->csv(explode("\r\n", $contents));
        }

    }

    /**
     * @param $contents
     */
    public function csv($contents)
    {
        $array = [];
        foreach ($contents as $key => $item){
            if ($key != 0){
                $item = explode(',', $item);
                $result = [];
                foreach ($item as $k => $str){
                    if ($str != '') {
                        if ($k == 0) {
                            $result['id'] = $str;
                        }
                        if ($k == 1) {
                            $result['category'] = $str;
                        }
                        $result['parent_id'] = null;
                        if ($k == 2) {
                            $result['parent_id'] = $str;
                        }
                    }
                }
                if (count($result)) {
                    $array[] = $result;
                }
            }
        }
        $result = $this->buildTree($array);
        $this->create($result);
    }

    /**
     * @param array $elements
     * @param int $parentId
     * @return array
     */
    public function buildTree(array &$elements, $parentId = 0) {

        $branch = array();

        foreach ($elements as &$element) {

            if ($element['parent_id'] == $parentId) {
                $children = $this->buildTree($elements, $element['id']);
                if ($children) {
                    $element['subcategories'] = $children;
                }
                $branch[$element['id']] = $element;
                unset($element);
            }
        }
        return $branch;
    }

    /**
     * @return mixed
     */
    public function get()
    {
        return Category::get()->toTree()->toArray();
    }

    /**
     * @param $categories
     */
    public function create($categories)
    {
        if (!count($this->get())) {
            foreach ($categories as $category) {
                Category::create($category);
            }
        }else{
            $this->update(Arr::flatten($this->get()), $categories);
            $this->delete($this->get(), Arr::flatten($categories));
        }
    }

    /**
     * @param $oldCategories
     * @param $newCategories
     * @param int $id
     */
    public function update($oldCategories, $newCategories, $id = null)
    {
        foreach ($newCategories as $newCategory) {
            if (array_search($newCategory['id'], $oldCategories) === false) {
                Category::create(
                    [
                        'id' => $newCategory['id'],
                        'category' => $newCategory['category'],
                        '_lft' => 0,
                        '_rgt' => 0,
                        'parent_id' => $id,
                    ]
                );
            }
            if (isset($newCategory['subcategories'])) {
                $this->update($oldCategories, $newCategory['subcategories'], $newCategory['id']);
            }
        }
    }

    /**
     * @param $oldCategories
     * @param $newCategories
     */
    public function delete($oldCategories, $newCategories)
    {
        foreach ($oldCategories as $oldCategory) {
            if (array_search($oldCategory['id'], $newCategories) === false) {
                Category::where('id', $oldCategory['id'])->delete();
            }
            $this->delete($oldCategory['children'], $newCategories );
        }
    }


}
