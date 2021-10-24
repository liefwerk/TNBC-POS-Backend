<?php

namespace App\Services\Item;

use Illuminate\Support\Str;

//Interface
use App\Contracts\Item\ItemRepositoryInterface;

//Resources
use App\Http\Resources\PaginationResource;

//Utilities
use App\Utilities\FileUtilities;

//Models
use App\Models\Item;
use App\Models\Category;
use App\Models\Brand;
use App\Models\Unit;
use App\Models\Supplier;

class ItemServices{
    
    private $itemRepositoryInterface;
    private $fileUtilities;
    public static $imagePath = 'images/item';
    public static $explode_at = "item/";

    public function __construct(
        ItemRepositoryInterface $itemRepositoryInterface,
        FileUtilities $fileUtilities){
        $this->itemRI = $itemRepositoryInterface;
        $this->fileUtilities = $fileUtilities;

        $this->itemModel = Item::class;
        $this->categoryModel = Category::class;
        $this->brandModel = Brand::class;
        $this->unitModel = Unit::class;
        $this->supplierModel = Supplier::class;
    }

    public function randomItems(){
        return $this->itemRI->randomItems();
    }

    public function itemList($request){
        $limit = $request->limit;
        if($request->has('q')){
            $q = $request->q;
            $prop1 = 'category_id';
            $prop2 = 'brand_id';
            $prop3 = 'unit_id';
            $prop4 = 'supplier_id';
            switch (true) {
                case $this->itemRI->checkIfObj($this->categoryModel, $q):
                    $item = $this->itemRI->filterByProp($this->itemModel, $this->categoryModel, $q, $limit, $prop1);
                    break;
                case $this->itemRI->checkIfObj($this->brandModel, $q):
                    $item = $this->itemRI->filterByProp($this->itemModel, $this->brandModel, $q, $limit, $prop2);
                    break;
                case $this->itemRI->checkIfObj($this->unitModel, $q):
                    $item = $this->itemRI->filterByProp($this->itemModel, $this->unitModel, $q, $limit, $prop3);
                    break;
                case $this->itemRI->checkIfObj($this->supplierModel, $q):
                    $item = $this->itemRI->filterByProp($this->itemModel, $this->supplierModel, $q, $limit, $prop4);
                    break;
                default:
                    $item = $this->itemRI->itemSearch($q, $limit);
            }
        }else{
            $item = $this->itemRI->itemList($limit);
        }

        if($item){
            return $item;
        }else{
            return response(["failed"=>'item not found'],404);
        }
    }

    public function itemGetById($id){
        $item = $this->itemRI->itemGetById($id);
        if($item){
            return $item;
        }else{
            return response(["failed"=>'item not found'],404);
        }
    }

    public function itemCreate($request){
        $fields = $request->validate([
            'category_id'=>'required|numeric',
            'brand_id'=>'required|numeric',
            'unit_id'=>'required|numeric',
            'supplier_id'=>'required|numeric',
            'name'=>'required|string|unique:categories,name',
            'price'=>'required|numeric',
            'inventory'=>'required|numeric',
        ]);

        //image upload
        $image = $this->fileUtilities->fileUpload($request, url(''), self::$imagePath, false, false, false);
        $data = $request->all();
        $data['image'] = $image;

        $item = $this->itemRI->itemCreate([
            'category_id' => $fields['category_id'],
            'brand_id' => $fields['brand_id'],
            'unit_id' => $fields['unit_id'],
            'supplier_id' => $fields['supplier_id'],
            'name' => $fields['name'],
            'slug' => Str::slug($fields['name']),
            'sku' => rand(1111,100000),
            'price' => $fields['price'],
            'discount' => $data['discount'],
            'inventory' => $fields['inventory'],
            'expire_date' => $data['expire_date'],
            'available' => $data['available'],
            'image' => $data['image']
        ]);

        return response($item,201);
    }

    public function itemUpdate($request, $id){
        $item = $this->itemRI->itemFindById($id);

        if($item){
            $fields = $request->validate([
                'category_id'=>'required|numeric',
                'brand_id'=>'required|numeric',
                'unit_id'=>'required|numeric',
                'supplier_id'=>'required|numeric',
                'name'=>'required|string|unique:categories,name',
                'price'=>'required|numeric',
                'inventory'=>'required|numeric',
            ]);

            $data = $request->all();
            //image upload
            $exImagePath = $item->image;
            $image = $this->fileUtilities->fileUpload($request, url(''), self::$imagePath, self::$explode_at, $exImagePath, true);
            $data['image'] = $image;

            $item->update([
                'category_id' => $fields['category_id'],
                'brand_id' => $fields['brand_id'],
                'unit_id' => $fields['unit_id'],
                'supplier_id' => $fields['supplier_id'],
                'name' => $fields['name'],
                'slug' => Str::slug($fields['name']),
                'price' => $fields['price'],
                'discount' => $data['discount'],
                'inventory' => $fields['inventory'],
                'expire_date' => $data['expire_date'],
                'available' => $data['available'],
                'image' => $data['image']
            ]);
            return response($item,201);
        }else{
            return response(["failed"=>'item not found'],404);
        }
    }

    public function itemDelete($id){
        $item = $this->itemRI->itemFindById($id);
        if($item){
            $item->delete();
            return response(["done"=>'item Deleted Successfully'],200);
        }else{
            return response(["failed"=>'item not found'],404);
        }
    }
}