@if (!$entry->is_paid)
    <a href="javascript:void(0)" class="btn btn-sm btn-link text-success"
        onclick="if (confirm('Mark this invoice as paid? It will show as a Receipt when downloaded.')) { this.nextElementSibling.submit(); }">
        <i class="la la-check-circle"></i> Mark Paid
    </a>
    <form method="POST" action="{{ route('invoice.mark_paid', $entry->id) }}" class="d-none">
        @csrf
    </form>
@endif
