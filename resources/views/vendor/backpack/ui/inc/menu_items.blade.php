{{-- This file is used for menu items by any Backpack v7 theme --}}
<li class="nav-item"><a class="nav-link" href="{{ backpack_url('dashboard') }}"><i class="la la-home nav-icon"></i> {{ trans('backpack::base.dashboard') }}</a></li>
<!-- <x-backpack::menu-item title="Users" icon="la la-users" :link="backpack_url('user')" /> -->
<x-backpack::menu-item title="Invoices" icon="la la-file-invoice-dollar" :link="backpack_url('invoice')" />
<x-backpack::menu-item title="Products" icon="la la-boxes" :link="backpack_url('product')" />
<x-backpack::menu-item title="Contacts" icon="la la-address-book" :link="backpack_url('contact')" />
<x-backpack::menu-item title="Settings" icon="la la-cog" :link="backpack_url('setting')" />
