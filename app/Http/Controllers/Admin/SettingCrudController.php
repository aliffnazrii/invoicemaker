<?php

namespace App\Http\Controllers\Admin;

use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;

use App\Http\Controllers\Admin\Traits\TraitPhone;
use Illuminate\Http\Request;
use App\Models\Setting;


/**
 * Class SettingCrudController
 * @package App\Http\Controllers\Admin
 * @property-read \Backpack\CRUD\app\Library\CrudPanel\CrudPanel $crud
 */
class SettingCrudController extends CrudController
{
    use \Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\CreateOperation {
        store as traitStore;
    }
    use \Backpack\CRUD\app\Http\Controllers\Operations\UpdateOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\DeleteOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\ShowOperation;

    use TraitPhone;

    /**
     * Configure the CrudPanel object. Apply settings to all operations.
     * 
     * @return void
     */
    public function setup()
    {
        CRUD::setModel(\App\Models\Setting::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/setting');
        CRUD::setEntityNameStrings('setting', 'settings');
    }

    /**
     * Define what happens when the List operation is loaded.
     * 
     * @see  https://backpackforlaravel.com/docs/crud-operation-list-entries
     * @return void
     */
    protected function setupListOperation()
    {
        $createUrl = backpack_url('setting/create');

        \Illuminate\Support\Facades\Redirect::to($createUrl)->send();
        exit;
    }

    /**
     * Define what happens when the Create operation is loaded.
     * 
     * @see https://backpackforlaravel.com/docs/crud-operation-create
     * @return void
     */
    protected function setupCreateOperation()
    {
        $savedSettings = Setting::pluck('value', 'key')->toArray();

        $this->crud->addFields([
            [
                'name'    => 'company_name',
                'label'   => 'Company Name',
                'type'    => 'text',
                // 2. Use the saved value if it exists, otherwise leave it empty
                'default' => $savedSettings['company_name'] ?? '',
                'tab' => 'Address'
            ],
            [
                'name'    => 'company_address',
                'label'   => 'Company Address',
                'type'    => 'textarea',
                'default' => $savedSettings['company_address'] ?? '',
                'tab' => 'Address'
            ],
            [
                'name'    => 'company_phone',
                'label'   => 'Company Phone',
                'type'    => 'phone',
                'default' => $savedSettings['company_phone'] ? substr($savedSettings['company_phone'], 2) : '',
                'tab' => 'Address',
                'prefix' => '+60'
            ],
        ]);
    }

    protected function setupUpdateOperation()
    {
        $this->setupCreateOperation();
    }

    public function store(Request $request)
    {
        $this->crud->hasAccessOrFail('create');

        $requestData = $this->crud->getStrippedSaveRequest($request);

        $requestData['company_phone'] = $this->handlePhone2($requestData['company_phone']);

        foreach ($requestData as $key => $value) {
            // Skips backpack control fields like '_token' or '_save_action'
            if (str_starts_with($key, '_')) {
                continue;
            }

            // Automatically updates if 'key' exists, or creates if it does not
            Setting::updateOrCreate(
                ['key' => $key], // Match condition
                [
                    'name'  => ucwords(str_replace('_', ' ', $key)), // Prettifies 'company_name' to 'Company Name'
                    'value' => $value,
                    'type'  => 'text'
                ]
            );
        }

        \Alert::success(trans('backpack::crud.update_success'))->flash();

        $this->crud->setSaveAction();
        return redirect(backpack_url('setting'));
    }
}
