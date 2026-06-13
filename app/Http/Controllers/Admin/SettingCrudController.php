<?php

namespace App\Http\Controllers\Admin;

use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;

use App\Http\Controllers\Admin\Traits\TraitPhone;
use App\Support\CompanySettings;
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
                'default' => $savedSettings['company_name'] ?? '',
                'tab' => 'Address'
            ],
            [
                'name'         => 'company_logo',
                'label'        => 'Company Logo',
                'type'         => 'company_logo',
                'crop_width'   => CompanySettings::LOGO_WIDTH,
                'crop_height'  => CompanySettings::LOGO_HEIGHT,
                'default'      => $savedSettings['company_logo'] ?? '',
                'tab'          => 'Address',
                'hint'         => 'Upload and crop your logo. It will be saved at ' . CompanySettings::LOGO_WIDTH . '×' . CompanySettings::LOGO_HEIGHT . ' pixels for use on invoices.',
            ],
            [
                'name'    => 'company_extras',
                'label'   => 'Company Extras',
                'type'    => 'text',
                'default' => $savedSettings['company_extras'] ?? '',
                'tab' => 'Address'
            ],
            [
                'name'    => 'company_address_line_1',
                'label'   => 'Address Line 1',
                'type'    => 'textarea',
                'default' => $savedSettings['company_address_line_1'] ?? '',
                'tab' => 'Address'
            ],
            [
                'name'    => 'company_address_line_2',
                'label'   => 'Address Line 2',
                'type'    => 'textarea',
                'default' => $savedSettings['company_address_line_2'] ?? '',
                'tab' => 'Address'
            ],
            [
                'name'    => 'company_postal_code',
                'label'   => 'Postcode',
                'type'    => 'text',
                'default' => $savedSettings['company_postal_code'] ?? '',
                'tab' => 'Address'
            ],
            [
                'name'    => 'company_city',
                'label'   => 'City',
                'type'    => 'text',
                'default' => $savedSettings['company_city'] ?? '',
                'tab' => 'Address'
            ],
            [
                'name'    => 'company_state',
                'label'   => 'State',
                'type'    => 'text',
                'default' => $savedSettings['company_state'] ?? '',
                'tab' => 'Address'
            ],
            [
                'name'    => 'company_phone',
                'label'   => 'Company Phone',
                'type'    => 'phone',
                'default' => $savedSettings['company_phone'] ?? '',
                'tab' => 'Address',
                'prefix' => '+60'
            ],
            [
                'name'    => 'allow_client_address',
                'label'   => 'Show Client Address',
                'type'    => 'select_from_array',
                'options'  => [
                    1 => 'Yes',
                    0 => 'No',
                ],
                'default' => $savedSettings['allow_client_address'] ?? '',
                'tab' => 'Client',
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

        $this->handleCompanyLogo($request);

        $skipKeys = ['company_logo', 'company_logo_cropped', 'company_logo_clear'];

        foreach ($requestData as $key => $value) {
            if (str_starts_with($key, '_') || in_array($key, $skipKeys, true)) {
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

    private function handleCompanyLogo(Request $request): void
    {
        if ($request->input('company_logo_clear') === '1') {
            CompanySettings::deleteLogo();

            Setting::updateOrCreate(
                ['key' => 'company_logo'],
                ['name' => 'Company Logo', 'value' => '', 'type' => 'image']
            );

            return;
        }

        if (!$request->filled('company_logo_cropped')) {
            return;
        }

        $path = CompanySettings::saveLogoFromBase64($request->input('company_logo_cropped'));

        Setting::updateOrCreate(
            ['key' => 'company_logo'],
            ['name' => 'Company Logo', 'value' => $path, 'type' => 'image']
        );
    }
}
