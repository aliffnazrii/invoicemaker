<?php

namespace App\Http\Controllers\Admin;

use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;
use Illuminate\Http\Request;
use \App\Models\Product;

/**
 * Class ProductCrudController
 * @package App\Http\Controllers\Admin
 * @property-read \Backpack\CRUD\app\Library\CrudPanel\CrudPanel $crud
 */
class ProductCrudController extends CrudController
{
    use \Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\CreateOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\UpdateOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\DeleteOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\ShowOperation;

    /**
     * Configure the CrudPanel object. Apply settings to all operations.
     * 
     * @return void
     */
    public function setup()
    {
        CRUD::setModel(\App\Models\Product::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/product');
        CRUD::setEntityNameStrings('product', 'products');
    }


    protected function setupListOperation()
    {
        $show = $this->crud->getCurrentOperation() == 'show';

        if (!$show) {
            $text_align =  [
                'element' => 'div',
                'style'   => 'width: 100%; text-align: right; display: block;',
            ];
        } else {
            $text_align = array();
        }

        $this->crud->addColumns([
            [
                'name'  => 'name',
                'label' => 'Name',
                'type'  => 'custom_html',
                'value' => function ($entry) {
                    return '<a href="' . route('product.show', $entry->id) . '">' . $entry->name . '</a>';
                },
    
            ],
            [
                'name'  => 'description',
                'label' => 'Description',
                'type'  => 'text',
            ],
            // [
            //     'name'  => 'quantity',
            //     'label' => 'Quantity',
            //     'type'  => 'number',
            // ],
            [
                'name'  => 'price',
                'label' => 'Price',
                'type'  => 'text',
                'prefix' => 'RM',
                'value' => function ($entry) {
                    return number_format($entry->price, 2);
                },
                'wrapper' => $text_align
            ],
        ]);
    }


    protected function setupCreateOperation()
    {
        CRUD::setValidation([
            // 'name' => 'required|min:2',
        ]);

        $this->crud->addFields([
            [
                'name'  => 'name',
                'label' => 'Name',
                'type'  => 'text',
            ],
            [
                'name'  => 'description',
                'label' => 'Description',
                'type'  => 'text',
            ],
            // [
            //     'name'  => 'quantity',
            //     'label' => 'Quantity',
            //     'type'  => 'number',
            // ],
            [
                'name'  => 'price',
                'label' => 'Price',
                'type'  => 'number',
                'prefix' => 'RM'
            ],
        ]);
    }

    protected function setupUpdateOperation()
    {
        $this->setupCreateOperation();
    }

    protected function setupShowOperation()
    {
        $this->setupListOperation();
    }

    public function fetch(Request $request)
    {
        $search = $request->get('q');

        $products = Product::query()
            ->when($search, function ($query, $search) {
                return $query->where('name', 'like', "%{$search}%");
            })
            ->limit(20)
            ->get();

        $results = $products->map(function ($product) {
            return [
                'id' => $product->id,
                'name' => $product->name,
                'price' => $product->price,
            ];
        });

        return response()->json([
            'results' => $results,
            'total_count' => $products->count(),
        ]);
    }
}
