<?php

namespace App\Http\Controllers\Admin;

use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;

use App\Http\Controllers\Admin\Traits\TraitPhone;
use Illuminate\Http\Request;
use app\Models\Contact;

/**
 * Class ContactCrudController
 * @package App\Http\Controllers\Admin
 * @property-read \Backpack\CRUD\app\Library\CrudPanel\CrudPanel $crud
 */
class ContactCrudController extends CrudController
{
    use \Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\CreateOperation {
        store as traitStore;
    }
    use \Backpack\CRUD\app\Http\Controllers\Operations\UpdateOperation {
        update as traitUpdate;
    }
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
        CRUD::setModel(\App\Models\Contact::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/contact');
        CRUD::setEntityNameStrings('contact', 'contacts');
    }

    /**
     * Define what happens when the List operation is loaded.
     * 
     * @see  https://backpackforlaravel.com/docs/crud-operation-list-entries
     * @return void
     */
    protected function setupListOperation()
    {
        CRUD::addColumn([
            'name'  => 'first_name',
            'label' => 'Name',
            'type'  => 'text',
            // Custom search logic so typing in the search box checks both first and last name
            'searchLogic' => function ($query, $column, $searchTerm) {
                $query->orWhere('first_name', 'like', '%' . $searchTerm . '%')
                    ->orWhere('last_name', 'like', '%' . $searchTerm . '%');
            }
        ]);

        // 2. Display Company Name
        CRUD::addColumn([
            'name'  => 'company_name',
            'label' => 'Company',
            'type'  => 'text',
        ]);

        // 3. Display Email Address
        CRUD::addColumn([
            'name'  => 'email',
            'label' => 'Email Address',
            'type'  => 'email',
        ]);

        // 4. Display Phone (Using your custom phone prefix field pattern if needed)
        // If storing separate columns, you can display the main number here
        CRUD::addColumn([
            'name'  => 'phone',
            'label' => 'Phone',
            'type'  => 'text',
        ]);

        // 5. Display City & State combined
        CRUD::addColumn([
            'name'  => 'city',
            'label' => 'Location',
            'type'  => 'text',
            'value' => function ($entry) {
                return $entry->city && $entry->state ? "{$entry->city}, {$entry->state}" : ($entry->city ?? $entry->state ?? '-');
            }
        ]);
    }

    protected function setupCreateOperation($update = false)
    {
        CRUD::setValidation();

        // Row 1: First Name & Last Name (Side by side)
        CRUD::addField([
            'name'    => 'first_name',
            'label'   => 'First Name',
            'type'    => 'text',
            'wrapper' => ['class' => 'form-group col-md-6'],
        ]);

        CRUD::addField([
            'name'    => 'last_name',
            'label'   => 'Last Name',
            'type'    => 'text',
            'wrapper' => ['class' => 'form-group col-md-6'],
        ]);

        // Row 2: Company Name (Full width)
        CRUD::addField([
            'name'    => 'company_name',
            'label'   => 'Company Name',
            'type'    => 'text',
        ]);

        // Row 3: Email Address & Custom Phone Field (Side by side)
        CRUD::addField([
            'name'    => 'email',
            'label'   => 'Email Address',
            'type'    => 'email',
            'wrapper' => ['class' => 'form-group col-md-6'],
        ]);

        if ($update) {
            $entry = $this->crud->getCurrentEntry();
            CRUD::addField([
                'name'    => 'phone',
                'label'   => 'Phone Number',
                'type'    => 'phone', // Uses your custom Backpack v7 phone template!
                'value'   => $entry->phone ? substr($entry->phone, 2) : '',
                'wrapper' => ['class' => 'form-group col-md-6'],
            ]);
        } else {

            CRUD::addField([
                'name'    => 'phone',
                'label'   => 'Phone Number',
                'type'    => 'phone', // Uses your custom Backpack v7 phone template!
                'wrapper' => ['class' => 'form-group col-md-6'],
            ]);
        }

        // Row 4: Address Details
        CRUD::addField([
            'name'    => 'address_line_1',
            'label'   => 'Address Line 1',
            'type'    => 'text',
        ]);

        CRUD::addField([
            'name'    => 'address_line_2',
            'label'   => 'Address Line 2 (Optional)',
            'type'    => 'text',
        ]);

        // Row 5: City, State, Postal Code (3 columns side-by-side)
        CRUD::addField([
            'name'    => 'city',
            'label'   => 'City',
            'type'    => 'text',
            'wrapper' => ['class' => 'form-group col-md-4'],
        ]);

        CRUD::addField([
            'name'    => 'state',
            'label'   => 'State / Region',
            'type'    => 'text',
            'wrapper' => ['class' => 'form-group col-md-4'],
        ]);

        CRUD::addField([
            'name'    => 'postal_code',
            'label'   => 'Postal / ZIP Code',
            'type'    => 'text',
            'wrapper' => ['class' => 'form-group col-md-4'],
        ]);
    }

    protected function setupUpdateOperation()
    {
        $this->setupCreateOperation(true);
    }

    public function store(Request $request, $update = false)
    {

        // $entry = $this->crud->getStrippedSaveRequest($request);
        $entry = $this->crud->getRequest();
        $entry->request->set('phone', $this->handlePhone2($entry->phone));

        if ($update) {

            return $this->traitUpdate();
        }

        return  $this->traitStore();
    }

    public function update(Request $request)
    {
        return $this->store($request, true);
    }

    public function fetch(Request $request)
    {
        $search = $request->get('q');

        $contacts = Contact::query()
            ->when($search, function ($query, $search) {
                return $query->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            })
            ->limit(20)
            ->get();

        $results = $contacts->map(function ($contact) {

            return [
                'id' => $contact->id,
                'name' => $contact->first_name . ' ' . $contact->last_name,
                'email' => $contact->email,
                'phone' => $contact->phone,
                'address' => $contact->address_line_1 . ', ' . $contact->address_line_2 . ', ' . $contact->city . ', ' . $contact->postal_code . ', ' . $contact->state,
            ];
        });

        return response()->json([
            'results' => $results,
            'total_count' => $contacts->count(),
        ]);
    }
}
