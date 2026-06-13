@extends(backpack_view('blank'))

@php
    $maxMonthlySales = max($monthlySales->max('sales'), 1);
@endphp

@section('header')
    <section class="header-operation container-fluid animated fadeIn d-flex mb-2 align-items-baseline d-print-none" bp-section="page-header">
        <h1 class="text-capitalize mb-0" bp-section="page-heading">{{ $title ?? trans('backpack::base.dashboard') }}</h1>
    </section>
@endsection

@section('content')
    <div class="row mb-4">
        <div class="col-md-4">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="text-muted text-uppercase fw-bold small mb-1">Total Overall Sales</div>
                    <div class="display-6 fw-bold text-primary">RM {{ number_format($totalSales, 2) }}</div>
                    <div class="text-muted small mt-1">All invoices (after discount)</div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="text-muted text-uppercase fw-bold small mb-1">This Month</div>
                    @php $currentMonth = $monthlySales->last(); @endphp
                    <div class="display-6 fw-bold">RM {{ number_format($currentMonth['sales'] ?? 0, 2) }}</div>
                    <div class="text-muted small mt-1">{{ $currentMonth['label'] ?? now()->format('M Y') }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="text-muted text-uppercase fw-bold small mb-1">Invoices This Month</div>
                    <div class="display-6 fw-bold">{{ number_format($invoicesThisMonth) }}</div>
                    <div class="text-muted small mt-1">{{ now()->format('M Y') }}</div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-7 mb-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white">
                    <h3 class="card-title mb-0">Monthly Sales</h3>
                    <div class="text-muted small">&nbsp;Last 12 months</div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Month</th>
                                    <th class="text-end">Sales (RM)</th>
                                    <th style="width: 35%;"></th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($monthlySales->reverse() as $month)
                                    <tr>
                                        <td>{{ $month['label'] }}</td>
                                        <td class="text-end fw-semibold">{{ number_format($month['sales'], 2) }}</td>
                                        <td>
                                            <div class="progress" style="height: 8px;">
                                                <div class="progress-bar bg-primary" role="progressbar"
                                                    style="width: {{ ($month['sales'] / $maxMonthlySales) * 100 }}%;"
                                                    aria-valuenow="{{ $month['sales'] }}" aria-valuemin="0"
                                                    aria-valuemax="{{ $maxMonthlySales }}"></div>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="3" class="text-center text-muted py-4">No sales data yet.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-5 mb-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white">
                    <h3 class="card-title mb-0">Top 10 Products</h3>
                    <div class="text-muted small">&nbsp;By quantity billed on invoices</div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>#</th>
                                    <th>Product</th>
                                    <th class="text-end">Qty</th>
                                    <th class="text-end">Revenue (RM)</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($topProducts as $index => $product)
                                    <tr>
                                        <td>{{ $index + 1 }}</td>
                                        <td>{{ $product->product_name }}</td>
                                        <td class="text-end">{{ number_format($product->total_quantity) }}</td>
                                        <td class="text-end">{{ number_format($product->total_revenue, 2) }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="text-center text-muted py-4">No product data yet.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
