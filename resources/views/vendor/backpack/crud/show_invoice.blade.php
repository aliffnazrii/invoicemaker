@extends(backpack_view('blank'))

@php
    $defaultBreadcrumbs = [
      trans('backpack::crud.admin') => url(config('backpack.base.route_prefix'), 'dashboard'),
      $crud->entity_name_plural => url($crud->route),
      trans('backpack::crud.preview') => false,
    ];

    // if breadcrumbs aren't defined in the CrudController, use the default breadcrumbs
    $breadcrumbs = $breadcrumbs ?? $defaultBreadcrumbs;
@endphp

@section('header')
    <div class="container-fluid d-flex justify-content-between my-3">
        <section class="header-operation animated fadeIn d-flex mb-2 align-items-baseline d-print-none" bp-section="page-header">
            <h1 class="text-capitalize mb-0" bp-section="page-heading">{!! $crud->getHeading() ?? $crud->entity_name_plural !!}</h1>
            <p class="ms-2 ml-2 mb-0" bp-section="page-subheading">{!! $crud->getSubheading() ?? mb_ucfirst(trans('backpack::crud.preview')).' '.$crud->entity_name !!}</p>
            @if ($crud->hasAccess('list'))
                <p class="ms-2 ml-2 mb-0" bp-section="page-subheading-back-button">
                    <small><a href="{{ $crud->getOperationSetting('backToAllEntriesUrl') ?? url($crud->route) }}" class="font-sm"><i class="la la-angle-double-left"></i> {{ trans('backpack::crud.back_to_all') }} <span>{{ $crud->entity_name_plural }}</span></a></small>
                </p>
            @endif
        </section>
        <a href="javascript: window.print();" class="btn float-end float-right"><i class="la la-print"></i></a>
    </div>
@endsection

@section('content')
<div class="row" bp-section="crud-operation-show">
    <div class="{{ $crud->getShowContentClass() }}">

        {{-- Default Invoice Summary Box --}}
        <div class="mb-4">
        @if ($crud->model->translationEnabled())
            <div class="row">
                <div class="col-md-12 mb-2" bp-section="show-operation-language-dropdown">
                    <div class="btn-group float-right">
                    <button type="button" class="btn btn-sm btn-primary dropdown-toggle" data-toggle="dropdown" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                        {{trans('backpack::crud.language')}}: {{ $crud->model->getAvailableLocales()[request()->input('_locale')?request()->input('_locale'):App::getLocale()] }} &nbsp; <span class="caret"></span>
                    </button>
                    <ul class="dropdown-menu">
                        @foreach ($crud->model->getAvailableLocales() as $key => $locale)
                            <a class="dropdown-item" href="{{ url($crud->route.'/'.$entry->getKey().'/show') }}?_locale={{ $key }}">{{ $locale }}</a>
                        @endforeach
                    </ul>
                    </div>
                </div>
            </div>
        @endif
            @if($crud->tabsEnabled() && count($crud->getUniqueTabNames('columns')))
                @include('crud::inc.show_tabbed_table')
            @else
                <div class="card no-padding no-border mb-0">
                    @include('crud::inc.show_table', ['columns' => $crud->columns()])
                </div>
            @endif
        </div>

        {{-- Custom Breakdown Table Added Directly Below --}}
        <div class="card no-padding no-border shadow-sm mb-4">
            <div class="card-header font-weight-bold bg-light">
                <i class="la la-list"></i> Invoice Line Items
            </div>
            <div class="table-responsive">
                <table class="table table-bordered table-striped table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Description</th>
                            <th class="text-center" style="width: 120px;">Quantity</th>
                            <th class="text-end text-right" style="width: 160px;">Unit Price</th>
                            <th class="text-end text-right" style="width: 160px;">Subtotal</th>
                            <th class="text-end text-right" style="width: 160px;">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($entry->items as $item)
                            <tr>
                                <td>{{ $item->description }}</td>
                                <td class="text-center">{{ $item->quantity }}</td>
                                <td class="text-end text-right">RM{{ number_format($item->unit_price, 2) }}</td>
                                <td class="text-end text-right">RM{{ number_format($item->subtotal, 2) }}</td>
                                <td class="text-end text-right">RM{{ number_format($item->total, 2) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center text-muted py-3">No line items tied to this invoice record.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</div>
@endsection
