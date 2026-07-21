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
use App\Support\CompanySettings;

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
        $this->crud->addButtonFromView('line', 'mark_paid_invoice', 'mark_paid_invoice', 'end');
        $this->crud->addButtonFromView('line', 'redownload_invoice', 'redownload_invoice', 'end');


        $show = $this->crud->getCurrentOperation() == 'show';

        if (!$show) {
            $text_align =  [
                'element' => 'div',
                'style'   => 'width: 100%; text-align: right; display: block;',
            ];
        } else {
            $text_align = array();
        }
        CRUD::addColumn([
            'name' => 'invoice_number',
            'type' => 'custom_html',
            'value' => function ($entry) use ($show) {
                if ($show) {
                    return $entry->invoice_number;
                } else {
                    return '<a href="' . route('invoice.show', $entry->id) . '">' . $entry->invoice_number . '</a>';
                }
            },
        ]);

        CRUD::column('contact_id')
            ->type('custom_html')
            ->label('Client Name')
            ->value(function ($entry) {
                return $entry->contact->first_name . ' ' . $entry->contact->last_name;
            });

        CRUD::column('date')->type('date');

        CRUD::addColumn([
            'name' => 'subtotal',
            'type' => 'text',
            'prefix' => 'RM',
            'value' => function ($entry) {
                return number_format($entry->subtotal, 2);
            },
            'wrapper' => $text_align
        ]);
        CRUD::addColumn([
            'name' => 'discount',
            'type' => 'text',
            'prefix' => 'RM',
            'value' => function ($entry) {
                return number_format($entry->discount, 2);
            },
            'wrapper' => $text_align
        ]);
        CRUD::addColumn([
            'name' => 'total',
            'type' => 'text',
            'prefix' => 'RM',
            'value' => function ($entry) {
                return number_format($entry->total, 2);
            },
            'wrapper' => $text_align
        ]);

        CRUD::column('is_paid')
            ->type('custom_html')
            ->label('Status')
            ->value(function ($entry) {
                return $entry->is_paid
                    ? '<span class="badge bg-success text-white">Paid</span>'
                    : '<span class="badge bg-warning text-white">Unpaid</span>';
            });
    }
    protected function setupCreateOperation()
    {
        $this->crud->setCreateView('crud::invoice_maker');
    }

    protected function setupUpdateOperation()
    {
        $this->crud->setEditView('crud::edit_invoice');
        $this->crud->with(['contact', 'items.product']);
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
                $price = $value['price'] ?? floatval($product->price ?? 0);
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
        $grandTotal = ($subtotal - $request->discount) + $taxAmount;

        $data = array_merge(CompanySettings::forInvoice(), [
            'invoice_number' => $request->invoice_number,
            'date'       => date('Y-m-d', strtotime($request->date)),
            'billing_notes'  => $request->notes,
            'items'          => $items,
            'subtotal'       => $subtotal,
            'grand_total'    => $grandTotal,
            'client_name'       => $request->client_name,
            'client_address'    => $request->client_address,
            'client_email'    => $request->client_email,
            'client_phone'    => $request->client_phone,
            'discount'    => $request->discount,
            'is_paid'     => 0,
        ]);


        $invoice = Invoice::firstOrCreate([
            'invoice_number' => $request->invoice_number,
            'contact_id' => $contact->id,
            'date' => date('Y-m-d H:i:s', strtotime($request->date)),
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

        // return $pdf->download('invoice-' . $request->invoice_number . '.pdf');
        return redirect()->route('invoice.index')->with('success', 'Invoice created successfully.');
    }

    public function update($id)
    {
        $this->crud->hasAccessOrFail('update');

        $request = request();

        $request->validate([
            'invoice_number' => 'required|string|unique:invoices,invoice_number,' . $id,
            'date'           => 'required|date',
            'client_name'    => 'required',
            'items'            => 'required|array',
        ]);

        $invoice = Invoice::with('items')->findOrFail($id);

        $contact = Contact::firstOrCreate([
            'phone' => $request->client_phone,
        ], [
            'first_name'     => $request->client_name,
            'company_name'   => $request->client_name,
            'email'          => $request->client_email,
            'phone'          => $request->client_phone,
            'address_line_1' => $request->address_line_1,
            'address_line_2' => $request->address_line_2,
            'city'           => $request->city,
            'postal_code'    => $request->postal_code,
            'state'          => $request->state,
        ]);

        $items = [];
        $subtotal = 0;

        foreach ($request->items as $value) {
            $product = Product::findOrFail((int) $value['product_id']);

            $qty = floatval($value['quantity'] ?? 1);
            $price = floatval($value['price'] ?? $product->price ?? 0);
            $lineTotal = $qty * $price;

            $subtotal += $lineTotal;

            $items[] = [
                'description' => $value['description'],
                'quantity'    => $qty,
                'price'       => $price,
                'total'       => $lineTotal,
            ];
        }

        $taxPercent = floatval($request->tax_percent ?? 0);
        $taxAmount = $subtotal * ($taxPercent / 100);
        $grandTotal = $subtotal - floatval($request->discount ?? 0) + floatval($request->shipping ?? 0) + $taxAmount;

        $invoice->update([
            'invoice_number' => $request->invoice_number,
            'contact_id'     => $contact->id,
            'date'           => $request->date,
            'subtotal'       => $subtotal,
            'discount'       => $request->discount ?? 0,
            'total'          => $grandTotal,
            'notes'          => $request->notes,
        ]);

        $invoice->items()->delete();

        foreach ($request->items as $item) {
            $product = Product::findOrFail((int) $item['product_id']);

            InvoiceItem::create([
                'invoice_id'  => $invoice->id,
                'product_id'  => $product->id,
                'description' => $item['description'],
                'quantity'    => $item['quantity'],
                'unit_price'  => floatval($item['price'] ?? $product->price),
                'subtotal'    => $item['quantity'] * floatval($item['price'] ?? $product->price),
                'total'       => $item['quantity'] * floatval($item['price'] ?? $product->price),
            ]);
        }

        // $data = [
        //     'company_name'           => config('settings.company_name'),
        //     'company_extras'         => config('settings.company_extras'),
        //     'company_address_line_1' => config('settings.company_address_line_1'),
        //     'company_address_line_2' => config('settings.company_address_line_2'),
        //     'company_postal_code'    => config('settings.company_postal_code'),
        //     'company_city'           => config('settings.company_city'),
        //     'company_state'          => config('settings.company_state'),
        //     'company_phone'          => config('settings.company_phone'),
        //     'invoice_number'  => $request->invoice_number,
        //     'date'            => $request->date,
        //     'billing_notes'   => $request->notes,
        //     'items'           => $items,
        //     'subtotal'        => $subtotal,
        //     'grand_total'     => $grandTotal,
        //     'client_name'     => $request->client_name,
        //     'client_address'  => $request->client_address ?? '',
        //     'client_email'    => $request->client_email,
        //     'client_phone'    => $request->client_phone,
        //     'discount'        => $request->discount,
        // ];

        // $pdf = Pdf::loadView('admin.invoice', $data);
        // $pdf->setPaper('A4', 'portrait');
        // return $pdf->download('invoice-' . $request->invoice_number . '.pdf');
        return redirect()->route('invoice.index')->with('success', 'Invoice updated successfully.');
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

        $data = array_merge(CompanySettings::forInvoice(), [
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
            'is_paid'     => $invoice->is_paid,
        ]);

        if (config('settings.allow_client_address')) {
            $data['client_address'] = $contact->address_line_1 . ', ' . $contact->address_line_2 . ', ' . $contact->city . ', ' . $contact->postal_code . ', ' . $contact->state;
        }

        $pdf = Pdf::loadView('admin.invoice', $data);

        $pdf->setPaper('A4', 'portrait');

        return $pdf->download('invoice-' . $invoice->invoice_number . '.pdf');
    }

    public function markPaid($id)
    {
        $this->crud->hasAccessOrFail('update');

        $invoice = Invoice::findOrFail($id);

        if ($invoice->is_paid) {
            \Alert::info('This invoice is already marked as paid.')->flash();

            return redirect()->back();
        }

        $invoice->update([
            'is_paid' => 1,
            'paid_at' => now(),
        ]);

        \Alert::success('Invoice marked as paid. Downloads will now show as Receipt.')->flash();

        return redirect()->back();
    }
}
