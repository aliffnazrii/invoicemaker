<?php

namespace App\Http\Controllers\Admin;

use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use App\Models\Invoice;
use App\Models\Contact;
use App\Models\InvoiceItem;

/**
 * Class InvoiceCrudController
 * @package App\Http\Controllers\Admin
 * @property-read \Backpack\CRUD\app\Library\CrudPanel\CrudPanel $crud
 */
class InvoiceCrudController extends CrudController
{
    use \Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\CreateOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\UpdateOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\DeleteOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\ShowOperation;


    public function setup()
    {
        CRUD::setModel(\App\Models\invoice::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/invoice');
        CRUD::setEntityNameStrings('Invoice', 'Invoices');
    }


    protected function setupListOperation() {}

    protected function setupCreateOperation()
    {

        $this->crud->setCreateView('crud::invoice_maker');
    }

    protected function setupUpdateOperation()
    {
        $this->setupCreateOperation();
    }

    public function invoice(Request $request)
    {
        $request->validate([
            'invoice_number'    => 'required|string',
            'date'              => 'required|date',
            'client_name'       => 'required',
            'items'             => 'required|array',
        ]);

        $contact = Contact::firstOrCreate([
            'client_phone' => $request->phone
        ], [
            'first_name' => $request->client_name,
            'company_name' => $request->client_name,
            'email' =>  $request->client_email,
            'phone' =>  $request->client_phone
        ]);

        $items = [];
        $subtotal = 0;

        if (isset($request->items) && is_array($request->items)) {
            foreach ($request->items as $key => $value) {
                $qty = floatval($value['quantity'] ?? 1);
                $price = floatval($value['price'] ?? 0);
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

        $invoice = Invoice::create([
            'invoice_number' => $request->invoice_number,
            'contact_id' => $contact->id,
            'date' => date('Y-m-d H:i:s'),
            'subtotal' => $subtotal,
            'discount' => $request->discount,
            'total' => $grandTotal,
            'notes' => $request->notes,
        ]);

        foreach ($request->items as $item) {
            InvoiceItem::create([
                'invoice_id' => $invoice->id,
                'product_id' => $item->quantity,
                'description' => $item->quantity,
                'quantity' => $item->quantity,
                'unit_price' => $item->price,
                'quantity' => $item->quantity,
                'subtotal' => $item->quantity * $item->price,
                'total' => $item->quantity * $item->price,
            ]);
        }

        $pdf = Pdf::loadView('admin.invoice', $data);

        $pdf->setPaper('A4', 'portrait');

        return $pdf->download('invoice-' . $request->invoice_number . '.pdf');
    }
}
