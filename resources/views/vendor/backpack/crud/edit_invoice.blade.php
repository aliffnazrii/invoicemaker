@extends(backpack_view('blank'))

@php
  $defaultBreadcrumbs = [
    trans('backpack::crud.admin') => url(config('backpack.base.route_prefix'), 'dashboard'),
    $crud->entity_name_plural => url($crud->route),
    trans('backpack::crud.edit') => false,
  ];

  $breadcrumbs = $breadcrumbs ?? $defaultBreadcrumbs;

  $contact = $entry->contact;
  $clientName = $contact
    ? (trim(($contact->first_name ?? '') . ' ' . ($contact->last_name ?? '')) ?: ($contact->company_name ?? ''))
    : '';
  $invoiceDate = $entry->date instanceof \Carbon\Carbon
    ? $entry->date->format('Y-m-d')
    : \Carbon\Carbon::parse($entry->date)->format('Y-m-d');
  $lineItems = $entry->items->isNotEmpty() ? $entry->items : collect([null]);
  $existingContactData = $contact ? [
    'id' => $contact->id,
    'name' => $clientName,
    'email' => $contact->email,
    'phone' => $contact->phone,
    'address_line_1' => $contact->address_line_1,
    'address_line_2' => $contact->address_line_2,
    'city' => $contact->city,
    'state' => $contact->state,
    'postal_code' => $contact->postal_code,
  ] : null;
@endphp

@section('content')
  <div class="row" bp-section="invoice-edit-form">
    <div class="col-md-12">

      <a href="{{ url($crud->route) }}" class="hidden-print back-btn"><i class="la la-angle-double-left"></i>
        {{ trans('backpack::crud.back_to_all') }} <span>{{ $crud->entity_name_plural }}</span></a>

      <form method="POST" action="{{ url($crud->route . '/' . $entry->getKey()) }}" class="mt-2">
        {!! csrf_field() !!}
        @method('PUT')

        <div class="card">
          <div class="card-header font-weight-bold">
            <i class="la la-file-invoice"></i> &nbsp;Edit Invoice
          </div>

          <div class="card-body">
            <div class="row">
              <div class="form-group col-md-4">
                <label class="font-weight-bold">Invoice Number</label>
                <input type="text" name="invoice_number" class="form-control" value="{{ old('invoice_number', $entry->invoice_number) }}" required>
              </div>
              <div class="form-group col-md-4">
                <label class="font-weight-bold">Date</label>
                <input type="date" name="date" class="form-control" value="{{ old('date', $invoiceDate) }}" required>
              </div>
            </div>

            <div class="row mt-2">
              <div class="form-group col-md-12">
                <label class="font-weight-bold">Select Contact</label>
                <select id="contact-selector" class="form-control" style="width: 100%;" required>
                  <option value="">-- Search for a contact --</option>
                </select>
              </div>
            </div>

            <div id="client-details-wrapper" style="{{ $contact ? 'display: block;' : 'display: none;' }}">
              <div class="row">
                <div class="form-group col-md-6">
                  <label class="font-weight-bold">Client Name</label>
                  <input type="text" name="client_name" id="client_name" class="form-control" value="{{ old('client_name', $clientName) }}">
                </div>
                <div class="form-group col-md-6">
                  <label class="font-weight-bold">Client Email</label>
                  <input type="email" name="client_email" id="client_email" class="form-control" value="{{ old('client_email', $contact?->email ?? '') }}">
                </div>
                <div class="form-group col-md-6">
                  <label class="font-weight-bold">Client Phone</label>
                  <input type="tel" name="client_phone" id="client_phone" class="form-control" value="{{ old('client_phone', $contact?->phone ?? '') }}">
                </div>
                <div class="form-group col-md-6">
                  <label class="font-weight-bold">Address 1</label>
                  <input type="text" name="address_line_1" id="client_address_line_1" class="form-control" value="{{ old('address_line_1', $contact?->address_line_1 ?? '') }}">
                </div>
                <div class="form-group col-md-6">
                  <label class="font-weight-bold">Address 2</label>
                  <input type="text" name="address_line_2" id="client_address_line_2" class="form-control" value="{{ old('address_line_2', $contact?->address_line_2 ?? '') }}">
                </div>
                <div class="form-group col-md-6">
                  <label class="font-weight-bold">City</label>
                  <input type="text" name="city" id="client_city" class="form-control" value="{{ old('city', $contact?->city ?? '') }}">
                </div>
                <div class="form-group col-md-6">
                  <label class="font-weight-bold">State</label>
                  <input type="text" name="state" id="client_state" class="form-control" value="{{ old('state', $contact?->state ?? '') }}">
                </div>
                <div class="form-group col-md-6">
                  <label class="font-weight-bold">Postcode</label>
                  <input type="text" name="postal_code" id="client_postal_code" class="form-control" value="{{ old('postal_code', $contact?->postal_code ?? '') }}">
                </div>
              </div>
            </div>

            <h5 class="mt-4 font-weight-bold text-secondary">Line Items</h5>
            <div class="table-responsive">
              <table class="table table-bordered table-striped mt-2" id="invoice-items-table">
                <thead class="bg-light">
                  <tr>
                    <th style="width: 60%;">Product / Service</th>
                    <th style="width: 10%;">Quantity</th>
                    <th style="width: 10%;">Unit Price (RM)</th>
                    <th style="width: 10%;">Total (RM)</th>
                    <th style="width: 10%;">Actions</th>
                  </tr>
                </thead>
                <tbody id="invoice-items-body">
                  @foreach($lineItems as $index => $item)
                  <tr class="line-item">
                    <td class="product-cell">
                      <select name="items[{{ $index }}][product_id]" class="form-control product-select" style="width: 100%;"
                        required>
                        <option value="">-- Search for a product --</option>
                        @if($item?->product_id)
                          <option value="{{ $item->product_id }}" selected>{{ $item->description }}</option>
                        @endif
                      </select>
                      <input type="hidden" name="items[{{ $index }}][description]" class="item-description-hidden" value="{{ $item?->description ?? '' }}">
                    </td>
                    <td class="qty-cell">
                      <input type="number" name="items[{{ $index }}][quantity]" class="form-control item-qty" value="{{ $item?->quantity ?? 1 }}" min="1"
                        step="any" required>
                    </td>
                    <td class="price-cell">
                      <input type="number" name="items[{{ $index }}][price]" class="form-control item-price" value="{{ number_format($item?->unit_price ?? 0, 2, '.', '') }}" min="0" step="0.01" required>
                    </td>
                    <td class="total-cell">
                      <input type="text" class="form-control item-total" value="{{ number_format(($item?->quantity ?? 0) * ($item?->unit_price ?? 0), 2, '.', '') }}" readonly>
                    </td>
                    <td class="text-center action-cell">
                      <button type="button" class="btn btn-sm btn-danger remove-item-btn" style="{{ $lineItems->count() === 1 ? 'display: none;' : '' }}"><i
                          class="la la-trash"></i></button>
                    </td>
                  </tr>
                  @endforeach
                </tbody>
              </table>
            </div>

            <div class="row mt-2">
              <div class="col-md-12">
                <button type="button" id="add-item-btn" class="btn btn-sm btn-secondary"><i class="la la-plus"></i> Add
                  Line Item</button>
              </div>
            </div>

            <div class="row justify-content-end mt-3">
              <div class="col-md-5">
                <div class="border rounded p-3 bg-light">
                  <div class="d-flex justify-content-between mb-2">
                    <span class="font-weight-bold">Subtotal:</span>
                    <span>RM <span id="invoice-subtotal">0.00</span></span>
                  </div>

                  <div class="d-flex justify-content-between mb-2 align-items-center">
                    <span class="font-weight-bold">Discount:</span>
                    <div class="d-flex align-items-center">
                      <span class="mr-2">RM</span>
                      <input type="number" id="discount-amount" name="discount"
                        class="form-control form-control-sm text-right" style="width: 120px;" value="{{ old('discount', number_format($entry->discount ?? 0, 2, '.', '')) }}" min="0"
                        step="0.01">
                    </div>
                  </div>

                  <div class="d-flex justify-content-between mb-2 align-items-center">
                    <span class="font-weight-bold">Shipping:</span>
                    <div class="d-flex align-items-center">
                      <span class="mr-2">RM</span>
                      <input type="number" id="shipping-amount" name="shipping"
                        class="form-control form-control-sm text-right" style="width: 120px;" value="{{ old('shipping', '0.00') }}" min="0"
                        step="0.01">
                    </div>
                  </div>

                  <hr>

                  <div class="d-flex justify-content-between text-primary h5 font-weight-bold mb-0">
                    <span>Grand Total:</span>
                    <span>RM <span id="invoice-grand-total">0.00</span></span>
                  </div>
                </div>
              </div>
            </div>
            <div class="form-group mt-3">
              <label class="font-weight-bold">Notes / Payment Terms</label>
              <textarea name="notes" class="form-control" rows="2"
                placeholder="Bank account details, payment instructions, or thank you note...">{{ old('notes', $entry->notes) }}</textarea>
            </div>

          </div>
          <div class="card-footer bg-white">
            <button type="submit" class="btn btn-success"><i class="la la-save"></i>&nbsp;Save</button>
          </div>
        </div>
      </form>

    </div>
  </div>
@endsection

@section('after_styles')
  {{-- Include Select2 CSS --}}
  <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
  <link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css"
    rel="stylesheet" />
  <style>
    .select2-container--bootstrap-5 .select2-selection {
      min-height: calc(1.5em + 0.75rem + 2px);
    }

    .line-item {
      vertical-align: middle;
    }

    .product-cell {
      min-width: 250px;
    }

    .qty-cell,
    .price-cell,
    .total-cell {
      width: 120px;
    }

    .action-cell {
      width: 60px;
    }

    .form-control-sm {
      font-size: 0.875rem;
      padding: 0.25rem 0.5rem;
    }

    .mr-2 {
      margin-right: 0.5rem;
    }

    .text-right {
      text-align: right;
    }
  </style>
@endsection

@section('after_scripts')
  {{-- Include Select2 JS --}}
  <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

  <script>
    document.addEventListener('DOMContentLoaded', function () {
      const existingContact = @json($existingContactData);

      const contactAjaxUrl = "{{ backpack_url('api/contacts/search') }}";

      if (existingContact && existingContact.id) {
        const contactLabel = existingContact.email
          ? `${existingContact.name} (${existingContact.email})`
          : existingContact.name;
        const contactOption = new Option(contactLabel, existingContact.id, true, true);
        $('#contact-selector').append(contactOption);
      }

      // Initialize Select2 for contact selector
      $('#contact-selector').select2({
        theme: 'bootstrap-5',
        width: '100%',
        placeholder: '-- Search for a contact --',
        allowClear: true,
        ajax: {
          url: contactAjaxUrl,
          dataType: 'json',
          delay: 250,
          data: function (params) {
            return {
              q: params.term,
              page: params.page || 1
            };
          },
          processResults: function (data, params) {
            params.page = params.page || 1;

            // Add "Add New Contact" option at the beginning of results
            const results = data.results || data.items || data.data || [];

            // Create custom option for adding new contact
            const newContactOption = {
              id: 'new',
              text: '+ Add New Contact',
              isNew: true,
              name: 'Add New Contact'
            };

            return {
              results: [newContactOption, ...results],
              pagination: {
                more: (params.page * 10) < (data.total_count || 0)
              }
            };
          },
          cache: true
        },
        minimumInputLength: 0,
        templateResult: formatContactResult,
        templateSelection: formatContactSelection
      });

      // Format contact results in dropdown
      function formatContactResult(contact) {
        if (contact.loading) {
          return contact.text;
        }

        if (contact.isNew) {
          return $(`<div class="text-primary font-weight-bold">${contact.text}</div>`);
        }

        const name = contact.name || '';
        const email = contact.email || '';
        const phone = contact.phone || '';

        let markup = `<div>`;
        markup += `<strong>${name}</strong>`;
        if (email) markup += `<br><small class="text-muted">${email}</small>`;
        if (phone) markup += ` <small class="text-muted">(${phone})</small>`;
        markup += `</div>`;

        return $(markup);
      }

      // Format selected contact display
      function formatContactSelection(contact) {
        if (!contact.id) return contact.text;

        if (contact.isNew) {
          return 'Add New Contact';
        }

        const name = contact.name || contact.text;
        const email = contact.email ? ` (${contact.email})` : '';
        return `${name}${email}`;
      }

      const detailsWrapper = document.getElementById('client-details-wrapper');
      const clientNameInput = document.getElementById('client_name');
      const clientEmailInput = document.getElementById('client_email');
      const clientPhoneInput = document.getElementById('client_phone');
      // const clientAddressInput = document.getElementById('client_address');
      const clientAddress1 = document.getElementById('client_address_line_1');
      const clientAddress2 = document.getElementById('client_address_line_2');
      const clientAddressCity = document.getElementById('client_city');
      const clientAddressState = document.getElementById('client_state');
      const clientAddressPostalCode = document.getElementById('client_postal_code');

      // Handle contact selection change
      $('#contact-selector').on('change', function (e) {
        const selectedData = $(this).select2('data')[0];

        if (!selectedData) {
          detailsWrapper.style.display = 'none';
          clientNameInput.required = false;
          return;
        }

        if (selectedData.id === 'new') {
          detailsWrapper.style.display = 'block';
          clientNameInput.value = '';
          clientEmailInput.value = '';
          clientPhoneInput.value = '';
          clientAddress1.value = '';
          clientAddress2.value = '';
          clientAddressCity.value = '';
          clientAddressState.value = '';
          clientAddressPostalCode.value = '';
          clientNameInput.required = true;
          clientEmailInput.required = false;
          clientPhoneInput.required = false;
          clientAddress1.required = false;
          clientAddress2.required = false;
          clientAddressCity.required = false;
          clientAddressState.required = false;
          clientAddressPostalCode.required = false;
        } else if (selectedData.id && selectedData.id !== 'new') {
          detailsWrapper.style.display = 'block';
          clientNameInput.value = selectedData.name || selectedData.text || '';
          clientEmailInput.value = selectedData.email || '';
          clientPhoneInput.value = selectedData.phone || '';
          clientNameInput.required = true;
          clientEmailInput.required = false;
          clientPhoneInput.required = false;
          clientAddress1.value = selectedData.address_line_1 || '';
          clientAddress2.value = selectedData.address_line_2 || '';
          clientAddressCity.value = selectedData.city || '';
          clientAddressState.value = selectedData.state || '';
          clientAddressPostalCode.value = selectedData.postal_code || '';
          clientAddress1.required = false;
          clientAddress2.required = false;
          clientAddressCity.required = false;
          clientAddressState.required = false;
          clientAddressPostalCode.required = false;
        } else {
          detailsWrapper.style.display = 'none';
          clientNameInput.required = false;
        }
      });

      const productAjaxUrl = "{{ backpack_url('api/products/search') }}";

      let itemIndex = {{ $lineItems->count() }};

      const discountInput = document.getElementById('discount-amount');
      const shippingInput = document.getElementById('shipping-amount');

      function initProductSelect(selectElement) {
        $(selectElement).select2({
          theme: 'bootstrap-5',
          width: '100%',
          placeholder: '-- Search for a product --',
          allowClear: true,
          ajax: {
            url: productAjaxUrl,
            dataType: 'json',
            delay: 250,
            data: function (params) {
              return {
                q: params.term,
                page: params.page || 1
              };
            },
            processResults: function (data, params) {
              params.page = params.page || 1;
              return {
                results: data.results || data.items || data.data || [],
                pagination: {
                  more: (params.page * 10) < (data.total_count || 0)
                }
              };
            },
            cache: true
          },
          templateResult: formatProductResult,
          templateSelection: formatProductSelection
        });

        $(selectElement).on('select2:select', function (e) {
          const selectedData = e.params.data;
          const row = $(selectElement).closest('tr');

          const priceField = row.find('.item-price');
          const productPrice = selectedData.price || selectedData.unit_price || 0;
          priceField.val(productPrice);

          const hiddenDesc = row.find('.item-description-hidden');
          const productName = selectedData.text || selectedData.name || selectedData.product_name;
          hiddenDesc.val(productName);

          calculateTotals();
        });

        $(selectElement).on('select2:clear', function () {
          const row = $(selectElement).closest('tr');
          row.find('.item-price').val(0);
          row.find('.item-description-hidden').val('');
          calculateTotals();
        });
      }

      function formatProductResult(product) {
        if (product.loading) {
          return product.text;
        }

        const price = product.price || product.unit_price || 0;
        const name = product.text || product.name || product.product_name;

        let markup = `<div class="d-flex justify-content-between">`;
        markup += `<span><strong>${name}</strong></span>`;
        markup += `<span class="text-primary">RM ${parseFloat(price).toFixed(2)}</span>`;
        markup += `</div>`;

        return $(markup);
      }

      function formatProductSelection(product) {
        if (!product.id) return product.text;

        const name = product.text || product.name || product.product_name;
        return `${name}`;
      }

      function calculateTotals() {
        let subtotal = 0;
        const rows = document.querySelectorAll('#invoice-items-body .line-item');

        rows.forEach(row => {
          const qty = parseFloat(row.querySelector('.item-qty').value) || 0;
          const price = parseFloat(row.querySelector('.item-price').value) || 0;
          const total = qty * price;

          const totalField = row.querySelector('.item-total');
          totalField.value = total.toFixed(2);
          subtotal += total;
        });

        const discount = parseFloat(discountInput.value) || 0;
        const shipping = parseFloat(shippingInput.value) || 0;

        const grandTotal = subtotal - discount + shipping;

        const finalGrandTotal = grandTotal < 0 ? 0 : grandTotal;

        document.getElementById('invoice-subtotal').innerText = subtotal.toFixed(2);
        document.getElementById('invoice-grand-total').innerText = finalGrandTotal.toFixed(2);
      }

      function addLineItem() {
        const newRow = document.createElement('tr');
        newRow.className = 'line-item';
        newRow.innerHTML = `
          <td class="product-cell">
            <select name="items[${itemIndex}][product_id]" class="form-control product-select" style="width: 100%;" required>
              <option value="">-- Search for a product --</option>
            </select>
            <input type="hidden" name="items[${itemIndex}][description]" class="item-description-hidden">
          </td>
          <td class="qty-cell">
            <input type="number" name="items[${itemIndex}][quantity]" class="form-control item-qty" value="1" min="1" step="any" required>
          </td>
          <td class="price-cell">
            <input type="number" name="items[${itemIndex}][price]" class="form-control item-price" value="0.00" min="0" step="0.01" required>
          </td>
          <td class="total-cell">
            <input type="text" class="form-control item-total" value="0.00" readonly>
          </td>
          <td class="text-center action-cell">
            <button type="button" class="btn btn-sm btn-danger remove-item-btn"><i class="la la-trash"></i></button>
          </td>
        `;

        document.getElementById('invoice-items-body').appendChild(newRow);

        const newSelect = newRow.querySelector('.product-select');
        initProductSelect(newSelect);

        attachRowEvents(newRow);

        itemIndex++;
        calculateTotals();
        updateRemoveButtons();
      }

      function attachRowEvents(row) {
        const qtyInput = row.querySelector('.item-qty');
        const priceInput = row.querySelector('.item-price');
        const removeBtn = row.querySelector('.remove-item-btn');

        if (qtyInput) qtyInput.addEventListener('input', calculateTotals);
        if (priceInput) priceInput.addEventListener('input', calculateTotals);
        if (removeBtn) {
          removeBtn.addEventListener('click', function () {
            const select2El = $(row.querySelector('.product-select'));
            if (select2El.length && select2El.hasClass('select2-hidden-accessible')) {
              select2El.select2('destroy');
            }
            row.remove();
            calculateTotals();
            reindexItems();
            updateRemoveButtons();
          });
        }
      }

      function reindexItems() {
        const rows = document.querySelectorAll('#invoice-items-body .line-item');
        rows.forEach((row, idx) => {
          const productSelect = row.querySelector('select[name*="[product_id]"]');
          const hiddenDesc = row.querySelector('input[name*="[description]"]');
          const qtyInput = row.querySelector('input[name*="[quantity]"]');
          const priceInput = row.querySelector('input[name*="[price]"]');

          if (productSelect) productSelect.name = `items[${idx}][product_id]`;
          if (hiddenDesc) hiddenDesc.name = `items[${idx}][description]`;
          if (qtyInput) qtyInput.name = `items[${idx}][quantity]`;
          if (priceInput) priceInput.name = `items[${idx}][price]`;
        });
      }

      function updateRemoveButtons() {
        const rows = document.querySelectorAll('#invoice-items-body .line-item');
        const removeBtns = document.querySelectorAll('.remove-item-btn');
        if (rows.length === 1) {
          removeBtns.forEach(btn => btn.style.display = 'none');
        } else {
          removeBtns.forEach(btn => btn.style.display = 'inline-block');
        }
      }

      if (discountInput) {
        discountInput.addEventListener('input', calculateTotals);
      }
      if (shippingInput) {
        shippingInput.addEventListener('input', calculateTotals);
      }

      document.querySelectorAll('#invoice-items-body .product-select').forEach(function (selectElement) {
        initProductSelect(selectElement);
      });

      document.querySelectorAll('#invoice-items-body .line-item').forEach(function (row) {
        attachRowEvents(row);
      });

      const addItemBtn = document.getElementById('add-item-btn');
      addItemBtn.addEventListener('click', addLineItem);

      calculateTotals();
    });
  </script>
@endsection