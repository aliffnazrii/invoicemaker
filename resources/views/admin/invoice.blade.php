<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Invoice - {{ $invoice_number }}</title>
    <style>
        /* CSS reset optimized explicitly for DomPDF parser core engines */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
        }

        body {
            padding: 40px;
            font-size: 14px;
            color: #333;
            line-height: 1.5;
            background-color: #fff;
        }

        .invoice-header-table {
            width: 100%;
            margin-bottom: 40px;
            border-bottom: 2px solid #e0e6ed;
            padding-bottom: 20px;
        }

        .company-name {
            font-size: 28px;
            color: #2c3e50;
            font-weight: bold;
        }

        .invoice-title {
            font-size: 32px;
            color: #2c3e50;
            text-align: right;
            font-weight: bold;
        }

        .details-table {
            width: 100%;
            margin-bottom: 40px;
        }

        .details-table td {
            width: 50%;
            vertical-align: top;
        }

        .section-title {
            font-size: 14px;
            color: #7f8c8d;
            text-transform: uppercase;
            font-weight: bold;
            margin-bottom: 5px;
        }

        .meta-text {
            color: #34495e;
            margin-bottom: 4px;
        }

        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
        }

        .items-table th {
            background-color: #f8f9fa;
            color: #2c3e50;
            font-weight: bold;
            text-align: left;
            padding: 12px;
            border-bottom: 2px solid #eaeaea;
        }

        .items-table td {
            padding: 12px;
            border-bottom: 1px solid #eaeaea;
            vertical-align: middle;
        }

        .text-right {
            text-align: right !important;
        }

        .totals-wrapper {
            width: 100%;
            margin-top: 20px;
        }

        .totals-table {
            width: 40%;
            float: right;
            border-collapse: collapse;
        }

        .totals-table td {
            padding: 8px 12px;
        }

        .total-label {
            font-weight: 600;
            color: #34495e;
        }

        .grand-total-row td {
            font-size: 18px;
            color: #2c3e50;
            font-weight: bold;
            border-top: 2px solid #eaeaea;
            padding-top: 10px;
        }

        .invoice-footer {
            margin-top: 60px;
            clear: both;
            padding-top: 20px;
            border-top: 1px solid #eaeaea;
            text-align: center;
            color: #7f8c8d;
            font-style: italic;
        }

        .notes-box {
            margin-top: 40px;
            clear: both;
            padding: 15px;
            background-color: #f8f9fa;
            border-left: 4px solid #3498db;
        }
    </style>
</head>

<body>

    <!-- Header Block -->
    <table class="invoice-header-table">
        <tr>
            <td>
                <div class="company-name">{{ $company_name }}</div>
                <p style="color: #7f8c8d;">Computers & Website</p>
                @php
                $addr = explode(', ',$company_address);
                @endphp
                <p>{{ $addr[0] }}</p>
                <p>{{ $addr[1] . ', ' . $addr[2] }}</p>
                <p>{{ $addr[3] }}</p>
            </td>
            <td class="text-right">
                <div class="invoice-title">INVOICE/RECEIPT</div>
                <p class="meta-text"><strong>No:</strong> {{ $invoice_number }}</p>
            </td>
        </tr>
    </table>

    <!-- Meta Information Information Section -->
    <table class="details-table">
        <tr>
            <td>
                <div class="section-title">Billed To</div>
                <div class="meta-text">{{ $client_name }}</div>
                <div class="meta-text">{{ $client_email }}</div>
                <div class="meta-text">{{ $client_phone }}</div>
                <div class="meta-text">{{ $client_address }}</div>
            </td>
            <td class="text-right">
                <div class="section-title">Date</div>
                <div class="meta-text"><strong>Date:</strong> {{ date('d M Y', strtotime($date)) }}</div>
            </td>
        </tr>
    </table>

    <!-- Line Item Grid List -->
    <table class="items-table">
        <thead>
            <tr>
                <th style="width: 50%;">Item / Description</th>
                <th style="width: 15%;" class="text-right">Qty</th>
                <th style="width: 15%;" class="text-right">Unit Price</th>
                <th style="width: 20%;" class="text-right">Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach($items as $item)
            <tr>
                <td>{{ $item['description'] }}</td>
                <td class="text-right">{{ $item['quantity'] }}</td>
                <td class="text-right">RM{{ number_format($item['price'], 2) }}</td>
                <td class="text-right">RM{{ number_format($item['total'], 2) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <!-- Subtotals Calculation Column Wrapper -->
    <div class="totals-wrapper">
        <table class="totals-table">
            <tr>
                <td class="total-label">Subtotal:</td>
                <td class="text-right">RM{{ number_format($subtotal, 2) }}</td>
            </tr>
            @if ($discount > 0)
                <tr>
                    <td class="total-label">Discount:</td>
                    <td class="text-right">- RM{{ number_format($discount, 2) }}</td>
                </tr>
            @endif
            <tr class="grand-total-row">
                <td class="total-label">Total:</td>
                <td class="text-right">RM{{ number_format($grand_total - $discount, 2) }}</td>
            </tr>
        </table>
    </div>

    <!-- Notes Block (Rendered if set) -->
    @if(!empty($billing_notes))
    <div class="notes-box">
        <div class="section-title" style="color: #2c3e50; margin-bottom: 5px;">Notes</div>
        <p style="color: #555;">{{ $billing_notes }}</p>
    </div>
    @endif

    <!-- Base Document Footer Info -->
    <div class="invoice-footer">
        Thank you for your business!
    </div>

</body>

</html>