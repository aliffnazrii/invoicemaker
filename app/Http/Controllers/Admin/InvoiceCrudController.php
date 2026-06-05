<?php

namespace App\Http\Controllers\Admin;

use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

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


    protected function setupListOperation()
    {

    }

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
        // dd($request->all());

        // 1. Validate form incoming data structure safely
        $request->validate([
            'invoice_number'    => 'required|string',
            'date'              => 'required|date',
            'client_name'       => 'required',
            'items'             => 'required|array',
        ]);

        // 2. Format structure arrays cleanly for the PDF data parser loop
        $items = [];
        $subtotal = 0;

        // Transform input columns into organized items array safely
        if (isset($request->items) && is_array($request->items)) {
            foreach ($request->items as $key => $value) {
                // Fixed typo: Ensure both variables use '$'
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

        // 3. Bind properties into payload variable arrays
        $data = [
            'company_name'   => 'AliffTech Solution',
            'company_address'   => '55 Jalan Kubah U8/59, Bukit Jelutong, 40150, Shah Alam Selangor',
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

        // 4. Generate the PDF view asset layout configuration
        $pdf = Pdf::loadView('admin.invoice', $data);

        // Set layout parameters for safety boundary options
        $pdf->setPaper('A4', 'portrait');

        // 5. Instantly force download back to client web streams
        return $pdf->download('invoice-' . $request->invoice_number . '.pdf');

        // \Alert::flash('success');
    }
}
