<?php

namespace App\Http\Controllers\Admin;

use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use App\Models\Invoice;
use App\Models\Contact;
use App\Models\InvoiceItem;
use App\Models\Product;

/**
 * Class InvoiceCrudController
 * @package App\Http\Controllers\Admin
 * @property-read \Backpack\CRUD\app\Library\CrudPanel\CrudPanel $crud
 */
class InvoiceCrudController extends CrudController
{
    use \Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\CreateOperation;
    // use \Backpack\CRUD\app\Http\Controllers\Operations\UpdateOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\DeleteOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\ShowOperation;

    public function setup()
    {
        CRUD::setModel(\App\Models\invoice::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/invoice');
        CRUD::setEntityNameStrings('Invoice', 'Invoices');
    }

    protected function setupListOperation()
    {

        $this->crud->addButtonFromView('line', 'redownload_invoice', 'redownload_invoice', 'end');

        CRUD::column('invoice_number')->type('text')->label('Invoice');

        CRUD::column('contact_id')
            ->type('custom_html')
            ->label('Client Name')
            ->value(function ($entry) {
                return $entry->contact->first_name . ' ' . $entry->contact->last_name;
            });

        CRUD::column('date')->type('date');
        CRUD::column('subtotal')->type('number')->prefix('RM');
        CRUD::column('discount')->type('number')->prefix('RM');

        CRUD::column('total')
            ->type('number')
            ->prefix('RM');
    }
    protected function setupCreateOperation()
    {
        $this->crud->setCreateView('crud::invoice_maker');
    }

    protected function setupUpdateOperation()
    {
        $this->setupCreateOperation();
    }

    protected function setupShowOperation()
    {

        $this->setupListOperation();

        $this->crud->addColumn([
            'name'         => 'contact',
            'type'         => 'relationship',
            'label'        => 'Billing Address',
            'attribute'    => 'address_line_1',
            'model'        => "App\Models\Contact",
            'value'        => function ($entry) {
                if (!$entry->contact) {
                    return '-';
                }
                $addressParts = array_filter([
                    $entry->contact->address_line_1,
                    $entry->contact->address_line_2,
                    $entry->contact->city,
                    $entry->contact->state,
                    $entry->contact->postal_code,
                ]);
                return implode(', ', $addressParts);
            }
        ]);

        $this->crud->with(['contact', 'items']);
        $this->crud->setShowView('crud::show_invoice');
    }


    public function invoice(Request $request)
    {
        $request->validate([
            'invoice_number'    => 'required|string|unique:invoices,invoice_number',
            'date'              => 'required|date',
            'client_name'       => 'required',
            'items'             => 'required|array',
        ]);

        $contact = Contact::firstOrCreate([
            'phone' => $request->client_phone
        ], [
            'first_name' => $request->client_name,
            'company_name' => $request->client_name,
            'email' => $request->client_email,
            'phone' => $request->client_phone,
            'address_line_1' => $request->address_line_1,
            'address_line_2' => $request->address_line_2,
            'city' => $request->city,
            'postal_code' => $request->postal_code,
            'state' => $request->state,
        ]);

        $items = [];
        $subtotal = 0;

        if (isset($request->items) && is_array($request->items)) {
            foreach ($request->items as $key => $value) {

                $product_id = (int)$value['product_id'];
                $product = Product::findOrFail($product_id);
    
                $qty = floatval($value['quantity'] ?? 1);
                $price = floatval($product->price ?? 0);
                $lineTotal = $qty * $price;

                $subtotal += $lineTotal;

                $items[] = [
                    'description' => $value['description'],
                    'quantity'    => $qty,
                    'price'       => $price,
                    'total'       => $lineTotal
                ];
            }
        }

        $taxPercent = floatval($request->tax_percent ?? 0);
        $taxAmount = $subtotal * ($taxPercent / 100);
        $grandTotal = $subtotal + $taxAmount;

        $data = [
            'company_name'   => config('settings.company_name'),
            'company_extras'   => config('settings.company_extras'),
            'company_address'   => config('settings.company_address'),
            'company_phone'   => config('settings.company_phone'),
            'invoice_number' => $request->invoice_number,
            'date'       => $request->date,
            'billing_notes'  => $request->notes,
            'items'          => $items,
            'subtotal'       => $subtotal,
            'grand_total'    => $grandTotal,
            'client_name'       => $request->client_name,
            'client_address'    => $request->client_address,
            'client_email'    => $request->client_email,
            'client_phone'    => $request->client_phone,
            'discount'    => $request->discount,
        ];

        $invoice = Invoice::firstOrCreate([
            'invoice_number' => $request->invoice_number,
            'contact_id' => $contact->id,
            'date' => date('Y-m-d H:i:s'),
            'subtotal' => $subtotal,
            'discount' => $request->discount,
            'total' => $grandTotal,
            'notes' => $request->notes,
        ]);

        foreach ($request->items as $item) {

            $product_id = (int)$item['product_id'];

            $product = Product::findOrFail($product_id);

            InvoiceItem::create([
                'invoice_id' => $invoice->id,
                'product_id' => $product_id,
                'description' => $item['description'],
                'quantity' => $item['quantity'],
                'unit_price' => $product->price,
                'quantity' => $item['quantity'],
                'subtotal' => $item['quantity'] * (float)$product->price,
                'total' => $item['quantity'] *  (float)$product->price,
            ]);
        }

        $pdf = Pdf::loadView('admin.invoice', $data);

        $pdf->setPaper('A4', 'portrait');

        return $pdf->download('invoice-' . $request->invoice_number . '.pdf');
    }

    public function redownload(Request $request)
    {
        $invoice_id = $request->id;

        $invoice = Invoice::with('items')->findOrFail($invoice_id);

        $items = [];
        $subtotal = 0;

        if ($invoice->items) {
            foreach ($invoice->items as $value) {
                $qty = $value->quantity ?? 1;
                $price = $value->unit_price ?? 0;
                $lineTotal = $qty * $price;

                $subtotal += $lineTotal;

                $items[] = [
                    'description' => $value->description,
                    'quantity'    => $qty,
                    'price'       => $price,
                    'total'       => $lineTotal
                ];
            }
        }

        $taxPercent = floatval($invoice->tax_total ?? 0);
        $taxAmount = $subtotal * ($taxPercent / 100);
        $grandTotal = $subtotal + $taxAmount;

        $contact = $invoice->contact()->first();

        $data = [
            'company_name'   => config('settings.company_name'),
            'company_extras'   => config('settings.company_extras'),
            'company_address'   => config('settings.company_address'),
            'company_phone'   => config('settings.company_phone'),
            'invoice_number' => $invoice->invoice_number,
            'date'       => $invoice->date,
            'billing_notes'  => $invoice->notes,
            'items'          => $items,
            'subtotal'       => $subtotal,
            'grand_total'    => $grandTotal,
            'client_name'       => $contact->first_name . ' ' . $contact->last_name,
            'client_email'    => $contact->email,
            'client_address' => '',
            'client_phone'    => $contact->phone,
            'discount'    => $invoice->discount,
        ];

        if (config('settings.allow_client_address')) {
            $data['client_address'] = $contact->address_line_1 . ', ' . $contact->address_line_2 . ', ' . $contact->city . ', ' . $contact->postal_code . ', ' . $contact->state;
        }

        $pdf = Pdf::loadView('admin.invoice', $data);

        $pdf->setPaper('A4', 'portrait');

        return $pdf->download('invoice-' . $invoice->invoice_number . '.pdf');
    }
}
